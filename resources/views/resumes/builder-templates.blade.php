@extends('layouts.app')

@section('title', 'Choose a Resume Template | Resume Review')
@section('meta_description', 'Preview four clean resume layouts, choose the style you like, and fill in your own details.')

@section('content')
<div class="mx-auto max-w-7xl px-4 py-10 sm:px-6 sm:py-14 lg:px-8">
    <header class="mx-auto mb-10 max-w-3xl text-center">
        <span class="inline-flex items-center gap-2 rounded-full border border-indigo-100 bg-indigo-50 px-3.5 py-1.5 text-xs font-bold tracking-wide text-indigo-700">
            <i class="fa-solid fa-file-circle-check" aria-hidden="true"></i>
            Resume builder
        </span>
        <h1 class="mt-4 text-3xl font-extrabold tracking-tight text-slate-950 sm:text-5xl">Choose a resume design</h1>
        <p class="mx-auto mt-4 max-w-2xl text-sm leading-relaxed text-slate-600 sm:text-base">
            Preview each layout, then choose one to open the editor and add your details.
        </p>
        <p class="mt-3 text-xs text-slate-500">All four layouts use a single-column reading order. Employers' systems may parse resumes differently.</p>
    </header>

    <section aria-label="Resume template choices" class="grid gap-5 sm:grid-cols-2 xl:grid-cols-4">
        @foreach($templates as $key => $label)
            <a href="{{ route('resume-builder.edit', ['template' => $key]) }}" aria-label="Choose the {{ $label }} layout" class="group rounded-2xl border border-slate-200 bg-white p-4 shadow-sm transition hover:-translate-y-1 hover:border-indigo-300 hover:shadow-lg focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-indigo-600">
                <div class="mb-4 flex aspect-[0.86] items-center justify-center overflow-hidden rounded-xl bg-slate-100 p-4 sm:p-5">
                    <div class="h-full w-full max-w-[230px] overflow-hidden rounded-sm bg-white p-4 shadow-md sm:p-5">
                        @if($key === 'classic')
                            <div class="border-b-2 border-slate-800 pb-2 text-center">
                                <span class="block text-[10px] font-extrabold tracking-wide text-slate-900 sm:text-xs">YOUR NAME</span>
                                <span class="mt-1 block text-[6px] text-slate-500 sm:text-[7px]">TARGET ROLE · EMAIL · LOCATION</span>
                            </div>
                            <div class="mt-3 space-y-3">
                                @foreach(['PROFILE', 'EXPERIENCE', 'EDUCATION'] as $heading)
                                    <div>
                                        <span class="block text-[6px] font-extrabold tracking-widest text-slate-800 sm:text-[7px]">{{ $heading }}</span>
                                        <span class="mt-1 block h-px w-full bg-slate-300"></span>
                                        <span class="mt-1.5 block h-1 w-full bg-slate-200"></span>
                                        <span class="mt-1 block h-1 w-4/5 bg-slate-200"></span>
                                    </div>
                                @endforeach
                            </div>
                        @elseif($key === 'modern')
                            <div class="border-l-4 border-indigo-600 pl-2.5">
                                <span class="block text-[10px] font-extrabold text-slate-900 sm:text-xs">YOUR NAME</span>
                                <span class="mt-1 block text-[6px] font-semibold text-indigo-600 sm:text-[7px]">TARGET ROLE</span>
                            </div>
                            <div class="mt-4 space-y-3 border-l border-slate-200 pl-2.5">
                                @foreach(['ABOUT', 'EXPERIENCE', 'EDUCATION'] as $heading)
                                    <div>
                                        <span class="block text-[6px] font-bold text-indigo-700 sm:text-[7px]">{{ $heading }}</span>
                                        <span class="mt-1.5 block h-1 w-full rounded bg-slate-200"></span>
                                        <span class="mt-1 block h-1 w-4/5 rounded bg-slate-200"></span>
                                        <span class="mt-1 block h-1 w-3/5 rounded bg-slate-200"></span>
                                    </div>
                                @endforeach
                            </div>
                        @elseif($key === 'compact')
                            <div class="border-b border-teal-700 pb-1.5">
                                <span class="block text-[9px] font-extrabold text-slate-900 sm:text-[10px]">YOUR NAME</span>
                                <span class="mt-0.5 block text-[5px] text-slate-500 sm:text-[6px]">TARGET ROLE · EMAIL · LOCATION</span>
                            </div>
                            <div class="mt-2 space-y-2">
                                @foreach(['SUMMARY', 'EXPERIENCE', 'EDUCATION', 'SKILLS'] as $heading)
                                    <div class="flex gap-2">
                                        <span class="w-12 shrink-0 text-[5px] font-extrabold text-teal-700 sm:text-[6px]">{{ $heading }}</span>
                                        <span class="flex-1 border-t border-slate-200 pt-1">
                                            <span class="block h-1 w-full bg-slate-200"></span>
                                            <span class="mt-1 block h-1 w-4/5 bg-slate-200"></span>
                                        </span>
                                    </div>
                                @endforeach
                            </div>
                        @else
                            <div class="pb-3">
                                <span class="block font-serif text-[11px] text-slate-900 sm:text-sm">Your Name</span>
                                <span class="mt-1 block text-[6px] text-slate-500 sm:text-[7px]">Target Role · Contact details</span>
                            </div>
                            <div class="space-y-4">
                                @foreach(['Profile', 'Experience', 'Education'] as $heading)
                                    <div>
                                        <span class="block font-serif text-[7px] text-slate-800 sm:text-[8px]">{{ $heading }}</span>
                                        <span class="mt-1.5 block h-px w-8 bg-slate-400"></span>
                                        <span class="mt-1.5 block h-1 w-full bg-slate-200"></span>
                                        <span class="mt-1 block h-1 w-3/4 bg-slate-200"></span>
                                    </div>
                                @endforeach
                            </div>
                        @endif
                    </div>
                </div>

                <div class="flex items-start justify-between gap-3">
                    <div>
                        <h2 class="text-base font-extrabold text-slate-900">{{ $label }}</h2>
                        <p class="mt-1 min-h-10 text-xs leading-relaxed text-slate-500">
                            @switch($key)
                                @case('classic') Traditional headings with a restrained navy accent. @break
                                @case('modern') Indigo labels and clear, balanced section spacing. @break
                                @case('compact') Tighter spacing for a concise, information-dense resume. @break
                                @default Minimal ink, simple headings, and generous white space.
                            @endswitch
                        </p>
                    </div>
                    <span class="inline-flex shrink-0 items-center gap-1.5 rounded-lg bg-indigo-600 px-3 py-2 text-xs font-bold text-white transition group-hover:bg-indigo-700">
                        Choose <i class="fa-solid fa-arrow-right" aria-hidden="true"></i>
                    </span>
                </div>
            </a>
        @endforeach
    </section>
</div>
@endsection
