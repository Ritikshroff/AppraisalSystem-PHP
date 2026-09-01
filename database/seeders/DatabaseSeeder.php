<?php

namespace Database\Seeders;

use App\Models\Appraisal;
use App\Models\AppraisalCycle;
use App\Models\CompetencyRating;
use App\Models\Employee;
use App\Models\Kra;
use App\Models\SkillRating;
use App\Models\SystemSettings;
use App\Models\Team;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class DatabaseSeeder extends Seeder
{
    private $sectionQuestions = [
        "Has the past year been good/bad/satisfactory or otherwise for you, and why?",
        "What do you consider to be your most important achievements of the past year?",
        "What elements of your job do you find most difficult?",
        "What elements of your job interest you the most, and least?",
        "What action could be taken to improve your performance in your current position by you, and your boss?",
        "What sort of training/experiences would benefit you in the next year? Not just job-skills - also your natural strengths and personal passions you'd like to develop - you and your work can benefit from these.",
        "Mention if you have any grievances/problem/are of dissatisfaction which affect your performance.",
    ];

    private $skillTemplates = [
        "Technical Knowledge",
        "Communication",
        "Problem Solving",
        "Ownership",
        "Stakeholder Management",
    ];

    private function buildSectionAnswers(string $employeeName, string $focus): array
    {
        return array_map(function ($question) use ($focus) {
            return [
                'question' => $question,
                'answer' => $focus // real status context if provided, else empty
            ];
        }, $this->sectionQuestions);
    }

    private function buildKras(array $objectives, bool $managerComplete = false): array
    {
        $kras = [];
        foreach ($objectives as $index => $objective) {
            $kras[] = [
                'id' => Str::uuid()->toString(),
                'objective' => $objective,
                'weightage' => null,
                'appraiseeRating' => null,
                'appraiserRating' => null,
                'appraiseeComment' => '',
                'comments' => '',
                'displayOrder' => $index,
            ];
        }
        return $kras;
    }

    private function buildCompetencies(bool $appraiserComplete = false): array
    {
        $competencies = \App\Services\AppraisalHelperService::DEFAULT_COMPETENCIES;
        $result = [];
        foreach ($competencies as $index => $name) {
            $result[] = [
                'id' => Str::uuid()->toString(),
                'competencyName' => $name,
                'employeeScore' => null,
                'appraiserScore' => null,
                'displayOrder' => $index,
            ];
        }
        return $result;
    }

    private function buildSkills(bool $managerComplete = false): array
    {
        $skills = [];
        foreach ($this->skillTemplates as $index => $skillName) {
            $skills[] = [
                'id' => Str::uuid()->toString(),
                'skillName' => $skillName,
                'employeeRating' => null,
                'managerRating' => null,
                'displayOrder' => $index,
            ];
        }
        return $skills;
    }

    private function createUserForEmployee(array $data): User
    {
        return User::create([
            'id' => Str::uuid()->toString(),
            'email' => $data['email'],
            'passwordHash' => $data['passwordHash'],
            'name' => $data['fullName'],
            'role' => $data['role'],
            'employeeId' => $data['employeeId'],
            'teamId' => $data['teamId'],
        ]);
    }

    private function createAppraisal(array $data): Appraisal
    {
        $status = $data['status'];
        $managerComplete = ($status === 'MANAGER_REVIEW' || $status === 'COMPLETED');
        $employeeName = $data['employeeName'];

        $appraisal = Appraisal::create([
            'id' => Str::uuid()->toString(),
            'employeeId' => $data['employeeId'],
            'teamId' => $data['teamId'],
            'cycleId' => $data['cycleId'],
            'managerId' => $data['managerId'],
            'buHeadId' => $data['buHeadId'],
            'type' => $data['type'],
            'appraisalPeriod' => $data['appraisalPeriod'],
            'status' => $status,
            'sectionOneAnswers' => json_encode($this->buildSectionAnswers($employeeName, $data['sectionFocus'] ?? '')),
            'managerReview' => $managerComplete ? json_encode([
                'comments' => $data['managerComment'] ?? "",
                'overallRating' => $data['managerOverallRating'] ?? null
            ]) : null,
            'buHeadReview' => ($status === 'COMPLETED') ? json_encode([
                'comments' => $data['buHeadComment'] ?? "",
                'finalRating' => $data['finalRating'] ?? null,
                'hikePercentage' => $data['hikePercentage'] ?? null,
            ]) : null,
            'managerOverallRating' => isset($data['managerOverallRating']) ? floatval($data['managerOverallRating']) : null,
            'finalRating' => isset($data['finalRating']) ? floatval($data['finalRating']) : null,
            'hikePercentage' => isset($data['hikePercentage']) ? floatval($data['hikePercentage']) : null,
            'aiPerformanceSummary' => $data['summary'] ?? null,
            'sentimentLabel' => $data['sentimentLabel'] ?? null,
            'sentimentScore' => isset($data['sentimentScore']) ? floatval($data['sentimentScore']) : null,
            'aiStrengths' => isset($data['strengths']) ? json_encode($data['strengths']) : null,
            'aiWeaknesses' => isset($data['weaknesses']) ? json_encode($data['weaknesses']) : null,
            'aiRiskSignals' => isset($data['risks']) ? json_encode($data['risks']) : null,
            'employeeSubmittedAt' => ($status === 'DRAFT') ? null : '2026-04-12 09:00:00',
            'managerSubmittedAt' => $managerComplete ? '2026-04-16 10:00:00' : null,
            'buHeadSubmittedAt' => ($status === 'COMPLETED') ? '2026-04-22 12:00:00' : null,
            'analyzedAt' => $managerComplete ? '2026-04-22 12:10:00' : null,
        ]);

        foreach ($this->buildKras($data['kraObjectives'], $managerComplete) as $kra) {
            $kra['appraisalId'] = $appraisal->id;
            Kra::create($kra);
        }

        foreach ($this->buildSkills($managerComplete) as $skill) {
            $skill['appraisalId'] = $appraisal->id;
            SkillRating::create($skill);
        }

        foreach ($this->buildCompetencies($managerComplete) as $comp) {
            $comp['appraisalId'] = $appraisal->id;
            CompetencyRating::create($comp);
        }

        return $appraisal;
    }

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // Clear tables
        \Illuminate\Support\Facades\Schema::disableForeignKeyConstraints();
        CompetencyRating::truncate();
        SkillRating::truncate();
        Kra::truncate();
        Appraisal::truncate();
        User::truncate();
        AppraisalCycle::truncate();
        Team::truncate();
        Employee::truncate();
        \Illuminate\Support\Facades\Schema::enableForeignKeyConstraints();

        $defaultPassword = "Cybermedia@123";
        $passwordHash = Hash::make($defaultPassword);

        // 1. BU Head (Reviewer)
        $buHead = Employee::create([
            'id' => Str::uuid()->toString(),
            'employeeCode' => "BUH-0001",
            'fullName' => "PR Team",
            'email' => "pram@cmr.net",
            'department' => "Executive Review",
            'designation' => "Reviewer / BU Head",
            'role' => "BU_HEAD",
        ]);

        // 2. HR Admin
        $hr = Employee::create([
            'id' => Str::uuid()->toString(),
            'employeeCode' => "HR-0001",
            'fullName' => "HR Admin",
            'email' => "hr@cmrindia.com",
            'department' => "Human Resources",
            'designation' => "HR Director",
            'role' => "HR",
        ]);

        // 3. Appraisers (Managers)
        $mudrikaManager = Employee::create([
            'id' => Str::uuid()->toString(),
            'employeeCode' => "MGR-1001",
            'fullName' => "Mudrika Ram",
            'email' => "pram@cmrindia.com",
            'department' => "Research",
            'designation' => "Manager - Research",
            'role' => "MANAGER",
        ]);

        $sugandhaManager = Employee::create([
            'id' => Str::uuid()->toString(),
            'employeeCode' => "MGR-1002",
            'fullName' => "Sugandha Srivastava",
            'email' => "srivastava@cmrindia.com",
            'department' => "Research",
            'designation' => "Manager - Research",
            'grade' => "M2",
            'doj' => "2019-06-15",
            'dob' => "1988-11-20",
            'lastPromotionDate' => "2023-04-01",
            'companyExperienceYears' => 5.2,
            'totalExperienceYears' => 9.5,
            'role' => "MANAGER",
        ]);

        // Teams
        $researchTeam1 = Team::create([
            'id' => Str::uuid()->toString(),
            'name' => "Research - Mudrika Team",
            'managerId' => $mudrikaManager->id,
        ]);

        $researchTeam2 = Team::create([
            'id' => Str::uuid()->toString(),
            'name' => "Research - Sugandha Team",
            'managerId' => $sugandhaManager->id,
        ]);

        $mudrikaManager->update(['teamId' => $researchTeam1->id]);
        $sugandhaManager->update(['teamId' => $researchTeam2->id]);

        // 4. Employees (Appraisees)
        $nugent = Employee::create([
            'id' => Str::uuid()->toString(),
            'employeeCode' => "EMP-3001",
            'fullName' => "Nugent Srivastava",
            'email' => "nsrivastava@cmrindia.com",
            'department' => "Research",
            'designation' => "Sr. Manager - Research",
            'grade' => "VIII",
            'role' => "EMPLOYEE",
            'teamId' => $researchTeam1->id,
            'managerId' => $mudrikaManager->id,
            'doj' => '2020-12-14 00:00:00',
            'companyExperienceYears' => 5.7,
            'totalExperienceYears' => 10.7,
            'salary' => 1041000,
            'lastPromotionDate' => '2023-10-01 00:00:00',
        ]);

        $himanshi = Employee::create([
            'id' => Str::uuid()->toString(),
            'employeeCode' => "EMP-3002",
            'fullName' => "Himanshi Pant",
            'email' => "hpant@cmrindia.com",
            'department' => "Research",
            'designation' => "Assistant Manager - Research",
            'grade' => "IVD",
            'role' => "EMPLOYEE",
            'teamId' => $researchTeam2->id,
            'managerId' => $sugandhaManager->id,
            'doj' => '2023-12-01 00:00:00',
            'companyExperienceYears' => 2.0,
            'totalExperienceYears' => 4.0,
            'salary' => 504000,
            'lastPromotionDate' => '2025-10-01 00:00:00',
        ]);

        $nalini = Employee::create([
            'id' => Str::uuid()->toString(),
            'employeeCode' => "EMP-3003",
            'fullName' => "Nalini Kanta Nayak",
            'email' => "nnayak@cmrindia.com",
            'department' => "Research Operations",
            'designation' => "Manager - Research Operations",
            'grade' => "VB",
            'role' => "EMPLOYEE",
            'teamId' => $researchTeam2->id,
            'managerId' => $sugandhaManager->id,
            'doj' => '2025-10-01 00:00:00',
            'companyExperienceYears' => 0.9,
            'totalExperienceYears' => 15.3,
            'salary' => 725820,
        ]);

        $saurabh = Employee::create([
            'id' => Str::uuid()->toString(),
            'employeeCode' => "EMP-3004",
            'fullName' => "Saurabh Pandey",
            'email' => "spandey@cmrindia.com",
            'department' => "Research",
            'designation' => "Manager - Research",
            'grade' => "VB",
            'role' => "EMPLOYEE",
            'teamId' => $researchTeam2->id,
            'managerId' => $sugandhaManager->id,
            'doj' => '2025-06-16 00:00:00',
            'companyExperienceYears' => 1.2,
            'totalExperienceYears' => 7.2,
            'salary' => 826140,
            'lastHike' => 10.0,
        ]);

        // Users
        $allStaff = [
            $buHead,
            $hr,
            $mudrikaManager,
            $sugandhaManager,
            $nugent,
            $himanshi,
            $nalini,
            $saurabh
        ];

        foreach ($allStaff as $staff) {
            $this->createUserForEmployee([
                'email' => $staff->email,
                'fullName' => $staff->fullName,
                'role' => $staff->role,
                'employeeId' => $staff->id,
                'teamId' => $staff->teamId,
                'passwordHash' => $passwordHash,
            ]);
        }

        // Appraisal Cycles
        $aprilCycle = AppraisalCycle::create([
            'id' => Str::uuid()->toString(),
            'name' => "April 2026 Work Appraisal",
            'appraisalType' => 'WORK',
            'periodLabel' => "April",
            'startDate' => '2026-04-01 00:00:00',
            'endDate' => '2026-04-30 23:59:59',
            'isActive' => true,
        ]);

        $octoberCycle = AppraisalCycle::create([
            'id' => Str::uuid()->toString(),
            'name' => "October 2026 Appraisal Cycle",
            'appraisalType' => 'GENERAL',
            'periodLabel' => "October",
            'startDate' => '2026-10-01 00:00:00',
            'endDate' => '2026-10-31 23:59:59',
            'isActive' => true,
        ]);

        // Seed Appraisals matching Excel structure per employee
        // 1. Nugent Srivastava (Appraisal Type: Work)
        $this->createAppraisal([
            'employeeId' => $nugent->id,
            'teamId' => $researchTeam1->id,
            'cycleId' => $octoberCycle->id,
            'managerId' => $mudrikaManager->id,
            'buHeadId' => $buHead->id,
            'type' => 'WORK',
            'appraisalPeriod' => "October",
            'status' => 'DRAFT',
            'employeeName' => $nugent->fullName,
            'sectionFocus' => "",
            'kraObjectives' => [],
        ]);

        // 2. Himanshi Pant (Appraisal Type: Work)
        $this->createAppraisal([
            'employeeId' => $himanshi->id,
            'teamId' => $researchTeam2->id,
            'cycleId' => $octoberCycle->id,
            'managerId' => $sugandhaManager->id,
            'buHeadId' => $buHead->id,
            'type' => 'WORK',
            'appraisalPeriod' => "October",
            'status' => 'DRAFT',
            'employeeName' => $himanshi->fullName,
            'sectionFocus' => "",
            'kraObjectives' => [],
        ]);

        // 3. Nalini Kanta Nayak (Appraisal Type: Work)
        $this->createAppraisal([
            'employeeId' => $nalini->id,
            'teamId' => $researchTeam2->id,
            'cycleId' => $octoberCycle->id,
            'managerId' => $sugandhaManager->id,
            'buHeadId' => $buHead->id,
            'type' => 'WORK',
            'appraisalPeriod' => "October",
            'status' => 'DRAFT',
            'employeeName' => $nalini->fullName,
            'sectionFocus' => "",
            'kraObjectives' => [],
        ]);

        // 4. Saurabh Pandey (Appraisal Type: Salary)
        $this->createAppraisal([
            'employeeId' => $saurabh->id,
            'teamId' => $researchTeam2->id,
            'cycleId' => $octoberCycle->id,
            'managerId' => $sugandhaManager->id,
            'buHeadId' => $buHead->id,
            'type' => 'SALARY',
            'appraisalPeriod' => "October",
            'status' => 'DRAFT',
            'employeeName' => $saurabh->fullName,
            'sectionFocus' => "",
            'kraObjectives' => [],
        ]);

        
        // 5. Sugandha Srivastava Self Appraisal
        $this->createAppraisal([
            'employeeId' => $sugandhaManager->id,
            'teamId' => $researchTeam2->id,
            'cycleId' => $octoberCycle->id,
            'managerId' => $buHead->id,
            'buHeadId' => $buHead->id,
            'type' => 'WORK',
            'appraisalPeriod' => "October",
            'status' => 'DRAFT',
            'employeeName' => $sugandhaManager->fullName,
            'sectionFocus' => "",
            'kraObjectives' => [],
        ]);

        // 6. Mudrika Ram Self Appraisal
        $this->createAppraisal([
            'employeeId' => $mudrikaManager->id,
            'teamId' => $researchTeam1->id,
            'cycleId' => $octoberCycle->id,
            'managerId' => $buHead->id,
            'buHeadId' => $buHead->id,
            'type' => 'WORK',
            'appraisalPeriod' => "October",
            'status' => 'DRAFT',
            'employeeName' => $mudrikaManager->fullName,
            'sectionFocus' => "",
            'kraObjectives' => [],
        ]);

        // Global System Settings
        SystemSettings::create([
            'id' => 'GLOBAL',
            'globalDeadlineStart' => '2026-04-01 00:00:00',
            'globalDeadlineEnd' => '2026-04-30 23:59:59',
        ]);
    }
}
