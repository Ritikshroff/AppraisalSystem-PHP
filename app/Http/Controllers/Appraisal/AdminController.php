<?php

namespace App\Http\Controllers\Appraisal;

use App\Http\Controllers\Controller;
use App\Services\AppraisalService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AdminController extends Controller
{
    public function updateCycleWindow(Request $request, string $cycleId)
    {
        $user = Auth::user();
        $validated = $request->validate([
            'startDate' => ['required', 'date'],
            'endDate' => ['required', 'date'],
        ]);

        try {
            $userSummary = [
                'id' => $user->id,
                'role' => $user->role,
            ];
            AppraisalService::updateCycleWindow($cycleId, $validated['startDate'], $validated['endDate'], $userSummary);
            return back()->with('success', 'Appraisal cycle dates updated successfully.');
        } catch (\Exception $e) {
            return back()->withErrors(['admin_error' => $e->getMessage()]);
        }
    }

    public function assignEmployeeToCycle(Request $request)
    {
        $user = Auth::user();
        $validated = $request->validate([
            'employeeId' => ['required', 'string'],
            'cycleId' => ['required', 'string'],
        ]);

        try {
            $userSummary = [
                'id' => $user->id,
                'role' => $user->role,
            ];
            AppraisalService::assignEmployeeToCycle($validated['employeeId'], $validated['cycleId'], $userSummary);
            return back()->with('success', 'Employee successfully enrolled in cycle.');
        } catch (\Exception $e) {
            return back()->withErrors(['admin_error' => $e->getMessage()]);
        }
    }

    public function enrollAllEmployees(Request $request, string $cycleId)
    {
        $user = Auth::user();
        try {
            $userSummary = [
                'id' => $user->id,
                'role' => $user->role,
            ];
            $result = AppraisalService::enrollAllEmployees($cycleId, $userSummary);
            return back()->with('success', "Enrolled {$result['count']} employees in the cycle successfully.");
        } catch (\Exception $e) {
            return back()->withErrors(['admin_error' => $e->getMessage()]);
        }
    }

    public function updateEmployee(Request $request, string $id)
    {
        $employee = \App\Models\Employee::findOrFail($id);
        
        $validated = $request->validate([
            'fullName' => ['required', 'string', 'max:255'],
            'employeeCode' => ['required', 'string', 'max:50'],
            'email' => ['required', 'email', 'max:255'],
            'department' => ['required', 'string', 'max:255'],
            'designation' => ['required', 'string', 'max:255'],
            'grade' => ['nullable', 'string', 'max:50'],
            'doj' => ['nullable', 'date'],
            'dob' => ['nullable', 'date'],
            'lastPromotionDate' => ['nullable', 'date'],
            'companyExperienceYears' => ['nullable', 'numeric'],
            'totalExperienceYears' => ['nullable', 'numeric'],
            'salary' => ['nullable', 'numeric'],
            'managerId' => ['nullable', 'string'],
        ]);

        $employee->update($validated);

        // Update corresponding User record if email or name changes
        $user = \App\Models\User::where('employeeId', $employee->id)->first();
        if ($user) {
            $user->update([
                'name' => $validated['fullName'],
                'email' => $validated['email'],
            ]);
        }

        return back()->with('success', 'Employee profile updated successfully.');
    }
}
