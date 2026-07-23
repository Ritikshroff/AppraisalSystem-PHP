<?php

namespace App\Services;

use App\Models\Appraisal;
use App\Models\AppraisalCycle;
use App\Models\Employee;
use App\Models\Kra;
use App\Models\SkillRating;
use App\Models\SystemSettings;
use App\Models\Team;
use App\Models\User;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class AppraisalService
{
    private const DASHBOARD_PAGE_SIZES = [
        'visibleAppraisals' => 8,
        'pendingAppraisals' => 8,
        'teamMemberStatuses' => 8,
        'employees' => 8,
    ];

    private static function getUserOrThrow(string $userId): User
    {
        $user = User::find($userId);
        if (!$user) {
            throw new \Exception("User not found: {$userId}");
        }
        return $user;
    }

    private static function getEmployeeProfileOrThrow(User $user): Employee
    {
        $employee = Employee::find($user->employeeId);
        if (!$employee) {
            throw new \Exception("Employee profile not found for user: {$user->id}");
        }
        return $employee;
    }

    private static function serializeActor(Employee $employee): array
    {
        $manager = $employee->manager;
        $team = $employee->team;

        return [
            'id' => $employee->id,
            'employeeCode' => $employee->employeeCode,
            'fullName' => $employee->fullName,
            'email' => $employee->email,
            'designation' => $employee->designation,
            'department' => $employee->department,
            'role' => $employee->role,
            'teamId' => $employee->teamId,
            'teamName' => $team ? $team->name : null,
            'managerId' => $employee->managerId,
            'managerName' => $manager ? $manager->fullName : null,
            'finalReviewerName' => null, // ceo placeholder
            'doj' => $employee->doj ? $employee->doj->toIso8601String() : null,
            'salary' => $employee->salary,
            'lastHike' => $employee->lastHike,
            'activeCycleName' => null,
            'appraisalId' => null,
        ];
    }

    private static function serializeViewer(User $user): array
    {
        $team = $user->team;
        return [
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'role' => $user->role,
            'teamId' => $user->teamId,
            'teamName' => $team ? $team->name : null,
            'employeeId' => $user->employeeId,
        ];
    }

    private static function serializeTeam(Team $team): array
    {
        $manager = $team->manager;
        return [
            'id' => $team->id,
            'name' => $team->name,
            'managerName' => $manager ? $manager->fullName : null,
            'memberCount' => $team->members()->count(),
        ];
    }

    private static function serializeAppraisalListItem(Appraisal $appraisal): array
    {
        $employee = $appraisal->employee;
        $team = $appraisal->team;
        $manager = $appraisal->manager;
        $cycle = $appraisal->cycle;

        return [
            'id' => $appraisal->id,
            'employeeId' => $appraisal->employeeId,
            'employeeName' => $employee ? $employee->fullName : 'Unknown',
            'employeeCode' => $employee ? $employee->employeeCode : 'Unknown',
            'teamName' => $team ? $team->name : 'Unknown',
            'managerName' => $manager ? $manager->fullName : null,
            'cycleId' => $appraisal->cycleId,
            'cycleName' => $cycle ? $cycle->name : 'Unknown',
            'appraisalType' => $appraisal->type,
            'appraisalPeriod' => $appraisal->appraisalPeriod,
            'status' => $appraisal->status,
            'finalRating' => $appraisal->finalRating,
            'hikePercentage' => $appraisal->hikePercentage,
            'sentimentLabel' => $appraisal->sentimentLabel,
            'updatedAt' => $appraisal->updated_at->toIso8601String(),
        ];
    }

    private static function serializeTeamStatusItem(Appraisal $appraisal): array
    {
        $employee = $appraisal->employee;
        return [
            'employeeName' => $employee ? $employee->fullName : 'Unknown',
            'employeeCode' => $employee ? $employee->employeeCode : 'Unknown',
            'appraisalType' => $appraisal->type,
            'appraisalPeriod' => $appraisal->appraisalPeriod,
            'status' => $appraisal->status,
            'updatedAt' => $appraisal->updated_at->toIso8601String(),
        ];
    }

    private static function isAllowedToView(User $user, Appraisal $appraisal): bool
    {
        if ($user->role === 'CEO' || $user->role === 'HR') {
            return true;
        }

        if ($user->role === 'MANAGER') {
            return ($user->teamId === $appraisal->teamId || $user->employeeId === $appraisal->managerId);
        }

        return ($user->employeeId === $appraisal->employeeId);
    }

    private static function canSave(User $user, Appraisal $appraisal): bool
    {
        $status = strtoupper($appraisal->status);

        if ($user->role === 'EMPLOYEE') {
            return ($status === 'DRAFT' && $user->employeeId === $appraisal->employeeId);
        }

        if ($user->role === 'MANAGER') {
            return ($status === 'SUBMITTED' && $user->employeeId === $appraisal->managerId);
        }

        if ($user->role === 'CEO') {
            return ($status === 'MANAGER_REVIEW');
        }

        return false;
    }

    private static function canSubmit(User $user, Appraisal $appraisal): bool
    {
        // For simple rule-based alignment, if we can save it, we can submit it.
        return self::canSave($user, $appraisal);
    }

    private static function isInsideWindow(?SystemSettings $settings): bool
    {
        if (!$settings) {
            return true;
        }
        $now = now();
        return ($now->greaterThanOrEqualTo($settings->globalDeadlineStart) &&
            $now->lessThanOrEqualTo($settings->globalDeadlineEnd));
    }

    private static function buildPermissions(User $user, Appraisal $appraisal, ?SystemSettings $settings): array
    {
        $canSave = self::canSave($user, $appraisal);
        $canSubmit = self::canSubmit($user, $appraisal);

        $role = strtoupper($user->role);
        $status = strtoupper($appraisal->status);

        $canEditEmployeeSection = ($role === 'EMPLOYEE' && $status === 'DRAFT' && $user->employeeId === $appraisal->employeeId);
        $canEditManagerSection = ($role === 'MANAGER' && $status === 'SUBMITTED' && $user->employeeId === $appraisal->managerId);
        $canEditCEOSection = ($role === 'CEO' && $status === 'MANAGER_REVIEW');
        $canEditKRASection = $canEditEmployeeSection || $canEditManagerSection;

        $nextActionLabel = null;
        if ($canSubmit) {
            $nextActionLabel = match ($role) {
                'EMPLOYEE' => "Submit Appraisal",
                'MANAGER' => "Submit Review",
                'CEO' => "Finalize Appraisal",
                default => null
            };
        }

        return [
            'canSave' => $canSave,
            'canSubmit' => $canSubmit,
            'canEditEmployeeSection' => $canEditEmployeeSection,
            'canEditManagerSection' => $canEditManagerSection,
            'canEditCEOSection' => $canEditCEOSection,
            'canEditKRASection' => $canEditKRASection,
            'currentStageLabel' => AppraisalHelperService::getCurrentStageLabel($status),
            'nextActionLabel' => $nextActionLabel,
        ];
    }

    public static function getDashboardData(string $userId, array $options = []): array
    {
        $user = self::getUserOrThrow($userId);
        $actor = self::serializeActor(self::getEmployeeProfileOrThrow($user));

        $query = $options['query'] ?? '';
        $visiblePage = intval($options['visiblePage'] ?? 1);
        $pendingPage = intval($options['pendingPage'] ?? 1);
        $teamStatusPage = intval($options['teamStatusPage'] ?? 1);

        $view = $options['view'] ?? 'dashboard';

        // Set query scopes based on roles and view selections
        $role = strtoupper($user->role);

        // Scope to fetch visible appraisals
        $visibleQuery = Appraisal::query();
        if ($view === 'my-appraisal') {
            $visibleQuery->where('employeeId', $user->employeeId);
        } else {
            if ($role === 'CEO' || $role === 'HR') {
                // Enterprise access
            } elseif ($role === 'MANAGER') {
                $visibleQuery->where('teamId', $user->teamId);
            } else {
                $visibleQuery->where('employeeId', $user->employeeId);
            }
        }

        // Apply text queries if present (joins on Employee)
        if (!empty($query)) {
            $visibleQuery->whereHas('employee', function ($q) use ($query) {
                $q->where('fullName', 'like', "%{$query}%")
                    ->orWhere('employeeCode', 'like', "%{$query}%");
            });
        }

        // Aggregate Status Counts
        $statusCountsQuery = clone $visibleQuery;
        $groupedCounts = $statusCountsQuery->select('status', DB::raw('count(*) as count'))->groupBy('status')->get();
        $counts = [
            'total' => 0,
            'draft' => 0,
            'submitted' => 0,
            'managerReview' => 0,
            'completed' => 0,
        ];
        foreach ($groupedCounts as $item) {
            $itemStatus = strtoupper($item->status);
            $counts['total'] += $item->count;
            if ($itemStatus === 'DRAFT')
                $counts['draft'] = $item->count;
            if ($itemStatus === 'SUBMITTED')
                $counts['submitted'] = $item->count;
            if ($itemStatus === 'MANAGER_REVIEW')
                $counts['managerReview'] = $item->count;
            if ($itemStatus === 'COMPLETED')
                $counts['completed'] = $item->count;
        }

        // Pending Appraisals Query
        $pendingQuery = clone $visibleQuery;
        if ($role === 'EMPLOYEE') {
            $pendingQuery->where('status', '!=', 'COMPLETED');
        } elseif ($role === 'MANAGER') {
            $pendingQuery->whereIn('status', ['SUBMITTED', 'DRAFT']);
        } else {
            $pendingQuery->where('status', 'MANAGER_REVIEW');
        }

        // Team Status Query (co-workers)
        $teamStatusQuery = Appraisal::query()->where('employeeId', '__none__'); // Default empty
        if ($role === 'EMPLOYEE' && $user->teamId) {
            $teamStatusQuery = Appraisal::query()
                ->where('teamId', $user->teamId)
                ->where('employeeId', '!=', $user->employeeId);

            if (!empty($query)) {
                $teamStatusQuery->whereHas('employee', function ($q) use ($query) {
                    $q->where('fullName', 'like', "%{$query}%")
                        ->orWhere('employeeCode', 'like', "%{$query}%");
                });
            }
        }

        // Paginate results
        $visibleAppraisals = $visibleQuery->orderBy('updated_at', 'desc')
            ->paginate(self::DASHBOARD_PAGE_SIZES['visibleAppraisals'], ['*'], 'visiblePage', $visiblePage);
        $visibleItems = collect($visibleAppraisals->items())->map(fn($item) => self::serializeAppraisalListItem($item))->toArray();

        $pendingAppraisals = $pendingQuery->orderBy('updated_at', 'desc')
            ->paginate(self::DASHBOARD_PAGE_SIZES['pendingAppraisals'], ['*'], 'pendingPage', $pendingPage);
        $pendingItems = collect($pendingAppraisals->items())->map(fn($item) => self::serializeAppraisalListItem($item))->toArray();

        $teamMemberStatuses = $teamStatusQuery->orderBy('updated_at', 'desc')
            ->paginate(self::DASHBOARD_PAGE_SIZES['teamMemberStatuses'], ['*'], 'teamStatusPage', $teamStatusPage);
        $teamStatusItems = collect($teamMemberStatuses->items())->map(fn($item) => self::serializeTeamStatusItem($item))->toArray();

        // Build Metrics
        $metrics = [];
        if ($role === 'EMPLOYEE') {
            $metrics = [
                ['label' => "My Appraisals", 'value' => (string) $counts['total'], 'detail' => "Work and salary appraisals assigned to you."],
                ['label' => "Draft", 'value' => (string) $counts['draft'], 'detail' => "Appraisals still editable by you."],
                ['label' => "Pending", 'value' => (string) $counts['submitted'], 'detail' => "Submitted and awaiting manager review."],
                ['label' => "Final", 'value' => (string) $counts['completed'], 'detail' => "Appraisals finalized by the CEO."],
            ];
        } elseif ($role === 'MANAGER') {
            $metrics = [
                ['label' => "Team Reviews", 'value' => (string) $counts['total'], 'detail' => "Appraisals in your team portfolio."],
                ['label' => "Pending Reviews", 'value' => (string) $counts['submitted'], 'detail' => "Employee submissions awaiting your review."],
                ['label' => "Reviewed", 'value' => (string) $counts['managerReview'], 'detail' => "Manager-reviewed appraisals ready for the CEO."],
                ['label' => "Completed", 'value' => (string) $counts['completed'], 'detail' => "Finalized appraisals in your team."],
            ];
        } else {
            $metrics = [
                ['label' => "Enterprise Appraisals", 'value' => (string) $counts['total'], 'detail' => "All appraisals across teams and cycles."],
                ['label' => "Pending Final", 'value' => (string) $counts['managerReview'], 'detail' => "Manager-reviewed appraisals awaiting final decision."],
                ['label' => "Completed", 'value' => (string) $counts['completed'], 'detail' => "CEO finalized appraisals."],
            ];
        }

        // Team Summaries (Manager, CEO, HR only)
        $teamSummary = [];
        if ($role !== 'EMPLOYEE') {
            $groupedSummary = Appraisal::query();
            if ($role === 'MANAGER') {
                $groupedSummary->where('teamId', $user->teamId);
            }
            $teamStats = $groupedSummary->select(
                'teamId',
                DB::raw('count(*) as total'),
                DB::raw('sum(case when status = "COMPLETED" then 1 else 0 end) as completed'),
                DB::raw('avg(finalRating) as avg_rating')
            )->groupBy('teamId')->get();

            $teamIds = $teamStats->pluck('teamId')->toArray();
            $teams = Team::whereIn('id', $teamIds)->get()->keyBy('id');

            foreach ($teamStats as $stat) {
                $team = $teams->get($stat->teamId);
                $teamSummary[] = [
                    'teamName' => $team ? $team->name : 'Unknown Team',
                    'totalAppraisals' => $stat->total,
                    'completedCount' => $stat->completed,
                    'pendingCount' => $stat->total - $stat->completed,
                    'averageFinalRating' => $stat->avg_rating ? AppraisalHelperService::roundTo($stat->avg_rating) : null,
                ];
            }
            usort($teamSummary, fn($a, $b) => strcmp($a['teamName'], $b['teamName']));
        }

        // Budget Impact (CEO/HR only)
        $budgetImpact = null;
        if ($role === 'CEO' || $role === 'HR') {
            $budgetStats = Appraisal::where('type', 'SALARY')
                ->where('status', 'COMPLETED')
                ->whereNotNull('hikePercentage')
                ->select(DB::raw('count(*) as count'), DB::raw('sum(hikePercentage) as sum'), DB::raw('avg(hikePercentage) as avg'))
                ->first();

            if ($budgetStats && $budgetStats->count > 0) {
                $budgetImpact = [
                    'appraisalCount' => $budgetStats->count,
                    'totalHikePercentage' => AppraisalHelperService::roundTo($budgetStats->sum),
                    'averageHikePercentage' => AppraisalHelperService::roundTo($budgetStats->avg),
                ];
            }
        }

        // HR Specific Data Management
        $hrData = null;
        if ($role === 'HR') {
            $empQuery = Employee::query();
            if (!empty($query)) {
                $empQuery->where('fullName', 'like', "%{$query}%")
                    ->orWhere('employeeCode', 'like', "%{$query}%");
            }
            $employeesList = $empQuery->orderBy('fullName', 'asc')->paginate(self::DASHBOARD_PAGE_SIZES['employees'], ['*'], 'employeePage', $visiblePage);
            $employeeSummaries = collect($employeesList->items())->map(fn($emp) => self::serializeActor($emp))->toArray();

            $activeCycles = AppraisalCycle::where('isActive', true)->get()->map(fn($c) => [
                'id' => $c->id,
                'name' => $c->name,
                'appraisalType' => $c->appraisalType,
                'periodLabel' => $c->periodLabel,
                'startDate' => $c->startDate->toIso8601String(),
                'endDate' => $c->endDate->toIso8601String(),
                'isActive' => $c->isActive,
            ])->toArray();

            $allTeams = Team::with('manager')->get()->map(fn($t) => self::serializeTeam($t))->toArray();

            $systemSettings = SystemSettings::find('GLOBAL');
            $settingsSummary = [
                'globalDeadlineStart' => $systemSettings ? $systemSettings->globalDeadlineStart->toIso8601String() : now()->toIso8601String(),
                'globalDeadlineEnd' => $systemSettings ? $systemSettings->globalDeadlineEnd->toIso8601String() : now()->toIso8601String(),
            ];

            $allEmployeesRaw = Employee::orderBy('fullName', 'asc')->get(['id', 'fullName'])->toArray();

            $hrData = [
                'employees' => [
                    'items' => $employeeSummaries,
                    'pageInfo' => [
                        'page' => $employeesList->currentPage(),
                        'pageSize' => $employeesList->perPage(),
                        'totalItems' => $employeesList->total(),
                        'totalPages' => $employeesList->lastPage(),
                        'hasPreviousPage' => $employeesList->currentPage() > 1,
                        'hasNextPage' => $employeesList->hasMorePages(),
                    ]
                ],
                'activeCycles' => $activeCycles,
                'allTeams' => $allTeams,
                'systemSettings' => $settingsSummary,
                'allEmployees' => $allEmployeesRaw,
            ];
        }

        return [
            'viewer' => self::serializeViewer($user),
            'actor' => $actor,
            'filters' => [
                'query' => $query,
                'visiblePage' => $visiblePage,
                'pendingPage' => $pendingPage,
                'teamStatusPage' => $teamStatusPage,
            ],
            'metrics' => $metrics,
            'visibleAppraisals' => [
                'items' => $visibleItems,
                'pageInfo' => [
                    'page' => $visibleAppraisals->currentPage(),
                    'pageSize' => $visibleAppraisals->perPage(),
                    'totalItems' => $visibleAppraisals->total(),
                    'totalPages' => $visibleAppraisals->lastPage(),
                    'hasPreviousPage' => $visibleAppraisals->currentPage() > 1,
                    'hasNextPage' => $visibleAppraisals->hasMorePages(),
                ]
            ],
            'pendingAppraisals' => [
                'items' => $pendingItems,
                'pageInfo' => [
                    'page' => $pendingAppraisals->currentPage(),
                    'pageSize' => $pendingAppraisals->perPage(),
                    'totalItems' => $pendingAppraisals->total(),
                    'totalPages' => $pendingAppraisals->lastPage(),
                    'hasPreviousPage' => $pendingAppraisals->currentPage() > 1,
                    'hasNextPage' => $pendingAppraisals->hasMorePages(),
                ]
            ],
            'teamMemberStatuses' => [
                'items' => $teamStatusItems,
                'pageInfo' => [
                    'page' => $teamMemberStatuses->currentPage(),
                    'pageSize' => $teamMemberStatuses->perPage(),
                    'totalItems' => $teamMemberStatuses->total(),
                    'totalPages' => $teamMemberStatuses->lastPage(),
                    'hasPreviousPage' => $teamMemberStatuses->currentPage() > 1,
                    'hasNextPage' => $teamMemberStatuses->hasMorePages(),
                ]
            ],
            'teamSummary' => $teamSummary,
            'topPerformers' => [
                'items' => [],
                'pageInfo' => ['page' => 1, 'pageSize' => 8, 'totalItems' => 0, 'totalPages' => 1, 'hasPreviousPage' => false, 'hasNextPage' => false]
            ],
            'budgetImpact' => $budgetImpact,
            'hrData' => $hrData,
        ];
    }

    public static function getAppraisalDetail(string $userId, string $appraisalId): ?array
    {
        $user = self::getUserOrThrow($userId);
        $appraisal = Appraisal::with(['cycle', 'team.manager', 'team.members', 'employee.manager', 'employee.team', 'manager.team', 'ceo', 'kras', 'skillRatings'])->find($appraisalId);

        if (!$appraisal || !self::isAllowedToView($user, $appraisal)) {
            return null;
        }

        $settings = SystemSettings::find('GLOBAL');
        $permissions = self::buildPermissions($user, $appraisal, $settings);

        // Helper string array parsers
        $parseStringArray = function ($value) {
            if (empty($value))
                return [];
            $decoded = json_decode($value, true);
            return is_array($decoded) ? $decoded : [];
        };

        $sectionOneAnswers = json_decode($appraisal->sectionOneAnswers, true) ?: [];
        $managerReview = json_decode($appraisal->managerReview, true) ?: [];
        $ceoReview = json_decode($appraisal->ceoReview, true) ?: [];

        return [
            'id' => $appraisal->id,
            'type' => $appraisal->type,
            'appraisalPeriod' => $appraisal->appraisalPeriod,
            'status' => $appraisal->status,
            'cycle' => [
                'id' => $appraisal->cycle->id,
                'name' => $appraisal->cycle->name,
                'appraisalType' => $appraisal->cycle->appraisalType,
                'periodLabel' => $appraisal->cycle->periodLabel,
                'startDate' => $appraisal->cycle->startDate->toIso8601String(),
                'endDate' => $appraisal->cycle->endDate->toIso8601String(),
                'isActive' => $appraisal->cycle->isActive,
            ],
            'employee' => self::serializeActor($appraisal->employee),
            'manager' => $appraisal->manager ? self::serializeActor($appraisal->manager) : null,
            'ceo' => $appraisal->ceo ? self::serializeActor($appraisal->ceo) : null,
            'team' => self::serializeTeam($appraisal->team),
            'sectionOneAnswers' => AppraisalHelperService::normalizeSectionAnswers($sectionOneAnswers),
            'kras' => AppraisalHelperService::ensureKraRows(
                $appraisal->kras->map(fn($item) => [
                    'id' => $item->id,
                    'objective' => $item->objective,
                    'weightage' => $item->weightage,
                    'appraiseeRating' => $item->appraiseeRating,
                    'appraiserRating' => $item->appraiserRating,
                    'comments' => $item->comments ?? "",
                    'displayOrder' => $item->displayOrder,
                ])->toArray()
            ),
            'skillRatings' => AppraisalHelperService::defaultSkillRows(
                $appraisal->skillRatings->map(fn($item) => [
                    'id' => $item->id,
                    'skillName' => $item->skillName,
                    'employeeRating' => $item->employeeRating,
                    'managerRating' => $item->managerRating,
                    'displayOrder' => $item->displayOrder,
                ])->toArray()
            ),
            'managerReview' => [
                'overallRating' => $appraisal->managerOverallRating,
                'comments' => $managerReview['comments'] ?? "",
            ],
            'ceoReview' => [
                'comments' => $ceoReview['comments'] ?? "",
                'finalRating' => $appraisal->finalRating,
                'hikePercentage' => $appraisal->hikePercentage,
            ],
            'finalRating' => $appraisal->finalRating,
            'hikePercentage' => $appraisal->hikePercentage,
            'aiSummary' => $appraisal->aiPerformanceSummary,
            'sentimentLabel' => $appraisal->sentimentLabel,
            'sentimentScore' => $appraisal->sentimentScore,
            'strengths' => $parseStringArray($appraisal->aiStrengths),
            'weaknesses' => $parseStringArray($appraisal->aiWeaknesses),
            'riskSignals' => $parseStringArray($appraisal->aiRiskSignals),
            'permissions' => $permissions,
            'employeeSubmittedAt' => $appraisal->employeeSubmittedAt ? $appraisal->employeeSubmittedAt->toIso8601String() : null,
            'managerSubmittedAt' => $appraisal->managerSubmittedAt ? $appraisal->managerSubmittedAt->toIso8601String() : null,
            'ceoSubmittedAt' => $appraisal->ceoSubmittedAt ? $appraisal->ceoSubmittedAt->toIso8601String() : null,
            'deadlineAt' => $appraisal->deadlineAt ? $appraisal->deadlineAt->toIso8601String() : null,
            'updatedAt' => $appraisal->updated_at->toIso8601String(),
        ];
    }

    public static function mutateAppraisal(string $userId, array $payload, string $mode): ?array
    {
        $user = self::getUserOrThrow($userId);
        $appraisal = Appraisal::find($payload['appraisalId']);

        if (!$appraisal || !self::isAllowedToView($user, $appraisal)) {
            throw new \Exception("Appraisal not found or unauthorized.");
        }

        $mode = strtolower(trim($mode));
        if ($mode === 'save' && !self::canSave($user, $appraisal)) {
            throw new \Exception("This user cannot edit the current appraisal stage.");
        }

        if ($mode === 'submit' && !self::canSubmit($user, $appraisal)) {
            throw new \Exception("This user cannot submit the current appraisal stage.");
        }

        if ($mode === 'submit') {
            self::validateRequiredForSubmit($user->role, $payload);
        }

        DB::transaction(function () use ($user, $appraisal, $payload, $mode) {
            $role = strtoupper($user->role);
            $updates = [];

            if ($role === 'EMPLOYEE') {
                $updates['sectionOneAnswers'] = json_encode(AppraisalHelperService::normalizeSectionAnswers($payload['sectionOneAnswers'] ?? []));
                $updates['status'] = ($mode === 'submit') ? 'SUBMITTED' : 'DRAFT';
                $updates['employeeSubmittedAt'] = ($mode === 'submit') ? now() : $appraisal->employeeSubmittedAt;

                if (isset($payload['kras'])) {
                    Kra::where('appraisalId', $appraisal->id)->delete();
                    foreach (AppraisalHelperService::ensureKraRows($payload['kras']) as $kraItem) {
                        Kra::create([
                            'id' => Str::uuid()->toString(),
                            'appraisalId' => $appraisal->id,
                            'objective' => $kraItem['objective'],
                            'weightage' => $kraItem['weightage'],
                            'appraiseeRating' => $kraItem['appraiseeRating'],
                            'appraiserRating' => $kraItem['appraiserRating'],
                            'comments' => $kraItem['comments'] ?: null,
                            'displayOrder' => $kraItem['displayOrder'],
                        ]);
                    }
                }

                if (isset($payload['skillRatings'])) {
                    SkillRating::where('appraisalId', $appraisal->id)->delete();
                    foreach (AppraisalHelperService::defaultSkillRows($payload['skillRatings']) as $skillItem) {
                        SkillRating::create([
                            'id' => Str::uuid()->toString(),
                            'appraisalId' => $appraisal->id,
                            'skillName' => $skillItem['skillName'],
                            'employeeRating' => $skillItem['employeeRating'],
                            'managerRating' => $skillItem['managerRating'],
                            'displayOrder' => $skillItem['displayOrder'],
                        ]);
                    }
                }
            }

            if ($role === 'MANAGER') {
                $overallRating = null;
                if (isset($payload['managerReview']['overallRating']) && $payload['managerReview']['overallRating'] !== null) {
                    $overallRating = AppraisalHelperService::roundTo(AppraisalHelperService::clampScale(floatval($payload['managerReview']['overallRating']), 0, 10));
                }

                $managerReview = [
                    'overallRating' => $overallRating,
                    'comments' => isset($payload['managerReview']['comments']) ? trim($payload['managerReview']['comments']) : '',
                ];

                $updates['managerReview'] = json_encode($managerReview);
                $updates['managerOverallRating'] = $overallRating;
                $updates['status'] = ($mode === 'submit') ? 'MANAGER_REVIEW' : $appraisal->status;
                $updates['managerSubmittedAt'] = ($mode === 'submit') ? now() : $appraisal->managerSubmittedAt;

                if (isset($payload['kras'])) {
                    Kra::where('appraisalId', $appraisal->id)->delete();
                    foreach (AppraisalHelperService::ensureKraRows($payload['kras']) as $kraItem) {
                        Kra::create([
                            'id' => Str::uuid()->toString(),
                            'appraisalId' => $appraisal->id,
                            'objective' => $kraItem['objective'],
                            'weightage' => $kraItem['weightage'],
                            'appraiseeRating' => $kraItem['appraiseeRating'],
                            'appraiserRating' => $kraItem['appraiserRating'],
                            'comments' => $kraItem['comments'] ?: null,
                            'displayOrder' => $kraItem['displayOrder'],
                        ]);
                    }
                }

                if (isset($payload['skillRatings'])) {
                    SkillRating::where('appraisalId', $appraisal->id)->delete();
                    foreach (AppraisalHelperService::defaultSkillRows($payload['skillRatings']) as $skillItem) {
                        SkillRating::create([
                            'id' => Str::uuid()->toString(),
                            'appraisalId' => $appraisal->id,
                            'skillName' => $skillItem['skillName'],
                            'employeeRating' => $skillItem['employeeRating'],
                            'managerRating' => $skillItem['managerRating'],
                            'displayOrder' => $skillItem['displayOrder'],
                        ]);
                    }
                }
            }

            if ($role === 'CEO') {
                $finalRating = null;
                if (isset($payload['ceoReview']['finalRating']) && $payload['ceoReview']['finalRating'] !== null) {
                    $finalRating = AppraisalHelperService::roundTo(AppraisalHelperService::clampScale(floatval($payload['ceoReview']['finalRating']), 0, 10));
                }

                $hikePercentage = null;
                if (isset($payload['ceoReview']['hikePercentage']) && $payload['ceoReview']['hikePercentage'] !== null) {
                    $hikePercentage = AppraisalHelperService::roundTo(AppraisalHelperService::clampScale(floatval($payload['ceoReview']['hikePercentage']), 0, 100));
                }

                $updates['ceoReview'] = json_encode([
                    'comments' => isset($payload['ceoReview']['comments']) ? trim($payload['ceoReview']['comments']) : "",
                    'finalRating' => $finalRating,
                    'hikePercentage' => $hikePercentage,
                ]);
                $updates['finalRating'] = $finalRating;
                $updates['hikePercentage'] = $hikePercentage;
                $updates['status'] = ($mode === 'submit') ? 'COMPLETED' : $appraisal->status;
                $updates['ceoSubmittedAt'] = ($mode === 'submit') ? now() : $appraisal->ceoSubmittedAt;
                $updates['ceoId'] = $user->employeeId;
            }

            $appraisal->update($updates);
        });

        // Trigger AI performance analysis if submitted by Manager/CEO
        if (strtoupper($user->role) !== 'EMPLOYEE' && $mode === 'submit') {
            self::refreshAnalysis($appraisal->id);
        }

        return self::getAppraisalDetail($userId, $appraisal->id);
    }

    private static function validateRequiredForSubmit(string $role, array $payload): void
    {
        $role = strtoupper($role);

        if ($role === 'EMPLOYEE') {
            $allAnswersFilled = true;
            if (isset($payload['sectionOneAnswers'])) {
                foreach ($payload['sectionOneAnswers'] as $ans) {
                    if (empty(trim($ans['answer'] ?? ''))) {
                        $allAnswersFilled = false;
                        break;
                    }
                }
            } else {
                $allAnswersFilled = false;
            }

            if (!$allAnswersFilled || empty($payload['kras']) || empty($payload['skillRatings'])) {
                throw new \Exception("Employee submission requires all answers, KRAs, and skills.");
            }
        }

        if ($role === 'MANAGER') {
            if (empty(trim($payload['managerReview']['comments'] ?? '')) || !isset($payload['managerReview']['overallRating']) || $payload['managerReview']['overallRating'] === null) {
                throw new \Exception("Manager review requires comments and an overall rating.");
            }
        }

        if ($role === 'CEO') {
            if (
                !isset($payload['ceoReview']['finalRating']) || $payload['ceoReview']['finalRating'] === null ||
                !isset($payload['ceoReview']['hikePercentage']) || $payload['ceoReview']['hikePercentage'] === null
            ) {
                throw new \Exception("CEO decision requires a final rating and hike percentage.");
            }
        }
    }

    private static function refreshAnalysis(string $appraisalId): void
    {
        $appraisal = Appraisal::with(['employee', 'team', 'kras', 'skillRatings'])->find($appraisalId);
        if (!$appraisal) {
            return;
        }

        $sectionOneAnswers = json_decode($appraisal->sectionOneAnswers, true) ?: [];
        $sectionText = collect(AppraisalHelperService::normalizeSectionAnswers($sectionOneAnswers))
            ->map(fn($item) => "{$item['question']}: {$item['answer']}")
            ->join(" ");

        $kraText = $appraisal->kras
            ->map(fn($item) => "{$item->objective}. Weight {$item->weightage}. Employee " . ($item->appraiseeRating ?? "n/a") . ". Manager " . ($item->appraiserRating ?? "n/a") . ". " . ($item->comments ?? ""))
            ->join(" ");

        $skillText = $appraisal->skillRatings
            ->map(fn($item) => "{$item->skillName}: employee " . ($item->employeeRating ?? "n/a") . ", manager " . ($item->managerRating ?? "n/a"))
            ->join(" ");

        $managerReview = json_decode($appraisal->managerReview, true) ?: [];
        $managerText = $managerReview['comments'] ?? "";

        $ceoReview = json_decode($appraisal->ceoReview, true) ?: [];
        $ceoText = $ceoReview['comments'] ?? "";

        $analysis = GeminiService::generatePerformanceAnalysis([
            'employeeName' => $appraisal->employee->fullName,
            'designation' => $appraisal->employee->designation,
            'teamName' => $appraisal->team->name,
            'appraisalType' => $appraisal->type,
            'appraisalPeriod' => $appraisal->appraisalPeriod,
            'fullText' => implode(" ", array_filter([$sectionText, $kraText, $skillText, $managerText, $ceoText])),
            'managerOverallRating' => $appraisal->managerOverallRating,
            'finalRating' => $appraisal->finalRating,
        ]);

        $appraisal->update([
            'aiPerformanceSummary' => $analysis['performanceSummary'],
            'sentimentLabel' => $analysis['sentimentLabel'],
            'sentimentScore' => $analysis['sentimentScore'],
            'aiStrengths' => json_encode($analysis['strengths']),
            'aiWeaknesses' => json_encode($analysis['weaknesses']),
            'aiRiskSignals' => json_encode($analysis['riskSignals']),
            'analyzedAt' => now(),
        ]);
    }

    public static function updateCycleWindow(string $cycleId, string $startDate, string $endDate, array $user): array
    {
        $role = strtoupper($user['role'] ?? '');
        if ($role !== 'HR' && $role !== 'CEO') {
            throw new \Exception("Unauthorized");
        }

        $cycle = AppraisalCycle::find($cycleId);
        if (!$cycle) {
            throw new \Exception("Cycle not found");
        }

        $cycle->update([
            'startDate' => now()->parse($startDate),
            'endDate' => now()->parse($endDate),
        ]);

        return $cycle->toArray();
    }

    public static function assignEmployeeToCycle(string $employeeId, string $cycleId, array $user): array
    {
        $role = strtoupper($user['role'] ?? '');
        if ($role !== 'HR' && $role !== 'CEO') {
            throw new \Exception("Unauthorized");
        }

        $cycle = AppraisalCycle::find($cycleId);
        if (!$cycle) {
            throw new \Exception("Cycle not found");
        }

        $emp = Employee::find($employeeId);
        if (!$emp) {
            throw new \Exception("Employee not found");
        }

        $existing = Appraisal::where('employeeId', $employeeId)->where('cycleId', $cycleId)->first();
        if ($existing) {
            return $existing->toArray();
        }

        $targetTeamId = $emp->teamId;
        if (!$targetTeamId) {
            $firstTeam = Team::first();
            $targetTeamId = $firstTeam ? $firstTeam->id : 'SYSTEM_FALLBACK';
        }

        $appraisal = Appraisal::create([
            'id' => Str::uuid()->toString(),
            'employeeId' => $employeeId,
            'teamId' => $targetTeamId,
            'cycleId' => $cycleId,
            'managerId' => $emp->managerId,
            'type' => $cycle->appraisalType,
            'appraisalPeriod' => $cycle->periodLabel,
            'status' => 'DRAFT',
        ]);

        return $appraisal->toArray();
    }

    public static function enrollAllEmployees(string $cycleId, array $user): array
    {
        $role = strtoupper($user['role'] ?? '');
        if ($role !== 'HR' && $role !== 'CEO') {
            throw new \Exception("Unauthorized");
        }

        $cycle = AppraisalCycle::find($cycleId);
        if (!$cycle) {
            throw new \Exception("Cycle not found");
        }

        $employees = Employee::where('role', '!=', 'CEO')->get();
        $count = 0;

        foreach ($employees as $emp) {
            $existing = Appraisal::where('employeeId', $emp->id)->where('cycleId', $cycleId)->first();
            if (!$existing) {
                Appraisal::create([
                    'id' => Str::uuid()->toString(),
                    'employeeId' => $emp->id,
                    'teamId' => $emp->teamId ?: 'SYSTEM',
                    'cycleId' => $cycleId,
                    'managerId' => $emp->managerId,
                    'type' => $cycle->appraisalType,
                    'appraisalPeriod' => $cycle->periodLabel,
                    'status' => 'DRAFT',
                ]);
                $count++;
            }
        }

        return ['count' => $count];
    }
}
