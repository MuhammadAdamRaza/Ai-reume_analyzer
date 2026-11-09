@extends('layouts.app')

@section('title', 'Resume Readability Report: ' . ($analysis->candidate_name ?: 'Candidate') . ' | Free Resume Checker')

@section('content')
<div class="py-6 sm:py-10 lg:py-14 max-w-6xl mx-auto px-4 sm:px-6 lg:px-8">

    {{-- ======================================================== --}}
    {{-- STATE 1: PENDING (QUEUED OR PROCESSING)                  --}}
    {{-- ======================================================== --}}
    @if($analysis->isPending())
        <div class="max-w-2xl mx-auto my-8 sm:my-14 bg-white border border-slate-200/90 rounded-3xl p-6 sm:p-14 text-center shadow-xl shadow-slate-200/40 relative overflow-hidden">
            <div class="absolute top-0 left-0 right-0 h-1.5 bg-gradient-to-r from-indigo-500 via-purple-500 to-pink-500"></div>

            <div class="w-16 h-16 sm:w-20 sm:h-20 mx-auto rounded-3xl bg-indigo-50 border border-indigo-100 text-indigo-600 flex items-center justify-center mb-5 sm:mb-6 shadow-sm">
                <svg class="w-8 h-8 sm:w-10 sm:h-10 animate-spin" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-20" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="3"></circle>
                    <path class="opacity-80" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                </svg>
            </div>

            <div class="inline-flex items-center gap-2 px-3.5 py-1 rounded-full bg-indigo-50 border border-indigo-200 text-indigo-700 text-xs font-bold uppercase tracking-wider mb-3">
                <span class="w-2 h-2 rounded-full bg-indigo-600 animate-pulse"></span>
                <span id="queueBadgeText">{{ $analysis->status === 'processing' ? 'Running Resume Checks' : 'Queued for Analysis' }}</span>
            </div>

            <h1 class="text-2xl sm:text-3xl font-extrabold text-slate-900 tracking-tight" id="statusTitle">
                {{ $analysis->status === 'processing' ? 'Analyzing your resume...' : 'Your resume is in the queue...' }}
            </h1>

            <p class="mt-3 text-xs sm:text-sm text-slate-600 leading-relaxed max-w-md mx-auto" id="statusDescription">
                Extracting resume text and running format checks. The report may also include AI-assisted content suggestions.
            </p>

            <div class="mt-8 max-w-md mx-auto">
                <div class="w-full bg-slate-100 rounded-full h-2 overflow-hidden">
                    <div id="dynamicProgressBar" class="h-2 rounded-full bg-indigo-600 transition-all duration-700" style="width: 35%"></div>
                </div>
                <div class="flex items-center justify-between text-[11px] text-slate-400 mt-2 font-medium">
                    <span>Reading Document</span>
                    <span>Rule Checks</span>
                    <span>Content review</span>
                </div>
            </div>

            <p class="text-xs text-slate-400 mt-8">
                Checking status automatically every 2 seconds. Please keep this tab open.
            </p>
        </div>

        @push('scripts')
        <script>
            let pollAttempts = 0;
            const statusUrl = "{{ route('analyses.status', $analysis) }}";
            const progressBar = document.getElementById('dynamicProgressBar');
            const statusTitle = document.getElementById('statusTitle');
            const queueBadge = document.getElementById('queueBadgeText');

            const poller = setInterval(async () => {
                pollAttempts++;

                if (progressBar) {
                    const progress = Math.min(94, 35 + (pollAttempts * 7));
                    progressBar.style.width = progress + '%';
                }

                try {
                    const response = await fetch(statusUrl, {
                        headers: {
                            'Accept': 'application/json',
                            'X-Requested-With': 'XMLHttpRequest'
                        }
                    });

                    if (response.ok) {
                        const data = await response.json();

                        if (data.status === 'processing' && statusTitle) {
                            statusTitle.innerText = "Analyzing resume content & ATS readability rules...";
                            if (queueBadge) queueBadge.innerText = "Running Resume Checks";
                        }

                        if (data.is_finished) {
                            clearInterval(poller);
                            if (progressBar) progressBar.style.width = '100%';
                            window.location.reload();
                        }
                    }
                } catch (err) {
                    console.log('Poll check error, will retry in 2s...', err);
                }

                if (pollAttempts > 45) {
                    clearInterval(poller);
                    window.location.reload();
                }
            }, 2000);
        </script>
        @endpush

    {{-- ======================================================== --}}
    {{-- STATE 2: FAILED                                          --}}
    {{-- ======================================================== --}}
    @elseif($analysis->isFailed())
        <div class="max-w-xl mx-auto my-8 sm:my-14 bg-white border border-rose-200 rounded-3xl p-6 sm:p-12 text-center shadow-lg shadow-rose-100/50">
            <div class="w-16 h-16 mx-auto rounded-2xl bg-rose-50 border border-rose-200 text-rose-600 flex items-center justify-center mb-5">
                <svg class="w-8 h-8" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                </svg>
            </div>

            <h1 class="text-xl sm:text-2xl font-extrabold text-slate-900 tracking-tight">Analysis Could Not Be Completed</h1>
            
            <p class="mt-3 text-xs sm:text-sm text-slate-600 leading-relaxed">
                {{ $analysis->error_message ?: 'We encountered a problem while reading your document. Please verify the file is not corrupted or password protected and try again.' }}
            </p>

            <div class="mt-8 flex items-center justify-center gap-3">
                <a href="{{ route('home') }}" class="px-6 py-3 rounded-xl bg-indigo-600 hover:bg-indigo-700 text-white font-bold text-xs sm:text-sm shadow-md shadow-indigo-600/25 transition">
                    Upload Another Resume
                </a>
            </div>
        </div>

    {{-- ======================================================== --}}
    {{-- STATE 3: DONE (FULL ENHANCV REPORT)                      --}}
    {{-- ======================================================== --}}
    @else
        @php
            $band = $analysis->readability_band;
            $ruleResults = $analysis->rule_results ?? [];
            $components = $ruleResults['components'] ?? [];
            $failedChecks = $ruleResults['failed_checks'] ?? [];
            $passedChecks = $ruleResults['passed_checks'] ?? [];
            $categoryScores = $analysis->category_scores;
            $priorityIssues = $analysis->priority_issues;
            $rewrites = $analysis->bullet_point_rewrites;
            $missingKeywords = $analysis->missing_keywords;
            $scoreEvidence = $analysis->result_json['score_evidence'] ?? [];
            $hasJobDesc = !empty(trim((string) $analysis->job_description));
            $isRedistributed = $ruleResults['redistributed'] ?? false;
        @endphp

        <!-- Top Action Bar (Fully Responsive) -->
        <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 mb-6 sm:mb-8 pb-6 border-b border-slate-200">
            <div>
                <a href="{{ route('home') }}" class="inline-flex items-center gap-1 text-xs font-bold text-indigo-600 hover:text-indigo-800 transition mb-2">
                    &larr; Back to CV Checker
                </a>
                <div class="flex flex-wrap items-center gap-2.5">
                    <h1 class="text-2xl sm:text-3xl font-extrabold text-slate-900 tracking-tight">
                        {{ $analysis->candidate_name ?: 'Candidate Resume' }}
                    </h1>
                    @if($analysis->target_role)
                        <span class="px-3 py-1 rounded-lg text-xs font-bold bg-slate-100 text-slate-700 border border-slate-200">
                            {{ $analysis->target_role }}
                        </span>
                    @endif
                </div>
                <p class="text-xs text-slate-400 mt-1">
                    Audited {{ $analysis->created_at->format('M d, Y \a\t h:i A') }}
                    @if($analysis->resume_file_name)
                        &bull; File: <span class="font-mono text-slate-600">{{ $analysis->resume_file_name }}</span>
                    @endif
                </p>
            </div>

            <!-- Export Buttons -->
            <div class="flex flex-wrap items-center gap-2">
                <a href="{{ route('analyses.export', $analysis) }}" target="_blank" class="inline-flex items-center gap-1.5 px-3.5 py-2 rounded-xl bg-white border border-slate-300 text-slate-700 hover:text-indigo-600 hover:border-indigo-400 font-bold text-xs shadow-xs transition">
                    <svg class="w-4 h-4 text-slate-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z" />
                    </svg>
                    <span>Print / PDF</span>
                </a>
                <a href="{{ route('analyses.export-json', $analysis) }}" class="inline-flex items-center gap-1.5 px-3.5 py-2 rounded-xl bg-white border border-slate-300 text-slate-700 hover:text-indigo-600 hover:border-indigo-400 font-bold text-xs shadow-xs transition">
                    <svg class="w-4 h-4 text-slate-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4" />
                    </svg>
                    <span>JSON</span>
                </a>
                <a href="{{ route('home') }}" class="inline-flex items-center gap-1.5 px-4 py-2 rounded-xl bg-indigo-600 hover:bg-indigo-700 text-white font-bold text-xs shadow-sm shadow-indigo-600/25 transition">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 4v16m8-8H4" />
                    </svg>
                    <span>New Scan</span>
                </a>
            </div>
        </div>

        {{-- ======================================================== --}}
        {{-- SECTION 1: OVERALL SCORE & HONEST ATS WORDING            --}}
        {{-- ======================================================== --}}
        <div class="bg-white border border-slate-200/90 rounded-3xl p-6 sm:p-8 lg:p-10 shadow-sm mb-8 sm:mb-10">
            <div class="grid grid-cols-1 lg:grid-cols-12 gap-8 items-center">
                <!-- Circular Overall Score Gauge -->
                <div class="lg:col-span-5 flex flex-col items-center justify-center text-center lg:border-r lg:border-slate-100 lg:pr-8">
                    <div class="relative w-40 h-40 sm:w-44 sm:h-44 flex items-center justify-center">
                        <svg class="w-full h-full transform -rotate-90" viewBox="0 0 120 120">
                            <circle cx="60" cy="60" r="50" stroke="#F1F5F9" stroke-width="10" fill="transparent" />
                            <circle cx="60" cy="60" r="50"
                                stroke="{{ $band['color'] }}"
                                stroke-width="10"
                                stroke-linecap="round"
                                fill="transparent"
                                stroke-dasharray="314"
                                stroke-dashoffset="{{ 314 - (314 * ($analysis->overall_score / 100)) }}"
                                class="transition-all duration-1000 ease-out" />
                        </svg>
                        <div class="absolute inset-0 flex flex-col items-center justify-center">
                            <span class="text-4xl sm:text-5xl font-black text-slate-900 tracking-tight">{{ $analysis->overall_score }}</span>
                            <span class="text-[11px] font-bold uppercase tracking-wider text-slate-400 mt-0.5">Overall Score</span>
                        </div>
                    </div>

                    <div class="mt-4">
                        <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-bold border {{ $band['badge'] }}">
                            {{ $band['label'] }}
                        </span>
                    </div>
                </div>

                <!-- Score Highlights & Honest Explanation -->
                <div class="lg:col-span-7 space-y-4">
                    <!-- Recommended Honest Wording -->
                    <div class="p-4 sm:p-5 rounded-2xl bg-indigo-50/50 border border-indigo-100 text-slate-800">
                        <p class="text-xs sm:text-sm font-semibold leading-relaxed">
                            "Your resume scored <span class="font-bold text-indigo-700">{{ $analysis->ats_score }} out of 100</span> on this app's text and format checks. This is an estimate, not a test of any specific employer's system or a prediction of hiring outcomes."
                        </p>
                    </div>

                    <div class="rounded-2xl border border-slate-200 bg-white p-4">
                        <p class="text-[11px] font-bold uppercase tracking-wider text-slate-700">How the overall score is calculated</p>
                        @if($hasJobDesc && $analysis->job_match_score !== null)
                            <p class="mt-1 text-xs leading-relaxed text-slate-600">30% readability checks + 40% average AI content quality (Impact, Clarity, Relevance) + 30% AI-estimated job-description match.</p>
                        @else
                            <p class="mt-1 text-xs leading-relaxed text-slate-600">50% readability checks + 50% average AI content quality (Impact, Clarity, Relevance). Add a job description to include a job-match estimate.</p>
                        @endif
                    </div>

                    <div class="grid grid-cols-2 sm:grid-cols-3 gap-3">
                        <div class="p-3.5 bg-slate-50 rounded-2xl border border-slate-100">
                            <p class="text-[11px] font-bold text-slate-500 uppercase tracking-wider">ATS Readability</p>
                            <p class="text-lg sm:text-xl font-black text-slate-900 mt-1">{{ $analysis->ats_score }} <span class="text-xs font-semibold text-slate-400">/ 100</span></p>
                            <p class="text-[10px] text-slate-400 mt-0.5">Rule parser index</p>
                        </div>

                        <div class="p-3.5 bg-slate-50 rounded-2xl border border-slate-100">
                            <p class="text-[11px] font-bold text-slate-500 uppercase tracking-wider">Word Count</p>
                            <p class="text-lg sm:text-xl font-black text-slate-900 mt-1">{{ number_format($analysis->word_count) }}</p>
                            <p class="text-[10px] text-slate-400 mt-0.5">Est. {{ $analysis->page_count }} page(s)</p>
                        </div>

                        @if($hasJobDesc && $analysis->job_match_score !== null)
                            <div class="p-3.5 bg-indigo-50/60 rounded-2xl border border-indigo-100 col-span-2 sm:col-span-1">
                                <p class="text-[11px] font-bold text-indigo-700 uppercase tracking-wider">Job description match</p>
                                <p class="text-lg sm:text-xl font-black text-indigo-600 mt-1">{{ $analysis->job_match_score }}%</p>
                                <p class="text-[10px] text-indigo-500 mt-0.5">AI-generated estimate</p>
                            </div>
                        @else
                            <div class="p-3.5 bg-slate-50 rounded-2xl border border-slate-100 col-span-2 sm:col-span-1">
                                <p class="text-[11px] font-bold text-slate-500 uppercase tracking-wider">Checks Summary</p>
                                <p class="text-lg sm:text-xl font-black text-emerald-600 mt-1">{{ count($passedChecks) }} <span class="text-xs font-semibold text-slate-400">passed</span></p>
                                <p class="text-[10px] text-slate-400 mt-0.5">{{ count($failedChecks) }} fix(es) suggested</p>
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>

        {{-- ======================================================== --}}
        {{-- SECTION 2: 6-COMPONENT BREAKDOWN WITH PROGRESS BARS      --}}
        {{-- ======================================================== --}}
        <div class="bg-white border border-slate-200/90 rounded-3xl p-6 sm:p-8 shadow-sm mb-8 sm:mb-10">
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-2 mb-6 pb-4 border-b border-slate-100">
                <div>
                    <h2 class="text-base sm:text-lg font-extrabold text-slate-900 tracking-tight">Resume Readability Score: Parsing and Format Checks</h2>
                    <p class="text-xs text-slate-500 mt-0.5">
                        Weighted checks from this application. Keyword coverage is included when a job description is provided; this is not a test of a specific ATS.
                        @if($isRedistributed)
                            <span class="text-indigo-600 font-semibold">(No job description provided; 15 keyword points redistributed across pillars).</span>
                        @endif
                    </p>
                </div>
            </div>

            <div class="space-y-4">
                @foreach($components as $key => $comp)
                    @php
                        $pct = $comp['max_points'] > 0 ? (int) round(($comp['score'] / $comp['max_points']) * 100) : 0;
                        $colorClass = $pct >= 85 ? 'bg-emerald-600' : ($pct >= 70 ? 'bg-indigo-600' : ($pct >= 50 ? 'bg-amber-500' : 'bg-rose-500'));
                    @endphp
                    <div class="p-4 rounded-2xl bg-slate-50 border border-slate-100">
                        <div class="flex items-center justify-between text-xs mb-2">
                            <span class="font-bold text-slate-900">{{ $comp['title'] }}</span>
                            <div class="flex items-center gap-2">
                                <span class="font-extrabold text-slate-800">{{ $comp['score'] }} / {{ $comp['max_points'] }} pts</span>
                                <span class="text-[11px] font-bold text-slate-400">({{ $pct }}%)</span>
                            </div>
                        </div>
                        <div class="w-full bg-slate-200 rounded-full h-2 overflow-hidden">
                            <div class="h-2 rounded-full {{ $colorClass }} transition-all duration-700" style="width: {{ $pct }}%"></div>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>

        {{-- ======================================================== --}}
        {{-- SECTION 3: THREE CATEGORY QUALITY SCORES                 --}}
        {{-- ======================================================== --}}
        <div class="mb-8 sm:mb-10">
            <h2 class="text-base sm:text-lg font-extrabold text-slate-900 tracking-tight mb-4">Content Quality Scores</h2>
            
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4 sm:gap-5">
                <!-- Impact -->
                <div class="bg-white border border-slate-200/80 rounded-2xl p-5 shadow-xs">
                    <div class="flex items-center justify-between text-xs mb-2">
                        <span class="font-bold text-slate-900 flex items-center gap-1.5">
                            <span class="w-2.5 h-2.5 rounded-full bg-indigo-600"></span>
                            Impact
                        </span>
                        <span class="font-black text-sm text-indigo-600">{{ $categoryScores['impact'] ?? 'Not available' }}@if($categoryScores['impact'] !== null) / 100 @endif</span>
                    </div>
                    @if($categoryScores['impact'] !== null)
                        <div class="w-full bg-slate-100 rounded-full h-2 overflow-hidden mb-2">
                            <div class="h-2 rounded-full bg-indigo-600" style="width: {{ $categoryScores['impact'] }}%"></div>
                        </div>
                    @endif
                    <p class="text-[11px] text-slate-500 leading-relaxed">
                        AI-generated estimate based on achievement descriptions, metrics, and action-oriented wording.
                    </p>
                    @if(!empty($scoreEvidence['impact']['quotes']))
                        <div class="mt-3 border-t border-slate-100 pt-3">
                            <p class="text-[10px] font-bold uppercase tracking-wider text-slate-400">Resume evidence</p>
                            @if(!empty($scoreEvidence['impact']['reasoning']))
                                <p class="mt-1 text-[11px] leading-relaxed text-slate-600">{{ $scoreEvidence['impact']['reasoning'] }}</p>
                            @endif
                            @foreach($scoreEvidence['impact']['quotes'] as $quote)
                                <blockquote class="mt-2 rounded-lg bg-slate-50 px-3 py-2 text-[11px] leading-relaxed text-slate-700">“{{ $quote }}”</blockquote>
                            @endforeach
                        </div>
                    @endif
                </div>

                <!-- Clarity -->
                <div class="bg-white border border-slate-200/80 rounded-2xl p-5 shadow-xs">
                    <div class="flex items-center justify-between text-xs mb-2">
                        <span class="font-bold text-slate-900 flex items-center gap-1.5">
                            <span class="w-2.5 h-2.5 rounded-full bg-blue-600"></span>
                            Clarity
                        </span>
                        <span class="font-black text-sm text-blue-600">{{ $categoryScores['clarity'] ?? 'Not available' }}@if($categoryScores['clarity'] !== null) / 100 @endif</span>
                    </div>
                    @if($categoryScores['clarity'] !== null)
                        <div class="w-full bg-slate-100 rounded-full h-2 overflow-hidden mb-2">
                            <div class="h-2 rounded-full bg-blue-600" style="width: {{ $categoryScores['clarity'] }}%"></div>
                        </div>
                    @endif
                    <p class="text-[11px] text-slate-500 leading-relaxed">
                        AI-generated estimate based on text clarity, bullet length, and wording.
                    </p>
                    @if(!empty($scoreEvidence['clarity']['quotes']))
                        <div class="mt-3 border-t border-slate-100 pt-3">
                            <p class="text-[10px] font-bold uppercase tracking-wider text-slate-400">Resume evidence</p>
                            @if(!empty($scoreEvidence['clarity']['reasoning']))
                                <p class="mt-1 text-[11px] leading-relaxed text-slate-600">{{ $scoreEvidence['clarity']['reasoning'] }}</p>
                            @endif
                            @foreach($scoreEvidence['clarity']['quotes'] as $quote)
                                <blockquote class="mt-2 rounded-lg bg-slate-50 px-3 py-2 text-[11px] leading-relaxed text-slate-700">“{{ $quote }}”</blockquote>
                            @endforeach
                        </div>
                    @endif
                </div>

                <!-- Relevance -->
                <div class="bg-white border border-slate-200/80 rounded-2xl p-5 shadow-xs">
                    <div class="flex items-center justify-between text-xs mb-2">
                        <span class="font-bold text-slate-900 flex items-center gap-1.5">
                            <span class="w-2.5 h-2.5 rounded-full bg-emerald-600"></span>
                            Relevance
                        </span>
                        <span class="font-black text-sm text-emerald-600">{{ $categoryScores['relevance'] ?? 'Not available' }}@if($categoryScores['relevance'] !== null) / 100 @endif</span>
                    </div>
                    @if($categoryScores['relevance'] !== null)
                        <div class="w-full bg-slate-100 rounded-full h-2 overflow-hidden mb-2">
                            <div class="h-2 rounded-full bg-emerald-600" style="width: {{ $categoryScores['relevance'] }}%"></div>
                        </div>
                    @endif
                    <p class="text-[11px] text-slate-500 leading-relaxed">
                        AI-generated estimate based on resume content and any target role or job description provided.
                    </p>
                    @if(!empty($scoreEvidence['relevance']['quotes']))
                        <div class="mt-3 border-t border-slate-100 pt-3">
                            <p class="text-[10px] font-bold uppercase tracking-wider text-slate-400">Resume evidence</p>
                            @if(!empty($scoreEvidence['relevance']['reasoning']))
                                <p class="mt-1 text-[11px] leading-relaxed text-slate-600">{{ $scoreEvidence['relevance']['reasoning'] }}</p>
                            @endif
                            @foreach($scoreEvidence['relevance']['quotes'] as $quote)
                                <blockquote class="mt-2 rounded-lg bg-slate-50 px-3 py-2 text-[11px] leading-relaxed text-slate-700">“{{ $quote }}”</blockquote>
                            @endforeach
                        </div>
                    @endif
                </div>
            </div>
        </div>

        {{-- ======================================================== --}}
        {{-- SECTION 4: SPECIFIC FIXES (ORDERED BY POINTS LOST)       --}}
        {{-- ======================================================== --}}
        <div class="bg-white border border-slate-200/90 rounded-3xl p-6 sm:p-8 shadow-sm mb-8 sm:mb-10">
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 mb-6 pb-4 border-b border-slate-100">
                <div>
                    <h2 class="text-base sm:text-lg font-extrabold text-slate-900 tracking-tight">Pass / Fail Checklist & Fixes</h2>
                    <p class="text-xs text-slate-500 mt-0.5">
                        Specific fixes ordered by points lost. Every failed check includes a concrete fix.
                    </p>
                </div>
                <div class="flex items-center gap-2 text-xs font-bold">
                    <span class="px-2.5 py-1 rounded-lg bg-emerald-50 text-emerald-700 border border-emerald-200">
                        {{ count($passedChecks) }} Passed
                    </span>
                    <span class="px-2.5 py-1 rounded-lg bg-rose-50 text-rose-700 border border-rose-200">
                        {{ count($failedChecks) }} Need Fix
                    </span>
                </div>
            </div>

            <!-- Failed Checks -->
            @if(count($failedChecks) > 0)
                <div class="mb-8">
                    <h3 class="text-xs font-bold uppercase tracking-wider text-rose-700 mb-3 flex items-center gap-1.5">
                        <svg class="w-4 h-4 text-rose-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                        </svg>
                        <span>Fix These Items First (Ordered by Points Lost)</span>
                    </h3>

                    <div class="space-y-3">
                        @foreach($failedChecks as $chk)
                            <div class="p-4 rounded-2xl bg-rose-50/40 border border-rose-200/80 flex flex-col sm:flex-row sm:items-start justify-between gap-3">
                                <div class="space-y-1">
                                    <div class="flex items-center gap-2">
                                        <span class="w-5 h-5 rounded-full bg-rose-100 text-rose-700 flex items-center justify-center shrink-0">
                                            <svg class="w-3 h-3" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M6 18L18 6M6 6l12 12" />
                                            </svg>
                                        </span>
                                        <span class="text-xs sm:text-sm font-bold text-slate-900">{{ $chk['title'] }}</span>
                                        <span class="text-[10px] uppercase font-semibold text-slate-400 bg-white px-2 py-0.5 rounded border border-slate-200">{{ $chk['component'] }}</span>
                                    </div>
                                    <p class="text-xs text-rose-900 font-medium pl-7 flex items-start gap-1.5">
                                        <svg class="w-3.5 h-3.5 text-rose-600 shrink-0 mt-0.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M13.5 4.5L21 12m0 0l-7.5 7.5M21 12H3" />
                                        </svg>
                                        <span><span class="font-bold">Fix:</span> {{ $chk['fix'] }}</span>
                                    </p>
                                    @if(!empty($chk['detail']))
                                        <p class="text-[11px] text-slate-500 pl-7">{{ $chk['detail'] }}</p>
                                    @endif
                                </div>
                                <span class="text-xs font-extrabold text-rose-700 bg-white px-2.5 py-1 rounded-lg border border-rose-200 shrink-0 self-start sm:self-center">
                                    -{{ $chk['max_points'] - $chk['score'] }} pts
                                </span>
                            </div>
                        @endforeach
                    </div>
                </div>
            @endif

            <!-- Passed Checks -->
            <div>
                <h3 class="text-xs font-bold uppercase tracking-wider text-emerald-700 mb-3 flex items-center gap-1.5">
                    <svg class="w-4 h-4 text-emerald-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7" />
                    </svg>
                    <span>Passed Checks ({{ count($passedChecks) }})</span>
                </h3>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                    @foreach($passedChecks as $chk)
                        <div class="p-3.5 rounded-2xl bg-emerald-50/40 border border-emerald-200/70 flex items-center justify-between gap-2">
                            <div class="flex items-center gap-2">
                                <span class="w-5 h-5 rounded-full bg-emerald-100 text-emerald-700 flex items-center justify-center shrink-0">
                                    <svg class="w-3 h-3" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7" />
                                    </svg>
                                </span>
                                <div>
                                    <p class="text-xs font-bold text-slate-900">{{ $chk['title'] }}</p>
                                    <p class="text-[10px] text-slate-500">{{ $chk['detail'] ?: $chk['component'] }}</p>
                                </div>
                            </div>
                            <span class="text-xs font-extrabold text-emerald-700 shrink-0">+{{ $chk['score'] }} pts</span>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>

        {{-- ======================================================== --}}
        {{-- SECTION 5: PRIORITY RECRUITER ISSUES                     --}}
        {{-- ======================================================== --}}
        @if(count($priorityIssues) > 0)
            <div class="bg-white border border-slate-200/90 rounded-3xl p-6 sm:p-8 shadow-sm mb-8 sm:mb-10">
                <div class="mb-6">
                    <h2 class="text-base sm:text-lg font-extrabold text-slate-900 tracking-tight">Priority Recruiter Issues</h2>
                    <p class="text-xs text-slate-500 mt-0.5">Priority labels are estimates from the content analysis. Review each suggestion against your experience.</p>
                </div>

                <div class="space-y-4">
                    @foreach($priorityIssues as $issue)
                        @php
                            $sev = strtolower($issue['severity'] ?? 'medium');
                            $sevClasses = match($sev) {
                                'high' => ['border' => 'border-rose-200 bg-rose-50/30', 'badge' => 'bg-rose-100 text-rose-800 border-rose-200', 'tag' => 'High Priority'],
                                'medium' => ['border' => 'border-amber-200 bg-amber-50/30', 'badge' => 'bg-amber-100 text-amber-800 border-amber-200', 'tag' => 'Medium Priority'],
                                default => ['border' => 'border-blue-200 bg-blue-50/30', 'badge' => 'bg-blue-100 text-blue-800 border-blue-200', 'tag' => 'Low Priority'],
                            };
                        @endphp
                        <div class="p-4 sm:p-5 rounded-2xl border {{ $sevClasses['border'] }}">
                            <div class="flex items-center justify-between gap-2 mb-2">
                                <div class="flex items-center gap-2">
                                    <span class="px-2.5 py-0.5 rounded-md text-[10px] font-extrabold uppercase border {{ $sevClasses['badge'] }}">
                                        {{ $sevClasses['tag'] }}
                                    </span>
                                    @if(!empty($issue['section']))
                                        <span class="text-xs font-bold text-slate-500">in {{ $issue['section'] }}</span>
                                    @endif
                                </div>
                            </div>
                            <p class="text-xs sm:text-sm font-bold text-slate-900 mb-1">
                                {{ $issue['problem'] ?? ($issue['title'] ?? '') }}
                            </p>
                            @if(!empty($issue['fix']))
                                <p class="text-xs text-slate-700 leading-relaxed bg-white/80 p-3 rounded-xl border border-slate-200 mt-2">
                                    <span class="font-bold text-indigo-600">Actionable Fix:</span> {{ $issue['fix'] }}
                                </p>
                            @elseif(!empty($issue['description']))
                                <p class="text-xs text-slate-600 mt-1">{{ $issue['description'] }}</p>
                            @endif
                        </div>
                    @endforeach
                </div>
            </div>
        @endif

        {{-- ======================================================== --}}
        {{-- SECTION 6: SUGGESTED REWRITES                           --}}
        {{-- ======================================================== --}}
        @if(count($rewrites) > 0)
            <div class="bg-white border border-slate-200/90 rounded-3xl p-6 sm:p-8 shadow-sm mb-8 sm:mb-10">
                <div class="mb-6">
                    <span class="text-xs font-bold uppercase tracking-wider text-indigo-600 bg-indigo-50 px-3 py-1 rounded-full border border-indigo-200">AI-assisted suggestions</span>
                    <h2 class="text-base sm:text-lg font-extrabold text-slate-900 tracking-tight mt-2">Bullet Point Rewrites</h2>
                    <p class="text-xs text-slate-500 mt-0.5">
                        Suggestions may be inaccurate or include assumptions. Verify every detail and only use statements that reflect your experience.
                    </p>
                </div>

                <div class="space-y-6">
                    @foreach($rewrites as $idx => $rw)
                        <div class="p-4 sm:p-5 rounded-2xl bg-slate-50 border border-slate-200 space-y-3">
                            <!-- Original -->
                            <div class="p-3.5 rounded-xl bg-white border border-slate-200">
                                <span class="text-[10px] font-bold text-slate-400 uppercase tracking-wider block mb-1">Original Bullet</span>
                                <p class="text-xs sm:text-sm text-slate-700 italic">"{{ $rw['original'] }}"</p>
                            </div>

                            <!-- Improved Rewrite -->
                            <div class="p-3.5 rounded-xl bg-emerald-50 border border-emerald-200 relative group">
                                <div class="flex items-center justify-between mb-1">
                                    <span class="text-[10px] font-bold text-emerald-700 uppercase tracking-wider">Suggested rewrite</span>
                                    <button type="button" onclick="copyRewrite(this, '{{ addslashes($rw['improved']) }}')" class="px-2.5 py-1 rounded-lg bg-white border border-emerald-300 text-emerald-800 hover:bg-emerald-600 hover:text-white text-[11px] font-bold transition flex items-center gap-1 cursor-pointer">
                                        <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z" />
                                        </svg>
                                        <span>Copy</span>
                                    </button>
                                </div>
                                <p class="text-xs sm:text-sm text-slate-900 font-semibold leading-relaxed">
                                    "{{ $rw['improved'] }}"
                                </p>
                            </div>

                            @if(!empty($rw['reasoning']))
                                <p class="text-[11px] text-slate-500 font-medium pl-1 flex items-start gap-1.5">
                                    <svg class="w-3.5 h-3.5 text-amber-500 shrink-0 mt-0.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z" />
                                    </svg>
                                    <span><span class="font-bold text-slate-700">Why this works:</span> {{ $rw['reasoning'] }}</span>
                                </p>
                            @endif
                        </div>
                    @endforeach
                </div>
            </div>
        @endif

        {{-- ======================================================== --}}
        {{-- SECTION 7: MISSING KEYWORDS AS TAGS                      --}}
        {{-- ======================================================== --}}
        @if(count($missingKeywords) > 0)
            <div class="bg-white border border-slate-200/90 rounded-3xl p-6 sm:p-8 shadow-sm mb-8 sm:mb-10">
                <div class="mb-4">
                    <h2 class="text-base sm:text-lg font-extrabold text-slate-900 tracking-tight">Missing Keywords</h2>
                    <p class="text-xs text-slate-500 mt-0.5">
                        High-value competencies or tools expected for your target profile that were not detected in your resume text.
                    </p>
                </div>

                <div class="flex flex-wrap gap-2">
                    @foreach($missingKeywords as $kw)
                        <span class="inline-flex items-center gap-1 px-3 py-1.5 rounded-xl text-xs font-semibold bg-rose-50 text-rose-700 border border-rose-200">
                            <span class="text-rose-400 font-bold">+</span>
                            <span>{{ $kw }}</span>
                        </span>
                    @endforeach
                </div>

                <p class="text-[11px] text-slate-400 mt-4 leading-relaxed flex items-center gap-1.5">
                    <svg class="w-3.5 h-3.5 text-amber-500 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                    </svg>
                    <span><span class="font-semibold text-slate-600">Note:</span> Only include missing keywords where you have genuine experience. Do not keyword-stuff.</span>
                </p>
            </div>
        @endif

        {{-- ======================================================== --}}
        {{-- SECTION 8: "WHAT WE CHECKED" & HONEST DISCLAIMER         --}}
        {{-- ======================================================== --}}
        <div class="bg-slate-50 border border-slate-200 rounded-3xl p-6 sm:p-8 shadow-xs mb-8 sm:mb-10">
            <h2 class="text-xs font-bold uppercase tracking-wider text-slate-500 mb-3">What We Checked & Parser Limits</h2>
            <div class="space-y-2 text-xs text-slate-600 leading-relaxed">
                <p>
                    <strong>1. What was checked:</strong> Extracted resume text, common section headings, contact detail placement, date consistency, text characters, and keyword overlap when a job description is provided.
                </p>
                <p>
                    <strong>2. Limitations:</strong> Applicant tracking systems parse documents differently. This report uses this application's checks and does not guarantee how any specific employer's system will process your resume.
                </p>
            </div>
        </div>

        <!-- Footer Call to Action -->
        <div class="text-center py-6">
            <a href="{{ route('home') }}" class="inline-flex items-center gap-2 px-8 py-3.5 rounded-2xl bg-indigo-600 hover:bg-indigo-700 text-white font-extrabold text-sm shadow-lg shadow-indigo-600/30 transition">
                <span>Upload Another CV</span>
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3" />
                </svg>
            </a>
        </div>
    @endif
</div>
@endsection

@push('scripts')
<script>
    function copyRewrite(btn, text) {
        navigator.clipboard.writeText(text).then(() => {
            const originalHtml = btn.innerHTML;
            btn.innerHTML = `
                <svg class="w-3.5 h-3.5 text-emerald-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7" />
                </svg>
                <span>Copied!</span>
            `;
            setTimeout(() => {
                btn.innerHTML = originalHtml;
            }, 2000);
        }).catch(err => {
            alert('Could not copy to clipboard.');
        });
    }
</script>
@endpush
