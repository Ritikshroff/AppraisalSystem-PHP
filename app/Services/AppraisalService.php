<?php

namespace App\Services;

use App\Models\Appraisal;
use App\Models\AppraisalCycle;
use App\Models\CompetencyRating;
use App\Models\Employee;
use App\Models\Kra;
use App\Models\NextCycleKra;
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
            'finalReviewerName' => null, // buHead placeholder
            'doj' => $employee->doj ? ($employee->doj instanceof \DateTimeInterface ? $employee->doj->toIso8601String() : (string) $employee->doj) : null,
            'salary' => $employee->salary,
            'lastHike' => $employee->lastHike,
            'activeCycleName' => null,
            'appraisalId' => null,
            'grade' => $employee->grade,
            'dob' => $employee->dob ? ($employee->dob instanceof \DateTimeInterface ? $employee->dob->toIso8601String() : (string) $employee->dob) : null,
            'companyExperienceYears' => $employee->companyExperienceYears,
            'totalExperienceYears' => $employee->totalExperienceYears,
            'lastPromotionDate' => $employee->lastPromotionDate ? ($employee->lastPromotionDate instanceof \DateTimeInterface ? $employee->lastPromotionDate->toIso8601String() : (string) $employee->lastPromotionDate) : null,
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
        $buHead = $appraisal->buHead;
        $cycle = $appraisal->cycle;

        $history = Appraisal::where('employeeId', $appraisal->employeeId)
            ->where('id', '!=', $appraisal->id)
            ->with('cycle')
            ->orderBy('created_at', 'desc')
            ->get()
            ->map(function ($hist) {
                return [
                    'id' => $hist->id,
                    'cycleName' => $hist->cycle ? $hist->cycle->name : 'Unknown',
                    'appraisalType' => $hist->type,
                    'appraisalPeriod' => $hist->appraisalPeriod,
                    'status' => $hist->status,
                    'finalRating' => $hist->finalRating,
                    'hikePercentage' => $hist->hikePercentage,
                ];
            })->toArray();

        return [
            'id' => $appraisal->id,
            'employeeId' => $appraisal->employeeId,
            'employeeName' => $employee ? $employee->fullName : 'Unknown',
            'employeeCode' => $employee ? $employee->employeeCode : 'Unknown',
            'designation' => $employee ? $employee->designation : 'N/A',
            'teamName' => $team ? $team->name : 'Unknown',
            'managerName' => $manager ? $manager->fullName : 'N/A',
            'reviewerName' => $buHead ? $buHead->fullName : 'PR Team',
            'cycleId' => $appraisal->cycleId,
            'cycleName' => $cycle ? $cycle->name : 'Unknown',
            'appraisalType' => $appraisal->type,
            'appraisalPeriod' => $appraisal->appraisalPeriod,
            'status' => $appraisal->status,
            'specialAppeal' => $appraisal->specialAppeal,
            'specialAppealStatus' => $appraisal->specialAppealStatus,
            'finalRating' => $appraisal->finalRating,
            'hikePercentage' => $appraisal->hikePercentage,
            'sentimentLabel' => $appraisal->sentimentLabel,
            'updatedAt' => $appraisal->updated_at->toIso8601String(),
            'employee' => $employee ? [
                'fullName' => $employee->fullName,
                'employeeCode' => $employee->employeeCode,
                'email' => $employee->email,
                'managerId' => $employee->managerId,
                'department' => $employee->department,
                'designation' => $employee->designation,
                'grade' => $employee->grade,
                'doj' => $employee->doj ? ($employee->doj instanceof \DateTimeInterface ? $employee->doj->format('Y-m-d') : substr($employee->doj, 0, 10)) : null,
                'dob' => $employee->dob ? ($employee->dob instanceof \DateTimeInterface ? $employee->dob->format('Y-m-d') : substr($employee->dob, 0, 10)) : null,
                'companyExperienceYears' => $employee->companyExperienceYears,
                'totalExperienceYears' => $employee->totalExperienceYears,
                'lastPromotionDate' => $employee->lastPromotionDate ? ($employee->lastPromotionDate instanceof \DateTimeInterface ? $employee->lastPromotionDate->format('Y-m-d') : substr($employee->lastPromotionDate, 0, 10)) : null,
                'salary' => $employee->salary,
                'lastHike' => $employee->lastHike,
            ] : null,
            'history' => $history,
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
        if ($user->role === 'BU_HEAD' || $user->role === 'HR') {
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
        $isSelf = ($user->employeeId === $appraisal->employeeId);

        if ($isSelf && $status === 'DRAFT') {
            return true;
        }

        if ($user->role === 'EMPLOYEE') {
            if ($status === 'DRAFT' && $user->employeeId === $appraisal->employeeId) {
                return true;
            }
            if ($status === 'COMPLETED' && $user->employeeId === $appraisal->employeeId && !$appraisal->specialAppeal) {
                return true;
            }
            return false;
        }

        if ($user->role === 'MANAGER') {
            if ($status === 'SUBMITTED' && $user->employeeId === $appraisal->managerId) {
                return true;
            }
            if ($status === 'COMPLETED' && $user->employeeId === $appraisal->managerId) {
                if ($appraisal->buHeadSubmittedAt && now()->diffInDays($appraisal->buHeadSubmittedAt) <= 30) {
                    return true;
                }
            }
            return false;
        }

        if ($user->role === 'BU_HEAD') {
            if ($status === 'MANAGER_REVIEW') {
                return true;
            }
            if ($status === 'COMPLETED' && $appraisal->specialAppeal) {
                return true;
            }
            return false;
        }

        if ($user->role === 'HR') {
            if ($status === 'COMPLETED' && $appraisal->specialAppeal) {
                return true;
            }
            return false;
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

        $isSelf = ($user->employeeId === $appraisal->employeeId);
        $canEditEmployeeSection = ($status === 'DRAFT' && $isSelf);
        $canEditManagerSection = ($role === 'MANAGER' && $status === 'SUBMITTED' && $user->employeeId === $appraisal->managerId && !$isSelf);
        $canEditBUHeadSection = ($role === 'BU_HEAD' && $status === 'MANAGER_REVIEW');
        $canEditKRASection = $canEditEmployeeSection || $canEditManagerSection;

        $nextActionLabel = null;
        if ($canSubmit) {
            $nextActionLabel = match ($role) {
                'EMPLOYEE' => "Submit Appraisal",
                'MANAGER' => "Submit Review",
                'BU_HEAD' => "Finalize Appraisal",
                default => null
            };
        }

        return [
            'canSave' => $canSave,
            'canSubmit' => $canSubmit,
            'canEditEmployeeSection' => $canEditEmployeeSection,
            'canEditManagerSection' => $canEditManagerSection,
            'canEditBUHeadSection' => $canEditBUHeadSection,
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
            if ($role === 'BU_HEAD' || $role === 'HR') {
                // Enterprise access
            } elseif ($role === 'MANAGER') {
                $visibleQuery->where('teamId', $user->teamId);
            } else {
                $latestActiveAppraisalId = Appraisal::where('employeeId', $user->employeeId)
                    ->whereHas('cycle', function ($q) {
                        $q->where('isActive', true);
                    })
                    ->join('appraisal_cycles', 'appraisals.cycleId', '=', 'appraisal_cycles.id')
                    ->orderBy('appraisal_cycles.startDate', 'desc')
                    ->value('appraisals.id');

                if ($latestActiveAppraisalId) {
                    $visibleQuery->where('id', $latestActiveAppraisalId);
                } else {
                    $visibleQuery->where('employeeId', $user->employeeId);
                }
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
            $pendingQuery->where('status', 'DRAFT');
        } elseif ($role === 'MANAGER') {
            $pendingQuery->where('status', 'SUBMITTED')
                ->where('managerId', $user->employeeId);
        } elseif ($role === 'BU_HEAD') {
            $pendingQuery->where(function ($q) {
                $q->where('status', 'MANAGER_REVIEW')
                    ->orWhere(function ($sq) {
                        $sq->where('status', 'COMPLETED')
                            ->where('specialAppeal', true)
                            ->where('specialAppealStatus', 'PENDING');
                    });
            });
        } elseif ($role === 'HR') {
            $pendingQuery->where('status', 'COMPLETED')
                ->where('specialAppeal', true)
                ->where('specialAppealStatus', 'PENDING');
        } else {
            $pendingQuery->where('status', '__none__');
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
                ['label' => "Final", 'value' => (string) $counts['completed'], 'detail' => "Appraisals finalized by the BU Head."],
            ];
        } elseif ($role === 'MANAGER') {
            $metrics = [
                ['label' => "Team Reviews", 'value' => (string) $counts['total'], 'detail' => "Appraisals in your team portfolio."],
                ['label' => "Pending Reviews", 'value' => (string) $counts['submitted'], 'detail' => "Employee submissions awaiting your review."],
                ['label' => "Reviewed", 'value' => (string) $counts['managerReview'], 'detail' => "Manager-reviewed appraisals ready for the BU Head."],
                ['label' => "Completed", 'value' => (string) $counts['completed'], 'detail' => "Finalized appraisals in your team."],
            ];
        } else {
            $metrics = [
                ['label' => "Enterprise Appraisals", 'value' => (string) $counts['total'], 'detail' => "All appraisals across teams and cycles."],
                ['label' => "Pending Final", 'value' => (string) $counts['managerReview'], 'detail' => "Manager-reviewed appraisals awaiting final decision."],
                ['label' => "Completed", 'value' => (string) $counts['completed'], 'detail' => "BU Head finalized appraisals."],
            ];
        }

        // Team Summaries (Manager, BU Head, HR only)
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

        // Budget Impact (BU Head/HR only)
        $budgetImpact = null;
        if ($role === 'BU_HEAD' || $role === 'HR') {
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
        $appraisal = Appraisal::with(['cycle', 'team.manager', 'team.members', 'employee.manager', 'employee.team', 'manager.team', 'buHead', 'kras', 'competencyRatings', 'nextCycleKras'])->find($appraisalId);

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
        $buHeadReview = json_decode($appraisal->buHeadReview, true) ?: [];

        return [
            'id' => $appraisal->id,
            'type' => $appraisal->type,
            'grade' => $appraisal->grade,
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
            'buHead' => $appraisal->buHead ? self::serializeActor($appraisal->buHead) : null,
            'team' => self::serializeTeam($appraisal->team),
            'sectionOneAnswers' => AppraisalHelperService::normalizeSectionAnswers($sectionOneAnswers),
            'kras' => AppraisalHelperService::ensureKraRows(
                $appraisal->kras->map(fn($item) => [
                    'id' => $item->id,
                    'objective' => $item->objective,
                    'weightage' => $item->weightage,
                    'appraiseeRating' => $item->appraiseeRating,
                    'appraiserRating' => $item->appraiserRating,
                    'appraiseeComment' => $item->appraiseeComment ?? "",
                    'comments' => $item->comments ?? "",
                    'displayOrder' => $item->displayOrder,
                ])->toArray()
            ),
            // Section 4: Competency Ratings (11 fixed areas, scored 1–10)
            'competencyRatings' => AppraisalHelperService::defaultCompetencyRows(
                $appraisal->competencyRatings->map(fn($item) => [
                    'id' => $item->id,
                    'competencyName' => $item->competencyName,
                    'employeeScore' => $item->employeeScore,
                    'appraiserScore' => $item->appraiserScore,
                    'displayOrder' => $item->displayOrder,
                ])->toArray()
            ),
            // Section 5: Appraiser fields (Manager fills)
            'appraiserSection' => [
                'overallRating' => $appraisal->appraiserOverallRating,
                'recommendation' => $appraisal->appraiserRecommendation ?? '',
                'newKraNotes' => $appraisal->appraiserNewKraNotes ?? '',
                'newKras' => json_decode($appraisal->appraiserNewKraNotes, true) ?: (empty($appraisal->appraiserNewKraNotes) ? [] : [['objective' => $appraisal->appraiserNewKraNotes, 'weightage' => '']]),
                'signedAt' => $appraisal->appraiserSignedAt ? $appraisal->appraiserSignedAt->toIso8601String() : null,
            ],
            // Legacy managerReview kept for backward compat
            'managerReview' => [
                'overallRating' => $appraisal->managerOverallRating,
                'comments' => $managerReview['comments'] ?? '',
            ],
            // Section 6: Reviewer / BU Head fields
            'reviewerSection' => [
                'comments' => $appraisal->reviewerComments ?? '',
                'rating' => $appraisal->reviewerRating,
                'signedAt' => $appraisal->reviewerSignedAt ? $appraisal->reviewerSignedAt->toIso8601String() : null,
            ],
            'buHeadReview' => [
                'comments' => $buHeadReview['comments'] ?? '',
                'finalRating' => $appraisal->finalRating,
                'hikePercentage' => $appraisal->hikePercentage,
            ],
            'finalRating' => $appraisal->finalRating,
            'hikePercentage' => $appraisal->hikePercentage,
            'promotionRecommended' => $appraisal->promotionRecommended,
            'adjustments' => $appraisal->adjustments,
            'incrementAmount' => $appraisal->incrementAmount,
            'newCtc' => $appraisal->newCtc,
            'justification' => $appraisal->justification,
            'aiSummary' => $appraisal->aiPerformanceSummary,
            'sentimentLabel' => $appraisal->sentimentLabel,
            'sentimentScore' => $appraisal->sentimentScore,
            'strengths' => $parseStringArray($appraisal->aiStrengths),
            'weaknesses' => $parseStringArray($appraisal->aiWeaknesses),
            'riskSignals' => $parseStringArray($appraisal->aiRiskSignals),
            'permissions' => $permissions,
            'employeeSubmittedAt' => $appraisal->employeeSubmittedAt ? $appraisal->employeeSubmittedAt->toIso8601String() : null,
            'managerSubmittedAt' => $appraisal->managerSubmittedAt ? $appraisal->managerSubmittedAt->toIso8601String() : null,
            'appraiserSignedAt' => $appraisal->appraiserSignedAt ? $appraisal->appraiserSignedAt->toIso8601String() : null,
            'buHeadSubmittedAt' => $appraisal->buHeadSubmittedAt ? $appraisal->buHeadSubmittedAt->toIso8601String() : null,
            'reviewerSignedAt' => $appraisal->reviewerSignedAt ? $appraisal->reviewerSignedAt->toIso8601String() : null,
            'deadlineAt' => $appraisal->deadlineAt ? $appraisal->deadlineAt->toIso8601String() : null,
            'specialAppeal' => $appraisal->specialAppeal,
            'specialAppealStatus' => $appraisal->specialAppealStatus,
            'specialAppealComments' => $appraisal->specialAppealComments,
            'nextCycleKras' => [],
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
                if ($appraisal->status === 'COMPLETED') {
                    if (isset($payload['specialAppeal'])) {
                        $updates['specialAppeal'] = (bool) $payload['specialAppeal'];
                    }
                    if (isset($payload['justification'])) {
                        $updates['justification'] = trim($payload['justification']);
                    }
                    $updates['specialAppealStatus'] = 'PENDING';
                } else {
                    $updates['sectionOneAnswers'] = json_encode(AppraisalHelperService::normalizeSectionAnswers($payload['sectionOneAnswers'] ?? []));
                    $updates['status'] = ($mode === 'submit') ? 'SUBMITTED' : 'DRAFT';
                    $updates['employeeSubmittedAt'] = ($mode === 'submit') ? now() : $appraisal->employeeSubmittedAt;

                    // Save KRAs
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
                                'appraiseeComment' => $kraItem['appraiseeComment'] ?: null,
                                'comments' => $kraItem['comments'] ?: null,
                                'displayOrder' => $kraItem['displayOrder'],
                            ]);
                        }
                    }

                    // Save Competency Ratings (Section 4 — Employee scores)
                    if (isset($payload['competencyRatings'])) {
                        CompetencyRating::where('appraisalId', $appraisal->id)->delete();
                        foreach (AppraisalHelperService::defaultCompetencyRows($payload['competencyRatings']) as $item) {
                            CompetencyRating::create([
                                'id' => Str::uuid()->toString(),
                                'appraisalId' => $appraisal->id,
                                'competencyName' => $item['competencyName'],
                                'employeeScore' => isset($item['employeeScore']) && $item['employeeScore'] !== '' ? intval($item['employeeScore']) : null,
                                'appraiserScore' => null, // Appraiser fills this later
                                'displayOrder' => $item['displayOrder'],
                            ]);
                        }
                    }
                }
            }

            if ($role === 'MANAGER') {
                if ($appraisal->status === 'COMPLETED') {
                    if (isset($payload['nextCycleKras'])) {
                        // next cycle KRAs removed from UI but kept in service for backward compat
                    }
                } else {
                    // Section 5: Appraiser fields
                    $appraiserRating = null;
                    if (isset($payload['appraiserSection']['overallRating']) && $payload['appraiserSection']['overallRating'] !== null) {
                        $appraiserRating = AppraisalHelperService::roundTo(AppraisalHelperService::clampScale(floatval($payload['appraiserSection']['overallRating']), 0, 10));
                    }

                    $updates['appraiserOverallRating'] = $appraiserRating;
                    $updates['managerOverallRating'] = $appraiserRating; // keep legacy field in sync

                    if (isset($payload['appraiserSection']['recommendation'])) {
                        $updates['appraiserRecommendation'] = trim($payload['appraiserSection']['recommendation']);
                    }
                    if (isset($payload['appraiserSection']['newKras'])) {
                        $updates['appraiserNewKraNotes'] = json_encode(array_values(array_filter($payload['appraiserSection']['newKras'], fn($item) => !empty(trim($item['objective'] ?? '')))));
                    } elseif (isset($payload['appraiserSection']['newKraNotes'])) {
                        $updates['appraiserNewKraNotes'] = trim($payload['appraiserSection']['newKraNotes']);
                    }

                    // Keep legacy managerReview JSON for backward compat
                    $updates['managerReview'] = json_encode([
                        'overallRating' => $appraiserRating,
                        'comments' => $payload['appraiserSection']['recommendation'] ?? '',
                    ]);

                    $updates['status'] = ($mode === 'submit') ? 'MANAGER_REVIEW' : $appraisal->status;
                    $updates['managerSubmittedAt'] = ($mode === 'submit') ? now() : $appraisal->managerSubmittedAt;
                    $updates['appraiserSignedAt'] = ($mode === 'submit') ? now() : $appraisal->appraiserSignedAt;

                    // Save KRAs (Appraiser fills appraiserRating and comments)
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
                                'appraiseeComment' => $kraItem['appraiseeComment'] ?: null,
                                'comments' => $kraItem['comments'] ?: null,
                                'displayOrder' => $kraItem['displayOrder'],
                            ]);
                        }
                    }

                    // Save Competency Ratings (Appraiser fills appraiserScore)
                    if (isset($payload['competencyRatings'])) {
                        $existingCompetencies = CompetencyRating::where('appraisalId', $appraisal->id)->get()->keyBy('competencyName');
                        foreach (AppraisalHelperService::defaultCompetencyRows($payload['competencyRatings']) as $item) {
                            $existing = $existingCompetencies->get($item['competencyName']);
                            if ($existing) {
                                $existing->update([
                                    'appraiserScore' => isset($item['appraiserScore']) && $item['appraiserScore'] !== '' ? intval($item['appraiserScore']) : null,
                                ]);
                            } else {
                                CompetencyRating::create([
                                    'id' => Str::uuid()->toString(),
                                    'appraisalId' => $appraisal->id,
                                    'competencyName' => $item['competencyName'],
                                    'employeeScore' => null,
                                    'appraiserScore' => isset($item['appraiserScore']) && $item['appraiserScore'] !== '' ? intval($item['appraiserScore']) : null,
                                    'displayOrder' => $item['displayOrder'],
                                ]);
                            }
                        }
                    }
                }
            }

            if ($role === 'BU_HEAD' || $role === 'HR') {
                if ($appraisal->status === 'COMPLETED') {
                    if (isset($payload['specialAppealStatus'])) {
                        $updates['specialAppealStatus'] = trim($payload['specialAppealStatus']);
                    }
                    if (isset($payload['specialAppealComments'])) {
                        $updates['specialAppealComments'] = trim($payload['specialAppealComments']);
                    }
                } elseif ($role === 'BU_HEAD') {
                    $finalRating = null;
                    if (isset($payload['buHeadReview']['finalRating']) && $payload['buHeadReview']['finalRating'] !== null) {
                        $finalRating = AppraisalHelperService::roundTo(AppraisalHelperService::clampScale(floatval($payload['buHeadReview']['finalRating']), 0, 10));
                    }

                    $hikePercentage = null;
                    if (isset($payload['buHeadReview']['hikePercentage']) && $payload['buHeadReview']['hikePercentage'] !== null) {
                        $hikePercentage = AppraisalHelperService::roundTo(AppraisalHelperService::clampScale(floatval($payload['buHeadReview']['hikePercentage']), 0, 100));
                    }

                    // Section 6: Reviewer fields
                    $reviewerRating = null;
                    if (isset($payload['reviewerSection']['rating']) && $payload['reviewerSection']['rating'] !== null) {
                        $reviewerRating = AppraisalHelperService::roundTo(AppraisalHelperService::clampScale(floatval($payload['reviewerSection']['rating']), 0, 10));
                    }
                    if (isset($payload['reviewerSection']['comments'])) {
                        $updates['reviewerComments'] = trim($payload['reviewerSection']['comments']);
                    }
                    $updates['reviewerRating'] = $reviewerRating;

                    $updates['buHeadReview'] = json_encode([
                        'comments' => $payload['reviewerSection']['comments'] ?? '',
                        'finalRating' => $finalRating,
                        'hikePercentage' => $hikePercentage,
                    ]);
                    $updates['finalRating'] = $finalRating;
                    $updates['hikePercentage'] = $hikePercentage;

                    if (isset($payload['promotionRecommended'])) {
                        $updates['promotionRecommended'] = (bool) $payload['promotionRecommended'];
                    }
                    if (isset($payload['adjustments'])) {
                        $updates['adjustments'] = floatval($payload['adjustments']);
                    }
                    if (isset($payload['incrementAmount'])) {
                        $updates['incrementAmount'] = floatval($payload['incrementAmount']);
                    }
                    if (isset($payload['newCtc'])) {
                        $updates['newCtc'] = floatval($payload['newCtc']);
                    }
                    if (isset($payload['grade'])) {
                        $updates['grade'] = trim($payload['grade']);
                    }
                    if (isset($payload['justification'])) {
                        $updates['justification'] = trim($payload['justification']);
                    }

                    // Appraiser scores for competencies (Reviewer can also adjust)
                    if (isset($payload['competencyRatings'])) {
                        $existingCompetencies = CompetencyRating::where('appraisalId', $appraisal->id)->get()->keyBy('competencyName');
                        foreach (AppraisalHelperService::defaultCompetencyRows($payload['competencyRatings']) as $item) {
                            $existing = $existingCompetencies->get($item['competencyName']);
                            if ($existing) {
                                $existing->update([
                                    'appraiserScore' => isset($item['appraiserScore']) && $item['appraiserScore'] !== '' ? intval($item['appraiserScore']) : $existing->appraiserScore,
                                ]);
                            }
                        }
                    }

                    $updates['status'] = ($mode === 'submit') ? 'COMPLETED' : $appraisal->status;
                    $updates['buHeadSubmittedAt'] = ($mode === 'submit') ? now() : $appraisal->buHeadSubmittedAt;
                    $updates['reviewerSignedAt'] = ($mode === 'submit') ? now() : $appraisal->reviewerSignedAt;
                    $updates['buHeadId'] = $user->employeeId;
                }
            }

            $appraisal->update($updates);
        });

        // Trigger AI performance analysis if submitted by Manager/BU Head
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

            if (!$allAnswersFilled || empty($payload['kras'])) {
                throw new \Exception("Employee submission requires all answers and KRAs to be filled.");
            }
        }

        if ($role === 'MANAGER') {
            if (
                empty(trim($payload['appraiserSection']['recommendation'] ?? '')) ||
                !isset($payload['appraiserSection']['overallRating']) ||
                $payload['appraiserSection']['overallRating'] === null
            ) {
                throw new \Exception("Appraiser review requires a recommendation and an overall rating.");
            }
        }

        if ($role === 'BU_HEAD') {
            if (
                !isset($payload['buHeadReview']['finalRating']) || $payload['buHeadReview']['finalRating'] === null
            ) {
                throw new \Exception("Reviewer decision requires a final rating.");
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

        $buHeadReview = json_decode($appraisal->buHeadReview, true) ?: [];
        $buHeadText = $buHeadReview['comments'] ?? "";

        $analysis = GeminiService::generatePerformanceAnalysis([
            'employeeName' => $appraisal->employee->fullName,
            'designation' => $appraisal->employee->designation,
            'teamName' => $appraisal->team->name,
            'appraisalType' => $appraisal->type,
            'appraisalPeriod' => $appraisal->appraisalPeriod,
            'fullText' => implode(" ", array_filter([$sectionText, $kraText, $skillText, $managerText, $buHeadText])),
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
        if ($role !== 'HR') {
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
        if ($role !== 'HR') {
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
        if ($role !== 'HR') {
            throw new \Exception("Unauthorized");
        }

        $cycle = AppraisalCycle::find($cycleId);
        if (!$cycle) {
            throw new \Exception("Cycle not found");
        }

        $employees = Employee::where('role', '!=', 'BU_HEAD')->get();
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
