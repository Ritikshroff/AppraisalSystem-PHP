<?php

namespace App\Http\Controllers;

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

        return view('appraisals.show', compact('appraisal'));
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

        if ($request->has('sectionOneAnswers')) {
            $payload['sectionOneAnswers'] = $request->input('sectionOneAnswers');
        }

        if ($request->has('kras')) {
            $payload['kras'] = $request->input('kras');
        }

        if ($request->has('skillRatings')) {
            $payload['skillRatings'] = $request->input('skillRatings');
        }

        if ($request->has('managerReview')) {
            $payload['managerReview'] = $request->input('managerReview');
        }

        if ($request->has('ceoReview')) {
            $payload['ceoReview'] = $request->input('ceoReview');
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
