<?php

namespace App\Http\Controllers;

use App\Models\Employee;
use App\Models\Team;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class AuthController extends Controller
{
    public function showLogin()
    {
        if (Auth::check()) {
            return redirect('/');
        }
        return view('auth.login');
    }

    public function login(Request $request)
    {
        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required'],
        ]);

        $email = strtolower(trim($credentials['email']));
        $password = $credentials['password'];

        // Find user by email
        $user = User::where('email', $email)->first();

        if ($user && Hash::check($password, $user->passwordHash)) {
            Auth::login($user);
            $request->session()->regenerate();
            return redirect()->intended('/');
        }

        return back()->withErrors([
            'email' => 'Invalid email or password.',
        ])->onlyInput('email');
    }

    public function showSignup()
    {
        if (Auth::check()) {
            return redirect('/');
        }

        $teams = Team::orderBy('name', 'asc')->get(['id', 'name']);
        return view('auth.signup', compact('teams'));
    }

    public function signup(Request $request)
    {
        $validated = $request->validate([
            'fullName' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'string', 'min:8'],
            'teamId' => ['nullable', 'string'],
            'designation' => ['required', 'string', 'max:255'],
            'role' => ['required', 'string', 'in:EMPLOYEE,MANAGER,CEO'],
        ]);

        $role = strtoupper($validated['role']);
        $email = strtolower(trim($validated['email']));

        if ($role !== 'CEO' && empty($validated['teamId'])) {
            return back()->withErrors(['teamId' => 'A team is required for employee and manager accounts.'])->withInput();
        }

        // Check if CEO already exists
        if ($role === 'CEO') {
            $existingCeo = User::where('role', 'CEO')->first();
            if ($existingCeo) {
                return back()->withErrors(['role' => 'A CEO account already exists. Use the existing CEO login.'])->withInput();
            }
        }

        // Check selected team
        $team = null;
        if ($role !== 'CEO') {
            $team = Team::find($validated['teamId']);
            if (!$team) {
                return back()->withErrors(['teamId' => 'Selected team was not found.'])->withInput();
            }
        }

        // Check team manager
        if ($role === 'MANAGER' && $team && $team->managerId) {
            return back()->withErrors(['role' => 'This team already has a manager account.'])->withInput();
        }

        // Check if team has manager for employee
        if ($role === 'EMPLOYEE' && $team && !$team->managerId) {
            return back()->withErrors(['role' => 'Selected team does not have a manager yet. Create the manager account first.'])->withInput();
        }

        try {
            DB::transaction(function () use ($validated, $role, $email, $team) {
                $roleCount = Employee::where('role', $role)->count();
                
                $employeeCode = match ($role) {
                    'CEO' => 'CEO-' . str_pad($roleCount + 1, 4, '0', STR_PAD_LEFT),
                    'MANAGER' => 'MGR-' . str_pad($roleCount + 1001, 4, '0', STR_PAD_LEFT),
                    default => 'EMP-' . str_pad($roleCount + 2001, 4, '0', STR_PAD_LEFT),
                };

                $employee = Employee::create([
                    'id' => Str::uuid()->toString(),
                    'employeeCode' => $employeeCode,
                    'fullName' => trim($validated['fullName']),
                    'email' => $email,
                    'department' => ($role === 'CEO') ? 'Executive' : ($team->name ?? 'General'),
                    'designation' => trim($validated['designation']),
                    'role' => $role,
                    'teamId' => ($role === 'CEO') ? null : ($team->id ?? null),
                    'managerId' => ($role === 'EMPLOYEE' && $team) ? $team->managerId : null,
                ]);

                $user = User::create([
                    'id' => Str::uuid()->toString(),
                    'email' => $email,
                    'passwordHash' => Hash::make($validated['password']),
                    'name' => trim($validated['fullName']),
                    'role' => $role,
                    'teamId' => ($role === 'CEO') ? null : ($team->id ?? null),
                    'employeeId' => $employee->id,
                ]);

                if ($role === 'MANAGER' && $team) {
                    $team->update(['managerId' => $employee->id]);
                }
            });

            // Attempt login after signup
            $user = User::where('email', $email)->first();
            if ($user) {
                Auth::login($user);
                $request->session()->regenerate();
                return redirect('/');
            }

            return redirect('/login')->with('success', 'Account created successfully. Please log in.');

        } catch (\Exception $e) {
            return back()->withErrors(['email' => 'The authentication service encountered an error: ' . $e->getMessage()])->withInput();
        }
    }

    public function logout(Request $request)
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();
        return redirect('/login');
    }
}
