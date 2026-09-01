@extends('layouts.app')

@section('title', 'Dashboard - AppraisalFlow')

@section('content')
    @php
        $role = strtoupper($data['viewer']['role'] ?? '');
    @endphp
    <div class="space-y-8" x-data="{ 
                    currentTab: '{{ $role === 'HR' ? 'hr' : 'appraisals' }}',
                    isTabLoading: false,
                    switchTab(tabName) {
                        this.isTabLoading = true;
                        this.currentTab = tabName;
                        setTimeout(() => this.isTabLoading = false, 250);
                    },
                    showProfileModal: false,
                    profileData: {},
                    showEditEmployeeModal: false,
                    editEmployeeData: {},
                    showHistoryModal: false,
                    historyData: [],
                    historyEmployeeName: ''
                }">
        <!-- Header Summary Card -->
        <div class="bg-white border border-gray-200 p-6 flex flex-col md:flex-row md:items-center justify-between gap-6">
            <div>
                <h1 class="text-2xl font-bold tracking-tight text-black">
                    Welcome back, <span class="text-blue-500">{{ $data['viewer']['name'] }}</span>
                </h1>
                <p class="mt-1 text-sm text-gray-500">
                    Logged in as <span
                        class="font-semibold text-black uppercase">{{ str_replace('_', ' ', $data['viewer']['role']) }}</span>
                    @if($data['viewer']['teamName'])
                        &bull; Team <span class="font-semibold text-black">{{ $data['viewer']['teamName'] }}</span>
                    @endif
                </p>
            </div>

            <!-- Tab switches (only for HR) -->
            @if($data['viewer']['role'] === 'HR')
                <div class="flex bg-gray-100 p-1 border border-gray-200">
                    <button @click="switchTab('appraisals')"
                        :class="currentTab === 'appraisals' ? 'bg-blue-500 text-white font-semibold' : 'text-gray-600 hover:text-black'"
                        class="px-4 py-1.5 text-xs font-semibold transition-all cursor-pointer">
                        Appraisals
                    </button>
                    <button @click="switchTab('hr')"
                        :class="currentTab === 'hr' ? 'bg-blue-500 text-white font-semibold' : 'text-gray-600 hover:text-black'"
                        class="px-4 py-1.5 text-xs font-semibold transition-all cursor-pointer">
                        HR Panel
                    </button>
                </div>
            @endif
        </div>

        <!-- Tab Switching Shimmer Skeleton -->
        <div x-show="isTabLoading" class="space-y-6">
            <div class="h-12 w-full shimmer rounded"></div>
            <div class="h-64 w-full shimmer rounded-lg"></div>
        </div>

        <div x-show="!isTabLoading">

        <!-- Tab: Appraisals -->
        <div x-show="currentTab === 'appraisals'" class="space-y-8">
            {{-- My Self Appraisal Banner --}}
            @php
                $mySelfAppraisalItem = null;
                if (!empty($data['visibleAppraisals']['items'])) {
                    foreach ($data['visibleAppraisals']['items'] as $checkItem) {
                        if (($checkItem['employeeId'] ?? '') === ($data['viewer']['employeeId'] ?? '') || ($checkItem['employee']['id'] ?? '') === ($data['viewer']['employeeId'] ?? '')) {
                            $mySelfAppraisalItem = $checkItem;
                            break;
                        }
                    }
                }
            @endphp

            @if($mySelfAppraisalItem)
                <div class="bg-blue-50 border-2 border-blue-400 p-5 rounded-sm flex items-center justify-between shadow-sm">
                    <div>
                        <div class="flex items-center gap-2">
                            <span
                                class="px-2 py-0.5 bg-blue-600 text-white text-[10px] font-bold uppercase tracking-wider rounded-xs">My
                                Self Appraisal</span>
                            <span
                                class="inline-flex items-center px-2 py-0.5 text-[10px] font-bold border rounded-xs {{ $mySelfAppraisalItem['appraisalType'] === 'WORK' ? 'bg-blue-100 text-blue-800 border-blue-300' : 'bg-emerald-100 text-emerald-800 border-emerald-300' }}">
                                {{ $mySelfAppraisalItem['appraisalPeriod'] }}
                                ({{ ucfirst(strtolower($mySelfAppraisalItem['appraisalType'])) }})
                            </span>
                            <span
                                class="text-xs font-bold text-gray-500 font-mono">{{ $mySelfAppraisalItem['employeeCode'] }}</span>
                        </div>
                        <h3 class="text-base font-bold text-black mt-1.5">
                            {{ $mySelfAppraisalItem['appraisalPeriod'] }}
                            {{ substr($mySelfAppraisalItem['cycleName'], 0, 4) ?: '2026' }}
                            {{ $mySelfAppraisalItem['appraisalType'] === 'WORK' ? 'Work Appraisal' : 'Salary Review' }}
                        </h3>
                        <p class="text-xs text-gray-600 mt-0.5">Status: <span
                                class="font-bold text-amber-600 uppercase">{{ $mySelfAppraisalItem['status'] }}</span></p>
                    </div>
                    <div>
                        <a href="{{ route('appraisals.show', $mySelfAppraisalItem['id']) }}"
                            class="inline-flex items-center gap-2 px-5 py-2.5 bg-blue-600 hover:bg-blue-700 text-white font-bold text-xs uppercase tracking-wider shadow-sm transition-all cursor-pointer">
                            <i data-lucide="edit-3" class="w-4 h-4"></i>
                            {{ $mySelfAppraisalItem['status'] === 'DRAFT' ? 'Fill My Self Appraisal' : 'View My Appraisal' }}
                        </a>
                    </div>
                </div>
            @endif

            <!-- Metric Cards -->
            <div class="grid grid-cols-1 gap-5 sm:grid-cols-2 lg:grid-cols-4">
                @foreach($data['metrics'] as $metric)
                    <x-card-stat :label="$metric['label']" :value="$metric['value']" :detail="$metric['detail']" />
                @endforeach
            </div>

            <!-- Search Filter -->
            <div class="bg-white border border-gray-200 p-4">
                <form action="{{ route('dashboard') }}" method="GET" class="flex gap-3">
                    <input type="hidden" name="view" value="{{ request('view', 'dashboard') }}">
                    <div class="relative flex-1">
                        <span class="absolute inset-y-0 left-0 flex items-center pl-3 text-gray-400">
                            <i data-lucide="search" class="h-4 w-4"></i>
                        </span>
                        <input type="text" name="query" value="{{ $data['filters']['query'] }}"
                            placeholder="Search appraisals by employee code or name..."
                            class="block w-full border border-gray-300 py-2 pl-9 pr-4 text-black placeholder:text-gray-400 focus:outline-none focus:border-blue-500 text-sm">
                    </div>
                    <button type="submit"
                        class="border border-gray-300 bg-white px-4 py-2 text-xs font-bold text-black hover:bg-gray-50 transition-colors cursor-pointer">
                        Filter
                    </button>
                </form>
            </div>

            <!-- Dashboard Content Split Layout -->
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
                <!-- Left 2 Cols: Main Appraisal Tables -->
                <div class="lg:col-span-2 space-y-8">
                    <!-- Visible Appraisals -->
                    <div class="bg-white border border-gray-200">
                        <div class="px-6 py-4 border-b border-gray-200 flex justify-between items-center bg-gray-50">
                            <h3 class="text-sm font-bold text-black uppercase tracking-wider">All Appraisals</h3>
                            <span
                                class="bg-gray-100 border border-gray-200 px-2 py-0.5 text-[10px] font-semibold text-gray-600">Page
                                {{ $data['visibleAppraisals']['pageInfo']['page'] }}</span>
                        </div>

                        @php
                            $headers = ['Code', 'Name', 'Designation', 'Manager', 'Reviewer', 'Incoming Cycle', 'Appraisal Type', 'Status', 'Action'];
                        @endphp

                        <x-table :headers="$headers">
                            @if(empty($data['visibleAppraisals']['items']))
                                <tr>
                                    <td colspan="{{ count($headers) }}" class="px-6 py-10 text-center text-gray-500 text-sm">
                                        No active appraisals found.
                                    </td>
                                </tr>
                            @else
                                @foreach($data['visibleAppraisals']['items'] as $item)
                                    <tr class="hover:bg-gray-50/50 transition-colors whitespace-nowrap">
                                        <td class="px-6 py-4 font-mono text-xs text-blue-500 font-semibold">
                                            {{ $item['employeeCode'] }}
                                        </td>
                                        <td class="px-6 py-4 font-bold text-black">{{ $item['employeeName'] }}</td>
                                        <td class="px-6 py-4 text-xs font-medium text-gray-600">
                                            {{ $item['designation'] ?? ($item['employee']['designation'] ?? 'N/A') }}
                                        </td>
                                        <td class="px-6 py-4 text-xs font-semibold text-gray-700">
                                            {{ $item['managerName'] ?: 'N/A' }}
                                        </td>
                                        <td class="px-6 py-4 text-xs font-semibold text-gray-700">
                                            {{ $item['reviewerName'] ?: 'PR Team' }}
                                        </td>
                                        <td class="px-6 py-4 text-xs font-bold text-black">
                                            <span
                                                class="inline-flex items-center gap-1.5 px-2.5 py-1 text-xs font-bold border border-gray-200 bg-gray-50 text-gray-800 rounded-sm">
                                                <span class="w-1.5 h-1.5 rounded-full bg-green-500 animate-pulse"></span>
                                                {{ $item['appraisalPeriod'] }} 2026
                                            </span>
                                        </td>
                                        <td class="px-6 py-4 text-xs">
                                            <span
                                                class="inline-flex items-center px-2.5 py-1 text-xs font-bold border rounded-sm {{ $item['appraisalType'] === 'WORK' ? 'bg-blue-50 text-blue-700 border-blue-200' : 'bg-emerald-50 text-emerald-700 border-emerald-200' }}">
                                                {{ ucfirst(strtolower($item['appraisalType'])) }} Review
                                            </span>
                                        </td>
                                        <td class="px-6 py-4 text-xs">
                                            <span
                                                class="inline-flex items-center px-2.5 py-0.5 text-xs font-semibold border
                                                                                            {{ $item['status'] === 'DRAFT' ? 'bg-gray-100 text-gray-600 border-gray-200' : '' }}
                                                                                            {{ $item['status'] === 'SUBMITTED' ? 'bg-blue-50 text-blue-600 border-blue-200' : '' }}
                                                                                            {{ $item['status'] === 'MANAGER_REVIEW' ? 'bg-blue-50 text-blue-600 border-blue-200' : '' }}
                                                                                            {{ $item['status'] === 'COMPLETED' ? 'bg-black text-white border-black' : '' }}">
                                                {{ $item['status'] }}
                                            </span>
                                            @if($item['sentimentLabel'])
                                                <span
                                                    class="ml-1 inline-flex items-center px-2 py-0.5 text-[10px] font-bold border
                                                                                                                {{ $item['sentimentLabel'] === 'POSITIVE' ? 'bg-blue-50 text-blue-600 border-blue-200' : 'bg-gray-100 text-gray-600 border-gray-200' }}">
                                                    {{ $item['sentimentLabel'] }}
                                                </span>
                                            @endif
                                        </td>
                                        <td class="px-6 py-4 text-center space-y-1.5 md:space-y-0 md:space-x-1.5 whitespace-nowrap">
                                            @php
                                                $isEditable = false;
                                                $isSelf = ($data['viewer']['employeeId'] === ($item['employeeId'] ?? null) || $data['viewer']['employeeId'] === ($item['employee']['id'] ?? null));
                                                if ($item['status'] === 'DRAFT' && $isSelf) {
                                                    $isEditable = true;
                                                } elseif ($role === 'EMPLOYEE' && $item['status'] === 'DRAFT') {
                                                    $isEditable = true;
                                                } elseif ($role === 'MANAGER' && $item['status'] === 'SUBMITTED' && ($data['viewer']['employeeId'] === ($item['employee']['managerId'] ?? null))) {
                                                    $isEditable = true;
                                                } elseif ($role === 'BU_HEAD' && $item['status'] === 'MANAGER_REVIEW') {
                                                    $isEditable = true;
                                                } elseif (in_array($role, ['BU_HEAD', 'HR']) && $item['status'] === 'COMPLETED' && ($item['specialAppeal'] ?? false) && ($item['specialAppealStatus'] ?? '') === 'PENDING') {
                                                    $isEditable = true;
                                                }
                                            @endphp

                                            <div class="inline-flex items-center justify-center space-x-1.5">
                                                @if($isEditable)
                                                    <a href="{{ route('appraisals.show', $item['id']) }}" title="Edit Current Form"
                                                        class="inline-block p-1.5 bg-blue-500 hover:bg-blue-600 text-white transition-colors cursor-pointer rounded-sm"
                                                        aria-label="Edit">
                                                        <i data-lucide="edit-3" class="h-4 w-4"></i>
                                                    </a>
                                                @else
                                                    <a href="{{ route('appraisals.show', $item['id']) }}" title="Open Current Form"
                                                        class="inline-block p-1.5 bg-blue-500 hover:bg-blue-600 text-white transition-colors cursor-pointer rounded-sm"
                                                        aria-label="Open">
                                                        <i data-lucide="eye" class="h-4 w-4"></i>
                                                    </a>
                                                @endif

                                                <button type="button" title="View Appraisal History"
                                                    @click="showHistoryModal = true; historyData = {{ json_encode($item['history'] ?? []) }}; historyEmployeeName = '{{ $item['employeeName'] }}'"
                                                    class="inline-block p-1.5 border border-gray-300 bg-white hover:bg-gray-50 text-gray-700 transition-colors cursor-pointer rounded-sm"
                                                    aria-label="History">
                                                    <i data-lucide="history" class="h-4 w-4"></i>
                                                </button>

                                                <button type="button" title="View Employee Profile"
                                                    @click="showProfileModal = true; profileData = {{ json_encode($item['employee']) }}"
                                                    class="inline-block p-1.5 border border-gray-300 bg-white hover:bg-gray-50 text-gray-700 transition-colors cursor-pointer rounded-sm"
                                                    aria-label="Profile">
                                                    <i data-lucide="user" class="h-4 w-4"></i>
                                                </button>

                                                @if($role === 'HR')
                                                    <button type="button" title="Edit Employee Profile"
                                                        @click="showEditEmployeeModal = true; editEmployeeData = {{ json_encode($item['employee']) }}"
                                                        class="inline-block p-1.5 bg-amber-500 hover:bg-amber-600 text-white transition-colors cursor-pointer rounded-sm"
                                                        aria-label="Edit Profile">
                                                        <i data-lucide="user-cog" class="h-4 w-4"></i>
                                                    </button>
                                                @endif
                                            </div>
                                        </td>
                                    </tr>
                                @endforeach
                            @endif
                        </x-table>
                    </div>

                    <!-- Co-Workers / Team Progress -->
                    @if($data['viewer']['role'] === 'EMPLOYEE')
                        <div class="bg-white border border-gray-200">
                            <div class="px-6 py-4 border-b border-gray-200 bg-gray-50">
                                <h3 class="text-sm font-bold text-black uppercase tracking-wider">Co-Workers Progress</h3>
                            </div>
                            <div class="divide-y divide-gray-200">
                                @if(empty($data['teamMemberStatuses']['items']))
                                    <div class="px-6 py-8 text-center text-gray-500 text-sm">
                                        No other team members found.
                                    </div>
                                @else
                                    @foreach($data['teamMemberStatuses']['items'] as $item)
                                        <div class="px-6 py-4 flex items-center justify-between">
                                            <div>
                                                <p class="text-sm font-semibold text-black">{{ $item['employeeName'] }}</p>
                                                <p class="text-xs text-gray-500">{{ $item['appraisalPeriod'] }}
                                                    ({{ $item['appraisalType'] }})</p>
                                            </div>
                                            <span
                                                class="inline-flex items-center border border-gray-200 px-2 py-0.5 text-xs text-gray-600">
                                                {{ $item['status'] }}
                                            </span>
                                        </div>
                                    @endforeach
                                @endif
                            </div>
                        </div>
                    @endif
                </div>

                <!-- Right 1 Col: Dynamic Action Panel & Stats -->
                <div class="space-y-8">
                    <!-- Action Required / Pending Review Box -->
                    <div class="bg-white border-l-4 border-blue-500 border-y border-r border-gray-200 p-6">
                        <h3 class="text-base font-bold text-black flex items-center gap-2">
                            <i data-lucide="bell" class="h-4 w-4 text-blue-500"></i>
                            Action Items
                        </h3>
                        <p class="mt-1 text-xs text-gray-500">Tasks requiring your execution.</p>

                        <div class="mt-4 space-y-3">
                            @if(empty($data['pendingAppraisals']['items']))
                                <div class="bg-gray-50 p-4 border border-gray-200 text-center text-xs text-gray-500">
                                    All caught up! No pending reviews or submissions.
                                </div>
                            @else
                                @foreach($data['pendingAppraisals']['items'] as $item)
                                    <div class="bg-white border border-gray-200 p-4 flex flex-col gap-3">
                                        <div>
                                            <p class="text-[10px] text-blue-500 font-bold uppercase tracking-wider">
                                                {{ $item['status'] === 'DRAFT' ? 'Draft Save' : 'Needs Calibrated Review' }}
                                            </p>
                                            <p class="mt-1 text-sm font-bold text-black">{{ $item['employeeName'] }}</p>
                                            <p class="text-xs text-gray-500">{{ $item['cycleName'] }}</p>
                                        </div>
                                        <a href="{{ route('appraisals.show', $item['id']) }}"
                                            class="text-center bg-blue-500 hover:bg-blue-600 py-1.5 text-xs font-semibold text-white transition-colors cursor-pointer">
                                            Open Appraisal
                                        </a>
                                    </div>
                                @endforeach
                            @endif
                        </div>
                    </div>

                    <!-- Budget Impact Summary (BU Head/HR Only) -->
                    @if($data['budgetImpact'])
                        <div class="bg-white border border-gray-200 p-6">
                            <h3 class="text-sm font-bold text-black uppercase tracking-wider flex items-center gap-2 mb-4">
                                <i data-lucide="pie-chart" class="h-4 w-4 text-blue-500"></i>
                                Budget Impact
                            </h3>
                            <div class="grid grid-cols-2 gap-4">
                                <div>
                                    <p class="text-xs text-gray-500 font-medium uppercase tracking-wider">Hikes Issued</p>
                                    <p class="mt-1 text-xl font-extrabold text-black">
                                        {{ $data['budgetImpact']['appraisalCount'] }}
                                    </p>
                                </div>
                                <div>
                                    <p class="text-xs text-gray-500 font-medium uppercase tracking-wider">Avg Hike %</p>
                                    <p class="mt-1 text-xl font-extrabold text-blue-500">
                                        {{ $data['budgetImpact']['averageHikePercentage'] }}%
                                    </p>
                                </div>
                            </div>
                        </div>
                    @endif

                    <!-- Team Summaries -->
                    @if(!empty($data['teamSummary']))
                        <div class="bg-white border border-gray-200">
                            <div class="px-6 py-4 border-b border-gray-200 bg-gray-50">
                                <h3 class="text-sm font-bold text-black uppercase tracking-wider">Team Summary</h3>
                            </div>
                            <div class="divide-y divide-gray-200">
                                @foreach($data['teamSummary'] as $team)
                                    <div class="px-6 py-4 flex items-center justify-between">
                                        <div>
                                            <p class="text-sm font-semibold text-black">{{ $team['teamName'] }}</p>
                                            <p class="text-xs text-gray-500">
                                                Completed: {{ $team['completedCount'] }} / {{ $team['totalAppraisals'] }}
                                            </p>
                                        </div>
                                        <div class="text-right">
                                            <p class="text-[10px] text-gray-400 font-bold uppercase">Avg Rating</p>
                                            <p class="text-sm font-extrabold text-blue-500">
                                                {{ $team['averageFinalRating'] ?: 'N/A' }}
                                            </p>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    @endif
                </div>
            </div>
        </div>

        <!-- Tab: HR Admin -->
        @if($data['viewer']['role'] === 'HR')
            <div x-show="currentTab === 'hr'" class="space-y-8">
                <!-- Full Width Employee Management Table -->
                <div class="bg-white border border-gray-200 w-full">
                    <div class="px-6 py-4 border-b border-gray-200 flex justify-between items-center bg-gray-50">
                        <h3 class="text-sm font-bold text-black uppercase tracking-wider">Employees Directory</h3>
                        <span
                            class="border border-gray-200 bg-gray-100 px-2.5 py-0.5 text-xs font-semibold text-gray-600">Total:
                            {{ $data['hrData']['employees']['pageInfo']['totalItems'] }}</span>
                    </div>

                    <div class="overflow-x-auto">
                        <table class="w-full text-left text-sm text-black">
                            <thead
                                class="bg-gray-50 text-xs uppercase tracking-wider text-gray-500 border-b border-gray-200">
                                <tr>
                                    <th class="px-6 py-3">Code</th>
                                    <th class="px-6 py-3">Name</th>
                                    <th class="px-6 py-3">Department</th>
                                    <th class="px-6 py-3">Designation</th>
                                    <th class="px-6 py-3">Role</th>
                                    <th class="px-6 py-3 text-center">Action</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-200">
                                @foreach($data['hrData']['employees']['items'] as $emp)
                                    <tr class="hover:bg-gray-50/50">
                                        <td class="px-6 py-4 font-mono text-xs text-blue-500">{{ $emp['employeeCode'] }}</td>
                                        <td class="px-6 py-4 font-bold text-black">{{ $emp['fullName'] }}</td>
                                        <td class="px-6 py-4">{{ $emp['department'] }}</td>
                                        <td class="px-6 py-4 text-gray-600 text-xs">{{ $emp['designation'] }}</td>
                                        <td class="px-6 py-4">
                                            <span
                                                class="border border-gray-200 bg-gray-100 px-2 py-0.5 text-xs font-medium text-gray-600">
                                                {{ str_replace('_', ' ', $emp['role']) }}
                                            </span>
                                        </td>
                                        <td class="px-6 py-4 text-center whitespace-nowrap">
                                            <button type="button" title="Edit Employee Profile"
                                                @click="showEditEmployeeModal = true; editEmployeeData = {{ json_encode($emp) }}"
                                                class="inline-block p-1.5 bg-amber-500 hover:bg-amber-600 text-white transition-colors cursor-pointer rounded-sm text-xs font-semibold px-2.5"
                                                aria-label="Edit Profile">
                                                <i data-lucide="user-cog" class="h-3.5 w-3.5 inline mr-1"></i> Edit Info
                                            </button>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        @endif
        </div>

        <!-- Employee Profile Modal -->
        <x-modal showVar="showProfileModal" class="max-w-2xl">
            <x-slot name="headerIcon">
                <i data-lucide="user" class="h-5 w-5 text-blue-500"></i>
            </x-slot>
            <x-slot name="title">Employee Profile Details</x-slot>

            <div class="grid grid-cols-2 md:grid-cols-3 gap-6 text-xs font-mono">
                <div>
                    <span class="text-gray-400 font-bold uppercase tracking-wider block font-sans">Full Name</span>
                    <span class="text-black font-semibold mt-1 block text-sm" x-text="profileData.fullName || 'N/A'"></span>
                </div>
                <div>
                    <span class="text-gray-400 font-bold uppercase tracking-wider block font-sans">Employee Code</span>
                    <span class="text-blue-500 font-bold mt-1 block text-sm"
                        x-text="profileData.employeeCode || 'N/A'"></span>
                </div>
                <div class="col-span-2 md:col-span-3 min-w-0">
                    <span class="text-gray-400 font-bold uppercase tracking-wider block font-sans">Email Address</span>
                    <span class="text-black font-semibold mt-1 block text-sm break-all"
                        x-text="profileData.email || 'N/A'"></span>
                </div>
                <div>
                    <span class="text-gray-400 font-bold uppercase tracking-wider block font-sans">Department</span>
                    <span class="text-black font-semibold mt-1 block text-sm"
                        x-text="profileData.department || 'N/A'"></span>
                </div>
                <div class="col-span-2 md:col-span-1">
                    <span class="text-gray-400 font-bold uppercase tracking-wider block font-sans">Designation</span>
                    <span class="text-black font-semibold mt-1 block text-sm break-words"
                        x-text="profileData.designation || 'N/A'"></span>
                </div>
                <div>
                    <span class="text-gray-400 font-bold uppercase tracking-wider block font-sans">Grade</span>
                    <span class="text-black font-semibold mt-1 block text-sm" x-text="profileData.grade || 'N/A'"></span>
                </div>
                <div>
                    <span class="text-gray-400 font-bold uppercase tracking-wider block font-sans">Date of Joining</span>
                    <span class="text-black font-semibold mt-1 block text-sm" x-text="profileData.doj || 'N/A'"></span>
                </div>
                <div>
                    <span class="text-gray-400 font-bold uppercase tracking-wider block font-sans">Date of Birth</span>
                    <span class="text-black font-semibold mt-1 block text-sm" x-text="profileData.dob || 'N/A'"></span>
                </div>
                <div>
                    <span class="text-gray-400 font-bold uppercase tracking-wider block font-sans">Last Promotion
                        Date</span>
                    <span class="text-black font-semibold mt-1 block text-sm"
                        x-text="profileData.lastPromotionDate || 'N/A'"></span>
                </div>
                <div>
                    <span class="text-gray-400 font-bold uppercase tracking-wider block font-sans">Experience in
                        Company</span>
                    <span class="text-black font-semibold mt-1 block text-sm"
                        x-text="profileData.companyExperienceYears !== null ? parseFloat(profileData.companyExperienceYears).toFixed(1) + ' Years' : 'N/A'"></span>
                </div>
                <div>
                    <span class="text-gray-400 font-bold uppercase tracking-wider block font-sans">Total Experience</span>
                    <span class="text-black font-semibold mt-1 block text-sm"
                        x-text="profileData.totalExperienceYears !== null ? parseFloat(profileData.totalExperienceYears).toFixed(1) + ' Years' : 'N/A'"></span>
                </div>
                @if(in_array($role, ['HR', 'BU_HEAD']))
                    <div>
                        <span class="text-gray-400 font-bold uppercase tracking-wider block font-sans">Current CTC</span>
                        <span class="text-green-600 font-bold mt-1 block text-sm"
                            x-text="profileData.salary !== null ? 'INR ' + parseFloat(profileData.salary).toLocaleString('en-IN') : 'N/A'"></span>
                    </div>
                @endif
            </div>

            <div class="flex justify-end pt-4 border-t border-gray-200">
                <button type="button" @click="showProfileModal = false"
                    class="bg-black hover:bg-gray-800 px-5 py-2 text-xs font-bold text-white transition-colors cursor-pointer">
                    Close Details
                </button>
            </div>
        </x-modal>

        @if($role === 'HR')
            <!-- HR Edit Employee Profile Modal -->
            <x-modal showVar="showEditEmployeeModal" class="max-w-3xl">
                <x-slot name="headerIcon">
                    <i data-lucide="user-cog" class="h-5 w-5 text-amber-500"></i>
                </x-slot>
                <x-slot name="title">Edit Employee Information: <span class="text-blue-600 font-sans normal-case" x-text="editEmployeeData.fullName"></span></x-slot>

                <form :action="'/admin/employee/' + editEmployeeData.id + '/update'" method="POST" class="space-y-6 text-xs">
                    @csrf
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                        <div>
                            <label class="block font-bold text-gray-700 uppercase tracking-wider mb-1">Full Name</label>
                            <input type="text" name="fullName" x-model="editEmployeeData.fullName" required
                                class="w-full border border-gray-300 p-2 text-black focus:outline-none focus:border-blue-500">
                        </div>

                        <div>
                            <label class="block font-bold text-gray-700 uppercase tracking-wider mb-1">Employee Code</label>
                            <input type="text" name="employeeCode" x-model="editEmployeeData.employeeCode" required
                                class="w-full border border-gray-300 p-2 text-black focus:outline-none focus:border-blue-500 font-mono">
                        </div>

                        <div>
                            <label class="block font-bold text-gray-700 uppercase tracking-wider mb-1">Email Address</label>
                            <input type="email" name="email" x-model="editEmployeeData.email" required
                                class="w-full border border-gray-300 p-2 text-black focus:outline-none focus:border-blue-500">
                        </div>

                        <div>
                            <label class="block font-bold text-gray-700 uppercase tracking-wider mb-1">Department</label>
                            <input type="text" name="department" x-model="editEmployeeData.department" required
                                class="w-full border border-gray-300 p-2 text-black focus:outline-none focus:border-blue-500">
                        </div>

                        <div>
                            <label class="block font-bold text-gray-700 uppercase tracking-wider mb-1">Designation</label>
                            <input type="text" name="designation" x-model="editEmployeeData.designation" required
                                class="w-full border border-gray-300 p-2 text-black focus:outline-none focus:border-blue-500">
                        </div>

                        <div>
                            <label class="block font-bold text-gray-700 uppercase tracking-wider mb-1">Grade</label>
                            <input type="text" name="grade" x-model="editEmployeeData.grade"
                                class="w-full border border-gray-300 p-2 text-black focus:outline-none focus:border-blue-500">
                        </div>

                        <div>
                            <label class="block font-bold text-gray-700 uppercase tracking-wider mb-1">Date of Joining</label>
                            <input type="date" name="doj" x-model="editEmployeeData.doj"
                                class="w-full border border-gray-300 p-2 text-black focus:outline-none focus:border-blue-500">
                        </div>

                        <div>
                            <label class="block font-bold text-gray-700 uppercase tracking-wider mb-1">Date of Birth</label>
                            <input type="date" name="dob" x-model="editEmployeeData.dob"
                                class="w-full border border-gray-300 p-2 text-black focus:outline-none focus:border-blue-500">
                        </div>

                        <div>
                            <label class="block font-bold text-gray-700 uppercase tracking-wider mb-1">Last Promotion Date</label>
                            <input type="date" name="lastPromotionDate" x-model="editEmployeeData.lastPromotionDate"
                                class="w-full border border-gray-300 p-2 text-black focus:outline-none focus:border-blue-500">
                        </div>

                        <div>
                            <label class="block font-bold text-gray-700 uppercase tracking-wider mb-1">Company Experience (Years)</label>
                            <input type="number" step="0.1" name="companyExperienceYears" x-model="editEmployeeData.companyExperienceYears"
                                class="w-full border border-gray-300 p-2 text-black focus:outline-none focus:border-blue-500">
                        </div>

                        <div>
                            <label class="block font-bold text-gray-700 uppercase tracking-wider mb-1">Total Experience (Years)</label>
                            <input type="number" step="0.1" name="totalExperienceYears" x-model="editEmployeeData.totalExperienceYears"
                                class="w-full border border-gray-300 p-2 text-black focus:outline-none focus:border-blue-500">
                        </div>

                        <div>
                            <label class="block font-bold text-gray-700 uppercase tracking-wider mb-1">Current CTC (INR)</label>
                            <input type="number" name="salary" x-model="editEmployeeData.salary"
                                class="w-full border border-gray-300 p-2 text-black focus:outline-none focus:border-blue-500 font-mono">
                        </div>
                    </div>

                    <div class="flex justify-end gap-3 pt-4 border-t border-gray-200">
                        <button type="button" @click="showEditEmployeeModal = false"
                            class="border border-gray-300 bg-white hover:bg-gray-50 px-4 py-2 text-xs font-bold text-black transition-colors cursor-pointer">
                            Cancel
                        </button>
                        <button type="submit"
                            class="bg-blue-600 hover:bg-blue-700 px-5 py-2 text-xs font-bold text-white transition-colors cursor-pointer shadow-sm">
                            Save & Update Profile
                        </button>
                    </div>
                </form>
            </x-modal>
        @endif

        <!-- Employee History Modal -->
        <x-modal showVar="showHistoryModal" class="max-w-3xl">
            <x-slot name="headerIcon">
                <i data-lucide="history" class="h-5 w-5 text-blue-500"></i>
            </x-slot>
            <x-slot name="title">
                Appraisal History: <span class="text-blue-600 font-sans normal-case" x-text="historyEmployeeName"></span>
            </x-slot>

            <!-- History List Table -->
            <div class="max-h-96 overflow-y-auto">
                <x-table :headers="['Cycle Name', 'Type', 'Period', 'Rating', 'Hike %', 'Status', 'Action']">
                    <template x-if="historyData.length === 0">
                        <tr>
                            <td colspan="7" class="px-6 py-8 text-center text-gray-400 font-medium">
                                No historical appraisal records found for this employee.
                            </td>
                        </tr>
                    </template>
                    <template x-for="hist in historyData" :key="hist.id">
                        <tr class="hover:bg-gray-50 transition-colors">
                            <td class="px-6 py-3 font-semibold text-black" x-text="hist.cycleName"></td>
                            <td class="px-6 py-3 font-medium font-mono text-gray-500" x-text="hist.appraisalType"></td>
                            <td class="px-6 py-3 text-gray-600" x-text="hist.appraisalPeriod"></td>
                            <td class="px-6 py-3 font-bold text-blue-600 font-mono" x-text="hist.finalRating || '-'"></td>
                            <td class="px-6 py-3 font-bold text-green-600 font-mono"
                                x-text="hist.hikePercentage !== null ? hist.hikePercentage + '%' : '-'"></td>
                            <td class="px-6 py-3 text-center">
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-sm text-[10px] font-bold border"
                                    :class="{
                                                      'bg-yellow-50 text-yellow-600 border-yellow-200': hist.status === 'DRAFT',
                                                      'bg-orange-50 text-orange-600 border-orange-200': hist.status === 'SUBMITTED',
                                                      'bg-blue-50 text-blue-600 border-blue-200': hist.status === 'MANAGER_REVIEW',
                                                      'bg-black text-white border-black': hist.status === 'COMPLETED'
                                                  }" x-text="hist.status">
                                </span>
                            </td>
                            <td class="px-6 py-3 text-center">
                                <a :href="'/appraisals/' + hist.id"
                                    class="inline-block bg-black hover:bg-gray-800 text-white font-mono text-[10px] py-1 px-3.5 transition-colors cursor-pointer rounded-sm">
                                    View
                                </a>
                            </td>
                        </tr>
                    </template>
                </x-table>
            </div>

            <div class="flex justify-end pt-4 border-t border-gray-200">
                <button type="button" @click="showHistoryModal = false"
                    class="bg-black hover:bg-gray-800 px-5 py-2 text-xs font-bold text-white transition-colors cursor-pointer">
                    Close History
                </button>
            </div>
        </x-modal>
    </div>
@endsection