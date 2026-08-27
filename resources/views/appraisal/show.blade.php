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

<div class="space-y-8" 
     x-data="{
        kras: @json($appraisal['kras'] ?? []),
        skills: @json($appraisal['skillRatings'] ?? []),
        nextCycleKras: @json($appraisal['nextCycleKras'] ?? []),
        
        addKra() {
            this.kras.push({
                objective: '',
                weightage: 0,
                appraiseeRating: null,
                appraiserRating: null,
                comments: '',
                displayOrder: this.kras.length
            });
        },
        removeKra(index) {
            this.kras.splice(index, 1);
        },
        addNextCycleKra() {
            this.nextCycleKras.push({
                objective: '',
                weightage: 0,
                displayOrder: this.nextCycleKras.length
            });
        },
        removeNextCycleKra(index) {
            this.nextCycleKras.splice(index, 1);
        },
        get totalWeightage() {
            return this.kras.reduce(function(sum, item) {
                return sum + parseFloat(item.weightage || 0);
            }, 0);
        },
        get totalNextCycleWeightage() {
            return this.nextCycleKras.reduce(function(sum, item) {
                return sum + parseFloat(item.weightage || 0);
            }, 0);
        },
        get appraiseeKraAverage() {
            var ratings = this.kras.map(function(i) {
                return i.appraiseeRating;
            }).filter(function(r) {
                return r !== null && r !== '';
            });
            if(!ratings.length) return 'N/A';
            var total = ratings.reduce(function(sum, r) {
                return sum + parseFloat(r);
            }, 0);
            return (total / ratings.length).toFixed(2);
        },
        get appraiserKraAverage() {
            var ratings = this.kras.map(function(i) {
                return i.appraiserRating;
            }).filter(function(r) {
                return r !== null && r !== '';
            });
            if(!ratings.length) return 'N/A';
            var total = ratings.reduce(function(sum, r) {
                return sum + parseFloat(r);
            }, 0);
            return (total / ratings.length).toFixed(2);
        }
     }">
     
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
            <span class="text-gray-400 font-bold uppercase tracking-wider block">Department</span>
            <span class="text-black font-semibold mt-1 block">{{ $appraisal['employee']['department'] ?? 'N/A' }}</span>
        </div>
        <div>
            <span class="text-gray-400 font-bold uppercase tracking-wider block">Designation</span>
            <span class="text-black font-semibold mt-1 block">{{ $appraisal['employee']['designation'] ?? 'N/A' }}</span>
        </div>
        <div>
            <span class="text-gray-400 font-bold uppercase tracking-wider block">Grade</span>
            <span class="text-black font-semibold mt-1 block">{{ $appraisal['employee']['grade'] ?? 'N/A' }}</span>
        </div>
        <div>
            <span class="text-gray-400 font-bold uppercase tracking-wider block">Date of Joining</span>
            <span class="text-black font-semibold mt-1 block">
                {{ $appraisal['employee']['doj'] ? \Carbon\Carbon::parse($appraisal['employee']['doj'])->format('M d, Y') : 'N/A' }}
            </span>
        </div>
        <div>
            <span class="text-gray-400 font-bold uppercase tracking-wider block">Experience in Company</span>
            <span class="text-black font-semibold mt-1 block">
                {{ $appraisal['employee']['companyExperienceYears'] !== null ? number_format($appraisal['employee']['companyExperienceYears'], 1) . ' years' : 'N/A' }}
            </span>
        </div>
        <div>
            <span class="text-gray-400 font-bold uppercase tracking-wider block">Total Experience</span>
            <span class="text-black font-semibold mt-1 block">
                {{ $appraisal['employee']['totalExperienceYears'] !== null ? number_format($appraisal['employee']['totalExperienceYears'], 1) . ' years' : 'N/A' }}
            </span>
        </div>
        <div>
            <span class="text-gray-400 font-bold uppercase tracking-wider block">Last Promotion Date</span>
            <span class="text-black font-semibold mt-1 block">
                {{ $appraisal['employee']['lastPromotionDate'] ? \Carbon\Carbon::parse($appraisal['employee']['lastPromotionDate'])->format('M d, Y') : 'N/A' }}
            </span>
        </div>
        <div>
            <span class="text-gray-400 font-bold uppercase tracking-wider block">Current CTC</span>
            <span class="text-black font-semibold mt-1 block">
                {{ $appraisal['employee']['salary'] !== null ? 'INR ' . number_format($appraisal['employee']['salary']) : 'N/A' }}
            </span>
        </div>
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
                                    <textarea name="sectionOneAnswers[{{ $index }}][answer]" rows="3"
                                        class="block w-full p-3 text-sm input-flat focus:border-blue-500 placeholder:text-gray-300"
                                        placeholder="Type your response here...">{{ $qa['answer'] }}</textarea>
                                @else
                                    <p class="bg-gray-50 border border-gray-200 p-4 text-sm text-black leading-relaxed">
                                        {{ $qa['answer'] ?: 'No response provided.' }}
                                    </p>
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
                        @if($perms['canEditEmployeeSection'])
                            <button type="button" @click="addKra()"
                                class="border border-gray-300 bg-white hover:bg-gray-50 px-3 py-1 text-xs font-bold text-black transition-colors cursor-pointer">
                                + Add KRA
                            </button>
                        @endif
                    </div>

                    <!-- Dynamic KRA Rows Table -->
                    <div class="overflow-x-auto border border-gray-200">
                        <table class="w-full text-left text-sm text-black">
                            <thead class="bg-gray-50 text-xs uppercase tracking-wider text-gray-500 border-b border-gray-200">
                                <tr>
                                    <th class="px-4 py-3">KRA Objectives</th>
                                    <th class="px-4 py-3 w-20 text-center">Weight %</th>
                                    <th class="px-4 py-3 w-24 text-center">Self (1-10)</th>
                                    <th class="px-4 py-3 w-24 text-center">Mgr (1-10)</th>
                                    @if($perms['canEditManagerSection'] || $status !== 'DRAFT')
                                        <th class="px-4 py-3">Manager Comments</th>
                                    @endif
                                    @if($perms['canEditEmployeeSection'])
                                        <th class="px-4 py-3 w-10 text-center"></th>
                                    @endif
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-200">
                                <template x-for="(kra, index) in kras" :key="index">
                                    <tr class="hover:bg-gray-50/50">
                                        <!-- Objective Column -->
                                        <td class="px-4 py-3">
                                            <input type="hidden" :name="'kras[' + index + '][id]'" :value="kra.id">
                                            <template x-if="{{ $perms['canEditEmployeeSection'] ? 'true' : 'false' }}">
                                                <input type="text" :name="'kras[' + index + '][objective]'" x-model="kra.objective" required
                                                    class="block w-full py-1.5 px-2 text-xs input-flat focus:border-blue-500">
                                            </template>
                                            <template x-if="!{{ $perms['canEditEmployeeSection'] ? 'true' : 'false' }}">
                                                <span class="text-xs font-semibold text-black" x-text="kra.objective"></span>
                                            </template>
                                        </td>
                                        <!-- Weightage Column -->
                                        <td class="px-4 py-3 text-center">
                                            <template x-if="{{ $perms['canEditEmployeeSection'] ? 'true' : 'false' }}">
                                                <input type="number" min="0" max="100" step="1" :name="'kras[' + index + '][weightage]'" x-model="kra.weightage" required
                                                    class="block w-full text-center py-1.5 px-2 text-xs input-flat focus:border-blue-500">
                                            </template>
                                            <template x-if="!{{ $perms['canEditEmployeeSection'] ? 'true' : 'false' }}">
                                                <span class="text-xs font-bold text-gray-700" x-text="kra.weightage + '%'"></span>
                                            </template>
                                        </td>
                                        <!-- Appraisee Rating Column -->
                                        <td class="px-4 py-3 text-center">
                                            <template x-if="{{ $perms['canEditEmployeeSection'] ? 'true' : 'false' }}">
                                                <input type="number" min="1" max="10" step="0.1" :name="'kras[' + index + '][appraiseeRating]'" x-model="kra.appraiseeRating" required
                                                    class="block w-full text-center py-1.5 px-2 text-xs input-flat focus:border-blue-500">
                                            </template>
                                            <template x-if="!{{ $perms['canEditEmployeeSection'] ? 'true' : 'false' }}">
                                                <span class="text-xs font-bold text-blue-500" x-text="kra.appraiseeRating || '-'"></span>
                                            </template>
                                        </td>
                                        <!-- Appraiser Rating Column -->
                                        <td class="px-4 py-3 text-center">
                                            <template x-if="{{ $perms['canEditManagerSection'] ? 'true' : 'false' }}">
                                                <input type="number" min="1" max="10" step="0.1" :name="'kras[' + index + '][appraiserRating]'" x-model="kra.appraiserRating" required
                                                    class="block w-full text-center py-1.5 px-2 text-xs input-flat focus:border-blue-500">
                                            </template>
                                            <template x-if="!{{ $perms['canEditManagerSection'] ? 'true' : 'false' }}">
                                                <span class="text-xs font-bold text-gray-700" x-text="kra.appraiserRating || '-'"></span>
                                            </template>
                                        </td>
                                        <!-- Manager Comments Column -->
                                        @if($perms['canEditManagerSection'] || $status !== 'DRAFT')
                                            <td class="px-4 py-3">
                                                <template x-if="{{ $perms['canEditManagerSection'] ? 'true' : 'false' }}">
                                                    <input type="text" :name="'kras[' + index + '][comments]'" x-model="kra.comments"
                                                        class="block w-full py-1.5 px-2 text-xs input-flat focus:border-blue-500">
                                                </template>
                                                <template x-if="!{{ $perms['canEditManagerSection'] ? 'true' : 'false' }}">
                                                    <span class="text-xs text-gray-600 italic" x-text="kra.comments || '-'"></span>
                                                </template>
                                            </td>
                                        @endif
                                        <!-- Action Delete Row Column -->
                                        @if($perms['canEditEmployeeSection'])
                                            <td class="px-4 py-3 text-center">
                                                <button type="button" @click="removeKra(index)" class="text-gray-500 hover:text-black cursor-pointer">
                                                    <i data-lucide="trash-2" class="h-4 w-4"></i>
                                                </button>
                                            </td>
                                        @endif
                                    </tr>
                                </template>
                            </tbody>
                            <tfoot class="border-t border-gray-200 bg-gray-50 font-bold text-black">
                                <tr>
                                    <td class="px-4 py-3">Totals & Averages</td>
                                    <td class="px-4 py-3 text-center text-xs" :class="totalWeightage === 100 ? 'text-black' : 'text-red-600'" x-text="totalWeightage + '%'"></td>
                                    <td class="px-4 py-3 text-center text-xs text-blue-500" x-text="appraiseeKraAverage"></td>
                                    <td class="px-4 py-3 text-center text-xs text-gray-700" x-text="appraiserKraAverage"></td>
                                    @if($perms['canEditManagerSection'] || $status !== 'DRAFT')
                                        <td></td>
                                    @endif
                                    @if($perms['canEditEmployeeSection'])
                                        <td></td>
                                    @endif
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                </div>

            </div>

            <!-- Right 1 Col: Skills Ratings, Manager Review, CEO Review -->
            <div class="space-y-8">
                <!-- Section 3: Skill Calibrations -->
                <div class="bg-white border border-gray-200 p-6 space-y-4">
                    <h3 class="text-sm font-bold text-black uppercase tracking-wider flex items-center gap-2">
                        <i data-lucide="sliders" class="h-4 w-4 text-blue-500"></i>
                        Skill Ratings
                    </h3>
                    
                    <div class="space-y-4">
                        <template x-for="(skill, index) in skills" :key="index">
                            <div class="space-y-2 border-b border-gray-100 pb-3 last:border-0 last:pb-0">
                                <div class="flex justify-between items-center">
                                    <span class="text-xs font-bold text-black" x-text="skill.skillName"></span>
                                    <input type="hidden" :name="'skillRatings[' + index + '][id]'" :value="skill.id">
                                    <input type="hidden" :name="'skillRatings[' + index + '][skillName]'" :value="skill.skillName">
                                    <div class="flex items-center gap-2">
                                        <span class="text-[9px] text-blue-500 font-bold" x-text="'Self: ' + (skill.employeeRating || '-')"></span>
                                        <span class="text-[9px] text-gray-600 font-bold" x-text="'Mgr: ' + (skill.managerRating || '-')"></span>
                                    </div>
                                </div>

                                <div class="grid grid-cols-2 gap-3">
                                    <!-- Employee Input -->
                                    <div>
                                        <label class="text-[9px] uppercase font-bold text-gray-400">Self (1-10)</label>
                                        <template x-if="{{ $perms['canEditEmployeeSection'] ? 'true' : 'false' }}">
                                            <input type="number" min="1" max="10" step="1" :name="'skillRatings[' + index + '][employeeRating]'" x-model.number="skill.employeeRating" required
                                                class="block w-full py-1 px-2 text-xs input-flat focus:border-blue-500 text-center">
                                        </template>
                                        <template x-if="!{{ $perms['canEditEmployeeSection'] ? 'true' : 'false' }}">
                                            <span class="block py-1 text-xs text-blue-500 font-bold" x-text="skill.employeeRating || 'N/A'"></span>
                                        </template>
                                    </div>

                                    <!-- Manager Input -->
                                    <div>
                                        <label class="text-[9px] uppercase font-bold text-gray-400">Mgr (1-10)</label>
                                        <template x-if="{{ $perms['canEditManagerSection'] ? 'true' : 'false' }}">
                                            <input type="number" min="1" max="10" step="1" :name="'skillRatings[' + index + '][managerRating]'" x-model.number="skill.managerRating" required
                                                class="block w-full py-1 px-2 text-xs input-flat focus:border-blue-500 text-center">
                                        </template>
                                        <template x-if="!{{ $perms['canEditManagerSection'] ? 'true' : 'false' }}">
                                            <span class="block py-1 text-xs text-gray-700 font-bold" x-text="skill.managerRating || 'N/A'"></span>
                                        </template>
                                    </div>
                                </div>
                            </div>
                        </template>
                    </div>
                </div>

                <!-- Section 4: Manager Assessment comments -->
                @if($perms['canEditManagerSection'] || $appraisal['managerReview']['overallRating'] !== null || !empty($appraisal['managerReview']['comments']))
                    <div class="bg-white border-l-4 border-blue-500 border-y border-r border-gray-200 p-6 space-y-4">
                        <h3 class="text-sm font-bold text-black uppercase tracking-wider flex items-center gap-2">
                            <i data-lucide="message-square" class="h-4 w-4 text-blue-500"></i>
                            Manager Review
                        </h3>
                        
                        <div class="space-y-4">
                            <div>
                                <label for="manager_comments" class="block text-xs font-bold text-black">Assessment Comments</label>
                                @if($perms['canEditManagerSection'])
                                    <textarea id="manager_comments" name="managerReview[comments]" rows="3" required
                                        class="mt-1 block w-full p-3 text-sm input-flat focus:border-blue-500 placeholder:text-gray-300"
                                        placeholder="Write feedback comments on employee achievements and scope...">{{ $appraisal['managerReview']['comments'] }}</textarea>
                                @else
                                    <p class="mt-1 bg-gray-50 border border-gray-200 p-4 text-xs text-black leading-relaxed">
                                        {{ $appraisal['managerReview']['comments'] ?: 'No assessment comments provided.' }}
                                    </p>
                                @endif
                            </div>

                            <div>
                                <label for="manager_rating" class="block text-xs font-bold text-black">Recommended Overall Rating (1-10)</label>
                                @if($perms['canEditManagerSection'])
                                    <input id="manager_rating" name="managerReview[overallRating]" type="number" min="1" max="10" step="0.1" required
                                        value="{{ $appraisal['managerReview']['overallRating'] }}"
                                        class="mt-1 block w-full py-2 px-3 text-sm input-flat focus:border-blue-500 text-center font-bold">
                                @else
                                    <p class="mt-1 text-sm font-extrabold text-blue-500">
                                        {{ $appraisal['managerReview']['overallRating'] !== null ? number_format($appraisal['managerReview']['overallRating'], 2) : 'N/A' }}
                                    </p>
                                @endif
                            </div>
                        </div>
                    </div>
                @endif

                <!-- Section 5: BU Head Calibration Decision -->
                @if($perms['canEditBUHeadSection'] || $appraisal['buHeadReview']['finalRating'] !== null || $appraisal['buHeadReview']['hikePercentage'] !== null)
                    <div class="bg-white border-l-4 border-black border-y border-r border-gray-200 p-6 space-y-4">
                        <h3 class="text-sm font-bold text-black uppercase tracking-wider flex items-center gap-2">
                            <i data-lucide="shield-check" class="h-4 w-4 text-black"></i>
                            BU Head Calibrated Decision
                        </h3>
                        
                        <div class="space-y-4">
                            <div>
                                <label for="bu_head_comments" class="block text-xs font-bold text-black">Calibration Comments / Justification</label>
                                @if($perms['canEditBUHeadSection'])
                                    <textarea id="bu_head_comments" name="buHeadReview[comments]" rows="3"
                                        class="mt-1 block w-full p-3 text-sm input-flat focus:border-blue-500 placeholder:text-gray-300"
                                        placeholder="Write decision and calibration justification notes...">{{ $appraisal['buHeadReview']['comments'] }}</textarea>
                                @else
                                    <p class="mt-1 bg-gray-50 border border-gray-200 p-4 text-xs text-black leading-relaxed">
                                        {{ $appraisal['buHeadReview']['comments'] ?: 'No calibration feedback provided.' }}
                                    </p>
                                @endif
                            </div>

                            <div class="grid grid-cols-1 {{ $appraisal['type'] === 'SALARY' ? 'md:grid-cols-3' : 'md:grid-cols-2' }} gap-4">
                                <div>
                                    <label for="bu_head_rating" class="block text-xs font-bold text-black">Final Rating</label>
                                    @if($perms['canEditBUHeadSection'])
                                        <input id="bu_head_rating" name="buHeadReview[finalRating]" type="number" min="1" max="10" step="0.1" required
                                            value="{{ $appraisal['buHeadReview']['finalRating'] }}"
                                            class="mt-1 block w-full py-2 px-3 text-sm input-flat focus:border-blue-500 text-center font-bold">
                                    @else
                                        <p class="mt-1 text-sm font-extrabold text-black">
                                            {{ $appraisal['buHeadReview']['finalRating'] !== null ? number_format($appraisal['buHeadReview']['finalRating'], 2) : 'N/A' }}
                                        </p>
                                    @endif
                                </div>

                                @if($appraisal['type'] === 'SALARY')
                                    <div>
                                        <label for="bu_head_hike" class="block text-xs font-bold text-black">Hike Percentage</label>
                                        @if($perms['canEditBUHeadSection'])
                                            <input id="bu_head_hike" name="buHeadReview[hikePercentage]" type="number" min="0" max="100" step="0.1" required
                                                value="{{ $appraisal['buHeadReview']['hikePercentage'] }}"
                                                class="mt-1 block w-full py-2 px-3 text-sm input-flat focus:border-blue-500 text-center font-bold text-blue-500">
                                        @else
                                            <p class="mt-1 text-sm font-extrabold text-blue-500">
                                                {{ $appraisal['buHeadReview']['hikePercentage'] !== null ? '+' . number_format($appraisal['buHeadReview']['hikePercentage'], 1) . '%' : 'N/A' }}
                                            </p>
                                        @endif
                                    </div>
                                @endif

                                <div>
                                    <label for="promotion_recommended" class="block text-xs font-bold text-black">Promotion Recommended</label>
                                    @if($perms['canEditBUHeadSection'])
                                        <select id="promotion_recommended" name="promotionRecommended"
                                            class="mt-1 block w-full border border-gray-300 py-2 px-3 text-black focus:outline-none focus:border-blue-500 text-sm">
                                            <option value="0" {{ !$appraisal['promotionRecommended'] ? 'selected' : '' }}>No</option>
                                            <option value="1" {{ $appraisal['promotionRecommended'] ? 'selected' : '' }}>Yes</option>
                                        </select>
                                    @else
                                        <span class="mt-1 inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium {{ $appraisal['promotionRecommended'] ? 'bg-green-100 text-green-800' : 'bg-gray-100 text-gray-800' }}">
                                            {{ $appraisal['promotionRecommended'] ? 'Yes' : 'No' }}
                                        </span>
                                    @endif
                                </div>
                            </div>

                            @if($appraisal['type'] === 'SALARY')
                                <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                                    <div>
                                        <label for="adjustments" class="block text-xs font-bold text-black">Adjustments (INR)</label>
                                        @if($perms['canEditBUHeadSection'])
                                            <input id="adjustments" name="adjustments" type="number" step="1"
                                                value="{{ $appraisal['adjustments'] }}"
                                                class="mt-1 block w-full py-2 px-3 text-sm input-flat focus:border-blue-500 text-center">
                                        @else
                                            <p class="mt-1 text-sm font-semibold text-black">
                                                {{ $appraisal['adjustments'] !== null ? 'INR ' . number_format($appraisal['adjustments']) : 'N/A' }}
                                            </p>
                                        @endif
                                    </div>

                                    <div>
                                        <label for="increment_amount" class="block text-xs font-bold text-black">Increment Amount (INR)</label>
                                        @if($perms['canEditBUHeadSection'])
                                            <input id="increment_amount" name="incrementAmount" type="number" step="1"
                                                value="{{ $appraisal['incrementAmount'] }}"
                                                class="mt-1 block w-full py-2 px-3 text-sm input-flat focus:border-blue-500 text-center">
                                        @else
                                            <p class="mt-1 text-sm font-semibold text-black">
                                                {{ $appraisal['incrementAmount'] !== null ? 'INR ' . number_format($appraisal['incrementAmount']) : 'N/A' }}
                                            </p>
                                        @endif
                                    </div>

                                    <div>
                                        <label for="new_ctc" class="block text-xs font-bold text-black">New CTC (INR)</label>
                                        @if($perms['canEditBUHeadSection'])
                                            <input id="new_ctc" name="newCtc" type="number" step="1"
                                                value="{{ $appraisal['newCtc'] }}"
                                                class="mt-1 block w-full py-2 px-3 text-sm input-flat focus:border-blue-500 text-center">
                                        @else
                                            <p class="mt-1 text-sm font-semibold text-black">
                                                {{ $appraisal['newCtc'] !== null ? 'INR ' . number_format($appraisal['newCtc']) : 'N/A' }}
                                            </p>
                                        @endif
                                    </div>

                                    <div>
                                        <label for="grade" class="block text-xs font-bold text-black">Grade</label>
                                        @if($perms['canEditBUHeadSection'])
                                            <input id="grade" name="grade" type="text"
                                                value="{{ $appraisal['grade'] }}"
                                                class="mt-1 block w-full py-2 px-3 text-sm input-flat focus:border-blue-500 text-center">
                                        @else
                                            <p class="mt-1 text-sm font-semibold text-black">
                                                {{ $appraisal['grade'] ?: 'N/A' }}
                                            </p>
                                        @endif
                                    </div>
                                </div>
                            @else
                                <div class="grid grid-cols-1 md:grid-cols-1 gap-4">
                                    <div>
                                        <label for="grade" class="block text-xs font-bold text-black">Grade</label>
                                        @if($perms['canEditBUHeadSection'])
                                            <input id="grade" name="grade" type="text"
                                                value="{{ $appraisal['grade'] }}"
                                                class="mt-1 block w-full py-2 px-3 text-sm input-flat focus:border-blue-500 text-center">
                                        @else
                                            <p class="mt-1 text-sm font-semibold text-black">
                                                {{ $appraisal['grade'] ?: 'N/A' }}
                                            </p>
                                        @endif
                                    </div>
                                </div>
                            @endif
                        </div>
                    </div>
                @endif

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
        <!-- Section 7: Next Cycle KRA Settings -->
        @if($status === 'COMPLETED')
            <div class="bg-white border border-gray-200">
                <div class="px-6 py-4 border-b border-gray-200 bg-gray-50 flex justify-between items-center">
                    <h3 class="text-sm font-bold text-black uppercase tracking-wider flex items-center gap-2">
                        <i data-lucide="compass" class="h-4 w-4 text-blue-500"></i>
                        Section 7: Next Cycle KRA Goals (Next 6 Months)
                    </h3>
                    @php
                        $canEditNextCycle = ($role === 'MANAGER' && $data['viewer']['employeeId'] === $appraisal['managerId'] && !empty($appraisal['buHeadSubmittedAt']) && \Carbon\Carbon::parse($appraisal['buHeadSubmittedAt'])->diffInDays(now()) <= 30);
                    @endphp
                    @if($canEditNextCycle)
                        <span class="text-[10px] bg-green-50 text-green-700 border border-green-200 px-2 py-0.5 font-bold uppercase">
                            1-Month Edit Window Active
                        </span>
                    @else
                        <span class="text-[10px] bg-gray-100 text-gray-500 border border-gray-250 px-2 py-0.5 font-bold uppercase">
                            Read Only
                        </span>
                    @endif
                </div>

                <div class="p-6 space-y-6">
                    <p class="text-xs text-gray-500">
                        Discuss and establish the performance Key Result Areas (KRAs) for the upcoming six-month cycle. 
                        @if($canEditNextCycle)
                            As the reporting manager, you can define objectives and weightages. The total weightage must sum up to exactly 100%.
                        @endif
                    </p>

                    <div class="overflow-x-auto border border-gray-200">
                        <table class="w-full text-left text-sm text-black">
                            <thead class="bg-gray-50 text-xs uppercase tracking-wider text-gray-500 border-b border-gray-200 font-mono">
                                <tr>
                                    <th class="px-6 py-3 w-12">#</th>
                                    <th class="px-6 py-3">KRA Objective / Goal</th>
                                    <th class="px-6 py-3 w-32">Weightage (%)</th>
                                    @if($canEditNextCycle)
                                        <th class="px-6 py-3 w-20 text-center">Action</th>
                                    @endif
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-200">
                                <!-- Read-Only / Empty State when not editing next cycle and no KRAs set -->
                                <template x-if="nextCycleKras.length === 0">
                                    <tr>
                                        <td :colspan="nextCycleKras.length === 0 ? 4 : 3" class="px-6 py-8 text-center text-gray-500 text-xs italic">
                                            No next cycle KRAs have been defined yet.
                                        </td>
                                    </tr>
                                </template>

                                <!-- Loop through Next Cycle KRAs -->
                                <template x-for="(kra, index) in nextCycleKras" :key="index">
                                    <tr class="hover:bg-gray-50/50">
                                        <td class="px-6 py-4 font-mono text-xs text-gray-500" x-text="index + 1"></td>
                                        <td class="px-6 py-4">
                                            @if($canEditNextCycle)
                                                <input type="text" :name="'nextCycleKras[' + index + '][objective]'" x-model="kra.objective" required
                                                    placeholder="Define future KRA objective..."
                                                    class="block w-full border border-gray-300 py-1.5 px-3 text-sm text-black focus:outline-none focus:border-blue-500 placeholder:text-gray-300">
                                            @else
                                                <span class="text-sm font-semibold text-black" x-text="kra.objective"></span>
                                            @endif
                                        </td>
                                        <td class="px-6 py-4">
                                            @if($canEditNextCycle)
                                                <div class="relative">
                                                    <input type="number" :name="'nextCycleKras[' + index + '][weightage]'" x-model.number="kra.weightage" min="1" max="100" required
                                                        class="block w-full border border-gray-300 py-1.5 pl-3 pr-6 text-sm text-black focus:outline-none focus:border-blue-500 font-mono">
                                                    <span class="absolute inset-y-0 right-2 flex items-center text-xs text-gray-400 font-mono">%</span>
                                                </div>
                                            @else
                                                <span class="text-sm font-mono text-black font-semibold" x-text="kra.weightage + '%'"></span>
                                            @endif
                                        </td>
                                        @if($canEditNextCycle)
                                            <td class="px-6 py-4 text-center">
                                                <button type="button" @click="removeNextCycleKra(index)" class="text-red-500 hover:text-red-700 cursor-pointer">
                                                    <i data-lucide="trash-2" class="h-4 w-4 mx-auto"></i>
                                                </button>
                                            </td>
                                        @endif
                                    </tr>
                                </template>
                            </tbody>
                            <tfoot class="bg-gray-50/50 font-bold border-t border-gray-200 font-mono">
                                <tr>
                                    <td class="px-6 py-3"></td>
                                    <td class="px-6 py-3 text-right text-xs uppercase tracking-wider text-gray-500">Total Weightage:</td>
                                    <td class="px-6 py-3 text-sm" :class="totalNextCycleWeightage === 100 ? 'text-green-600' : 'text-red-600'" x-text="totalNextCycleWeightage + '%'"></td>
                                    @if($canEditNextCycle)
                                        <td></td>
                                    @endif
                                </tr>
                            </tfoot>
                        </table>
                    </div>

                    @if($canEditNextCycle)
                        <div class="flex justify-between items-center pt-2">
                            <button type="button" @click="addNextCycleKra()"
                                class="border border-gray-300 bg-white hover:bg-gray-50 px-4 py-2 text-xs font-bold text-black transition-colors cursor-pointer flex items-center gap-1.5">
                                <i data-lucide="plus" class="h-3.5 w-3.5"></i> Add Goal
                            </button>

                            <button type="submit" formaction="{{ route('appraisals.save', $appraisal['id']) }}"
                                :disabled="totalNextCycleWeightage !== 100"
                                :class="totalNextCycleWeightage !== 100 ? 'opacity-50 cursor-not-allowed' : ''"
                                class="bg-blue-500 hover:bg-blue-600 px-5 py-2 text-xs font-bold text-white transition-colors cursor-pointer flex items-center gap-2">
                                <i data-lucide="save" class="h-3.5 w-3.5"></i> Save Next Cycle KRAs
                            </button>
                        </div>

                        <!-- Weightage warning block -->
                        <div class="border border-red-200 bg-red-50 p-4 text-red-800 text-xs flex items-center gap-3" x-show="totalNextCycleWeightage !== 100">
                            <i data-lucide="alert-triangle" class="h-4.5 w-4.5 shrink-0 text-red-600"></i>
                            <p><strong>KRA Goal Alignment Required:</strong> Total weightage of the next-cycle goals must sum up to exactly <strong>100%</strong> to enable saving. Current total: <span class="font-bold" x-text="totalNextCycleWeightage + '%'"></span></p>
                        </div>
                    @endif
                </div>
            </div>
        @endif

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
