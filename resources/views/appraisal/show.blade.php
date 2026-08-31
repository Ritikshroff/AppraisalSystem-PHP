@extends('layouts.app')

@section('title', 'Appraisal Detail - AppraisalFlow')

@section('styles')
<style>
    .input-flat {
        background-color: #ffffff;
        border: 1px solid #d1d5db;
        color: #000000;
    }
    .input-flat:focus {
        border-color: #3b82f6;
        outline: none;
    }
</style>
@endsection

@section('content')
@php
    $perms = $appraisal['permissions'];
    $role = strtoupper($data['viewer']['role'] ?? '');
    $status = strtoupper($appraisal['status'] ?? '');
@endphp

<div class="space-y-8" x-data="appraisalForm()">
     
    <!-- Breadcrumb -->
    <nav class="flex items-center gap-2 text-xs text-gray-500">
        <a href="{{ route('dashboard') }}" class="hover:text-black hover:underline transition-colors">Dashboard</a>
        <i data-lucide="chevron-right" class="h-3 w-3"></i>
        <span class="text-black font-semibold">Appraisal details</span>
    </nav>

    <!-- Header Stats Ribbon -->
    <div class="bg-white border border-gray-200 p-6 flex flex-col lg:flex-row lg:items-center justify-between gap-6">
        <div class="space-y-1">
            <div class="flex items-center gap-3">
                <h1 class="text-2xl font-bold text-black">{{ $appraisal['employee']['fullName'] }}</h1>
                <span class="text-xs text-gray-500">({{ $appraisal['employee']['employeeCode'] }})</span>
            </div>
            <p class="text-sm text-gray-600">
                {{ $appraisal['cycle']['name'] }} &bull; {{ $appraisal['appraisalPeriod'] }}
                @if(in_array($role, ['HR', 'BU_HEAD']))
                    ({{ $appraisal['type'] }})
                @endif
            </p>
        </div>

        <div class="flex flex-wrap items-center gap-4">
            <!-- Info Status Badges -->
            <div class="flex flex-col items-end">
                <span class="text-[10px] text-gray-400 font-bold uppercase tracking-wider">Current Stage</span>
                <span class="mt-1 inline-flex items-center border border-blue-200 bg-blue-50 px-3 py-1 text-xs font-semibold text-blue-600">
                    {{ $perms['currentStageLabel'] }}
                </span>
            </div>

            <div class="h-8 w-px bg-gray-250"></div>

            <div class="flex flex-col items-end">
                <span class="text-[10px] text-gray-400 font-bold uppercase tracking-wider">Final rating</span>
                <span class="mt-1 text-lg font-bold text-black">
                    {{ $appraisal['finalRating'] !== null ? number_format($appraisal['finalRating'], 2) : 'N/A' }}
                </span>
            </div>

            @if($appraisal['hikePercentage'] !== null)
                <div class="h-8 w-px bg-gray-200"></div>
                <div class="flex flex-col items-end">
                    <span class="text-[10px] text-gray-400 font-bold uppercase tracking-wider">Hike Percentage</span>
                    <span class="mt-1 text-lg font-bold text-blue-500">
                        +{{ number_format($appraisal['hikePercentage'], 1) }}%
                    </span>
                </div>
            @endif
        </div>
    </div>

    <!-- Employee Metadata Grid -->
    <div class="bg-white border border-gray-200 p-6 grid grid-cols-2 md:grid-cols-4 gap-6 text-xs">
        <div>
            <span class="text-gray-400 font-bold uppercase tracking-wider block font-sans">Employee Name</span>
            <span class="text-black font-semibold mt-1 block text-sm">{{ $appraisal['employee']['fullName'] }}</span>
        </div>
        <div>
            <span class="text-gray-400 font-bold uppercase tracking-wider block font-sans">Designation</span>
            <span class="text-black font-semibold mt-1 block text-sm">{{ $appraisal['employee']['designation'] ?? 'N/A' }}</span>
        </div>
        <div>
            <span class="text-gray-400 font-bold uppercase tracking-wider block font-sans">Grade</span>
            <span class="text-black font-semibold mt-1 block text-sm font-mono">{{ $appraisal['employee']['grade'] ?? 'N/A' }}</span>
        </div>
        <div>
            <span class="text-gray-400 font-bold uppercase tracking-wider block font-sans">Department</span>
            <span class="text-black font-semibold mt-1 block text-sm">{{ $appraisal['employee']['department'] ?? 'N/A' }}</span>
        </div>
        <div>
            <span class="text-gray-400 font-bold uppercase tracking-wider block font-sans">Date of Joining</span>
            <span class="text-black font-semibold mt-1 block text-sm">
                {{ $appraisal['employee']['doj'] ? \Carbon\Carbon::parse($appraisal['employee']['doj'])->format('M d, Y') : 'N/A' }}
            </span>
        </div>
        <div>
            <span class="text-gray-400 font-bold uppercase tracking-wider block font-sans">Experience in Company</span>
            <span class="text-black font-semibold mt-1 block text-sm">
                {{ $appraisal['employee']['companyExperienceYears'] !== null ? number_format($appraisal['employee']['companyExperienceYears'], 1) . ' years' : 'N/A' }}
            </span>
        </div>
        <div>
            <span class="text-gray-400 font-bold uppercase tracking-wider block font-sans">Total Experience</span>
            <span class="text-black font-semibold mt-1 block text-sm">
                {{ $appraisal['employee']['totalExperienceYears'] !== null ? number_format($appraisal['employee']['totalExperienceYears'], 1) . ' years' : 'N/A' }}
            </span>
        </div>
        <div>
            <span class="text-gray-400 font-bold uppercase tracking-wider block font-sans">Last Promotion Date</span>
            <span class="text-black font-semibold mt-1 block text-sm">
                {{ $appraisal['employee']['lastPromotionDate'] ? \Carbon\Carbon::parse($appraisal['employee']['lastPromotionDate'])->format('M d, Y') : 'N/A' }}
            </span>
        </div>
        <div>
            <span class="text-gray-400 font-bold uppercase tracking-wider block font-sans">Appraisal Type</span>
            <span class="text-blue-500 font-bold mt-1 block text-sm font-mono">{{ $appraisal['type'] }}</span>
        </div>
        <div>
            <span class="text-gray-400 font-bold uppercase tracking-wider block font-sans">Appraiser</span>
            <span class="text-black font-semibold mt-1 block text-sm">{{ $appraisal['manager']['fullName'] ?? 'N/A' }}</span>
        </div>
        <div>
            <span class="text-gray-400 font-bold uppercase tracking-wider block font-sans">Reviewer</span>
            <span class="text-black font-semibold mt-1 block text-sm">{{ $appraisal['buHead']['fullName'] ?? 'N/A' }}</span>
        </div>
        @if(in_array($role, ['HR', 'BU_HEAD']))
            <div>
                <span class="text-gray-400 font-bold uppercase tracking-wider block font-sans">Current CTC</span>
                <span class="text-green-600 font-bold mt-1 block text-sm">
                    {{ $appraisal['employee']['salary'] !== null ? 'INR ' . number_format($appraisal['employee']['salary']) : 'N/A' }}
                </span>
            </div>
        @endif
    </div>

    <!-- Mutate Form Wrapping All Input Elements -->
    <form action="" method="POST" class="space-y-8">
        @csrf
        
        <!-- Save and Submit buttons sticking to top right when editing -->
        @if($perms['canSave'] && $status !== 'COMPLETED')
            <div class="flex items-center justify-end gap-3 bg-white border border-gray-200 p-4 sticky top-0 z-30">
                <p class="text-xs text-gray-500 mr-auto hidden md:block">
                    You have active edit permissions for this stage. Remember to save or submit.
                </p>
                <button type="submit" formaction="{{ route('appraisals.save', $appraisal['id']) }}"
                    class="border border-gray-300 bg-white px-4 py-2 text-xs font-bold text-black hover:bg-gray-50 transition-colors cursor-pointer">
                    Save Draft
                </button>
                @if($perms['canSubmit'])
                    <button type="submit" formaction="{{ route('appraisals.submit', $appraisal['id']) }}"
                        :disabled="totalWeightage !== 100 && '{{ $role }}' === 'EMPLOYEE'"
                        :class="(totalWeightage !== 100 && '{{ $role }}' === 'EMPLOYEE') ? 'opacity-50 cursor-not-allowed' : ''"
                        class="bg-blue-500 hover:bg-blue-600 px-4 py-2 text-xs font-bold text-white transition-colors cursor-pointer">
                        {{ $perms['nextActionLabel'] }}
                    </button>
                @endif
            </div>
            
            <!-- Warning weightage banner for Employees -->
            @if($role === 'EMPLOYEE')
                <div class="border border-red-500 bg-red-50 p-4 text-red-800 text-sm flex items-center gap-3" x-show="totalWeightage !== 100">
                    <i data-lucide="alert-triangle" class="h-5 w-5 shrink-0 text-red-600"></i>
                    <p><strong>Validation Notice:</strong> KRA total weightage must equal exactly <strong>100%</strong> before you can submit. Current: <span class="font-bold" x-text="totalWeightage + '%'"></span></p>
                </div>
            @endif
        @endif

        <!-- AI insights Summary Block if complete -->
        @if($status === 'COMPLETED' && $appraisal['aiSummary'])
            <div class="bg-white border-l-4 border-blue-500 border-y border-r border-gray-200 p-6">
                <h3 class="text-sm font-bold text-black uppercase tracking-wider flex items-center gap-2 mb-4">
                    <i data-lucide="cpu" class="h-4 w-4 text-blue-500"></i>
                    AI Appraisal Feedback Summary
                </h3>
                <p class="text-sm leading-7 text-gray-700">{{ $appraisal['aiSummary'] }}</p>

                <!-- Calibrated DNA lists -->
                <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mt-6 pt-6 border-t border-gray-200">
                    <div>
                        <h4 class="text-xs font-bold uppercase tracking-wider text-blue-500 mb-3">Top Strengths</h4>
                        <ul class="space-y-2">
                            @foreach($appraisal['strengths'] as $str)
                                <li class="text-xs text-black flex items-center gap-2">
                                    <i data-lucide="check-circle-2" class="h-4 w-4 text-blue-500 shrink-0"></i>
                                    {{ $str }}
                                </li>
                            @endforeach
                        </ul>
                    </div>

                    <div>
                        <h4 class="text-xs font-bold uppercase tracking-wider text-gray-600 mb-3">Development Areas</h4>
                        <ul class="space-y-2">
                            @foreach($appraisal['weaknesses'] as $weak)
                                <li class="text-xs text-black flex items-center gap-2">
                                    <i data-lucide="help-circle" class="h-4 w-4 text-gray-500 shrink-0"></i>
                                    {{ $weak }}
                                </li>
                            @endforeach
                        </ul>
                    </div>

                    <div>
                        <h4 class="text-xs font-bold uppercase tracking-wider text-black mb-3">Risk Signals</h4>
                        <ul class="space-y-2">
                            @foreach($appraisal['riskSignals'] as $risk)
                                <li class="text-xs text-black flex items-center gap-2">
                                    <i data-lucide="alert-circle" class="h-4 w-4 text-black shrink-0"></i>
                                    {{ $risk }}
                                </li>
                            @endforeach
                        </ul>
                    </div>
                </div>
            </div>
        @endif

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
            <!-- Left 2 Cols: Main Sections -->
            <div class="lg:col-span-2 space-y-8">
                
                <!-- Section 1: Self Appraisal Q&A -->
                <div class="bg-white border border-gray-200 p-6 space-y-6">
                    <h3 class="text-sm font-bold text-black uppercase tracking-wider flex items-center gap-2">
                        <i data-lucide="user-edit" class="h-4 w-4 text-blue-500"></i>
                        Self Appraisal Q&A
                    </h3>
                    
                    <div class="space-y-5">
                        @foreach($appraisal['sectionOneAnswers'] as $index => $qa)
                            <div class="space-y-2">
                                <label class="block text-xs font-bold text-black">
                                    {{ $index + 1 }}. {{ $qa['question'] }}
                                </label>
                                <input type="hidden" name="sectionOneAnswers[{{ $index }}][question]" value="{{ $qa['question'] }}">
                                @if($perms['canEditEmployeeSection'])
                                    <textarea name="sectionOneAnswers[{{ $index }}][answer]"
                                        class="block w-full p-3 text-sm input-flat focus:border-blue-500 placeholder:text-gray-350 min-h-[120px] resize-none overflow-y-auto"
                                        placeholder="Type your detailed response here (no word limit)...">{{ $qa['answer'] }}</textarea>
                                @else
                                    <div class="bg-gray-50 border border-gray-200 p-4 text-sm text-black leading-relaxed max-h-60 overflow-y-auto whitespace-pre-wrap">
                                        {{ $qa['answer'] ?: 'No response provided.' }}
                                    </div>
                                @endif
                            </div>
                        @endforeach
                    </div>
                </div>

                <!-- Section 2: Key Responsibility Areas (KRAs) -->
                <div class="bg-white border border-gray-200 p-6 space-y-6">
                    <div class="flex justify-between items-center">
                        <h3 class="text-sm font-bold text-black uppercase tracking-wider flex items-center gap-2">
                            <i data-lucide="target" class="h-4 w-4 text-blue-500"></i>
                            Key Responsibility Areas (KRAs)
                        </h3>
                        @if($perms['canEditEmployeeSection'] && $role !== 'EMPLOYEE')
                            <button type="button" @click="addKra()"
                                class="border border-gray-300 bg-white hover:bg-gray-50 px-3 py-1 text-xs font-bold text-black transition-colors cursor-pointer">
                                + Add KRA
                            </button>
                        @endif
                    </div>

                    <!-- Dynamic KRA Rows Table -->
                    <div class="overflow-x-auto border border-gray-200">
                        <table class="w-full text-left text-sm text-black">
                            <thead class="bg-gray-50 text-[10px] uppercase tracking-wider text-gray-500 border-b border-gray-200 font-bold font-sans">
                                <tr>
                                    <th class="px-4 py-3">Objective / KRA / KPI</th>
                                    <th class="px-4 py-3 w-24 text-center">Weight %</th>
                                    <th class="px-4 py-3 w-32 text-center">Rating — Appraisee</th>
                                    <th class="px-4 py-3">Comment — Appraisee</th>
                                    <th class="px-4 py-3 w-32 text-center">Rating — Appraiser</th>
                                    <th class="px-4 py-3">Comment — Appraiser</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-200">
                                <template x-for="(kra, index) in kras" :key="index">
                                    <tr class="hover:bg-gray-50/50">
                                        <!-- Objective Column -->
                                        <td class="px-4 py-3">
                                            <input type="hidden" :name="'kras[' + index + '][id]'" :value="kra.id">
                                            <template x-if="'{{ $role }}' === 'EMPLOYEE' || !{{ $perms['canEditEmployeeSection'] ? 'true' : 'false' }}">
                                                <div>
                                                    <span class="text-xs font-semibold text-black" x-text="kra.objective"></span>
                                                    <input type="hidden" :name="'kras[' + index + '][objective]'" :value="kra.objective">
                                                </div>
                                            </template>
                                            <template x-if="'{{ $role }}' !== 'EMPLOYEE' && {{ $perms['canEditEmployeeSection'] ? 'true' : 'false' }}">
                                                <input type="text" :name="'kras[' + index + '][objective]'" x-model="kra.objective" required
                                                    class="block w-full py-1.5 px-2 text-xs input-flat focus:border-blue-500">
                                            </template>
                                        </td>
                                        <!-- Weightage Column -->
                                        <td class="px-4 py-3 text-center">
                                            <template x-if="'{{ $role }}' === 'EMPLOYEE' || !{{ $perms['canEditEmployeeSection'] ? 'true' : 'false' }}">
                                                <div>
                                                    <span class="text-xs font-bold text-gray-700" x-text="kra.weightage + '%'"></span>
                                                    <input type="hidden" :name="'kras[' + index + '][weightage]'" :value="kra.weightage">
                                                </div>
                                            </template>
                                            <template x-if="'{{ $role }}' !== 'EMPLOYEE' && {{ $perms['canEditEmployeeSection'] ? 'true' : 'false' }}">
                                                <input type="number" min="0" max="100" step="1" :name="'kras[' + index + '][weightage]'" x-model="kra.weightage" required
                                                    class="block w-full text-center py-1.5 px-2 text-xs input-flat focus:border-blue-500">
                                            </template>
                                        </td>
                                        <!-- Appraisee Rating Column (Dropdown for Employee, text for others) -->
                                        <td class="px-4 py-3 text-center">
                                            <template x-if="{{ $perms['canEditEmployeeSection'] ? 'true' : 'false' }}">
                                                <select :name="'kras[' + index + '][appraiseeRating]'" x-model="kra.appraiseeRating" required
                                                    class="block w-full py-1.5 px-2 text-xs input-flat focus:border-blue-500 bg-white text-center">
                                                    <option value="">Select</option>
                                                    <option value="A+">A+</option>
                                                    <option value="A">A</option>
                                                    <option value="B+">B+</option>
                                                    <option value="B">B</option>
                                                    <option value="C">C</option>
                                                    <option value="D">D</option>
                                                </select>
                                            </template>
                                            <template x-if="!{{ $perms['canEditEmployeeSection'] ? 'true' : 'false' }}">
                                                <div>
                                                    <span class="text-xs font-bold text-blue-500" x-text="kra.appraiseeRating || '-'"></span>
                                                    <input type="hidden" :name="'kras[' + index + '][appraiseeRating]'" :value="kra.appraiseeRating">
                                                </div>
                                            </template>
                                        </td>
                                        <!-- Appraisee Comment Column (Editable text for Employee, text for others) -->
                                        <td class="px-4 py-3">
                                            <template x-if="{{ $perms['canEditEmployeeSection'] ? 'true' : 'false' }}">
                                                <input type="text" :name="'kras[' + index + '][appraiseeComment]'" x-model="kra.appraiseeComment" required
                                                    placeholder="Enter self-comment..."
                                                    class="block w-full py-1.5 px-2 text-xs input-flat focus:border-blue-500 bg-white">
                                            </template>
                                            <template x-if="!{{ $perms['canEditEmployeeSection'] ? 'true' : 'false' }}">
                                                <div>
                                                    <span class="text-xs text-gray-700 whitespace-pre-wrap" x-text="kra.appraiseeComment || '-'"></span>
                                                    <input type="hidden" :name="'kras[' + index + '][appraiseeComment]'" :value="kra.appraiseeComment">
                                                </div>
                                            </template>
                                        </td>
                                        <!-- Appraiser Rating Column -->
                                        <td class="px-4 py-3 text-center">
                                            @if($status === 'DRAFT')
                                                <!-- Locked/Later stage -->
                                                <span class="inline-flex items-center gap-1 bg-gray-100 text-gray-400 text-[10px] px-2 py-0.5 font-bold uppercase tracking-wider rounded font-sans">
                                                    <i data-lucide="lock" class="h-3 w-3"></i> Later
                                                </span>
                                            @else
                                                <template x-if="{{ $perms['canEditManagerSection'] ? 'true' : 'false' }}">
                                                    <select :name="'kras[' + index + '][appraiserRating]'" x-model="kra.appraiserRating" required
                                                        class="block w-full py-1.5 px-2 text-xs input-flat focus:border-blue-500 bg-white text-center">
                                                        <option value="">Select</option>
                                                        <option value="A+">A+</option>
                                                        <option value="A">A</option>
                                                        <option value="B+">B+</option>
                                                        <option value="B">B</option>
                                                        <option value="C">C</option>
                                                        <option value="D">D</option>
                                                    </select>
                                                </template>
                                                <template x-if="!{{ $perms['canEditManagerSection'] ? 'true' : 'false' }}">
                                                    <div>
                                                        <span class="text-xs font-bold text-gray-750" x-text="kra.appraiserRating || '-'"></span>
                                                        <input type="hidden" :name="'kras[' + index + '][appraiserRating]'" :value="kra.appraiserRating">
                                                    </div>
                                                </template>
                                            @endif
                                        </td>
                                        <!-- Appraiser Comment Column -->
                                        <td class="px-4 py-3">
                                            @if($status === 'DRAFT')
                                                <!-- Locked/Later stage -->
                                                <span class="inline-flex items-center gap-1 bg-gray-100 text-gray-400 text-[10px] px-2 py-0.5 font-bold uppercase tracking-wider rounded font-sans">
                                                    <i data-lucide="lock" class="h-3 w-3"></i> Later
                                                </span>
                                            @else
                                                <template x-if="{{ $perms['canEditManagerSection'] ? 'true' : 'false' }}">
                                                    <input type="text" :name="'kras[' + index + '][comments]'" x-model="kra.comments" required
                                                        placeholder="Enter manager comment..."
                                                        class="block w-full py-1.5 px-2 text-xs input-flat focus:border-blue-500">
                                                </template>
                                                <template x-if="!{{ $perms['canEditManagerSection'] ? 'true' : 'false' }}">
                                                    <div>
                                                        <span class="text-xs text-gray-600 italic whitespace-pre-wrap" x-text="kra.comments || '-'"></span>
                                                        <input type="hidden" :name="'kras[' + index + '][comments]'" :value="kra.comments">
                                                    </div>
                                                </template>
                                            @endif
                                        </td>
                                    </tr>
                                </template>
                            </tbody>
                            <tfoot class="border-t border-gray-200 bg-gray-50 font-bold text-black font-mono">
                                <tr>
                                    <td class="px-4 py-3 text-xs">Totals & Averages</td>
                                    <td class="px-4 py-3 text-center text-xs" :class="totalWeightage === 100 ? 'text-black' : 'text-red-600'" x-text="totalWeightage + '%'"></td>
                                    <td class="px-4 py-3 text-center text-xs text-blue-500" x-text="appraiseeKraAverage"></td>
                                    <td class="px-4 py-3"></td>
                                    <td class="px-4 py-3 text-center text-xs text-gray-700" x-text="appraiserKraAverage"></td>
                                    <td class="px-4 py-3"></td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                </div>

                <!-- Section 4: Capability / Competency Assessment -->
                <div class="bg-white border border-gray-200 p-6 space-y-5">
                    <!-- Section Header -->
                    <div>
                        <div class="flex items-center gap-2 mb-1">
                            <span class="inline-flex items-center justify-center w-6 h-6 rounded-full bg-blue-500 text-white text-[10px] font-bold">4</span>
                            <h3 class="text-sm font-bold text-black uppercase tracking-wider">Capability / Competency Assessment</h3>
                        </div>
                        <p class="text-xs text-gray-500 pl-8">Appraisee and Appraiser score each capability for current role requirements.</p>
                        <!-- Rating Legend with initials -->
                        <div class="mt-3 pl-8 flex flex-wrap items-center gap-3">
                            <span class="inline-flex items-center gap-1.5">
                                <span class="inline-flex items-center justify-center w-5 h-5 rounded bg-red-100 text-red-600 text-[11px] font-black">P</span>
                                <span class="text-[10px] text-gray-500 font-semibold">Poor <span class="text-gray-400">(1–3)</span></span>
                            </span>
                            <span class="text-gray-300">|</span>
                            <span class="inline-flex items-center gap-1.5">
                                <span class="inline-flex items-center justify-center w-5 h-5 rounded bg-yellow-100 text-yellow-700 text-[11px] font-black">S</span>
                                <span class="text-[10px] text-gray-500 font-semibold">Satisfactory <span class="text-gray-400">(4–6)</span></span>
                            </span>
                            <span class="text-gray-300">|</span>
                            <span class="inline-flex items-center gap-1.5">
                                <span class="inline-flex items-center justify-center w-5 h-5 rounded bg-green-100 text-green-700 text-[11px] font-black">G</span>
                                <span class="text-[10px] text-gray-500 font-semibold">Good <span class="text-gray-400">(7–9)</span></span>
                            </span>
                            <span class="text-gray-300">|</span>
                            <span class="inline-flex items-center gap-1.5">
                                <span class="inline-flex items-center justify-center w-5 h-5 rounded bg-blue-100 text-blue-700 text-[11px] font-black">E</span>
                                <span class="text-[10px] text-gray-500 font-semibold">Excellent <span class="text-gray-400">(10)</span></span>
                            </span>
                        </div>
                    </div>

                    <!-- Competency Table -->
                    <div class="overflow-x-auto border border-gray-200">
                        <table class="w-full text-left text-sm text-black">
                            <thead class="bg-gray-50 text-[10px] uppercase tracking-wider text-gray-500 border-b border-gray-200 font-bold font-sans">
                                <tr>
                                    <th class="px-3 py-3 w-8 text-center">#</th>
                                    <th class="px-4 py-3">Competency Area</th>
                                    <th class="px-4 py-3 w-36 text-center">Appraisee</th>
                                    <th class="px-4 py-3 w-36 text-center">Appraiser</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-200">
                                <template x-for="(comp, index) in competencies" :key="index">
                                    <tr class="hover:bg-gray-50/50">
                                        <td class="px-3 py-3 text-xs text-gray-400 font-bold text-center" x-text="index + 1"></td>
                                        <td class="px-4 py-3">
                                            <input type="hidden" :name="'competencyRatings[' + index + '][competencyName]'" :value="comp.competencyName">
                                            <span class="text-xs font-semibold text-black" x-text="comp.competencyName"></span>
                                        </td>

                                        <!-- Appraisee Score: number + fixed-width initial badge -->
                                        <td class="px-4 py-3">
                                            @if($perms['canEditEmployeeSection'])
                                                <div class="flex items-center justify-center gap-2">
                                                    <select :name="'competencyRatings[' + index + '][employeeScore]'" x-model="comp.employeeScore"
                                                        class="w-16 py-1.5 px-2 text-xs input-flat focus:border-blue-500 bg-white text-center font-bold">
                                                        <option value="">—</option>
                                                        @for($s = 1; $s <= 10; $s++)
                                                            <option value="{{ $s }}">{{ $s }}</option>
                                                        @endfor
                                                    </select>
                                                    <span class="inline-flex items-center justify-center w-6 h-6 rounded text-[11px] font-black flex-shrink-0"
                                                        :class="{
                                                            'bg-red-100 text-red-600':       comp.employeeScore >= 1 && comp.employeeScore <= 3,
                                                            'bg-yellow-100 text-yellow-700': comp.employeeScore >= 4 && comp.employeeScore <= 6,
                                                            'bg-green-100 text-green-700':   comp.employeeScore >= 7 && comp.employeeScore <= 9,
                                                            'bg-blue-100 text-blue-700':     comp.employeeScore == 10,
                                                            'bg-gray-100 text-gray-300':     !comp.employeeScore
                                                        }"
                                                        x-text="comp.employeeScore >= 1 && comp.employeeScore <= 3 ? 'P' : (comp.employeeScore >= 4 && comp.employeeScore <= 6 ? 'S' : (comp.employeeScore >= 7 && comp.employeeScore <= 9 ? 'G' : (comp.employeeScore == 10 ? 'E' : '—')))"
                                                    ></span>
                                                </div>
                                            @else
                                                <input type="hidden" :name="'competencyRatings[' + index + '][employeeScore]'" :value="comp.employeeScore">
                                                <div class="flex items-center justify-center gap-2">
                                                    <span class="text-sm font-extrabold w-6 text-center flex-shrink-0"
                                                        :class="{
                                                            'text-red-500':    comp.employeeScore >= 1 && comp.employeeScore <= 3,
                                                            'text-yellow-600': comp.employeeScore >= 4 && comp.employeeScore <= 6,
                                                            'text-green-600':  comp.employeeScore >= 7 && comp.employeeScore <= 9,
                                                            'text-blue-600':   comp.employeeScore == 10,
                                                            'text-gray-300':   !comp.employeeScore
                                                        }"
                                                        x-text="comp.employeeScore || '—'"
                                                    ></span>
                                                    <span class="inline-flex items-center justify-center w-6 h-6 rounded text-[11px] font-black flex-shrink-0"
                                                        :class="{
                                                            'bg-red-100 text-red-600':       comp.employeeScore >= 1 && comp.employeeScore <= 3,
                                                            'bg-yellow-100 text-yellow-700': comp.employeeScore >= 4 && comp.employeeScore <= 6,
                                                            'bg-green-100 text-green-700':   comp.employeeScore >= 7 && comp.employeeScore <= 9,
                                                            'bg-blue-100 text-blue-700':     comp.employeeScore == 10,
                                                            'bg-gray-100 text-gray-300':     !comp.employeeScore
                                                        }"
                                                        x-text="comp.employeeScore >= 1 && comp.employeeScore <= 3 ? 'P' : (comp.employeeScore >= 4 && comp.employeeScore <= 6 ? 'S' : (comp.employeeScore >= 7 && comp.employeeScore <= 9 ? 'G' : (comp.employeeScore == 10 ? 'E' : '—')))"
                                                    ></span>
                                                </div>
                                            @endif
                                        </td>

                                        <!-- Appraiser Score: number + fixed-width initial badge -->
                                        <td class="px-4 py-3">
                                            @if($status === 'DRAFT')
                                                <div class="flex items-center justify-center">
                                                    <span class="inline-flex items-center gap-1 bg-gray-100 text-gray-400 text-[10px] px-2 py-1 font-bold uppercase tracking-wider font-sans">
                                                        <i data-lucide="lock" class="h-3 w-3"></i> Later
                                                    </span>
                                                </div>
                                            @elseif($perms['canEditManagerSection'])
                                                <div class="flex items-center justify-center gap-2">
                                                    <select :name="'competencyRatings[' + index + '][appraiserScore]'" x-model="comp.appraiserScore"
                                                        class="w-16 py-1.5 px-2 text-xs input-flat focus:border-blue-500 bg-white text-center font-bold">
                                                        <option value="">—</option>
                                                        @for($s = 1; $s <= 10; $s++)
                                                            <option value="{{ $s }}">{{ $s }}</option>
                                                        @endfor
                                                    </select>
                                                    <span class="inline-flex items-center justify-center w-6 h-6 rounded text-[11px] font-black flex-shrink-0"
                                                        :class="{
                                                            'bg-red-100 text-red-600':       comp.appraiserScore >= 1 && comp.appraiserScore <= 3,
                                                            'bg-yellow-100 text-yellow-700': comp.appraiserScore >= 4 && comp.appraiserScore <= 6,
                                                            'bg-green-100 text-green-700':   comp.appraiserScore >= 7 && comp.appraiserScore <= 9,
                                                            'bg-blue-100 text-blue-700':     comp.appraiserScore == 10,
                                                            'bg-gray-100 text-gray-300':     !comp.appraiserScore
                                                        }"
                                                        x-text="comp.appraiserScore >= 1 && comp.appraiserScore <= 3 ? 'P' : (comp.appraiserScore >= 4 && comp.appraiserScore <= 6 ? 'S' : (comp.appraiserScore >= 7 && comp.appraiserScore <= 9 ? 'G' : (comp.appraiserScore == 10 ? 'E' : '—')))"
                                                    ></span>
                                                </div>
                                            @else
                                                <input type="hidden" :name="'competencyRatings[' + index + '][appraiserScore]'" :value="comp.appraiserScore">
                                                <div class="flex items-center justify-center gap-2">
                                                    <span class="text-sm font-extrabold w-6 text-center flex-shrink-0"
                                                        :class="{
                                                            'text-red-500':    comp.appraiserScore >= 1 && comp.appraiserScore <= 3,
                                                            'text-yellow-600': comp.appraiserScore >= 4 && comp.appraiserScore <= 6,
                                                            'text-green-600':  comp.appraiserScore >= 7 && comp.appraiserScore <= 9,
                                                            'text-blue-600':   comp.appraiserScore == 10,
                                                            'text-gray-300':   !comp.appraiserScore
                                                        }"
                                                        x-text="comp.appraiserScore || '—'"
                                                    ></span>
                                                    <span class="inline-flex items-center justify-center w-6 h-6 rounded text-[11px] font-black flex-shrink-0"
                                                        :class="{
                                                            'bg-red-100 text-red-600':       comp.appraiserScore >= 1 && comp.appraiserScore <= 3,
                                                            'bg-yellow-100 text-yellow-700': comp.appraiserScore >= 4 && comp.appraiserScore <= 6,
                                                            'bg-green-100 text-green-700':   comp.appraiserScore >= 7 && comp.appraiserScore <= 9,
                                                            'bg-blue-100 text-blue-700':     comp.appraiserScore == 10,
                                                            'bg-gray-100 text-gray-300':     !comp.appraiserScore
                                                        }"
                                                        x-text="comp.appraiserScore >= 1 && comp.appraiserScore <= 3 ? 'P' : (comp.appraiserScore >= 4 && comp.appraiserScore <= 6 ? 'S' : (comp.appraiserScore >= 7 && comp.appraiserScore <= 9 ? 'G' : (comp.appraiserScore == 10 ? 'E' : '—')))"
                                                    ></span>
                                                </div>
                                            @endif
                                        </td>
                                    </tr>
                                </template>
                            </tbody>
                            <!-- Footer averages -->
                            <tfoot class="border-t-2 border-gray-200 bg-gray-50">
                                <tr>
                                    <td colspan="2" class="px-4 py-3 text-xs text-gray-500 font-bold uppercase tracking-wider">Average Score</td>
                                    <td class="px-4 py-3 text-center">
                                        <span class="text-base font-extrabold text-blue-600" x-text="competencyEmployeeAvg"></span>
                                    </td>
                                    <td class="px-4 py-3 text-center">
                                        <span class="text-base font-extrabold text-green-700" x-text="competencyAppraiserAvg"></span>
                                    </td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                </div>

            </div>

            <!-- Right 1 Col: Appraiser, Reviewer, Special Appeal -->
            <div class="space-y-6">

                <!-- Section 5: Appraiser Section (To be filled by Appraiser) -->
                @php
                    $showAppraiserSection = $perms['canEditManagerSection']
                        || !empty($appraisal['appraiserSection']['recommendation'])
                        || $appraisal['appraiserSection']['overallRating'] !== null;
                @endphp
                @if($showAppraiserSection || $role !== 'EMPLOYEE')
                    <div class="bg-white border-l-4 border-blue-500 border-y border-r border-gray-200 p-5 space-y-4">
                        <!-- Header -->
                        <div>
                            <div class="flex items-center gap-2 mb-0.5">
                                <span class="inline-flex items-center justify-center w-6 h-6 rounded-full bg-blue-500 text-white text-[10px] font-bold">5</span>
                                <h3 class="text-sm font-bold text-black uppercase tracking-wider">Appraiser Section</h3>
                            </div>
                            <p class="text-[11px] text-gray-500 pl-8">
                                @if($perms['canEditManagerSection'])
                                    Fill your overall assessment and recommendation below.
                                @else
                                    To be filled by Appraiser.
                                @endif
                            </p>
                        </div>

                        @if(!$perms['canEditManagerSection'] && $appraisal['appraiserSection']['overallRating'] === null && empty($appraisal['appraiserSection']['recommendation']))
                            <div class="flex items-center gap-2 py-3 text-xs text-gray-400 italic">
                                <i data-lucide="clock" class="h-4 w-4"></i>
                                Awaiting Appraiser review.
                            </div>
                        @else
                            <div class="space-y-4">
                                <!-- Overall Rating -->
                                <div>
                                    <label for="appraiser_overall_rating" class="block text-xs font-bold text-black">Overall Rating (1–10)</label>
                                    @if($perms['canEditManagerSection'])
                                        <input id="appraiser_overall_rating" name="appraiserSection[overallRating]" type="number" min="1" max="10" step="0.1" required
                                            value="{{ $appraisal['appraiserSection']['overallRating'] }}"
                                            class="mt-1 block w-full py-2 px-3 text-sm input-flat focus:border-blue-500 text-center font-bold">
                                    @else
                                        <p class="mt-1 text-xl font-extrabold text-blue-500">
                                            {{ $appraisal['appraiserSection']['overallRating'] !== null ? number_format($appraisal['appraiserSection']['overallRating'], 2) : 'N/A' }}
                                        </p>
                                    @endif
                                </div>

                                <!-- Final Recommendation -->
                                <div>
                                    <label for="appraiser_recommendation" class="block text-xs font-bold text-black">Final Recommendation</label>
                                    @if($perms['canEditManagerSection'])
                                        <textarea id="appraiser_recommendation" name="appraiserSection[recommendation]" rows="4" required
                                            class="mt-1 block w-full p-3 text-sm input-flat focus:border-blue-500 placeholder:text-gray-300 resize-none"
                                            placeholder="Write your overall recommendation and performance assessment...">{{ $appraisal['appraiserSection']['recommendation'] }}</textarea>
                                    @else
                                        <div class="mt-1 bg-gray-50 border border-gray-200 p-3 text-xs text-gray-700 leading-relaxed">
                                            {{ $appraisal['appraiserSection']['recommendation'] ?: 'Not yet filled.' }}
                                        </div>
                                    @endif
                                </div>

                                <!-- New KRA/KPI Notes -->
                                <div>
                                    <label for="appraiser_new_kra" class="block text-xs font-bold text-black">New KRA/KPI Notes <span class="font-normal text-gray-400">(if any)</span></label>
                                    @if($perms['canEditManagerSection'])
                                        <textarea id="appraiser_new_kra" name="appraiserSection[newKraNotes]" rows="3"
                                            class="mt-1 block w-full p-3 text-sm input-flat focus:border-blue-500 placeholder:text-gray-300 resize-none"
                                            placeholder="Mention any new KRA/KPI targets for the next cycle...">{{ $appraisal['appraiserSection']['newKraNotes'] }}</textarea>
                                    @else
                                        <div class="mt-1 bg-gray-50 border border-gray-200 p-3 text-xs text-gray-600 leading-relaxed italic">
                                            {{ $appraisal['appraiserSection']['newKraNotes'] ?: 'None specified.' }}
                                        </div>
                                    @endif
                                </div>

                                <!-- Appraiser Signature -->
                                @if($appraisal['appraiserSignedAt'])
                                    <div class="border-t border-gray-100 pt-3">
                                        <div class="flex items-center gap-2">
                                            <i data-lucide="check-circle-2" class="h-4 w-4 text-green-600 shrink-0"></i>
                                            <div>
                                                <p class="text-[10px] font-bold uppercase tracking-wider text-gray-500">Appraiser Signed</p>
                                                <p class="text-xs font-semibold text-green-700">
                                                    {{ \Carbon\Carbon::parse($appraisal['appraiserSignedAt'])->format('M d, Y — H:i') }}
                                                </p>
                                            </div>
                                        </div>
                                    </div>
                                @endif
                            </div>
                        @endif
                    </div>
                @endif

                <!-- Section 6: Reviewer Section (To be filled by BU Head / Reviewer) -->
                @php
                    $showReviewerSection = $perms['canEditBUHeadSection']
                        || !empty($appraisal['reviewerSection']['comments'])
                        || $appraisal['reviewerSection']['rating'] !== null
                        || $appraisal['buHeadReview']['finalRating'] !== null;
                @endphp
                @if($showReviewerSection || $role !== 'EMPLOYEE')
                    <div class="bg-white border-l-4 border-gray-800 border-y border-r border-gray-200 p-5 space-y-4">
                        <!-- Header -->
                        <div>
                            <div class="flex items-center gap-2 mb-0.5">
                                <span class="inline-flex items-center justify-center w-6 h-6 rounded-full bg-gray-800 text-white text-[10px] font-bold">6</span>
                                <h3 class="text-sm font-bold text-black uppercase tracking-wider">Reviewer Section</h3>
                            </div>
                            <p class="text-[11px] text-gray-500 pl-8">
                                @if($perms['canEditBUHeadSection'])
                                    Complete the final review and rating below.
                                @else
                                    To be filled by Reviewer / BU Head.
                                @endif
                            </p>
                        </div>

                        @if(!$perms['canEditBUHeadSection'] && $appraisal['reviewerSection']['rating'] === null && empty($appraisal['reviewerSection']['comments']))
                            <div class="flex items-center gap-2 py-3 text-xs text-gray-400 italic">
                                <i data-lucide="clock" class="h-4 w-4"></i>
                                Awaiting Reviewer.
                            </div>
                        @else
                            <div class="space-y-4">
                                <!-- Reviewer Comments -->
                                <div>
                                    <label for="reviewer_comments" class="block text-xs font-bold text-black">Reviewer Comments</label>
                                    @if($perms['canEditBUHeadSection'])
                                        <textarea id="reviewer_comments" name="reviewerSection[comments]" rows="4"
                                            class="mt-1 block w-full p-3 text-sm input-flat focus:border-blue-500 placeholder:text-gray-300 resize-none"
                                            placeholder="Write decision and calibration justification notes...">{{ $appraisal['reviewerSection']['comments'] }}</textarea>
                                    @else
                                        <div class="mt-1 bg-gray-50 border border-gray-200 p-3 text-xs text-gray-700 leading-relaxed">
                                            {{ $appraisal['reviewerSection']['comments'] ?: 'Not yet filled.' }}
                                        </div>
                                    @endif
                                </div>

                                <!-- Reviewer Rating and Final Rating -->
                                <div class="grid grid-cols-2 gap-4">
                                    <div>
                                        <label for="reviewer_rating" class="block text-xs font-bold text-black">Reviewer Rating (1–10)</label>
                                        @if($perms['canEditBUHeadSection'])
                                            <input id="reviewer_rating" name="reviewerSection[rating]" type="number" min="1" max="10" step="0.1"
                                                value="{{ $appraisal['reviewerSection']['rating'] }}"
                                                class="mt-1 block w-full py-2 px-3 text-sm input-flat focus:border-blue-500 text-center font-bold">
                                        @else
                                            <p class="mt-1 text-xl font-extrabold text-gray-900">
                                                {{ $appraisal['reviewerSection']['rating'] !== null ? number_format($appraisal['reviewerSection']['rating'], 1) : 'N/A' }}
                                            </p>
                                        @endif
                                    </div>
                                    <div>
                                        <label for="bu_head_rating" class="block text-xs font-bold text-black">Final Rating</label>
                                        @if($perms['canEditBUHeadSection'])
                                            <input id="bu_head_rating" name="buHeadReview[finalRating]" type="number" min="1" max="10" step="0.1" required
                                                value="{{ $appraisal['buHeadReview']['finalRating'] }}"
                                                class="mt-1 block w-full py-2 px-3 text-sm input-flat focus:border-blue-500 text-center font-bold">
                                        @else
                                            <p class="mt-1 text-xl font-extrabold text-blue-600">
                                                {{ $appraisal['buHeadReview']['finalRating'] !== null ? number_format($appraisal['buHeadReview']['finalRating'], 2) : 'N/A' }}
                                            </p>
                                        @endif
                                    </div>
                                </div>

                                @if($appraisal['type'] === 'SALARY')
                                    <div>
                                        <label for="bu_head_hike" class="block text-xs font-bold text-black">Hike Percentage</label>
                                        @if($perms['canEditBUHeadSection'])
                                            <input id="bu_head_hike" name="buHeadReview[hikePercentage]" type="number" min="0" max="100" step="0.1"
                                                value="{{ $appraisal['buHeadReview']['hikePercentage'] }}"
                                                class="mt-1 block w-full py-2 px-3 text-sm input-flat focus:border-blue-500 text-center font-bold text-blue-500">
                                        @else
                                            <p class="mt-1 text-xl font-extrabold text-blue-500">
                                                {{ $appraisal['buHeadReview']['hikePercentage'] !== null ? '+' . number_format($appraisal['buHeadReview']['hikePercentage'], 1) . '%' : 'N/A' }}
                                            </p>
                                        @endif
                                    </div>
                                @endif

                                <!-- Promotion + Grade -->
                                @if($perms['canEditBUHeadSection'] || $appraisal['promotionRecommended'] !== null)
                                    <div class="grid grid-cols-2 gap-4">
                                        <div>
                                            <label for="promotion_recommended" class="block text-xs font-bold text-black">Promotion</label>
                                            @if($perms['canEditBUHeadSection'])
                                                <select id="promotion_recommended" name="promotionRecommended"
                                                    class="mt-1 block w-full border border-gray-300 py-2 px-3 text-black focus:outline-none focus:border-blue-500 text-sm">
                                                    <option value="0" {{ !$appraisal['promotionRecommended'] ? 'selected' : '' }}>No</option>
                                                    <option value="1" {{ $appraisal['promotionRecommended'] ? 'selected' : '' }}>Yes</option>
                                                </select>
                                            @else
                                                <span class="mt-1 inline-flex items-center px-2.5 py-0.5 text-xs font-bold {{ $appraisal['promotionRecommended'] ? 'bg-green-100 text-green-800' : 'bg-gray-100 text-gray-600' }}">
                                                    {{ $appraisal['promotionRecommended'] ? 'Yes' : 'No' }}
                                                </span>
                                            @endif
                                        </div>
                                        <div>
                                            <label for="grade" class="block text-xs font-bold text-black">Grade</label>
                                            @if($perms['canEditBUHeadSection'])
                                                <input id="grade" name="grade" type="text"
                                                    value="{{ $appraisal['grade'] }}"
                                                    class="mt-1 block w-full py-2 px-3 text-sm input-flat focus:border-blue-500 text-center">
                                            @else
                                                <p class="mt-1 text-sm font-extrabold text-black font-mono">{{ $appraisal['grade'] ?: 'N/A' }}</p>
                                            @endif
                                        </div>
                                    </div>
                                @endif

                                @if($appraisal['type'] === 'SALARY' && $perms['canEditBUHeadSection'])
                                    <div class="grid grid-cols-1 md:grid-cols-3 gap-3">
                                        <div>
                                            <label for="adjustments" class="block text-xs font-bold text-black">Adjustments (INR)</label>
                                            <input id="adjustments" name="adjustments" type="number" step="1"
                                                value="{{ $appraisal['adjustments'] }}"
                                                class="mt-1 block w-full py-2 px-3 text-sm input-flat focus:border-blue-500 text-center">
                                        </div>
                                        <div>
                                            <label for="increment_amount" class="block text-xs font-bold text-black">Increment (INR)</label>
                                            <input id="increment_amount" name="incrementAmount" type="number" step="1"
                                                value="{{ $appraisal['incrementAmount'] }}"
                                                class="mt-1 block w-full py-2 px-3 text-sm input-flat focus:border-blue-500 text-center">
                                        </div>
                                        <div>
                                            <label for="new_ctc" class="block text-xs font-bold text-black">New CTC (INR)</label>
                                            <input id="new_ctc" name="newCtc" type="number" step="1"
                                                value="{{ $appraisal['newCtc'] }}"
                                                class="mt-1 block w-full py-2 px-3 text-sm input-flat focus:border-blue-500 text-center">
                                        </div>
                                    </div>
                                @elseif($appraisal['type'] === 'SALARY')
                                    <div class="grid grid-cols-3 gap-3 text-xs">
                                        <div><span class="text-gray-400 font-bold">Adjustments</span><br><span class="font-semibold">{{ $appraisal['adjustments'] !== null ? 'INR ' . number_format($appraisal['adjustments']) : 'N/A' }}</span></div>
                                        <div><span class="text-gray-400 font-bold">Increment</span><br><span class="font-semibold">{{ $appraisal['incrementAmount'] !== null ? 'INR ' . number_format($appraisal['incrementAmount']) : 'N/A' }}</span></div>
                                        <div><span class="text-gray-400 font-bold">New CTC</span><br><span class="font-semibold">{{ $appraisal['newCtc'] !== null ? 'INR ' . number_format($appraisal['newCtc']) : 'N/A' }}</span></div>
                                    </div>
                                @endif

                                <!-- Signatures Row -->
                                <div class="border-t border-gray-100 pt-3 space-y-3">
                                    <p class="text-[10px] font-bold uppercase tracking-wider text-gray-400">Signatures</p>
                                    <!-- Appraiser Signature -->
                                    <div class="flex items-center gap-2">
                                        @if($appraisal['appraiserSignedAt'])
                                            <i data-lucide="check-circle-2" class="h-4 w-4 text-green-600 shrink-0"></i>
                                            <div>
                                                <p class="text-[10px] text-gray-500">Appraiser</p>
                                                <p class="text-xs font-bold text-green-700">{{ \Carbon\Carbon::parse($appraisal['appraiserSignedAt'])->format('M d, Y H:i') }}</p>
                                            </div>
                                        @else
                                            <i data-lucide="circle-dashed" class="h-4 w-4 text-gray-300 shrink-0"></i>
                                            <p class="text-xs text-gray-400">Appraiser — Not signed</p>
                                        @endif
                                    </div>
                                    <!-- Reviewer Signature -->
                                    <div class="flex items-center gap-2">
                                        @if($appraisal['reviewerSignedAt'])
                                            <i data-lucide="check-circle-2" class="h-4 w-4 text-green-600 shrink-0"></i>
                                            <div>
                                                <p class="text-[10px] text-gray-500">Reviewer</p>
                                                <p class="text-xs font-bold text-green-700">{{ \Carbon\Carbon::parse($appraisal['reviewerSignedAt'])->format('M d, Y H:i') }}</p>
                                            </div>
                                        @else
                                            <i data-lucide="circle-dashed" class="h-4 w-4 text-gray-300 shrink-0"></i>
                                            <p class="text-xs text-gray-400">Reviewer — Not signed</p>
                                        @endif
                                    </div>
                                </div>
                            </div>
                        @endif
                    </div>
                @endif


                <!-- Section 7: Special Appeal -->

                <!-- Section 6: Special Appeal -->
                @if($status === 'COMPLETED')
                    <div class="bg-white border-l-4 border-yellow-500 border-y border-r border-gray-200 p-6 space-y-4">
                        <h3 class="text-sm font-bold text-black uppercase tracking-wider flex items-center gap-2">
                            <i data-lucide="alert-triangle" class="h-4 w-4 text-yellow-500"></i>
                            Special Appeal Process
                        </h3>
                        
                        @if($appraisal['specialAppeal'])
                            <!-- Appeal has been filed -->
                            <div class="space-y-4">
                                <div class="bg-yellow-50 p-4 border border-yellow-200 rounded-sm">
                                    <span class="text-xs font-bold uppercase tracking-wider text-yellow-800">Appeal Status: {{ $appraisal['specialAppealStatus'] ?? 'PENDING' }}</span>
                                    <p class="mt-2 text-sm text-gray-700 leading-relaxed">
                                        <strong>Employee Appeal Justification:</strong> {{ $appraisal['justification'] ?: 'No details provided.' }}
                                    </p>
                                </div>

                                @if($role === 'BU_HEAD' || $role === 'HR')
                                    <!-- BU Head / HR can action the appeal -->
                                    <div class="space-y-4 border-t border-gray-150 pt-4">
                                        <div>
                                            <label for="special_appeal_status" class="block text-xs font-bold text-black">Action Decision</label>
                                            <select id="special_appeal_status" name="specialAppealStatus"
                                                class="mt-1 block w-full border border-gray-300 py-2 px-3 text-black focus:outline-none focus:border-blue-500 text-sm">
                                                <option value="PENDING" {{ $appraisal['specialAppealStatus'] === 'PENDING' ? 'selected' : '' }}>Pending Review</option>
                                                <option value="APPROVED" {{ $appraisal['specialAppealStatus'] === 'APPROVED' ? 'selected' : '' }}>Approve Appeal (Modify Decisions)</option>
                                                <option value="REJECTED" {{ $appraisal['specialAppealStatus'] === 'REJECTED' ? 'selected' : '' }}>Reject Appeal (Keep Decisions)</option>
                                            </select>
                                        </div>
                                        <div>
                                            <label for="special_appeal_comments" class="block text-xs font-bold text-black">Appeal Decision Comments</label>
                                            <textarea id="special_appeal_comments" name="specialAppealComments" rows="3"
                                                class="mt-1 block w-full p-3 text-sm input-flat focus:border-blue-500 placeholder:text-gray-300"
                                                placeholder="Write reasons for approving or rejecting the employee appeal...">{{ $appraisal['specialAppealComments'] }}</textarea>
                                        </div>
                                        <div class="pt-2">
                                            <button type="submit" formaction="{{ route('appraisals.submit', $appraisal['id']) }}"
                                                class="bg-blue-500 hover:bg-blue-600 px-4 py-2 text-xs font-bold text-white transition-colors cursor-pointer">
                                                Submit Appeal Resolution
                                            </button>
                                        </div>
                                    </div>
                                @else
                                    <!-- Employee / Manager can only view appeal decision -->
                                    @if(!empty($appraisal['specialAppealComments']))
                                        <div class="bg-gray-50 p-4 border border-gray-200 rounded-sm">
                                            <p class="text-sm text-gray-700 leading-relaxed">
                                                <strong>Decision Feedback:</strong> {{ $appraisal['specialAppealComments'] }}
                                            </p>
                                        </div>
                                    @endif
                                @endif
                            </div>
                        @else
                            <!-- Appeal not filed yet -->
                            @if($role === 'EMPLOYEE')
                                <!-- Employee can file an appeal -->
                                <div class="space-y-4" x-data="{ showAppealForm: false }">
                                    <p class="text-xs text-gray-500">
                                        If you disagree with the final rating or hike percentage assigned by the BU Head, you can file a Special Appeal for re-evaluation.
                                    </p>
                                    <button type="button" @click="showAppealForm = !showAppealForm"
                                        class="bg-yellow-500 hover:bg-yellow-600 px-4 py-2 text-xs font-bold text-white transition-colors cursor-pointer">
                                        File Special Appeal
                                    </button>

                                    <div x-show="showAppealForm" class="space-y-4 border-t border-gray-250 pt-4" x-cloak>
                                        <input type="hidden" name="specialAppeal" value="1">
                                        <div>
                                            <label for="justification" class="block text-xs font-bold text-black">Appeal Justification / Remarks</label>
                                            <textarea id="justification" name="justification" rows="4" required
                                                class="mt-1 block w-full p-3 text-sm input-flat focus:border-blue-500 placeholder:text-gray-300"
                                                placeholder="State the reasons, achievements, or context supporting your appeal..."></textarea>
                                        </div>
                                        <div class="pt-2">
                                            <button type="submit" formaction="{{ route('appraisals.submit', $appraisal['id']) }}"
                                                class="bg-blue-500 hover:bg-blue-600 px-4 py-2 text-xs font-bold text-white transition-colors cursor-pointer">
                                                Submit Special Appeal
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            @else
                                <p class="text-xs text-gray-500 italic">No Special Appeal has been filed for this appraisal.</p>
                            @endif
                        @endif
                    </div>
                @endif
            </div>
        </div>


        <!-- Bottom Save and Submit buttons when editing -->
        @if($perms['canSave'] && $status !== 'COMPLETED')
            <div class="flex items-center justify-end gap-3 bg-gray-50 border border-gray-200 p-6 mt-8">
                <p class="text-xs text-gray-500 mr-auto">
                    Make sure to save your changes before leaving.
                </p>
                <button type="submit" formaction="{{ route('appraisals.save', $appraisal['id']) }}"
                    class="border border-gray-300 bg-white px-5 py-2.5 text-xs font-bold text-black hover:bg-gray-50 transition-colors cursor-pointer">
                    Save Draft
                </button>
                @if($perms['canSubmit'])
                    <button type="submit" formaction="{{ route('appraisals.submit', $appraisal['id']) }}"
                        :disabled="totalWeightage !== 100 && '{{ $role }}' === 'EMPLOYEE'"
                        :class="(totalWeightage !== 100 && '{{ $role }}' === 'EMPLOYEE') ? 'opacity-50 cursor-not-allowed' : ''"
                        class="bg-blue-500 hover:bg-blue-600 px-5 py-2.5 text-xs font-bold text-white transition-colors cursor-pointer">
                        {{ $perms['nextActionLabel'] }}
                    </button>
                @endif
            </div>
        @endif
    </form>
</div>
@endsection

@section('scripts')
<script>
    function appraisalForm() {
        return {
                kras: @json($appraisal['kras'] ?? []),
            competencies: @json($appraisal['competencyRatings'] ?? []),

            addKra() {
                this.kras.push({
                    id: '',
                    objective: '',
                    weightage: 0,
                    appraiseeRating: '',
                    appraiseeComment: '',
                    appraiserRating: '',
                    comments: '',
                    displayOrder: this.kras.length
                });
            },
            removeKra(index) {
                this.kras.splice(index, 1);
            },
            gradeToNumeric(grade) {
                if (grade === null || grade === undefined || grade === '') return 0;
                var g = String(grade).toUpperCase().trim();
                switch (g) {
                    case 'A+': return 10.0;
                    case 'A': return 8.5;
                    case 'B+': return 7.5;
                    case 'B': return 6.5;
                    case 'C': return 5.0;
                    case 'D': return 3.0;
                    default:
                        var n = parseFloat(grade);
                        return isNaN(n) ? 0 : n;
                }
            },
            get totalWeightage() {
                return this.kras.reduce(function(sum, item) {
                    return sum + parseFloat(item.weightage || 0);
                }, 0);
            },
            get appraiseeKraAverage() {
                var self = this;
                var ratings = this.kras.map(function(i) {
                    return self.gradeToNumeric(i.appraiseeRating);
                }).filter(function(r) {
                    return r > 0;
                });
                if(!ratings.length) return 'N/A';
                var total = ratings.reduce(function(sum, r) {
                    return sum + r;
                }, 0);
                return (total / ratings.length).toFixed(2);
            },
            get appraiserKraAverage() {
                var self = this;
                var ratings = this.kras.map(function(i) {
                    return self.gradeToNumeric(i.appraiserRating);
                }).filter(function(r) {
                    return r > 0;
                });
                if(!ratings.length) return 'N/A';
                var total = ratings.reduce(function(sum, r) {
                    return sum + r;
                }, 0);
                return (total / ratings.length).toFixed(2);
            },
            get competencyEmployeeAvg() {
                var scores = this.competencies
                    .map(function(c) { return parseInt(c.employeeScore); })
                    .filter(function(s) { return !isNaN(s) && s > 0; });
                if (!scores.length) return 'N/A';
                return (scores.reduce(function(a, b) { return a + b; }, 0) / scores.length).toFixed(1);
            },
            get competencyAppraiserAvg() {
                var scores = this.competencies
                    .map(function(c) { return parseInt(c.appraiserScore); })
                    .filter(function(s) { return !isNaN(s) && s > 0; });
                if (!scores.length) return 'N/A';
                return (scores.reduce(function(a, b) { return a + b; }, 0) / scores.length).toFixed(1);
            }
        };
    }
</script>
@endsection
