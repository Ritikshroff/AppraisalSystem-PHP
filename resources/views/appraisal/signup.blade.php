@extends('layouts.app')

@section('title', 'Signup - AppraisalFlow')

@section('content')
<div class="mx-auto flex min-h-[80vh] w-full max-w-6xl items-center gap-10 py-10 px-4">
    <div class="hidden flex-1 lg:block">
        <p class="text-xs font-semibold uppercase tracking-[0.28em] text-blue-500">Role Setup</p>
        <h1 class="mt-4 max-w-xl text-4xl font-bold tracking-tight text-black leading-tight">
            Create a secure role-based account.
        </h1>
        <p class="mt-5 max-w-xl text-sm leading-7 text-gray-600">
            Roles dictate your workspace capabilities. Team-linked roles (Employees and Managers) stay scoped to their teams, while the BU Head runs organization-wide calibrations.
        </p>
    </div>

    <div class="flex-1 flex justify-center" x-data="{ role: 'EMPLOYEE' }">
        <div class="bg-white border border-gray-200 w-full max-w-md py-8 px-6 sm:px-10">
            <h2 class="text-2xl font-bold text-black mb-6">Create Account</h2>

            <form class="space-y-4" action="{{ route('signup') }}" method="POST">
                @csrf

                <div>
                    <label for="role" class="block text-sm font-medium text-black">Select Role</label>
                    <x-select 
                        name="role" 
                        model="role" 
                        :value="old('role', 'EMPLOYEE')" 
                        :options="[
                            ['value' => 'EMPLOYEE', 'label' => 'Employee'],
                            ['value' => 'MANAGER', 'label' => 'Manager'],
                            ['value' => 'BU_HEAD', 'label' => 'BU Head']
                        ]" 
                        class="mt-1"
                    />
                </div>

                <div>
                    <label for="fullName" class="block text-sm font-medium text-black">Full Name</label>
                    <input id="fullName" name="fullName" type="text" required value="{{ old('fullName') }}"
                        class="mt-1 block w-full border border-gray-300 py-2.5 px-3 text-black focus:outline-none focus:border-blue-500 sm:text-sm">
                </div>

                <div>
                    <label for="email" class="block text-sm font-medium text-black">Email address</label>
                    <input id="email" name="email" type="email" required value="{{ old('email') }}"
                        class="mt-1 block w-full border border-gray-300 py-2.5 px-3 text-black focus:outline-none focus:border-blue-500 sm:text-sm">
                    @error('email')
                        <p class="mt-2 text-xs text-red-600 font-medium">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label for="password" class="block text-sm font-medium text-black">Password</label>
                    <input id="password" name="password" type="password" required
                        class="mt-1 block w-full border border-gray-300 py-2.5 px-3 text-black focus:outline-none focus:border-blue-500 sm:text-sm">
                    @error('password')
                        <p class="mt-2 text-xs text-red-600 font-medium">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label for="designation" class="block text-sm font-medium text-black">Designation</label>
                    <input id="designation" name="designation" type="text" required value="{{ old('designation') }}"
                        placeholder="e.g. Senior Software Engineer"
                        class="mt-1 block w-full border border-gray-300 py-2.5 px-3 text-black focus:outline-none focus:border-blue-500 sm:text-sm">
                </div>

                <div x-show="role !== 'BU_HEAD'" x-cloak>
                    <label for="teamId" class="block text-sm font-medium text-black">Assigned Team</label>
                    <x-select 
                        name="teamId" 
                        placeholder="-- Select Team --" 
                        :value="old('teamId')" 
                        :options="array_merge([['value' => '', 'label' => '-- Select Team --']], array_map(fn($t) => ['value' => $t->id, 'label' => $t->name], $teams->all()))" 
                        class="mt-1"
                    />
                    @error('teamId')
                        <p class="mt-2 text-xs text-red-600 font-medium">{{ $message }}</p>
                    @enderror
                </div>

                <div class="pt-2">
                    <button type="submit"
                        class="flex w-full justify-center bg-blue-500 hover:bg-blue-600 py-2.5 px-4 text-sm font-semibold text-white focus:outline-none cursor-pointer">
                        Create Account
                    </button>
                </div>
            </form>

            <div class="mt-4 text-center">
                <a href="{{ route('login') }}" class="text-sm font-medium text-blue-500 hover:underline">
                    Already have an account? Log in
                </a>
            </div>
        </div>
    </div>
</div>
@endsection
