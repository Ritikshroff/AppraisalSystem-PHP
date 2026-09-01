<!DOCTYPE html>
<html lang="en" class="h-full bg-gray-50 text-black">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Appraisal Automation')</title>
    
    <!-- Google Fonts: Inter -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    
    <!-- Tailwind CSS CDN -->
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    fontFamily: {
                        sans: ['Inter', 'sans-serif'],
                    },
                }
            }
        }
    </script>
    
    <!-- Alpine.js CDN -->
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
    
    <!-- Lucide Icons CDN -->
    <script src="https://unpkg.com/lucide@latest"></script>

    <style>
        [x-cloak] { display: none !important; }
        body {
            font-family: 'Inter', sans-serif;
            background-color: #f9fafb;
            color: #000000;
        }
        .flat-card {
            background-color: #ffffff;
            border: 1px solid #e5e7eb;
        }
        
        /* Hide scrollbars globally */
        * {
            -ms-overflow-style: none;  /* IE and Edge */
            scrollbar-width: none;  /* Firefox */
        }
        *::-webkit-scrollbar {
            display: none; /* Chrome, Safari and Opera */
        }
        
        .no-scrollbar::-webkit-scrollbar {
            display: none;
        }
        .no-scrollbar {
            -ms-overflow-style: none;
            scrollbar-width: none;
        }
        
        /* Shimmer Animation Effect */
        @keyframes shimmer {
            0% { background-position: -200% 0; }
            100% { background-position: 200% 0; }
        }
        .shimmer {
            background: linear-gradient(90deg, #f3f4f6 25%, #e5e7eb 50%, #f3f4f6 75%);
            background-size: 200% 100%;
            animation: shimmer 1.5s infinite linear;
        }
        .shimmer-text {
            border-radius: 4px;
            color: transparent !important;
            user-select: none;
        }
    </style>
    @yield('styles')
</head>
<body class="h-full antialiased text-black" x-data="{ isLoading: true }" x-init="setTimeout(() => isLoading = false, 350)">
    <!-- Global Loading Shimmer Overlay on initial load / navigation -->
    <div x-show="isLoading" class="fixed inset-0 bg-white z-50 p-8 space-y-6 overflow-hidden pointer-events-none" transition:leave="transition ease-in duration-200" transition:leave-start="opacity-100" transition:leave-end="opacity-0">
        <!-- Nav Shimmer -->
        <div class="flex justify-between items-center pb-6 border-b border-gray-200">
            <div class="h-8 w-40 shimmer rounded"></div>
            <div class="h-8 w-32 shimmer rounded"></div>
        </div>
        <!-- Hero Shimmer -->
        <div class="h-28 w-full shimmer rounded-lg"></div>
        <!-- Table / Content Shimmer -->
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6 pt-4">
            <div class="md:col-span-2 space-y-4">
                <div class="h-10 w-full shimmer rounded"></div>
                <div class="h-16 w-full shimmer rounded"></div>
                <div class="h-16 w-full shimmer rounded"></div>
                <div class="h-16 w-full shimmer rounded"></div>
                <div class="h-16 w-full shimmer rounded"></div>
            </div>
            <div class="space-y-4">
                <div class="h-10 w-full shimmer rounded"></div>
                <div class="h-32 w-full shimmer rounded"></div>
                <div class="h-32 w-full shimmer rounded"></div>
            </div>
        </div>
    </div>

    <div class="min-h-full flex flex-col">
        <!-- Navigation Bar -->
        <nav class="bg-white border-b border-gray-200">
            <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                <div class="flex h-16 justify-between items-center">
                    <div class="flex items-center gap-3">
                        <div class="flex h-9 w-9 items-center justify-center bg-blue-500 text-white font-bold">
                            A
                        </div>
                        <a href="{{ route('dashboard') }}" class="text-xl font-bold tracking-tight text-black">
                            Appraisal<span class="text-blue-500">Flow</span>
                        </a>
                    </div>

                    @auth
                    <div class="flex items-center gap-4">
                        <div class="flex flex-col text-right">
                            <span class="text-sm font-semibold text-black">{{ Auth::user()->name }}</span>
                            <span class="text-xs text-blue-500 font-bold uppercase tracking-wider">{{ str_replace('_', ' ', Auth::user()->role) }}</span>
                        </div>
                        
                        <div class="h-6 w-px bg-gray-200"></div>
                        
                        <form action="{{ route('logout') }}" method="POST">
                            @csrf
                            <button type="submit" class="flex items-center gap-2 border border-gray-200 px-3 py-1.5 text-xs font-semibold text-black hover:bg-gray-50 cursor-pointer">
                                <i data-lucide="log-out" class="h-3.5 w-3.5"></i>
                                <span>Logout</span>
                            </button>
                        </form>
                    </div>
                    @endauth
                </div>
            </div>
        </nav>

        <!-- Main Content Area -->
        <main class="flex-1 py-8">
            <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                @if(session('success'))
                    <div class="mb-6 flex items-center gap-3 border border-emerald-500 bg-emerald-50 p-4 text-emerald-800 text-sm font-medium">
                        <i data-lucide="check-circle" class="h-5 w-5 shrink-0 text-emerald-600"></i>
                        <p>{{ session('success') }}</p>
                    </div>
                @endif

                @if($errors->has('error') || $errors->has('admin_error'))
                    <div class="mb-6 flex items-center gap-3 border border-red-500 bg-red-50 p-4 text-red-800 text-sm font-medium">
                        <i data-lucide="alert-circle" class="h-5 w-5 shrink-0 text-red-600"></i>
                        <p>{{ $errors->first('error') ?: $errors->first('admin_error') }}</p>
                    </div>
                @endif

                @yield('content')
            </div>
        </main>
        
        <!-- Footer -->
        <footer class="border-t border-gray-200 py-6 text-center text-xs text-gray-500 mt-auto bg-white">
            <p>&copy; {{ date('Y') }} AppraisalFlow &bull; Enterprise Calibrated Performance Feedback</p>
        </footer>
    </div>

    <!-- Initialize Lucide Icons -->
    <script>
        lucide.createIcons();
    </script>
    @yield('scripts')
</body>
</html>
