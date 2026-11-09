@extends('layouts.app')

@section('title', 'Scan History | Free Resume Checker')

@section('content')
<div class="py-8 lg:py-12 max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 mb-8">
        <div>
            <h1 class="text-2xl sm:text-3xl font-extrabold text-slate-900 tracking-tight">Analysis History</h1>
            <p class="text-xs sm:text-sm text-slate-500 mt-1">View available resume reports, review their scores, and export a copy.</p>
        </div>

        <div class="flex items-center gap-3">
            <a href="{{ route('home') }}" class="inline-flex items-center gap-2 px-4 py-2.5 rounded-xl bg-indigo-600 hover:bg-indigo-700 text-white font-semibold text-xs shadow-md shadow-indigo-600/20 transition">
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                </svg>
                <span>Analyze New Resume</span>
            </a>
        </div>
    </div>

    <!-- Search Bar -->
    <div class="mb-6">
        <form action="{{ route('analyses.history') }}" method="GET" class="flex items-center gap-2 max-w-md">
            <div class="relative flex-1">
                <input type="text" name="search" value="{{ request('search') }}" placeholder="Search candidate, role, or file..." class="w-full rounded-xl border border-slate-300 focus:border-indigo-500 focus:ring-4 focus:ring-indigo-500/10 pl-9 pr-3.5 py-2 text-xs text-slate-800 bg-white">
                <svg class="w-4 h-4 text-slate-400 absolute left-3 top-2.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                </svg>
            </div>
            <button type="submit" class="px-3.5 py-2 rounded-xl bg-slate-800 hover:bg-slate-900 text-white font-semibold text-xs transition">
                Filter
            </button>
            @if(request('search'))
                <a href="{{ route('analyses.history') }}" class="px-3 py-2 rounded-xl border border-slate-200 text-slate-600 hover:text-slate-900 text-xs transition">
                    Clear
                </a>
            @endif
        </form>
    </div>

    @if($analyses->isEmpty())
        <div class="bg-white border border-slate-200 rounded-3xl p-12 text-center max-w-lg mx-auto shadow-sm">
            <div class="w-16 h-16 rounded-2xl bg-indigo-50 text-indigo-600 flex items-center justify-center mx-auto mb-4">
                <svg class="w-8 h-8" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                </svg>
            </div>
            <h2 class="text-base font-bold text-slate-900">No Resume Analyses Found</h2>
            <p class="text-xs text-slate-500 mt-1">
                @if(request('search'))
                    No results matched your search term "{{ request('search') }}".
                @else
                    No resume reports are available yet. Upload a resume to run the checks and view a report when processing is complete.
                @endif
            </p>
            <div class="mt-6">
                <a href="{{ route('home') }}" class="inline-flex items-center gap-2 px-5 py-2.5 rounded-xl bg-indigo-600 hover:bg-indigo-700 text-white font-semibold text-xs shadow-md transition">
                    Start Your First Analysis
                </a>
            </div>
        </div>
    @else
        <!-- Analyses Table -->
        <div class="bg-white border border-slate-200 rounded-3xl shadow-sm overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse">
                    <thead>
                        <tr class="bg-slate-50/80 border-b border-slate-200 text-[11px] font-bold text-slate-500 uppercase tracking-wider">
                            <th class="py-3.5 px-6">Candidate & Role</th>
                            <th class="py-3.5 px-6">Overall Score</th>
                            <th class="py-3.5 px-6">ATS Readability</th>
                            <th class="py-3.5 px-6">Readability Status</th>
                            <th class="py-3.5 px-6">Date</th>
                            <th class="py-3.5 px-6 text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 text-xs">
                        @foreach($analyses as $item)
                            <tr class="hover:bg-slate-50/60 transition group">
                                <td class="py-4 px-6">
                                    <a href="{{ route('analyses.show', $item) }}" class="font-bold text-slate-900 group-hover:text-indigo-600 transition block">
                                        {{ $item->candidate_name ?: 'Candidate' }}
                                    </a>
                                    <div class="flex items-center gap-1.5 text-[11px] text-slate-500 mt-0.5">
                                        <span>{{ $item->target_role ?: ($item->result_json['detected_role'] ?? 'Professional') }}</span>
                                        @if($item->resume_file_name)
                                            <span class="text-slate-300">•</span>
                                            <span class="font-mono text-[10px] text-slate-400 truncate max-w-xs">{{ $item->resume_file_name }}</span>
                                        @endif
                                    </div>
                                </td>
                                <td class="py-4 px-6">
                                    @if($item->isDone())
                                        <div class="flex items-center gap-2">
                                            <span class="inline-flex items-center justify-center w-9 h-9 rounded-xl font-extrabold text-sm {{ $item->overall_score >= 80 ? 'bg-emerald-50 text-emerald-700 border border-emerald-200' : ($item->overall_score >= 65 ? 'bg-amber-50 text-amber-700 border border-amber-200' : 'bg-rose-50 text-rose-700 border border-rose-200') }}">
                                                {{ $item->overall_score }}
                                            </span>
                                            <span class="text-[11px] font-semibold text-slate-500">{{ $item->score_level }}</span>
                                        </div>
                                    @else
                                        <span class="inline-flex items-center gap-1.5 rounded-lg border border-slate-200 bg-slate-50 px-2.5 py-1 text-[11px] font-semibold text-slate-600">
                                            <i class="fa-solid {{ $item->isFailed() ? 'fa-circle-exclamation' : 'fa-clock' }}" aria-hidden="true"></i>
                                            {{ $item->isFailed() ? 'Failed' : 'In progress' }}
                                        </span>
                                    @endif
                                </td>
                                <td class="py-4 px-6">
                                    @if($item->isDone())
                                        <div class="flex items-center gap-2">
                                            <span class="font-bold text-slate-800">{{ $item->ats_score }}%</span>
                                            <div class="w-16 bg-slate-100 rounded-full h-1.5 overflow-hidden">
                                                <div class="h-1.5 rounded-full bg-indigo-600" style="width: {{ $item->ats_score }}%"></div>
                                            </div>
                                        </div>
                                    @else
                                        <span class="text-slate-400">—</span>
                                    @endif
                                </td>
                                <td class="py-4 px-6">
                                    @if($item->isDone())
                                        <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-lg text-[11px] font-semibold border {{ $item->readability_band['badge'] }}">
                                            {{ $item->readability_band['label'] }}
                                        </span>
                                    @else
                                        <span class="text-slate-400">—</span>
                                    @endif
                                </td>
                                <td class="py-4 px-6 text-slate-500">
                                    <span class="block text-slate-700">{{ $item->created_at->format('M d, Y') }}</span>
                                    <span class="text-[10px] text-slate-400">{{ $item->created_at->diffForHumans() }}</span>
                                </td>
                                <td class="py-4 px-6 text-right">
                                    <div class="flex items-center justify-end gap-2">
                                        <a href="{{ route('analyses.show', $item) }}" class="px-2.5 py-1 rounded-lg bg-indigo-50 text-indigo-700 hover:bg-indigo-100 font-semibold text-xs transition">
                                            View Report
                                        </a>
                                        @if($item->isDone())
                                        <a href="{{ route('analyses.export', $item) }}" target="_blank" class="p-1 text-slate-400 hover:text-slate-600 transition" title="Print / PDF">
                                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z" />
                                            </svg>
                                        </a>
                                        @endif
                                        <form action="{{ route('analyses.destroy', $item) }}" method="POST" onsubmit="return confirm('Are you sure you want to delete this analysis report?');" class="inline">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="p-1 text-slate-400 hover:text-rose-600 transition" title="Delete">
                                                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                                </svg>
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <!-- Pagination -->
            @if($analyses->hasPages())
                <div class="p-4 border-t border-slate-100 bg-slate-50/50">
                    {{ $analyses->links() }}
                </div>
            @endif
        </div>
    @endif
</div>
@endsection
