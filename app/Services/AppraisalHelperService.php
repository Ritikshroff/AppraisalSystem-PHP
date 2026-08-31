<?php

namespace App\Services;

class AppraisalHelperService
{
    public const SECTION_ONE_QUESTIONS = [
        "Has the past year been good/bad/satisfactory or otherwise for you, and why? (Overall Year Assessment)",
        "What do you consider to be your most important achievements of the past year? (Important Achievements)",
        "What elements of your job do you find most difficult? (Difficult Elements)",
        "What elements of your job interest you the most? (Most Interesting Aspects)",
        "What elements of your job interest you the least? (Least Interesting Aspects)",
        "What action could be taken by you to improve your performance in your current position? (Action by Employee)",
        "What action/support is expected from your Manager/Boss to improve your performance?",
        "What sort of training/experiences would benefit you in the next year? Not just job-skills - also your natural strengths and personal passions you'd like to develop - you and your work can benefit from these. (Training / Experiences Required)",
        "Mention if you have any grievances/problem/are of dissatisfaction which affect your performance. (Grievances / Dissatisfaction)",
    ];

    public const DEFAULT_SKILLS = [
        "Technical Knowledge",
        "Communication",
        "Problem Solving",
        "Ownership",
        "Stakeholder Management",
    ];

    /**
     * The 11 capability/competency areas for Section 4.
     * Both Employee (Appraisee) and Appraiser score each on a 1–10 scale.
     * Rating guide: 1–3 = Poor | 4–6 = Satisfactory | 7–9 = Good | 10 = Excellent
     */
    public const DEFAULT_COMPETENCIES = [
        'Product / Technical Knowledge',
        'Time Management',
        'Work Planning',
        'Reporting and Administration',
        'Communication Skills',
        'Delegation Skills',
        'Meeting Deadlines / Commitments',
        'Creativity',
        'Problem-Solving and Decision-Making',
        'Steadiness Under Pressure',
        'Leadership and Integrity',
    ];

    public static function roundTo(float $value, int $digits = 2): float
    {
        return round($value, $digits);
    }

    public static function clampScale(float $value, float $min, float $max): float
    {
        return min($max, max($min, $value));
    }

    public static function normalizeSentimentScore(float $value): float
    {
        return self::roundTo(self::clampScale($value, 0.0, 1.0));
    }

    public static function getSentimentLabel(float $score): string
    {
        if ($score >= 0.78) {
            return 'POSITIVE';
        }

        if ($score >= 0.58) {
            return 'NEUTRAL';
        }

        if ($score >= 0.36) {
            return 'MIXED';
        }

        return 'CONCERNING';
    }

    public static function getStatusBadgeLabel(string $status): string
    {
        switch ($status) {
            case 'DRAFT':
                return "Draft";
            case 'SUBMITTED':
                return "Pending";
            case 'MANAGER_REVIEW':
                return "Reviewed";
            case 'COMPLETED':
                return "Completed";
            default:
                return $status;
        }
    }

    public static function getCurrentStageLabel(string $status): string
    {
        switch ($status) {
            case 'DRAFT':
                return "Employee Draft";
            case 'SUBMITTED':
                return "Awaiting Manager Review";
            case 'MANAGER_REVIEW':
                return "Awaiting BU Head Decision";
            case 'COMPLETED':
                return "Final Decision Completed";
            default:
                return $status;
        }
    }

    public static function getSubmitLabelForRole(string $role): ?string
    {
        switch (strtoupper($role)) {
            case 'EMPLOYEE':
                return "Submit Appraisal";
            case 'MANAGER':
                return "Submit Review";
            case 'BU_HEAD':
                return "Finalize Appraisal";
            default:
                return null;
        }
    }

    public static function getDefaultViewForRole(string $role): string
    {
        switch (strtoupper($role)) {
            case 'EMPLOYEE':
                return "my-appraisal";
            case 'MANAGER':
                return "team-reviews";
            case 'BU_HEAD':
                return "bu-head-panel";
            case 'HR':
                return "hr-panel";
            default:
                return "dashboard";
        }
    }

    public static function normalizeSectionAnswers(?array $input): array
    {
        $normalized = [];
        foreach (self::SECTION_ONE_QUESTIONS as $index => $question) {
            $answerText = "";
            if (is_array($input) && isset($input[$index])) {
                $answerText = isset($input[$index]['answer']) ? trim($input[$index]['answer']) : "";
            }
            $normalized[] = [
                'question' => $question,
                'answer' => $answerText
            ];
        }
        return $normalized;
    }

    /**
     * Build the default list of competency rows, merging existing DB values.
     * Ensures all 11 competencies always appear in the correct order.
     */
    public static function defaultCompetencyRows(?array $existing): array
    {
        $mapped = [];
        foreach (self::DEFAULT_COMPETENCIES as $index => $name) {
            $existingItem = null;
            if (is_array($existing)) {
                foreach ($existing as $item) {
                    if (isset($item['competencyName']) && $item['competencyName'] === $name) {
                        $existingItem = $item;
                        break;
                    }
                }
            }

            $mapped[] = [
                'id'             => $existingItem['id'] ?? null,
                'competencyName' => $name,
                'employeeScore'  => $existingItem['employeeScore'] ?? null,
                'appraiserScore' => $existingItem['appraiserScore'] ?? null,
                'displayOrder'   => $index,
            ];
        }

        return $mapped;
    }

    public static function defaultSkillRows(?array $existing): array
    {
        $mappedDefaults = [];
        foreach (self::DEFAULT_SKILLS as $index => $skillName) {
            $existingItem = null;
            if (is_array($existing)) {
                foreach ($existing as $item) {
                    if (isset($item['skillName']) && $item['skillName'] === $skillName) {
                        $existingItem = $item;
                        break;
                    }
                }
            }

            $mappedDefaults[] = [
                'id' => $existingItem['id'] ?? null,
                'skillName' => $skillName,
                'employeeRating' => $existingItem['employeeRating'] ?? null,
                'managerRating' => $existingItem['managerRating'] ?? null,
                'displayOrder' => $index,
            ];
        }

        $customRows = [];
        if (is_array($existing)) {
            $customIndex = 0;
            foreach ($existing as $item) {
                if (isset($item['skillName']) && !in_array($item['skillName'], self::DEFAULT_SKILLS)) {
                    $item['displayOrder'] = count($mappedDefaults) + $customIndex;
                    $customRows[] = $item;
                    $customIndex++;
                }
            }
        }

        return array_merge($mappedDefaults, $customRows);
    }

    public static function gradeToNumeric(?string $grade): ?float
    {
        if ($grade === null || $grade === '') {
            return null;
        }
        $upper = strtoupper(trim($grade));
        return match ($upper) {
            'A+' => 10.0,
            'A' => 8.5,
            'B+' => 7.5,
            'B' => 6.5,
            'C' => 5.0,
            'D' => 3.0,
            default => is_numeric($grade) ? floatval($grade) : null
        };
    }

    public static function numericToGrade(?float $val): string
    {
        if ($val === null) return '';
        if ($val >= 9.5) return 'A+';
        if ($val >= 8.0) return 'A';
        if ($val >= 7.0) return 'B+';
        if ($val >= 6.0) return 'B';
        if ($val >= 4.5) return 'C';
        return 'D';
    }

    public static function ensureKraRows(?array $rows): array
    {
        if (is_array($rows) && count($rows) > 0) {
            $ensured = [];
            foreach ($rows as $index => $row) {
                $appraiseeRating = null;
                if (isset($row['appraiseeRating']) && $row['appraiseeRating'] !== null && $row['appraiseeRating'] !== '') {
                    $appraiseeRating = self::gradeToNumeric($row['appraiseeRating']);
                }

                $appraiserRating = null;
                if (isset($row['appraiserRating']) && $row['appraiserRating'] !== null && $row['appraiserRating'] !== '') {
                    $appraiserRating = self::gradeToNumeric($row['appraiserRating']);
                }

                $ensured[] = [
                    'id' => $row['id'] ?? null,
                    'objective' => isset($row['objective']) ? trim($row['objective']) : "",
                    'weightage' => isset($row['weightage']) ? self::roundTo(self::clampScale(floatval($row['weightage']), 0.0, 100.0)) : 0.0,
                    'appraiseeRating' => $appraiseeRating,
                    'appraiserRating' => $appraiserRating,
                    'appraiseeComment' => isset($row['appraiseeComment']) ? trim($row['appraiseeComment']) : "",
                    'comments' => isset($row['comments']) ? trim($row['comments']) : "",
                    'displayOrder' => $index,
                ];
            }
            return $ensured;
        }

        return [
            [
                'objective' => "",
                'weightage' => 0.0,
                'appraiseeRating' => null,
                'appraiserRating' => null,
                'appraiseeComment' => "",
                'comments' => "",
                'displayOrder' => 0,
            ]
        ];
    }

    public static function calculateAverage(?array $values): ?float
    {
        if (!is_array($values)) {
            return null;
        }

        $filtered = array_filter($values, function ($value) {
            return $value !== null && is_numeric($value);
        });

        if (count($filtered) === 0) {
            return null;
        }

        return self::roundTo(array_sum($filtered) / count($filtered));
    }

    private static function buildStrengths(string $text, ?float $rating): array
    {
        $strengths = [];

        if (preg_match('/(ownership|owned|led|lead|leadership)/i', $text)) {
            $strengths[] = "Ownership and leadership";
        }
        if (preg_match('/(collaborat|stakeholder|cross-functional)/i', $text)) {
            $strengths[] = "Cross-functional collaboration";
        }
        if (preg_match('/(delivery|execute|execution|reliability|quality)/i', $text)) {
            $strengths[] = "Reliable execution";
        }
        if ($rating !== null && $rating >= 8) {
            $strengths[] = "Strong overall performance";
        }

        return count($strengths) > 0 ? array_slice($strengths, 0, 3) : ["Consistent contribution"];
    }

    private static function buildWeaknesses(string $text, ?float $rating): array
    {
        $weaknesses = [];

        if (preg_match('/(support|need help|guidance|improve)/i', $text)) {
            $weaknesses[] = "Needs targeted development support";
        }
        if (preg_match('/(delay|blocker|risk|slow)/i', $text)) {
            $weaknesses[] = "Execution speed or dependency risks";
        }
        if ($rating !== null && $rating < 7) {
            $weaknesses[] = "Performance consistency needs improvement";
        }

        return count($weaknesses) > 0 ? array_slice($weaknesses, 0, 3) : ["No material weaknesses highlighted"];
    }

    private static function buildRiskSignals(string $text, ?float $rating): array
    {
        $risks = [];

        if (preg_match('/(burnout|overloaded|bandwidth)/i', $text)) {
            $risks[] = "Bandwidth risk";
        }
        if (preg_match('/(dependency|handoff|coordination)/i', $text)) {
            $risks[] = "Dependency management risk";
        }
        if ($rating !== null && $rating < 6.5) {
            $risks[] = "Low rating warrants leadership follow-up";
        }

        return count($risks) > 0 ? array_slice($risks, 0, 3) : ["No major risk signal identified"];
    }

    public static function buildFallbackAnalysis(array $input): array
    {
        $sentimentScore = 0.58;

        if ($input['finalRating'] !== null) {
            $sentimentScore += ($input['finalRating'] - 7) * 0.08;
        } elseif ($input['managerOverallRating'] !== null) {
            $sentimentScore += ($input['managerOverallRating'] - 7) * 0.07;
        }

        if (preg_match('/(excellent|strong|high-impact|outstanding|reliable)/i', $input['fullText'])) {
            $sentimentScore += 0.12;
        }

        if (preg_match('/(concern|delay|risk|inconsistent|support needed)/i', $input['fullText'])) {
            $sentimentScore -= 0.14;
        }

        $normalizedScore = self::normalizeSentimentScore($sentimentScore);
        $sentimentLabel = self::getSentimentLabel($normalizedScore);
        $rating = $input['finalRating'] ?? $input['managerOverallRating'];
        
        $strengths = self::buildStrengths($input['fullText'], $rating);
        $weaknesses = self::buildWeaknesses($input['fullText'], $rating);
        $riskSignals = self::buildRiskSignals($input['fullText'], $rating);

        return [
            'performanceSummary' => "{$input['employeeName']} completed the {$input['appraisalPeriod']} " . strtolower($input['appraisalType']) . " appraisal as {$input['designation']} in {$input['teamName']}. The narrative shows a balanced view of delivery, ownership, and development needs across the review chain.",
            'sentimentLabel' => $sentimentLabel,
            'sentimentScore' => $normalizedScore,
            'strengths' => $strengths,
            'weaknesses' => $weaknesses,
            'riskSignals' => $riskSignals,
            'source' => "fallback",
        ];
    }
}
