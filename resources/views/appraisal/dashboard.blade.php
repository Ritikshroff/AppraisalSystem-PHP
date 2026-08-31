@extends('layouts.app')

@section('title', 'Dashboard - AppraisalFlow')

@section('content')
@php
    $role = strtoupper($data['viewer']['role'] ?? '');
@endphp
<div class="space-y-8" x-data="{ 
    currentTab: '{{ $role === 'HR' ? 'hr' : 'appraisals' }}',
    showProfileModal: false,
    profileData: {},
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
                Logged in as <span class="font-semibold text-black capitalize">{{ strtolower($data['viewer']['role']) }}</span>
                @if($data['viewer']['teamName'])
                    &bull; Team <span class="font-semibold text-black">{{ $data['viewer']['teamName'] }}</span>
                @endif
            </p>
        </div>
        
        <!-- Tab switches (only for HR) -->
        @if($data['viewer']['role'] === 'HR')
            <div class="flex bg-gray-100 p-1 border border-gray-200">
                <button @click="currentTab = 'appraisals'" :class="currentTab === 'appraisals' ? 'bg-blue-500 text-white font-semibold' : 'text-gray-600 hover:text-black'" 
                    class="px-4 py-1.5 text-xs font-semibold transition-all cursor-pointer">
                    Appraisals
                </button>
                <button @click="currentTab = 'hr'" :class="currentTab === 'hr' ? 'bg-blue-500 text-white font-semibold' : 'text-gray-600 hover:text-black'" 
                    class="px-4 py-1.5 text-xs font-semibold transition-all cursor-pointer">
                    HR Panel
                </button>
            </div>
        @endif
    </div>

    <!-- Tab: Appraisals -->
    <div x-show="currentTab === 'appraisals'" class="space-y-8">
        <!-- Metric Cards -->
        <div class="grid grid-cols-1 gap-5 sm:grid-cols-2 lg:grid-cols-4">
            @foreach($data['metrics'] as $metric)
                <x-card-stat 
                    :label="$metric['label']" 
                    :value="$metric['value']" 
                    :detail="$metric['detail']" 
                />
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
                    <input type="text" name="query" value="{{ $data['filters']['query'] }}" placeholder="Search appraisals by employee code or name..."
                        class="block w-full border border-gray-300 py-2 pl-9 pr-4 text-black placeholder:text-gray-400 focus:outline-none focus:border-blue-500 text-sm">
                </div>
                <button type="submit" class="border border-gray-300 bg-white px-4 py-2 text-xs font-bold text-black hover:bg-gray-50 transition-colors cursor-pointer">
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
                        <span class="bg-gray-100 border border-gray-200 px-2 py-0.5 text-[10px] font-semibold text-gray-600">Page {{ $data['visibleAppraisals']['pageInfo']['page'] }}</span>
                    </div>

                    @php
                        $headers = ['Code', 'Name', 'Cycle'];
                        if (in_array($role, ['HR', 'BU_HEAD'])) {
                            $headers[] = 'Type';
                        }
                        $headers[] = 'Status';
                        $headers[] = 'Action';
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
                                <tr class="hover:bg-gray-50/50 transition-colors">
                                    <td class="px-6 py-4 font-mono text-xs text-blue-500 font-semibold">{{ $item['employeeCode'] }}</td>
                                    <td class="px-6 py-4 font-bold text-black">{{ $item['employeeName'] }}</td>
                                    <td class="px-6 py-4 text-xs text-gray-600">
                                        {{ $item['cycleName'] }} ({{ $item['appraisalPeriod'] }})
                                    </td>
                                    @if(in_array($role, ['HR', 'BU_HEAD']))
                                        <td class="px-6 py-4 text-xs">
                                            <span class="border border-gray-200 bg-gray-50 px-2 py-0.5 rounded-sm font-semibold text-gray-600">
                                                {{ $item['appraisalType'] }}
                                            </span>
                                        </td>
                                    @endif
                                    <td class="px-6 py-4 text-xs">
                                        <span class="inline-flex items-center px-2.5 py-0.5 text-xs font-semibold border
                                            {{ $item['status'] === 'DRAFT' ? 'bg-gray-100 text-gray-600 border-gray-200' : '' }}
                                            {{ $item['status'] === 'SUBMITTED' ? 'bg-blue-50 text-blue-600 border-blue-200' : '' }}
                                            {{ $item['status'] === 'MANAGER_REVIEW' ? 'bg-blue-50 text-blue-600 border-blue-200' : '' }}
                                            {{ $item['status'] === 'COMPLETED' ? 'bg-black text-white border-black' : '' }}">
                                            {{ $item['status'] }}
                                        </span>
                                        @if($item['sentimentLabel'])
                                            <span class="ml-1 inline-flex items-center px-2 py-0.5 text-[10px] font-bold border
                                                {{ $item['sentimentLabel'] === 'POSITIVE' ? 'bg-blue-50 text-blue-600 border-blue-200' : 'bg-gray-100 text-gray-600 border-gray-200' }}">
                                                {{ $item['sentimentLabel'] }}
                                            </span>
                                        @endif
                                    </td>
                                    <td class="px-6 py-4 text-center space-y-1.5 md:space-y-0 md:space-x-1.5 whitespace-nowrap">
                                        @php
                                            $isEditable = false;
                                            if ($role === 'EMPLOYEE' && $item['status'] === 'DRAFT') {
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
                                                <a href="{{ route('appraisals.show', $item['id']) }}" 
                                                   title="Edit Current Form"
                                                   class="inline-block p-1.5 bg-blue-500 hover:bg-blue-600 text-white transition-colors cursor-pointer rounded-sm"
                                                   aria-label="Edit">
                                                    <i data-lucide="edit-3" class="h-4 w-4"></i>
                                                </a>
                                            @else
                                                <a href="{{ route('appraisals.show', $item['id']) }}" 
                                                   title="Open Current Form"
                                                   class="inline-block p-1.5 bg-blue-500 hover:bg-blue-600 text-white transition-colors cursor-pointer rounded-sm"
                                                   aria-label="Open">
                                                    <i data-lucide="eye" class="h-4 w-4"></i>
                                                </a>
                                            @endif

                                            <button type="button" 
                                                    title="View Appraisal History"
                                                    @click="showHistoryModal = true; historyData = {{ json_encode($item['history'] ?? []) }}; historyEmployeeName = '{{ $item['employeeName'] }}'"
                                                    class="inline-block p-1.5 border border-gray-300 bg-white hover:bg-gray-50 text-gray-700 transition-colors cursor-pointer rounded-sm"
                                                    aria-label="History">
                                                <i data-lucide="history" class="h-4 w-4"></i>
                                            </button>

                                            <button type="button" 
                                                    title="View Employee Profile"
                                                    @click="showProfileModal = true; profileData = {{ json_encode($item['employee']) }}"
                                                    class="inline-block p-1.5 border border-gray-300 bg-white hover:bg-gray-50 text-gray-700 transition-colors cursor-pointer rounded-sm"
                                                    aria-label="Profile">
                                                <i data-lucide="user" class="h-4 w-4"></i>
                                            </button>
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
                                            <p class="text-xs text-gray-500">{{ $item['appraisalPeriod'] }} ({{ $item['appraisalType'] }})</p>
                                        </div>
                                        <span class="inline-flex items-center border border-gray-200 px-2 py-0.5 text-xs text-gray-600">
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
                                    <a href="{{ route('appraisals.show', $item['id']) }}" class="text-center bg-blue-500 hover:bg-blue-600 py-1.5 text-xs font-semibold text-white transition-colors cursor-pointer">
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
                                <p class="mt-1 text-xl font-extrabold text-black">{{ $data['budgetImpact']['appraisalCount'] }}</p>
                            </div>
                            <div>
                                <p class="text-xs text-gray-500 font-medium uppercase tracking-wider">Avg Hike %</p>
                                <p class="mt-1 text-xl font-extrabold text-blue-500">{{ $data['budgetImpact']['averageHikePercentage'] }}%</p>
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
                                        <p class="text-sm font-extrabold text-blue-500">{{ $team['averageFinalRating'] ?: 'N/A' }}</p>
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
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
                <!-- Left 2 Cols: Employee Management Table -->
                <div class="lg:col-span-2 bg-white border border-gray-200">
                    <div class="px-6 py-4 border-b border-gray-200 flex justify-between items-center bg-gray-50">
                        <h3 class="text-sm font-bold text-black uppercase tracking-wider">Employees Directory</h3>
                        <span class="border border-gray-200 bg-gray-100 px-2.5 py-0.5 text-xs font-semibold text-gray-600">Total: {{ $data['hrData']['employees']['pageInfo']['totalItems'] }}</span>
                    </div>

                    <div class="overflow-x-auto">
                        <table class="w-full text-left text-sm text-black">
                            <thead class="bg-gray-50 text-xs uppercase tracking-wider text-gray-500 border-b border-gray-200">
                                <tr>
                                    <th class="px-6 py-3">Code</th>
                                    <th class="px-6 py-3">Name</th>
                                    <th class="px-6 py-3">Department</th>
                                    <th class="px-6 py-3">Designation</th>
                                    <th class="px-6 py-3">Role</th>
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
                                            <span class="border border-gray-200 bg-gray-100 px-2 py-0.5 text-xs font-medium text-gray-600">
                                                {{ $emp['role'] }}
                                            </span>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- Right 1 Col: Active Cycles & Enrollment Window Config -->
                <div class="space-y-8">
                    <!-- Active Appraisal Cycles -->
                    <div class="bg-white border border-gray-200 p-6">
                        <h3 class="text-sm font-bold text-black uppercase tracking-wider flex items-center gap-2 mb-4">
                            <i data-lucide="refresh-cw" class="h-4 w-4 text-blue-500"></i>
                            Active Cycles
                        </h3>
                        <div class="space-y-4">
                            @foreach($data['hrData']['activeCycles'] as $cycle)
                                <div class="bg-white border border-gray-200 p-4 space-y-3">
                                    <div>
                                        <h4 class="text-sm font-bold text-black">{{ $cycle['name'] }}</h4>
                                        <p class="text-xs text-gray-500">{{ $cycle['periodLabel'] }} Cycle ({{ $cycle['appraisalType'] }})</p>
                                    </div>
                                    
                                    <!-- Window Config Form -->
                                    <form action="{{ route('admin.cycle.window', $cycle['id']) }}" method="POST" class="space-y-2 pt-2 border-t border-gray-200">
                                        @csrf
                                        <div class="grid grid-cols-2 gap-2">
                                            <div>
                                                <label class="text-[9px] uppercase font-bold text-gray-500">Start Date</label>
                                                <input type="date" name="startDate" value="{{ date('Y-m-d', strtotime($cycle['startDate'])) }}"
                                                    class="block w-full border border-gray-300 py-1.5 px-2 text-xs text-black focus:outline-none focus:border-blue-500">
                                            </div>
                                            <div>
                                                <label class="text-[9px] uppercase font-bold text-gray-500">End Date</label>
                                                <input type="date" name="endDate" value="{{ date('Y-m-d', strtotime($cycle['endDate'])) }}"
                                                    class="block w-full border border-gray-300 py-1.5 px-2 text-xs text-black focus:outline-none focus:border-blue-500">
                                            </div>
                                        </div>
                                        <button type="submit" class="w-full border border-gray-300 bg-white hover:bg-gray-50 py-1.5 text-xs font-bold text-black transition-colors cursor-pointer">
                                            Update Window
                                        </button>
                                    </form>

                                    <!-- Enroll All Actions -->
                                    <form action="{{ route('admin.cycle.enrollAll', $cycle['id']) }}" method="POST" class="pt-1">
                                        @csrf
                                        <button type="submit" class="w-full bg-blue-500 hover:bg-blue-600 py-1.5 text-xs font-bold text-white transition-colors cursor-pointer flex items-center justify-center gap-2">
                                            <i data-lucide="user-plus" class="h-3.5 w-3.5"></i>
                                            Enroll All Employees
                                        </button>
                                    </form>
                                </div>
                            @endforeach
                        </div>
                    </div>

                    <!-- Individual Enrollment Assignment -->
                    <div class="bg-white border border-gray-200 p-6">
                        <h3 class="text-sm font-bold text-black uppercase tracking-wider flex items-center gap-2 mb-4">
                            <i data-lucide="user-check" class="h-4 w-4 text-blue-500"></i>
                            Assign to Cycle
                        </h3>
                        <form action="{{ route('admin.cycle.assign') }}" method="POST" class="space-y-4">
                            @csrf
                            <div>
                                <label for="employeeId" class="block text-xs font-semibold text-black">Select Employee</label>
                                <select id="employeeId" name="employeeId" required
                                    class="mt-1 block w-full border border-gray-300 py-2 px-3 text-black focus:outline-none focus:border-blue-500 text-sm">
                                    <option value="">-- Choose Employee --</option>
                                    @foreach($data['hrData']['allEmployees'] as $empOption)
                                        <option value="{{ $empOption['id'] }}">{{ $empOption['fullName'] }}</option>
                                    @endforeach
                                </select>
                            </div>

                            <div>
                                <label for="cycleId" class="block text-xs font-semibold text-black">Select Cycle</label>
                                <select id="cycleId" name="cycleId" required
                                    class="mt-1 block w-full border border-gray-300 py-2 px-3 text-black focus:outline-none focus:border-blue-500 text-sm">
                                    <option value="">-- Choose Cycle --</option>
                                    @foreach($data['hrData']['activeCycles'] as $cOption)
                                        <option value="{{ $cOption['id'] }}">{{ $cOption['name'] }}</option>
                                    @endforeach
                                </select>
                            </div>

                            <button type="submit" class="w-full bg-blue-500 hover:bg-blue-600 py-2 px-4 text-xs font-bold text-white transition-all cursor-pointer">
                                Assign Employee
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    @endif
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
                <span class="text-blue-500 font-bold mt-1 block text-sm" x-text="profileData.employeeCode || 'N/A'"></span>
            </div>
            <div class="col-span-2 md:col-span-3 min-w-0">
                <span class="text-gray-400 font-bold uppercase tracking-wider block font-sans">Email Address</span>
                <span class="text-black font-semibold mt-1 block text-sm break-all" x-text="profileData.email || 'N/A'"></span>
            </div>
            <div>
                <span class="text-gray-400 font-bold uppercase tracking-wider block font-sans">Department</span>
                <span class="text-black font-semibold mt-1 block text-sm" x-text="profileData.department || 'N/A'"></span>
            </div>
            <div class="col-span-2 md:col-span-1">
                <span class="text-gray-400 font-bold uppercase tracking-wider block font-sans">Designation</span>
                <span class="text-black font-semibold mt-1 block text-sm break-words" x-text="profileData.designation || 'N/A'"></span>
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
                <span class="text-gray-400 font-bold uppercase tracking-wider block font-sans">Last Promotion Date</span>
                <span class="text-black font-semibold mt-1 block text-sm" x-text="profileData.lastPromotionDate || 'N/A'"></span>
            </div>
            <div>
                <span class="text-gray-400 font-bold uppercase tracking-wider block font-sans">Experience in Company</span>
                <span class="text-black font-semibold mt-1 block text-sm" x-text="profileData.companyExperienceYears !== null ? parseFloat(profileData.companyExperienceYears).toFixed(1) + ' Years' : 'N/A'"></span>
            </div>
            <div>
                <span class="text-gray-400 font-bold uppercase tracking-wider block font-sans">Total Experience</span>
                <span class="text-black font-semibold mt-1 block text-sm" x-text="profileData.totalExperienceYears !== null ? parseFloat(profileData.totalExperienceYears).toFixed(1) + ' Years' : 'N/A'"></span>
            </div>
            @if(in_array($role, ['HR', 'BU_HEAD']))
                <div>
                    <span class="text-gray-400 font-bold uppercase tracking-wider block font-sans">Current CTC</span>
                    <span class="text-green-600 font-bold mt-1 block text-sm" x-text="profileData.salary !== null ? 'INR ' + parseFloat(profileData.salary).toLocaleString('en-IN') : 'N/A'"></span>
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
                        <td class="px-6 py-3 font-bold text-green-600 font-mono" x-text="hist.hikePercentage !== null ? hist.hikePercentage + '%' : '-'"></td>
                        <td class="px-6 py-3 text-center">
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-sm text-[10px] font-bold border"
                                  :class="{
                                      'bg-yellow-50 text-yellow-600 border-yellow-200': hist.status === 'DRAFT',
                                      'bg-orange-50 text-orange-600 border-orange-200': hist.status === 'SUBMITTED',
                                      'bg-blue-50 text-blue-600 border-blue-200': hist.status === 'MANAGER_REVIEW',
                                      'bg-black text-white border-black': hist.status === 'COMPLETED'
                                  }"
                                  x-text="hist.status">
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
