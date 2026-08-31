<?php

namespace App\Http\Controllers\Appraisal;

use App\Http\Controllers\Controller;
use App\Services\AppraisalService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AppraisalController extends Controller
{
    public function show(string $id)
    {
        $user = Auth::user();
        $appraisal = AppraisalService::getAppraisalDetail($user->id, $id);

        if (!$appraisal) {
            abort(404, 'Appraisal not found or access denied.');
        }

        return view('appraisal.show', compact('appraisal'));
    }

    public function save(Request $request, string $id)
    {
        return $this->mutate($request, $id, 'save');
    }

    public function submit(Request $request, string $id)
    {
        return $this->mutate($request, $id, 'submit');
    }

    private function mutate(Request $request, string $id, string $mode)
    {
        $user = Auth::user();

        // Convert flat request parameters to AppraisalMutationPayload structure
        $payload = [
            'appraisalId' => $id,
        ];

        foreach ([
            'sectionOneAnswers',
            'kras',
            'skillRatings',
            'competencyRatings',
            'managerReview',
            'appraiserSection',
            'reviewerSection',
            'buHeadReview',
        ] as $key) {
            if ($request->has($key)) {
                $payload[$key] = $request->input($key);
            }
        }

        foreach ([
            'promotionRecommended',
            'adjustments',
            'incrementAmount',
            'newCtc',
            'grade',
            'justification',
            'specialAppealStatus',
            'specialAppealComments',
            'specialAppeal',
        ] as $key) {
            if ($request->has($key)) {
                $payload[$key] = $request->input($key);
            }
        }

        try {
            AppraisalService::mutateAppraisal($user->id, $payload, $mode);
            
            $message = $mode === 'submit' 
                ? 'Appraisal stage submitted successfully!' 
                : 'Appraisal changes saved successfully.';

            return redirect()->route('appraisals.show', $id)->with('success', $message);
        } catch (\Exception $e) {
            return back()->withErrors(['error' => $e->getMessage()])->withInput();
        }
    }
}
