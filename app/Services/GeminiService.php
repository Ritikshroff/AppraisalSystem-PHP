<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
// test

class GeminiService
{
    private const DEFAULT_MODEL = 'gemini-2.0-flash';

    public static function generatePerformanceAnalysis(array $input): array
    {
        $fallback = AppraisalHelperService::buildFallbackAnalysis($input);

        $apiKey = env('GOOGLE_API_KEY');
        $model = env('GEMINI_MODEL', self::DEFAULT_MODEL);

        if (empty($apiKey)) {
            Log::warning("GOOGLE_API_KEY not configured. Falling back to deterministic performance analysis.");
            return $fallback;
        }

        $systemInstructions = "You are a high-level Enterprise HR Analyst specializing in Objective Performance Calibration.
Your mission is to generate a Performance DNA summary that is entirely UNBIASED and uses the exact same criteria for everyone.

CRITICAL RUBRIC:
1. FOCUS ONLY on EVIDENCE: Base your analysis only on the achievements, metrics, and behaviors explicitly written in the provided text.
2. DISREGARD UNRELATED FACTORS: Ignore tenure, personal context, or characteristics like gender/ethnicity.
3. BEHAVIORAL CONSISTENCY: Map adjectives (like 'strong', 'consistent', 'exceptional') to documented outcomes.
4. CALIBRATE: Compare the Manager's rating with the textual evidence. Highlight if the rating is higher or lower than what the evidence suggests.
5. JSON OUTPUT: You must respond ONLY with a JSON object following the schema provided.

RATING SCALE (Sentiment Score):
0.0 - 0.35: CONCERNING (Major performance gaps documented)
0.36 - 0.57: MIXED (Some goals met, but with significant blockers or missed targets)
0.58 - 0.77: NEUTRAL (Met all standard expectations, reliable delivery)
0.78 - 0.89: POSITIVE (Exceeded expectations in scope or impact)
0.90 - 1.00: EXCEPTIONAL (Consistently outperformed and drove cross-team value)";

        $prompt = "Analyze this performance appraisal data and output JSON.
Data:
Employee: {$input['employeeName']}
Role: {$input['designation']} in {$input['teamName']}
Appraisal Type: {$input['appraisalType']} ({$input['appraisalPeriod']})
Manager Rating: " . ($input['managerOverallRating'] ?? 'N/A') . "
Narrative Evidence: {$input['fullText']}

Return strict JSON with fields: performanceSummary, sentimentLabel, sentimentScore, strengths, weaknesses, riskSignals.";

        try {
            $url = "https://generativelanguage.googleapis.com/v1beta/models/{$model}:generateContent?key={$apiKey}";

            $response = Http::timeout(10)->post($url, [
                'contents' => [
                    [
                        'parts' => [
                            ['text' => $prompt]
                        ]
                    ]
                ],
                'systemInstruction' => [
                    'parts' => [
                        ['text' => $systemInstructions]
                    ]
                ],
                'generationConfig' => [
                    'responseMimeType' => 'application/json'
                ]
            ]);

            if ($response->failed()) {
                throw new \Exception("Gemini API call failed with status: " . $response->status() . " Body: " . $response->body());
            }

            $result = $response->json();
            $candidates = $result['candidates'] ?? [];
            if (empty($candidates)) {
                throw new \Exception("No candidates returned from Gemini model.");
            }

            $textOutput = $candidates[0]['content']['parts'][0]['text'] ?? null;
            if (empty($textOutput)) {
                throw new \Exception("No text parts found in candidate content.");
            }

            $parsed = json_decode($textOutput, true);
            if (!is_array($parsed)) {
                throw new \Exception("Failed to decode JSON output from Gemini: " . $textOutput);
            }

            return self::normalizeModelOutput($parsed, $fallback);

        } catch (\Exception $e) {
            Log::error("Gemini enterprise appraisal analysis failed. Falling back to deterministic analysis.", [
                'exception' => $e->getMessage(),
                'input' => $input
            ]);
            return $fallback;
        }
    }

    private static function normalizeModelOutput(array $input, array $fallback): array
    {
        $sentimentScore = AppraisalHelperService::normalizeSentimentScore(
            isset($input['sentimentScore']) && is_numeric($input['sentimentScore'])
            ? floatval($input['sentimentScore'])
            : $fallback['sentimentScore']
        );

        $strengths = isset($input['strengths']) && is_array($input['strengths']) && count($input['strengths'])
            ? array_slice($input['strengths'], 0, 4)
            : $fallback['strengths'];

        $weaknesses = isset($input['weaknesses']) && is_array($input['weaknesses']) && count($input['weaknesses'])
            ? array_slice($input['weaknesses'], 0, 4)
            : $fallback['weaknesses'];

        $riskSignals = isset($input['riskSignals']) && is_array($input['riskSignals']) && count($input['riskSignals'])
            ? array_slice($input['riskSignals'], 0, 4)
            : $fallback['riskSignals'];

        return [
            'performanceSummary' => isset($input['performanceSummary']) ? trim($input['performanceSummary']) : $fallback['performanceSummary'],
            'sentimentLabel' => $input['sentimentLabel'] ?? AppraisalHelperService::getSentimentLabel($sentimentScore),
            'sentimentScore' => $sentimentScore,
            'strengths' => $strengths,
            'weaknesses' => $weaknesses,
            'riskSignals' => $riskSignals,
            'source' => 'gemini',
        ];
    }
}
