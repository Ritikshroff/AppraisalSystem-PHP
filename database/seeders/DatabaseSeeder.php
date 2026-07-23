<?php

namespace Database\Seeders;

use App\Models\Appraisal;
use App\Models\AppraisalCycle;
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
        return array_map(function ($question, $index) use ($employeeName, $focus) {
            return [
                'question' => $question,
                'answer' => "{$employeeName} response " . ($index + 1) . ": {$focus}"
            ];
        }, $this->sectionQuestions, array_keys($this->sectionQuestions));
    }

    private function buildKras(array $objectives, bool $managerComplete = false): array
    {
        $kras = [];
        $count = count($objectives);
        foreach ($objectives as $index => $objective) {
            $kras[] = [
                'id' => Str::uuid()->toString(),
                'objective' => $objective,
                'weightage' => 100 / $count,
                'appraiseeRating' => 7.2 + $index * 0.5,
                'appraiserRating' => $managerComplete ? 7.4 + $index * 0.4 : null,
                'comments' => $managerComplete ? "Manager comments for {$objective}." : null,
                'displayOrder' => $index,
            ];
        }
        return $kras;
    }

    private function buildSkills(bool $managerComplete = false): array
    {
        $skills = [];
        foreach ($this->skillTemplates as $index => $skillName) {
            $skills[] = [
                'id' => Str::uuid()->toString(),
                'skillName' => $skillName,
                'employeeRating' => min(10, 7 + $index),
                'managerRating' => $managerComplete ? min(10, 7 + min($index, 2)) : null,
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
            'ceoId' => $data['ceoId'],
            'type' => $data['type'],
            'appraisalPeriod' => $data['appraisalPeriod'],
            'status' => $status,
            'sectionOneAnswers' => json_encode($this->buildSectionAnswers($employeeName, $data['sectionFocus'])),
            'managerReview' => $managerComplete ? json_encode([
                'comments' => $data['managerComment'] ?? "{$employeeName} is delivering steadily and is ready for the next level of scope.",
                'overallRating' => $data['managerOverallRating'] ?? "7.80"
            ]) : null,
            'ceoReview' => ($status === 'COMPLETED') ? json_encode([
                'comments' => $data['ceoComment'] ?? "{$employeeName} is approved for the final cycle outcome.",
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
            'ceoSubmittedAt' => ($status === 'COMPLETED') ? '2026-04-22 12:00:00' : null,
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

        return $appraisal;
    }

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // Clear tables
        SkillRating::truncate();
        Kra::truncate();
        Appraisal::truncate();
        User::truncate();
        AppraisalCycle::truncate();
        
        // Remove foreign key constraints temporarily to truncate circular relationships
        \Illuminate\Support\Facades\Schema::disableForeignKeyConstraints();
        Team::truncate();
        Employee::truncate();
        \Illuminate\Support\Facades\Schema::enableForeignKeyConstraints();

        $defaultPassword = "Cybermedia@123";
        $passwordHash = Hash::make($defaultPassword);

        // CEO
        $ceo = Employee::create([
            'id' => Str::uuid()->toString(),
            'employeeCode' => "CEO-0001",
            'fullName' => "Meera Kapoor",
            'email' => "meera.kapoor@cmrsl.example",
            'department' => "Executive",
            'designation' => "Chief Executive Officer",
            'role' => "CEO",
        ]);

        // HR
        $hr = Employee::create([
            'id' => Str::uuid()->toString(),
            'employeeCode' => "HR-0001",
            'fullName' => "Sanjay Mishra",
            'email' => "sanjay.mishra@cmrsl.example",
            'department' => "Human Resources",
            'designation' => "HR Director",
            'role' => "HR",
        ]);

        // Tech Manager
        $techManager = Employee::create([
            'id' => Str::uuid()->toString(),
            'employeeCode' => "MGR-1001",
            'fullName' => "Anita Rao",
            'email' => "anita.rao@cmrsl.example",
            'department' => "Tech",
            'designation' => "Engineering Manager",
            'role' => "MANAGER",
        ]);

        // Media Manager
        $mediaManager = Employee::create([
            'id' => Str::uuid()->toString(),
            'employeeCode' => "MGR-1002",
            'fullName' => "Vikram Singh",
            'email' => "vikram.singh@cmrsl.example",
            'department' => "Media",
            'designation' => "Media Manager",
            'role' => "MANAGER",
        ]);

        // Marketing Manager
        $marketingManager = Employee::create([
            'id' => Str::uuid()->toString(),
            'employeeCode' => "MGR-1003",
            'fullName' => "Arjun Desai",
            'email' => "arjun.desai@cmrsl.example",
            'department' => "Marketing",
            'designation' => "Marketing Manager",
            'role' => "MANAGER",
        ]);

        // Teams
        $techTeam = Team::create([
            'id' => Str::uuid()->toString(),
            'name' => "Tech",
            'managerId' => $techManager->id,
        ]);

        $mediaTeam = Team::create([
            'id' => Str::uuid()->toString(),
            'name' => "Media",
            'managerId' => $mediaManager->id,
        ]);

        $marketingTeam = Team::create([
            'id' => Str::uuid()->toString(),
            'name' => "Marketing",
            'managerId' => $marketingManager->id,
        ]);

        // Update managers with team IDs
        $techManager->update(['teamId' => $techTeam->id]);
        $mediaManager->update(['teamId' => $mediaTeam->id]);
        $marketingManager->update(['teamId' => $marketingTeam->id]);

        // Employees
        $rahul = Employee::create([
            'id' => Str::uuid()->toString(),
            'employeeCode' => "EMP-2001",
            'fullName' => "Rahul Sharma",
            'email' => "rahul.sharma@cmrsl.example",
            'department' => "Tech",
            'designation' => "Senior Software Engineer",
            'role' => "EMPLOYEE",
            'teamId' => $techTeam->id,
            'managerId' => $techManager->id,
        ]);

        $priya = Employee::create([
            'id' => Str::uuid()->toString(),
            'employeeCode' => "EMP-2002",
            'fullName' => "Priya Nair",
            'email' => "priya.nair@cmrsl.example",
            'department' => "Tech",
            'designation' => "Frontend Engineer",
            'role' => "EMPLOYEE",
            'teamId' => $techTeam->id,
            'managerId' => $techManager->id,
        ]);

        $sneha = Employee::create([
            'id' => Str::uuid()->toString(),
            'employeeCode' => "EMP-2003",
            'fullName' => "Sneha Patel",
            'email' => "sneha.patel@cmrsl.example",
            'department' => "Media",
            'designation' => "Content Strategist",
            'role' => "EMPLOYEE",
            'teamId' => $mediaTeam->id,
            'managerId' => $mediaManager->id,
        ]);

        $aisha = Employee::create([
            'id' => Str::uuid()->toString(),
            'employeeCode' => "EMP-2004",
            'fullName' => "Aisha Khan",
            'email' => "aisha.khan@cmrsl.example",
            'department' => "Media",
            'designation' => "Business Analyst",
            'role' => "EMPLOYEE",
            'teamId' => $mediaTeam->id,
            'managerId' => $mediaManager->id,
        ]);

        $karan = Employee::create([
            'id' => Str::uuid()->toString(),
            'employeeCode' => "EMP-2005",
            'fullName' => "Karan Mehta",
            'email' => "karan.mehta@cmrsl.example",
            'department' => "Marketing",
            'designation' => "Performance Marketer",
            'role' => "EMPLOYEE",
            'teamId' => $marketingTeam->id,
            'managerId' => $marketingManager->id,
        ]);

        $nidhi = Employee::create([
            'id' => Str::uuid()->toString(),
            'employeeCode' => "EMP-2006",
            'fullName' => "Nidhi Verma",
            'email' => "nidhi.verma@cmrsl.example",
            'department' => "Marketing",
            'designation' => "Brand Executive",
            'role' => "EMPLOYEE",
            'teamId' => $marketingTeam->id,
            'managerId' => $marketingManager->id,
        ]);

        $neha = Employee::create([
            'id' => Str::uuid()->toString(),
            'employeeCode' => "EMP-2007",
            'fullName' => "Neha Bansal",
            'email' => "neha.bansal@cmrsl.example",
            'department' => "Tech",
            'designation' => "Senior Backend Engineer",
            'role' => "EMPLOYEE",
            'teamId' => $techTeam->id,
            'managerId' => $techManager->id,
        ]);

        $saurabh = Employee::create([
            'id' => Str::uuid()->toString(),
            'employeeCode' => "EMP-2008",
            'fullName' => "Saurabh Jain",
            'email' => "saurabh.jain@cmrsl.example",
            'department' => "Tech",
            'designation' => "DevOps Engineer",
            'role' => "EMPLOYEE",
            'teamId' => $techTeam->id,
            'managerId' => $techManager->id,
        ]);

        $kavya = Employee::create([
            'id' => Str::uuid()->toString(),
            'employeeCode' => "EMP-2009",
            'fullName' => "Kavya Iyer",
            'email' => "kavya.iyer@cmrsl.example",
            'department' => "Tech",
            'designation' => "QA Lead",
            'role' => "EMPLOYEE",
            'teamId' => $techTeam->id,
            'managerId' => $techManager->id,
        ]);

        $rohan = Employee::create([
            'id' => Str::uuid()->toString(),
            'employeeCode' => "EMP-2010",
            'fullName' => "Rohan Malhotra",
            'email' => "rohan.malhotra@cmrsl.example",
            'department' => "Media",
            'designation' => "Video Producer",
            'role' => "EMPLOYEE",
            'teamId' => $mediaTeam->id,
            'managerId' => $mediaManager->id,
        ]);

        $ishita = Employee::create([
            'id' => Str::uuid()->toString(),
            'employeeCode' => "EMP-2011",
            'fullName' => "Ishita Roy",
            'email' => "ishita.roy@cmrsl.example",
            'department' => "Media",
            'designation' => "Editorial Analyst",
            'role' => "EMPLOYEE",
            'teamId' => $mediaTeam->id,
            'managerId' => $mediaManager->id,
        ]);

        $dev = Employee::create([
            'id' => Str::uuid()->toString(),
            'employeeCode' => "EMP-2012",
            'fullName' => "Dev Joshi",
            'email' => "dev.joshi@cmrsl.example",
            'department' => "Media",
            'designation' => "Audience Growth Executive",
            'role' => "EMPLOYEE",
            'teamId' => $mediaTeam->id,
            'managerId' => $mediaManager->id,
        ]);

        $pooja = Employee::create([
            'id' => Str::uuid()->toString(),
            'employeeCode' => "EMP-2013",
            'fullName' => "Pooja Sethi",
            'email' => "pooja.sethi@cmrsl.example",
            'department' => "Marketing",
            'designation' => "SEO Specialist",
            'role' => "EMPLOYEE",
            'teamId' => $marketingTeam->id,
            'managerId' => $marketingManager->id,
        ]);

        $aditya = Employee::create([
            'id' => Str::uuid()->toString(),
            'employeeCode' => "EMP-2014",
            'fullName' => "Aditya Kulkarni",
            'email' => "aditya.kulkarni@cmrsl.example",
            'department' => "Marketing",
            'designation' => "Growth Analyst",
            'role' => "EMPLOYEE",
            'teamId' => $marketingTeam->id,
            'managerId' => $marketingManager->id,
        ]);

        $tanvi = Employee::create([
            'id' => Str::uuid()->toString(),
            'employeeCode' => "EMP-2015",
            'fullName' => "Tanvi Gupta",
            'email' => "tanvi.gupta@cmrsl.example",
            'department' => "Marketing",
            'designation' => "Campaign Manager",
            'role' => "EMPLOYEE",
            'teamId' => $marketingTeam->id,
            'managerId' => $marketingManager->id,
        ]);

        // Users
        $employees = [
            $ceo, $hr, $techManager, $mediaManager, $marketingManager,
            $rahul, $priya, $sneha, $aisha, $karan, $nidhi, $neha, $saurabh, $kavya,
            $rohan, $ishita, $dev, $pooja, $aditya, $tanvi
        ];

        foreach ($employees as $employee) {
            $this->createUserForEmployee([
                'email' => $employee->email,
                'fullName' => $employee->fullName,
                'role' => $employee->role,
                'employeeId' => $employee->id,
                'teamId' => $employee->teamId,
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
            'name' => "October 2026 Salary Appraisal",
            'appraisalType' => 'SALARY',
            'periodLabel' => "October",
            'startDate' => '2026-10-01 00:00:00',
            'endDate' => '2026-10-31 23:59:59',
            'isActive' => true,
        ]);

        // Seed Appraisals
        $this->createAppraisal([
            'employeeId' => $rahul->id,
            'teamId' => $techTeam->id,
            'cycleId' => $aprilCycle->id,
            'managerId' => $techManager->id,
            'ceoId' => $ceo->id,
            'type' => 'WORK',
            'appraisalPeriod' => "April",
            'status' => 'SUBMITTED',
            'employeeName' => $rahul->fullName,
            'sectionFocus' => "Delivered platform upgrades and stabilized backend APIs.",
            'kraObjectives' => ["Platform reliability", "API performance", "Mentoring"],
        ]);

        $this->createAppraisal([
            'employeeId' => $priya->id,
            'teamId' => $techTeam->id,
            'cycleId' => $aprilCycle->id,
            'managerId' => $techManager->id,
            'ceoId' => $ceo->id,
            'type' => 'WORK',
            'appraisalPeriod' => "April",
            'status' => 'MANAGER_REVIEW',
            'employeeName' => $priya->fullName,
            'sectionFocus' => "Led frontend delivery and improved UX consistency.",
            'kraObjectives' => ["Frontend delivery", "Design quality", "Release support"],
            'managerOverallRating' => "8.10",
            'managerComment' => "Strong execution and consistent collaboration across design and product.",
            'summary' => "Priya delivered strong frontend execution with good collaboration and increasing ownership.",
            'sentimentLabel' => 'POSITIVE',
            'sentimentScore' => "0.84",
            'strengths' => ["Frontend delivery", "Collaboration", "Ownership"],
            'weaknesses' => ["Broaden architecture depth"],
            'risks' => ["No major risk signal identified"],
        ]);

        $this->createAppraisal([
            'employeeId' => $rahul->id,
            'teamId' => $techTeam->id,
            'cycleId' => $octoberCycle->id,
            'managerId' => $techManager->id,
            'ceoId' => $ceo->id,
            'type' => 'SALARY',
            'appraisalPeriod' => "October",
            'status' => 'DRAFT',
            'employeeName' => $rahul->fullName,
            'sectionFocus' => "Preparing compensation case with impact documentation.",
            'kraObjectives' => ["Compensation case", "Quarterly impact summary", "Architecture contribution"],
        ]);

        $this->createAppraisal([
            'employeeId' => $priya->id,
            'teamId' => $techTeam->id,
            'cycleId' => $octoberCycle->id,
            'managerId' => $techManager->id,
            'ceoId' => $ceo->id,
            'type' => 'SALARY',
            'appraisalPeriod' => "October",
            'status' => 'COMPLETED',
            'employeeName' => $priya->fullName,
            'sectionFocus' => "Built clear case for promotion-level delivery and impact.",
            'kraObjectives' => ["Promotion readiness", "Product impact", "Team enablement"],
            'managerOverallRating' => "8.60",
            'finalRating' => "8.80",
            'hikePercentage' => "16.50",
            'managerComment' => "High performer with clear growth trajectory and consistent delivery quality.",
            'ceoComment' => "Approved for strong performance and sustained product impact.",
            'summary' => "Priya consistently delivered high-quality frontend outcomes and earned a strong salary outcome.",
            'sentimentLabel' => 'POSITIVE',
            'sentimentScore' => "0.89",
            'strengths' => ["High-impact execution", "Product quality", "Growth trajectory"],
            'weaknesses' => ["Needs broader org-level influence"],
            'risks' => ["No major risk signal identified"],
        ]);

        $this->createAppraisal([
            'employeeId' => $sneha->id,
            'teamId' => $mediaTeam->id,
            'cycleId' => $aprilCycle->id,
            'managerId' => $mediaManager->id,
            'ceoId' => $ceo->id,
            'type' => 'WORK',
            'appraisalPeriod' => "April",
            'status' => 'COMPLETED',
            'employeeName' => $sneha->fullName,
            'sectionFocus' => "Scaled editorial processes and improved content planning.",
            'kraObjectives' => ["Editorial planning", "Quality consistency", "Cross-team coordination"],
            'managerOverallRating' => "8.20",
            'finalRating' => "8.40",
            'hikePercentage' => "12.00",
            'managerComment' => "Reliable content leader with strong ownership and planning discipline.",
            'ceoComment' => "Completed with a positive final assessment.",
            'summary' => "Sneha brought stability and structure to media planning with reliable execution.",
            'sentimentLabel' => 'POSITIVE',
            'sentimentScore' => "0.83",
            'strengths' => ["Editorial ownership", "Planning discipline", "Reliable execution"],
            'weaknesses' => ["Needs more experimentation"],
            'risks' => ["No major risk signal identified"],
        ]);

        $this->createAppraisal([
            'employeeId' => $aisha->id,
            'teamId' => $mediaTeam->id,
            'cycleId' => $aprilCycle->id,
            'managerId' => $mediaManager->id,
            'ceoId' => $ceo->id,
            'type' => 'WORK',
            'appraisalPeriod' => "April",
            'status' => 'SUBMITTED',
            'employeeName' => $aisha->fullName,
            'sectionFocus' => "Improved reporting quality and insight storytelling.",
            'kraObjectives' => ["Reporting quality", "Insight generation", "Stakeholder communication"],
        ]);

        $this->createAppraisal([
            'employeeId' => $sneha->id,
            'teamId' => $mediaTeam->id,
            'cycleId' => $octoberCycle->id,
            'managerId' => $mediaManager->id,
            'ceoId' => $ceo->id,
            'type' => 'SALARY',
            'appraisalPeriod' => "October",
            'status' => 'MANAGER_REVIEW',
            'employeeName' => $sneha->fullName,
            'sectionFocus' => "Built a strong salary-cycle case around editorial leadership.",
            'kraObjectives' => ["Leadership impact", "Editorial quality", "Process maturity"],
            'managerOverallRating' => "8.10",
            'managerComment' => "Consistent delivery and growing leadership across the media function.",
            'summary' => "Sneha’s salary appraisal shows strong leadership momentum and dependable performance.",
            'sentimentLabel' => 'POSITIVE',
            'sentimentScore' => "0.81",
            'strengths' => ["Leadership growth", "Execution consistency", "Process ownership"],
            'weaknesses' => ["Needs more strategic experimentation"],
            'risks' => ["No major risk signal identified"],
        ]);

        $this->createAppraisal([
            'employeeId' => $aisha->id,
            'teamId' => $mediaTeam->id,
            'cycleId' => $octoberCycle->id,
            'managerId' => $mediaManager->id,
            'ceoId' => $ceo->id,
            'type' => 'SALARY',
            'appraisalPeriod' => "October",
            'status' => 'DRAFT',
            'employeeName' => $aisha->fullName,
            'sectionFocus' => "Drafting a salary-cycle case around reporting quality and insight generation.",
            'kraObjectives' => ["Strengthen compensation case", "Document insight impact", "Show process improvement"],
        ]);

        $this->createAppraisal([
            'employeeId' => $karan->id,
            'teamId' => $marketingTeam->id,
            'cycleId' => $aprilCycle->id,
            'managerId' => $marketingManager->id,
            'ceoId' => $ceo->id,
            'type' => 'WORK',
            'appraisalPeriod' => "April",
            'status' => 'SUBMITTED',
            'employeeName' => $karan->fullName,
            'sectionFocus' => "Improved campaign efficiency and channel insights.",
            'kraObjectives' => ["Campaign performance", "Insight quality", "Experiment velocity"],
        ]);

        $this->createAppraisal([
            'employeeId' => $nidhi->id,
            'teamId' => $marketingTeam->id,
            'cycleId' => $aprilCycle->id,
            'managerId' => $marketingManager->id,
            'ceoId' => $ceo->id,
            'type' => 'WORK',
            'appraisalPeriod' => "April",
            'status' => 'COMPLETED',
            'employeeName' => $nidhi->fullName,
            'sectionFocus' => "Built stronger brand coordination and launch support.",
            'kraObjectives' => ["Brand execution", "Launch support", "Stakeholder alignment"],
            'managerOverallRating' => "7.90",
            'finalRating' => "8.00",
            'hikePercentage' => "10.50",
            'managerComment' => "Strong brand execution with dependable stakeholder coordination.",
            'ceoComment' => "Approved with a solid final rating and measured hike.",
            'summary' => "Nidhi supported brand execution well and earned a steady final outcome.",
            'sentimentLabel' => 'NEUTRAL',
            'sentimentScore' => "0.72",
            'strengths' => ["Brand delivery", "Coordination", "Consistency"],
            'weaknesses' => ["Needs more measurable innovation"],
            'risks' => ["No major risk signal identified"],
        ]);

        $this->createAppraisal([
            'employeeId' => $karan->id,
            'teamId' => $marketingTeam->id,
            'cycleId' => $octoberCycle->id,
            'managerId' => $marketingManager->id,
            'ceoId' => $ceo->id,
            'type' => 'SALARY',
            'appraisalPeriod' => "October",
            'status' => 'MANAGER_REVIEW',
            'employeeName' => $karan->fullName,
            'sectionFocus' => "Positioned salary review around campaign performance and experimentation.",
            'kraObjectives' => ["Performance marketing impact", "Acquisition growth", "Experimentation maturity"],
            'managerOverallRating' => "7.70",
            'managerComment' => "Solid results with room to improve consistency in strategic planning.",
            'summary' => "Karan’s performance is solid, with clear delivery and some development areas to address.",
            'sentimentLabel' => 'MIXED',
            'sentimentScore' => "0.55",
            'strengths' => ["Campaign execution", "Experimentation", "Channel insight"],
            'weaknesses' => ["Planning consistency"],
            'risks' => ["Dependency management risk"],
        ]);

        $this->createAppraisal([
            'employeeId' => $nidhi->id,
            'teamId' => $marketingTeam->id,
            'cycleId' => $octoberCycle->id,
            'managerId' => $marketingManager->id,
            'ceoId' => $ceo->id,
            'type' => 'SALARY',
            'appraisalPeriod' => "October",
            'status' => 'DRAFT',
            'employeeName' => $nidhi->fullName,
            'sectionFocus' => "Preparing salary review around brand launches and stakeholder support.",
            'kraObjectives' => ["Launch readiness", "Brand delivery", "Cross-team coordination"],
        ]);

        $this->createAppraisal([
            'employeeId' => $neha->id,
            'teamId' => $techTeam->id,
            'cycleId' => $aprilCycle->id,
            'managerId' => $techManager->id,
            'ceoId' => $ceo->id,
            'type' => 'WORK',
            'appraisalPeriod' => "April",
            'status' => 'COMPLETED',
            'employeeName' => $neha->fullName,
            'sectionFocus' => "Improved backend stability and reduced production support load.",
            'kraObjectives' => ["Service reliability", "Incident reduction", "API quality"],
            'managerOverallRating' => "8.30",
            'finalRating' => "8.50",
            'hikePercentage' => "13.00",
            'managerComment' => "Strong backend ownership with reliable delivery under pressure.",
            'ceoComment' => "Approved with a strong performance outcome.",
            'summary' => "Neha improved stability and backend quality with dependable execution.",
            'sentimentLabel' => 'POSITIVE',
            'sentimentScore' => "0.86",
            'strengths' => ["Backend ownership", "Reliability", "Execution quality"],
            'weaknesses' => ["Needs broader stakeholder visibility"],
            'risks' => ["No major risk signal identified"],
        ]);

        $this->createAppraisal([
            'employeeId' => $neha->id,
            'teamId' => $techTeam->id,
            'cycleId' => $octoberCycle->id,
            'managerId' => $techManager->id,
            'ceoId' => $ceo->id,
            'type' => 'SALARY',
            'appraisalPeriod' => "October",
            'status' => 'DRAFT',
            'employeeName' => $neha->fullName,
            'sectionFocus' => "Preparing compensation case around reliability ownership and delivery consistency.",
            'kraObjectives' => ["Reliability ownership", "Delivery consistency", "Platform quality"],
        ]);

        $this->createAppraisal([
            'employeeId' => $saurabh->id,
            'teamId' => $techTeam->id,
            'cycleId' => $aprilCycle->id,
            'managerId' => $techManager->id,
            'ceoId' => $ceo->id,
            'type' => 'WORK',
            'appraisalPeriod' => "April",
            'status' => 'SUBMITTED',
            'employeeName' => $saurabh->fullName,
            'sectionFocus' => "Improved infrastructure automation and reduced deployment friction.",
            'kraObjectives' => ["Infrastructure automation", "Deployment reliability", "Monitoring coverage"],
        ]);

        $this->createAppraisal([
            'employeeId' => $saurabh->id,
            'teamId' => $techTeam->id,
            'cycleId' => $octoberCycle->id,
            'managerId' => $techManager->id,
            'ceoId' => $ceo->id,
            'type' => 'SALARY',
            'appraisalPeriod' => "October",
            'status' => 'MANAGER_REVIEW',
            'employeeName' => $saurabh->fullName,
            'sectionFocus' => "Built salary review case around platform automation and operational maturity.",
            'kraObjectives' => ["Operational maturity", "Automation impact", "Reliability support"],
            'managerOverallRating' => "7.90",
            'managerComment' => "Strong operational ownership with clear improvement in automation depth.",
            'summary' => "Saurabh improved operational maturity and automation consistency across the platform.",
            'sentimentLabel' => 'POSITIVE',
            'sentimentScore' => "0.80",
            'strengths' => ["Automation depth", "Operational ownership", "Reliability support"],
            'weaknesses' => ["Needs stronger business storytelling"],
            'risks' => ["No major risk signal identified"],
        ]);

        $this->createAppraisal([
            'employeeId' => $kavya->id,
            'teamId' => $techTeam->id,
            'cycleId' => $aprilCycle->id,
            'managerId' => $techManager->id,
            'ceoId' => $ceo->id,
            'type' => 'WORK',
            'appraisalPeriod' => "April",
            'status' => 'DRAFT',
            'employeeName' => $kavya->fullName,
            'sectionFocus' => "Drafting QA process improvements and release coverage metrics.",
            'kraObjectives' => ["Release quality", "Coverage depth", "Regression control"],
        ]);

        $this->createAppraisal([
            'employeeId' => $kavya->id,
            'teamId' => $techTeam->id,
            'cycleId' => $octoberCycle->id,
            'managerId' => $techManager->id,
            'ceoId' => $ceo->id,
            'type' => 'SALARY',
            'appraisalPeriod' => "October",
            'status' => 'SUBMITTED',
            'employeeName' => $kavya->fullName,
            'sectionFocus' => "Submitted salary review around QA coverage, release confidence, and process rigor.",
            'kraObjectives' => ["QA leadership", "Release confidence", "Process rigor"],
        ]);

        $this->createAppraisal([
            'employeeId' => $rohan->id,
            'teamId' => $mediaTeam->id,
            'cycleId' => $aprilCycle->id,
            'managerId' => $mediaManager->id,
            'ceoId' => $ceo->id,
            'type' => 'WORK',
            'appraisalPeriod' => "April",
            'status' => 'MANAGER_REVIEW',
            'employeeName' => $rohan->fullName,
            'sectionFocus' => "Expanded production throughput and strengthened cross-team delivery quality.",
            'kraObjectives' => ["Production throughput", "Delivery quality", "Stakeholder responsiveness"],
            'managerOverallRating' => "8.00",
            'managerComment' => "Strong delivery pace with improving collaboration quality.",
            'summary' => "Rohan improved throughput and delivery responsiveness with steady creative execution.",
            'sentimentLabel' => 'POSITIVE',
            'sentimentScore' => "0.78",
            'strengths' => ["Delivery pace", "Responsiveness", "Creative execution"],
            'weaknesses' => ["Needs stronger planning rigor"],
            'risks' => ["Minor coordination risk during peak launches"],
        ]);

        $this->createAppraisal([
            'employeeId' => $rohan->id,
            'teamId' => $mediaTeam->id,
            'cycleId' => $octoberCycle->id,
            'managerId' => $mediaManager->id,
            'ceoId' => $ceo->id,
            'type' => 'SALARY',
            'appraisalPeriod' => "October",
            'status' => 'COMPLETED',
            'employeeName' => $rohan->fullName,
            'sectionFocus' => "Built a strong salary review case around production scale and delivery consistency.",
            'kraObjectives' => ["Production impact", "Delivery consistency", "Cross-team support"],
            'managerOverallRating' => "8.10",
            'finalRating' => "8.20",
            'hikePercentage' => "11.50",
            'managerComment' => "Consistent output with improving quality and delivery ownership.",
            'ceoComment' => "Approved with a balanced salary outcome.",
            'summary' => "Rohan delivered consistent production impact and earned a positive salary decision.",
            'sentimentLabel' => 'POSITIVE',
            'sentimentScore' => "0.82",
            'strengths' => ["Production consistency", "Ownership", "Responsiveness"],
            'weaknesses' => ["Needs stronger pre-production planning"],
            'risks' => ["No major risk signal identified"],
        ]);

        $this->createAppraisal([
            'employeeId' => $ishita->id,
            'teamId' => $mediaTeam->id,
            'cycleId' => $aprilCycle->id,
            'managerId' => $mediaManager->id,
            'ceoId' => $ceo->id,
            'type' => 'WORK',
            'appraisalPeriod' => "April",
            'status' => 'DRAFT',
            'employeeName' => $ishita->fullName,
            'sectionFocus' => "Drafting editorial analytics impact and insight quality improvements.",
            'kraObjectives' => ["Insight quality", "Editorial reporting", "Decision support"],
        ]);

        $this->createAppraisal([
            'employeeId' => $ishita->id,
            'teamId' => $mediaTeam->id,
            'cycleId' => $octoberCycle->id,
            'managerId' => $mediaManager->id,
            'ceoId' => $ceo->id,
            'type' => 'SALARY',
            'appraisalPeriod' => "October",
            'status' => 'SUBMITTED',
            'employeeName' => $ishita->fullName,
            'sectionFocus' => "Submitted salary review around reporting accuracy and editorial insight quality.",
            'kraObjectives' => ["Insight accuracy", "Reporting clarity", "Decision support"],
        ]);

        $this->createAppraisal([
            'employeeId' => $dev->id,
            'teamId' => $mediaTeam->id,
            'cycleId' => $aprilCycle->id,
            'managerId' => $mediaManager->id,
            'ceoId' => $ceo->id,
            'type' => 'WORK',
            'appraisalPeriod' => "April",
            'status' => 'SUBMITTED',
            'employeeName' => $dev->fullName,
            'sectionFocus' => "Improved audience growth experiments and distribution analytics.",
            'kraObjectives' => ["Audience growth", "Experiment velocity", "Channel reporting"],
        ]);

        $this->createAppraisal([
            'employeeId' => $dev->id,
            'teamId' => $mediaTeam->id,
            'cycleId' => $octoberCycle->id,
            'managerId' => $mediaManager->id,
            'ceoId' => $ceo->id,
            'type' => 'SALARY',
            'appraisalPeriod' => "October",
            'status' => 'DRAFT',
            'employeeName' => $dev->fullName,
            'sectionFocus' => "Drafting salary review around distribution growth and audience insights.",
            'kraObjectives' => ["Growth case", "Experiment outcomes", "Audience insight"],
        ]);

        $this->createAppraisal([
            'employeeId' => $pooja->id,
            'teamId' => $marketingTeam->id,
            'cycleId' => $aprilCycle->id,
            'managerId' => $marketingManager->id,
            'ceoId' => $ceo->id,
            'type' => 'WORK',
            'appraisalPeriod' => "April",
            'status' => 'COMPLETED',
            'employeeName' => $pooja->fullName,
            'sectionFocus' => "Improved organic acquisition and search visibility for priority campaigns.",
            'kraObjectives' => ["Organic growth", "Search visibility", "Content optimization"],
            'managerOverallRating' => "8.00",
            'finalRating' => "8.10",
            'hikePercentage' => "11.00",
            'managerComment' => "Strong SEO discipline with reliable execution across launch windows.",
            'ceoComment' => "Approved with a positive final outcome.",
            'summary' => "Pooja improved search performance and delivered measurable acquisition gains.",
            'sentimentLabel' => 'POSITIVE',
            'sentimentScore' => "0.80",
            'strengths' => ["SEO execution", "Acquisition impact", "Consistency"],
            'weaknesses' => ["Needs broader channel experimentation"],
            'risks' => ["No major risk signal identified"],
        ]);

        $this->createAppraisal([
            'employeeId' => $pooja->id,
            'teamId' => $marketingTeam->id,
            'cycleId' => $octoberCycle->id,
            'managerId' => $marketingManager->id,
            'ceoId' => $ceo->id,
            'type' => 'SALARY',
            'appraisalPeriod' => "October",
            'status' => 'MANAGER_REVIEW',
            'employeeName' => $pooja->fullName,
            'sectionFocus' => "Built salary review case around sustained search performance and acquisition efficiency.",
            'kraObjectives' => ["Search efficiency", "Acquisition impact", "Optimization discipline"],
            'managerOverallRating' => "8.00",
            'managerComment' => "Strong search performance with disciplined execution and clear impact.",
            'summary' => "Pooja has strong optimization discipline and sustained organic growth impact.",
            'sentimentLabel' => 'POSITIVE',
            'sentimentScore' => "0.79",
            'strengths' => ["Optimization discipline", "Organic growth", "Reliable execution"],
            'weaknesses' => ["Needs stronger cross-channel influence"],
            'risks' => ["No major risk signal identified"],
        ]);

        $this->createAppraisal([
            'employeeId' => $aditya->id,
            'teamId' => $marketingTeam->id,
            'cycleId' => $aprilCycle->id,
            'managerId' => $marketingManager->id,
            'ceoId' => $ceo->id,
            'type' => 'WORK',
            'appraisalPeriod' => "April",
            'status' => 'SUBMITTED',
            'employeeName' => $aditya->fullName,
            'sectionFocus' => "Improved campaign measurement and growth analytics for acquisition programs.",
            'kraObjectives' => ["Measurement quality", "Growth analytics", "Decision support"],
        ]);

        $this->createAppraisal([
            'employeeId' => $aditya->id,
            'teamId' => $marketingTeam->id,
            'cycleId' => $octoberCycle->id,
            'managerId' => $marketingManager->id,
            'ceoId' => $ceo->id,
            'type' => 'SALARY',
            'appraisalPeriod' => "October",
            'status' => 'DRAFT',
            'employeeName' => $aditya->fullName,
            'sectionFocus' => "Drafting salary review around analytics depth and growth reporting improvements.",
            'kraObjectives' => ["Analytics depth", "Reporting quality", "Growth insight"],
        ]);

        $this->createAppraisal([
            'employeeId' => $tanvi->id,
            'teamId' => $marketingTeam->id,
            'cycleId' => $aprilCycle->id,
            'managerId' => $marketingManager->id,
            'ceoId' => $ceo->id,
            'type' => 'WORK',
            'appraisalPeriod' => "April",
            'status' => 'MANAGER_REVIEW',
            'employeeName' => $tanvi->fullName,
            'sectionFocus' => "Improved campaign orchestration and launch coordination across functions.",
            'kraObjectives' => ["Campaign orchestration", "Launch readiness", "Cross-team coordination"],
            'managerOverallRating' => "7.80",
            'managerComment' => "Solid campaign coordination with good communication and execution stability.",
            'summary' => "Tanvi coordinated campaign launches well and improved execution discipline.",
            'sentimentLabel' => 'NEUTRAL',
            'sentimentScore' => "0.69",
            'strengths' => ["Launch coordination", "Communication", "Execution stability"],
            'weaknesses' => ["Needs sharper prioritization"],
            'risks' => ["Moderate launch dependency risk"],
        ]);

        $this->createAppraisal([
            'employeeId' => $tanvi->id,
            'teamId' => $marketingTeam->id,
            'cycleId' => $octoberCycle->id,
            'managerId' => $marketingManager->id,
            'ceoId' => $ceo->id,
            'type' => 'SALARY',
            'appraisalPeriod' => "October",
            'status' => 'SUBMITTED',
            'employeeName' => $tanvi->fullName,
            'sectionFocus' => "Submitted salary review around campaign coordination and delivery governance.",
            'kraObjectives' => ["Delivery governance", "Launch coordination", "Stakeholder confidence"],
        ]);

        // Global System Settings
        SystemSettings::create([
            'id' => 'GLOBAL',
            'globalDeadlineStart' => '2026-04-01 00:00:00',
            'globalDeadlineEnd' => '2026-04-30 23:59:59',
        ]);
    }
}
