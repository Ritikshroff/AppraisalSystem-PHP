@extends('layouts.app')

@section('title', 'Login - AppraisalFlow')

@section('content')
<div class="flex min-h-[70vh] flex-col justify-center py-6 sm:px-6 lg:px-8">
    <div class="sm:mx-auto sm:w-full sm:max-w-md text-center">
        <h2 class="text-3xl font-bold tracking-tight text-black">Sign in to your account</h2>
        <p class="mt-2 text-sm text-gray-600">
            Or
            <a href="{{ route('signup') }}" class="font-semibold text-blue-500 hover:underline">
                create a new role-based account
            </a>
        </p>
    </div>

    <div class="mt-8 sm:mx-auto sm:w-full sm:max-w-md">
        <div class="bg-white border border-gray-200 py-8 px-4 sm:px-10">
            <form class="space-y-6" action="{{ route('login') }}" method="POST">
                @csrf

                <div>
                    <label for="email" class="block text-sm font-medium text-black">Email address</label>
                    <div class="mt-1">
                        <input id="email" name="email" type="email" autocomplete="email" required value="{{ old('email') }}"
                            class="block w-full border border-gray-300 py-2.5 px-3 text-black placeholder:text-gray-400 focus:outline-none focus:border-blue-500 sm:text-sm">
                    </div>
                    @error('email')
                        <p class="mt-2 text-xs text-red-600 font-medium">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label for="password" class="block text-sm font-medium text-black">Password</label>
                    <div class="mt-1">
                        <input id="password" name="password" type="password" autocomplete="current-password" required
                            class="block w-full border border-gray-300 py-2.5 px-3 text-black placeholder:text-gray-400 focus:outline-none focus:border-blue-500 sm:text-sm">
                    </div>
                </div>

                <div>
                    <button type="submit"
                        class="flex w-full justify-center bg-blue-500 hover:bg-blue-600 py-2.5 px-4 text-sm font-semibold text-white focus:outline-none cursor-pointer">
                        Sign in
                    </button>
                </div>
            </form>

            <div class="mt-6 border-t border-gray-200 pt-6">
                <p class="text-xs text-center text-gray-500 font-semibold mb-2">Available Accounts:</p>
                <div class="space-y-1.5 text-xs text-center">
                    @forelse($users ?? [] as $u)
                        <div>
                            <code class="text-gray-900 font-mono font-bold">{{ $u->email }}</code>
                            <span class="text-gray-500 text-[11px]">({{ str_replace('_', ' ', strtoupper($u->role)) }})</span>
                        </div>
                    @empty
                        <p class="text-gray-400 italic">No users found.</p>
                    @endforelse
                </div>
                <p class="mt-3 text-xs text-center text-gray-500">
                    Password for all: <code class="text-gray-900 font-mono font-bold">Cybermedia@123</code>
                </p>
            </div>
        </div>
    </div>
</div>
@endsection
