<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Resume Audit Report - {{ $analysis->candidate_name ?: 'Candidate' }}</title>
    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=instrument-sans:400,500,600,700" rel="stylesheet" />
    @vite(['resources/css/app.css'])

    <style>
        @media print {
            .no-print { display: none !important; }
            body { background: white !important; color: black !important; }
            .page-break { page-break-before: always; }
            @page { margin: 1.5cm; }
        }
    </style>
</head>
<body class="bg-slate-100 text-slate-800 font-sans p-4 sm:p-8">
    @php
        $res = $analysis->result_json ?? [];
        $categoryScores = $res['category_scores'] ?? [];
        $ats = $res['ats_check'] ?? [];
        $strengths = $res['strengths'] ?? [];
        $weaknesses = $res['weaknesses'] ?? [];
        $rewrites = $res['bullet_point_rewrites'] ?? [];
        $jobMatch = $res['job_match'] ?? [];
        $interviewPrep = $res['interview_prep'] ?? [];
        $skillTags = $res['skill_tags'] ?? [];
    @endphp

    <!-- Print Action Bar (Hidden on print) -->
    <div class="no-print max-w-4xl mx-auto mb-6 flex items-center justify-between bg-white p-4 rounded-2xl shadow-sm border border-slate-200">
        <div class="flex items-center gap-2">
            <a href="{{ route('analyses.show', $analysis) }}" class="text-xs font-semibold text-slate-600 hover:text-indigo-600 transition flex items-center gap-1">
                &larr; Back to Interactive Report
            </a>
        </div>
        <div class="flex items-center gap-2">
            <button onclick="window.print()" class="px-4 py-2 rounded-xl bg-indigo-600 hover:bg-indigo-700 text-white font-bold text-xs shadow transition flex items-center gap-1.5">
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z" />
                </svg>
                <span>Print / Save as PDF</span>
            </button>
        </div>
    </div>

    <!-- Printable Document Sheet -->
    <div class="max-w-4xl mx-auto bg-white p-8 sm:p-12 rounded-3xl shadow-sm border border-slate-200 space-y-8 print:border-none print:shadow-none print:p-0">
        <!-- Document Header -->
        <div class="border-b-2 border-slate-900 pb-6">
            <div class="flex items-start justify-between">
                <div>
                    <span class="text-[11px] font-bold uppercase tracking-widest text-indigo-600 block">Resume Readability Report</span>
                    <h1 class="text-3xl font-extrabold text-slate-900 mt-1">{{ $analysis->candidate_name ?: 'Candidate' }}</h1>
                    <p class="text-sm font-semibold text-slate-600 mt-0.5">{{ $analysis->target_role ?: ($res['detected_role'] ?? 'Target role not provided') }}</p>
                    <p class="text-xs text-slate-400 mt-1">Generated: {{ $analysis->created_at->format('F d, Y \a\t h:i A') }} &bull; App-based readability checks</p>
                </div>
                <div class="text-right">
                    <div class="inline-flex flex-col items-center justify-center p-3 bg-slate-50 border-2 border-slate-900 rounded-2xl min-w-24">
                        <span class="text-3xl font-black text-slate-900">{{ $analysis->overall_score ?? '—' }}</span>
                        <span class="text-[10px] font-bold uppercase tracking-wider text-slate-500">Overall Score</span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Executive Summary -->
        <div>
            <h2 class="text-xs font-bold uppercase tracking-wider text-slate-500 mb-1">AI-generated summary</h2>
            <p class="text-sm text-slate-800 leading-relaxed font-medium bg-slate-50 p-4 rounded-xl border border-slate-200">
                {{ $res['summary_verdict'] ?? 'No summary was returned for this analysis.' }}
            </p>
        </div>

        <!-- Pillar Scores Table -->
        <div>
            <h2 class="text-xs font-bold uppercase tracking-wider text-slate-500 mb-2">Score breakdown</h2>
            <table class="w-full text-xs text-left border border-slate-200 rounded-xl overflow-hidden">
                <thead class="bg-slate-100 font-bold text-slate-700">
                    <tr>
                        <th class="p-2.5">Category</th>
                        <th class="p-2.5 text-center">Score</th>
                        <th class="p-2.5">Assessment</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @if($analysis->ats_score !== null)
                        <tr>
                            <td class="p-2.5 font-semibold">Resume readability checks</td>
                            <td class="p-2.5 text-center font-bold text-indigo-600">{{ $analysis->ats_score }}%</td>
                            <td class="p-2.5 text-slate-600">Weighted text and format checks from this application.</td>
                        </tr>
                    @endif
                    @foreach([
                        'Impact' => $analysis->impact_score ?? $categoryScores['impact'] ?? null,
                        'Clarity' => $analysis->clarity_score ?? $categoryScores['clarity'] ?? null,
                        'Relevance' => $analysis->relevance_score ?? $categoryScores['relevance'] ?? null,
                    ] as $category => $score)
                        @if($score !== null)
                            <tr>
                                <td class="p-2.5 font-semibold">AI {{ $category }} estimate</td>
                                <td class="p-2.5 text-center font-bold text-indigo-600">{{ $score }}%</td>
                                <td class="p-2.5 text-slate-600">AI-generated content feedback; review the report details.</td>
                            </tr>
                        @endif
                    @endforeach
                </tbody>
            </table>
        </div>

        <!-- ATS Keywords Analysis -->
        <div>
            <h2 class="text-xs font-bold uppercase tracking-wider text-slate-500 mb-2">Keyword mentions</h2>
            <div class="grid grid-cols-2 gap-4">
                <div class="p-4 rounded-xl border border-emerald-200 bg-emerald-50/40">
                    <span class="text-xs font-bold text-emerald-800 block mb-1">Terms detected:</span>
                    <p class="text-xs text-slate-700 leading-relaxed">
                        {{ !empty($ats['detected_keywords']) ? implode(', ', $ats['detected_keywords']) : 'No detected keyword data was returned.' }}
                    </p>
                </div>
                <div class="p-4 rounded-xl border border-rose-200 bg-rose-50/40">
                    <span class="text-xs font-bold text-rose-800 block mb-1">Terms flagged for review:</span>
                    <p class="text-xs text-slate-700 leading-relaxed">
                        {{ !empty($ats['missing_critical_keywords']) ? implode(', ', $ats['missing_critical_keywords']) : 'No flagged keyword data was returned.' }}
                    </p>
                </div>
            </div>
        </div>

        <!-- Improved Professional Summary -->
        @if(!empty($res['improved_summary']))
            <div>
                <h2 class="text-xs font-bold uppercase tracking-wider text-indigo-600 mb-1">Recommended Professional Summary (Optimized)</h2>
                <div class="p-4 bg-indigo-50/60 rounded-xl border border-indigo-200 text-xs sm:text-sm text-slate-800 leading-relaxed font-sans">
                    {{ $res['improved_summary'] }}
                </div>
            </div>
        @endif

        <!-- Before & After Bullet Rewrites -->
        <div class="page-break">
            <h2 class="text-xs font-bold uppercase tracking-wider text-slate-500 mb-3">High-Impact Bullet Point Rewrites</h2>
            <div class="space-y-4">
                @foreach($rewrites as $index => $item)
                    <div class="p-4 rounded-2xl border border-slate-200 space-y-2">
                        <span class="text-[11px] font-bold text-slate-400 uppercase">Item #{{ $index + 1 }}</span>
                        <div class="text-xs">
                            <span class="font-bold text-rose-600 block">Original (Weak):</span>
                            <span class="text-slate-600 italic">"{{ $item['original'] }}"</span>
                        </div>
                        <div class="text-xs">
                            <span class="font-bold text-emerald-600 block">AI High-Impact Rewrite (XYZ Formula):</span>
                            <span class="text-slate-900 font-semibold">"{{ $item['improved'] }}"</span>
                        </div>
                        @if(!empty($item['reasoning']))
                            <p class="text-[11px] text-slate-500 italic mt-1">Recruiter Note: {{ $item['reasoning'] }}</p>
                        @endif
                    </div>
                @endforeach
            </div>
        </div>

        <!-- Tailored Interview Prep -->
        @if(!empty($interviewPrep))
            <div>
                <h2 class="text-xs font-bold uppercase tracking-wider text-slate-500 mb-3">Anticipated Interview Questions & Tips</h2>
                <div class="space-y-3">
                    @foreach($interviewPrep as $index => $q)
                        <div class="p-3.5 rounded-xl border border-slate-200 text-xs space-y-1">
                            <div class="flex items-center justify-between text-[11px] font-bold text-indigo-600">
                                <span>{{ $q['category'] ?? 'Technical Deep Dive' }}</span>
                                <span class="text-slate-400">Q{{ $index + 1 }}</span>
                            </div>
                            <p class="font-bold text-slate-900">"{{ $q['question'] }}"</p>
                            @if(!empty($q['tip']))
                                <p class="text-slate-500 mt-1"><strong class="text-slate-700">Answer Strategy:</strong> {{ $q['tip'] }}</p>
                            @endif
                        </div>
                    @endforeach
                </div>
            </div>
        @endif

        <!-- Footer Notice -->
        <div class="pt-6 border-t border-slate-200 text-center text-[10px] text-slate-400">
            Generated by Resume Review. Scores are estimates and do not predict hiring decisions.
        </div>
    </div>
</body>
</html>
