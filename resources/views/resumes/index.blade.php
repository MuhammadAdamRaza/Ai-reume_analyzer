@extends('layouts.app')

@section('title', 'Free Resume Checker | Get Your Resume Score')
@section('meta_description', 'Upload a PDF or DOCX resume for readability checks, practical content feedback, and optional job description comparison.')

@section('content')
<div class="py-10 sm:py-14 lg:py-16 max-w-6xl mx-auto px-4 sm:px-6 lg:px-8">
    <!-- Hero Header -->
    <div class="text-center max-w-3xl mx-auto mb-8 sm:mb-10">
        <div class="inline-flex items-center gap-2 px-3.5 py-1.5 rounded-full bg-indigo-50 border border-indigo-100 text-indigo-700 text-xs font-bold tracking-wide mb-5">
            <i class="fa-solid fa-file-circle-check" aria-hidden="true"></i>
            Resume readability review
        </div>
        
        <h1 class="text-4xl sm:text-5xl lg:text-6xl font-extrabold tracking-tight text-slate-950 leading-[1.08]">
            Check your resume.<br class="hidden sm:block"> <span class="text-indigo-600">Fix what matters.</span>
        </h1>
        
        <p class="mt-5 text-base sm:text-lg text-slate-600 leading-relaxed max-w-2xl mx-auto">
            Get a resume readability report with practical format checks and AI-assisted content suggestions. Add a job description for an optional text comparison.
        </p>

        <div class="mt-6 flex flex-wrap items-center justify-center gap-x-6 gap-y-3 text-xs sm:text-sm font-medium text-slate-600">
            <span class="inline-flex items-center gap-2"><i class="fa-regular fa-file-pdf text-indigo-600" aria-hidden="true"></i> PDF or DOCX, up to 2 MB</span>
            <span class="inline-flex items-center gap-2"><i class="fa-solid fa-list-check text-indigo-600" aria-hidden="true"></i> Readability and format checks</span>
            <span class="inline-flex items-center gap-2"><i class="fa-solid fa-wand-magic-sparkles text-indigo-600" aria-hidden="true"></i> Suggested edits to review</span>
        </div>
        <p class="mt-4 text-xs text-slate-400">Scores are estimates, not predictions of ATS decisions or hiring outcomes.</p>
    </div>

    <div class="mb-6 flex flex-col gap-4 rounded-2xl border border-indigo-100 bg-indigo-50/70 p-5 sm:flex-row sm:items-center sm:justify-between sm:px-7">
        <div class="flex items-start gap-3">
            <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-white text-indigo-600 shadow-sm">
                <i class="fa-solid fa-wand-magic-sparkles" aria-hidden="true"></i>
            </span>
            <div>
                <h2 class="text-sm font-bold text-slate-900">Starting from a blank page?</h2>
                <p class="mt-1 text-xs leading-relaxed text-slate-600">Choose a resume layout, add your own details, and get optional AI writing suggestions.</p>
            </div>
        </div>
        <a href="{{ route('resume-builder.index') }}" class="inline-flex shrink-0 items-center justify-center gap-2 rounded-xl border border-indigo-200 bg-white px-4 py-2.5 text-sm font-bold text-indigo-700 transition hover:border-indigo-300 hover:bg-indigo-100">
            <i class="fa-solid fa-arrow-right text-xs" aria-hidden="true"></i>
            Build a resume
        </a>
    </div>

    <!-- Main Scanner Upload Card -->
    <div class="bg-white border border-slate-200 rounded-3xl shadow-xl shadow-slate-900/5 p-5 sm:p-8 lg:p-10 mb-12 sm:mb-16 relative overflow-hidden">
        <div class="absolute top-0 left-0 right-0 h-1 bg-indigo-600"></div>

        <form action="{{ route('analyses.analyze') }}" method="POST" enctype="multipart/form-data" id="analysisForm" onsubmit="handleFormSubmit(event)">
            @csrf
            <!-- Default provider processed automatically in background -->
            <input type="hidden" name="provider" value="{{ config('services.ai.default_provider', 'gemini') }}">

            <!-- Input Tabs: File Upload vs Paste Text -->
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 border-b border-slate-100 pb-5 mb-6">
                <div>
                    <h2 class="text-base sm:text-lg font-bold text-slate-900">Upload your resume for a free review</h2>
                    <p class="text-xs text-slate-500 mt-1">Choose a PDF or Word DOCX file, or paste your resume text.</p>
                </div>

                <div class="inline-flex self-start sm:self-auto p-1 bg-slate-100 rounded-xl text-xs font-semibold" role="group" aria-label="Resume input method">
                    <button type="button" id="tabUploadBtn" aria-pressed="true" onclick="switchInputMode('upload')" class="px-3.5 sm:px-4 py-2 rounded-lg transition-all bg-white text-indigo-600 shadow-xs font-bold cursor-pointer">
                        Upload File
                    </button>
                    <button type="button" id="tabPasteBtn" aria-pressed="false" onclick="switchInputMode('paste')" class="px-3.5 sm:px-4 py-2 rounded-lg transition-all text-slate-600 hover:text-slate-900 font-medium cursor-pointer">
                        Paste Text
                    </button>
                </div>
            </div>

            <!-- Validation Error Alert -->
            @if($errors->has('resume_file'))
                <div class="p-3.5 rounded-xl bg-rose-50 border border-rose-200 text-rose-800 text-xs font-semibold mb-4 flex items-center gap-2">
                    <svg class="w-4 h-4 text-rose-600 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                    </svg>
                    <span>{{ $errors->first('resume_file') }}</span>
                </div>
            @endif

            <!-- Upload File Pane -->
            <div id="uploadPane" class="space-y-4">
                <div id="dropZone" role="button" tabindex="0" aria-label="Choose a PDF or DOCX resume" class="border-2 border-dashed border-slate-300 hover:border-indigo-500 focus-visible:outline focus-visible:outline-2 focus-visible:outline-indigo-600 bg-[#FBFBFE] hover:bg-indigo-50/40 rounded-3xl p-8 sm:p-14 text-center transition-all cursor-pointer relative group" onclick="document.getElementById('resumeFileInput').click()" onkeydown="if(event.key === 'Enter' || event.key === ' '){event.preventDefault();document.getElementById('resumeFileInput').click();}">
                    <input type="file" name="resume_file" id="resumeFileInput" accept=".pdf,.docx" class="hidden" onchange="handleFileSelected(this)">
                    
                    <div class="space-y-4 pointer-events-none">
                        <div class="w-16 h-16 sm:w-20 sm:h-20 mx-auto rounded-3xl bg-indigo-50 border border-indigo-100 text-indigo-600 flex items-center justify-center shadow-sm group-hover:scale-105 group-hover:bg-indigo-600 group-hover:text-white transition-all duration-300">
                            <i class="fa-solid fa-cloud-arrow-up text-3xl sm:text-4xl" aria-hidden="true"></i>
                        </div>
                        <div>
                            <p class="text-base sm:text-lg font-bold text-slate-900">
                                Drag and drop your CV here, or <span class="text-indigo-600 underline underline-offset-2">browse file</span>
                            </p>
                            <p class="text-xs text-slate-500 mt-1.5 font-medium">Supported formats: PDF or Word (.docx) &bull; Max file size: 2 MB</p>
                        </div>
                        <div class="flex items-center justify-center gap-2 pt-1 text-[11px] font-semibold text-slate-500">
                            <span class="px-2.5 py-1 rounded-md bg-white border border-slate-200 shadow-2xs">.PDF</span>
                            <span class="px-2.5 py-1 rounded-md bg-white border border-slate-200 shadow-2xs">.DOCX</span>
                            <span class="px-2.5 py-1 rounded-md bg-slate-100 border border-slate-200 text-slate-500">Max 2 MB</span>
                        </div>
                    </div>
                </div>

                <!-- Selected File Display Card -->
                <div id="selectedFileBadge" class="hidden flex items-center justify-between p-4 bg-indigo-50/60 border border-indigo-200/80 rounded-2xl">
                    <div class="flex items-center gap-3 min-w-0">
                        <div class="w-10 h-10 rounded-xl bg-indigo-600 text-white flex items-center justify-center shrink-0 shadow-xs">
                            <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                            </svg>
                        </div>
                        <div class="min-w-0">
                            <p class="text-xs sm:text-sm font-bold text-slate-900 truncate" id="fileNameText">resume.pdf</p>
                            <p class="text-[11px] text-slate-500 font-medium" id="fileSizeText">0 KB</p>
                        </div>
                    </div>
                    <button type="button" onclick="clearSelectedFile()" class="p-1.5 rounded-lg text-slate-400 hover:text-rose-600 hover:bg-white transition cursor-pointer shrink-0" title="Remove file">
                        <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>
            </div>

            <!-- Paste Text Pane -->
            <div id="pastePane" class="hidden space-y-2">
                <div class="relative">
                    <textarea name="resume_text" id="resumeTextArea" rows="10" placeholder="Paste your complete resume content here (summary, work experience, skills, education)..." class="w-full rounded-2xl border border-slate-200 focus:border-indigo-500 focus:ring-4 focus:ring-indigo-500/10 p-4 text-xs sm:text-sm font-sans text-slate-800 placeholder-slate-400 leading-relaxed" oninput="updateCharCount(this)">{{ old('resume_text') }}</textarea>
                    <div class="absolute bottom-3 right-3 flex items-center gap-2">
                        <span id="charCount" class="text-[11px] text-slate-400 bg-white/90 px-2 py-0.5 rounded border border-slate-200 font-mono">0 characters</span>
                    </div>
                </div>
            </div>

            <!-- Optional Target Job Description Section -->
            <div class="mt-6 pt-6 border-t border-slate-100">
                <button type="button" aria-expanded="false" aria-controls="jobMatchPane" onclick="toggleJobMatchSection()" class="flex items-center justify-between w-full text-left py-2 text-slate-800 hover:text-indigo-600 transition group cursor-pointer">
                    <div class="flex items-center gap-2.5">
                        <div class="w-6 h-6 rounded-lg bg-indigo-50 text-indigo-600 flex items-center justify-center text-xs font-bold group-hover:bg-indigo-600 group-hover:text-white transition">
                            +
                        </div>
                        <div>
                            <span class="text-sm font-bold text-slate-900 group-hover:text-indigo-600 transition">Target Job Alignment</span>
                            <span class="text-xs text-slate-500 ml-1.5 font-normal">(Optional — compare resume text with job description)</span>
                        </div>
                    </div>
                    <svg id="jobMatchChevron" class="w-4 h-4 text-slate-400 group-hover:text-indigo-600 transition-transform" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                    </svg>
                </button>

                <div id="jobMatchPane" class="hidden mt-4 space-y-4 bg-slate-50/80 p-5 rounded-2xl border border-slate-200/80">
                    <div>
                        <label for="target_role" class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">
                            Target Job Title
                        </label>
                        <input type="text" name="target_role" id="target_role" value="{{ old('target_role') }}" placeholder="e.g. Senior Software Engineer, Marketing Lead, Product Manager" class="w-full rounded-xl border border-slate-300 focus:border-indigo-500 focus:ring-4 focus:ring-indigo-500/10 px-3.5 py-2.5 text-xs sm:text-sm text-slate-800 bg-white">
                    </div>

                    <div>
                        <div class="flex items-center justify-between mb-1.5">
                            <label for="job_description" class="block text-xs font-bold text-slate-700 uppercase tracking-wider">
                                Target Job Description
                            </label>
                            <span id="jdCharCounter" class="text-[11px] text-slate-400 font-mono">0 / 8,000 characters</span>
                        </div>
                        <textarea name="job_description" id="job_description" rows="4" maxlength="8000" oninput="updateJdCharCount(this)" placeholder="Paste the job description to compare its wording with your resume (max 8,000 characters)..." class="w-full rounded-xl border border-slate-300 focus:border-indigo-500 focus:ring-4 focus:ring-indigo-500/10 p-3 text-xs sm:text-sm text-slate-800 bg-white leading-relaxed">{{ old('job_description') }}</textarea>
                    </div>
                </div>
            </div>

            <!-- Submit CTA & Privacy Reassurance -->
            <div class="mt-8 pt-6 border-t border-slate-100 flex flex-col sm:flex-row items-center justify-between gap-4">
                <div class="text-xs text-slate-500 flex items-center gap-2">
                    <svg class="w-4 h-4 text-emerald-500 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z" />
                    </svg>
                    <span>Your resume text is saved with the report. AI feedback is sent to the configured AI provider. Delete the report from Scan History when you no longer need it.</span>
                </div>

                <button type="submit" id="submitBtn" class="w-full sm:w-auto inline-flex items-center justify-center gap-3 px-10 py-4 rounded-2xl bg-indigo-600 hover:bg-indigo-700 text-white font-extrabold text-base shadow-lg shadow-indigo-600/30 hover:shadow-indigo-600/40 hover:-translate-y-0.5 transition-all cursor-pointer">
                    <span>Review My CV Now</span>
                    <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.3" d="M14 5l7 7m0 0l-7 7m7-7H3" />
                    </svg>
                </button>
            </div>
        </form>
    </div>

    <!-- Resume Readability Checks -->
    <div class="mb-16 sm:mb-20">
        <div class="text-center max-w-2xl mx-auto mb-10 sm:mb-12">
            <h2 class="text-2xl sm:text-3xl font-extrabold text-slate-900 tracking-tight">
                Resume Readability Score: Parsing and Format Check
            </h2>
            <p class="text-slate-600 text-sm mt-2">
                Five standard text and structure checks are always included. Add a job description to include the keyword comparison as a sixth check. This is not a compatibility test for any specific applicant tracking system.
            </p>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
            <!-- Component 1: File & Text Extraction -->
            <div class="bg-white border border-slate-200/80 rounded-2xl p-6 shadow-xs hover:border-indigo-300 transition-colors">
                <div class="flex items-center justify-between mb-3">
                    <div class="w-10 h-10 rounded-xl bg-indigo-50 text-indigo-600 flex items-center justify-center">
                        <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                        </svg>
                    </div>
                    <span class="text-xs font-extrabold text-indigo-700 bg-indigo-50 px-2.5 py-1 rounded-md border border-indigo-200">25 pts</span>
                </div>
                <h3 class="text-sm font-bold text-slate-900">1. File & Text Extraction</h3>
                <p class="text-xs text-slate-600 mt-1 leading-relaxed">
                    Verifies selectable text layer, detects unreadable scanned images, checks word count (300–900 words), reading order hierarchy, and top header placement.
                </p>
            </div>

            <!-- Component 2: Standard Section Headings -->
            <div class="bg-white border border-slate-200/80 rounded-2xl p-6 shadow-xs hover:border-blue-300 transition-colors">
                <div class="flex items-center justify-between mb-3">
                    <div class="w-10 h-10 rounded-xl bg-blue-50 text-blue-600 flex items-center justify-center">
                        <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 10h16M4 14h16M4 18h16" />
                        </svg>
                    </div>
                    <span class="text-xs font-extrabold text-blue-700 bg-blue-50 px-2.5 py-1 rounded-md border border-blue-200">20 pts</span>
                </div>
                <h3 class="text-sm font-bold text-slate-900">2. Standard Section Headings</h3>
                <p class="text-xs text-slate-600 mt-1 leading-relaxed">
                    Checks for common headings such as Experience, Education, Skills, and Summary, and flags headings outside the recognized list.
                </p>
            </div>

            <!-- Component 3: Contact Info Placement -->
            <div class="bg-white border border-slate-200/80 rounded-2xl p-6 shadow-xs hover:border-emerald-300 transition-colors">
                <div class="flex items-center justify-between mb-3">
                    <div class="w-10 h-10 rounded-xl bg-emerald-50 text-emerald-600 flex items-center justify-center">
                        <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 12a4 4 0 10-8 0 4 4 0 008 0zm0 0v1.5a2.5 2.5 0 005 0V12a9 9 0 10-9 9m4.5-1.206a8.959 8.959 0 01-4.5 1.207" />
                        </svg>
                    </div>
                    <span class="text-xs font-extrabold text-emerald-700 bg-emerald-50 px-2.5 py-1 rounded-md border border-emerald-200">15 pts</span>
                </div>
                <h3 class="text-sm font-bold text-slate-900">3. Contact Placement</h3>
                <p class="text-xs text-slate-600 mt-1 leading-relaxed">
                    Verifies email format (5 pts), phone pattern (4 pts), candidate name prominence at the very top (3 pts), and location/LinkedIn profile URL (3 pts).
                </p>
            </div>

            <!-- Component 4: Date & Formatting Consistency -->
            <div class="bg-white border border-slate-200/80 rounded-2xl p-6 shadow-xs hover:border-purple-300 transition-colors">
                <div class="flex items-center justify-between mb-3">
                    <div class="w-10 h-10 rounded-xl bg-purple-50 text-purple-600 flex items-center justify-center">
                        <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                        </svg>
                    </div>
                    <span class="text-xs font-extrabold text-purple-700 bg-purple-50 px-2.5 py-1 rounded-md border border-purple-200">15 pts</span>
                </div>
                <h3 class="text-sm font-bold text-slate-900">4. Date Consistency</h3>
                <p class="text-xs text-slate-600 mt-1 leading-relaxed">
                    Ensures dates follow a single uniform pattern (8 pts), every role has a start/end date range (4 pts), and bullet point symbols remain consistent (3 pts).
                </p>
            </div>

            <!-- Component 5: Character & Element Safety -->
            <div class="bg-white border border-slate-200/80 rounded-2xl p-6 shadow-xs hover:border-amber-300 transition-colors">
                <div class="flex items-center justify-between mb-3">
                    <div class="w-10 h-10 rounded-xl bg-amber-50 text-amber-600 flex items-center justify-center">
                        <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                        </svg>
                    </div>
                    <span class="text-xs font-extrabold text-amber-700 bg-amber-50 px-2.5 py-1 rounded-md border border-amber-200">10 pts</span>
                </div>
                <h3 class="text-sm font-bold text-slate-900">5. Element Safety</h3>
                <p class="text-xs text-slate-600 mt-1 leading-relaxed">
                    Flags unparseable emojis/icons (4 pts), checks text density across pages (3 pts), and flags arbitrary skill rating bars or star ratings (3 pts).
                </p>
            </div>

            <!-- Component 6: Keyword Coverage & Job Match -->
            <div class="bg-white border border-slate-200/80 rounded-2xl p-6 shadow-xs hover:border-teal-300 transition-colors">
                <div class="flex items-center justify-between mb-3">
                    <div class="w-10 h-10 rounded-xl bg-teal-50 text-teal-600 flex items-center justify-center">
                        <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z" />
                        </svg>
                    </div>
                    <span class="text-xs font-extrabold text-teal-700 bg-teal-50 px-2.5 py-1 rounded-md border border-teal-200">15 pts</span>
                </div>
                <h3 class="text-sm font-bold text-slate-900">6. Keyword Coverage</h3>
                <p class="text-xs text-slate-600 mt-1 leading-relaxed">
                    Compares resume wording with terms in the supplied job description (15 pts). If none is provided, these points are redistributed across the other checks.
                </p>
            </div>
        </div>
    </div>

    <!-- Example resume wording -->
    <div class="bg-white border border-slate-200/90 rounded-3xl p-6 sm:p-10 mb-16 shadow-xs">
        <div class="text-center max-w-xl mx-auto mb-8">
            <span class="text-xs font-bold uppercase tracking-wider text-indigo-600 bg-indigo-50 px-3 py-1 rounded-full border border-indigo-200">Illustrative example</span>
            <h2 class="text-2xl font-extrabold text-slate-900 mt-3">Make experience easier to understand</h2>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <!-- Weak Bullet -->
            <div class="p-5 rounded-2xl bg-rose-50/50 border border-rose-200">
                <div class="flex items-center gap-2 text-rose-700 text-xs font-bold uppercase tracking-wider mb-2">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                    <span>Before (Passive & Unquantified)</span>
                </div>
                <p class="text-xs sm:text-sm text-slate-700 italic leading-relaxed">
                    "Responsible for managing client accounts and replying to customer inquiries."
                </p>
                <div class="mt-3 text-[11px] text-rose-700 font-semibold flex items-center gap-1.5">
                    <svg class="w-3.5 h-3.5 text-rose-600 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                    </svg>
                    <span>Broad phrasing can make the work harder to scan.</span>
                </div>
            </div>

            <!-- Strong Bullet -->
            <div class="p-5 rounded-2xl bg-emerald-50/50 border border-emerald-200">
                <div class="flex items-center gap-2 text-emerald-700 text-xs font-bold uppercase tracking-wider mb-2">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7" />
                    </svg>
                    <span>Clearer wording</span>
                </div>
                <p class="text-xs sm:text-sm text-slate-900 font-semibold leading-relaxed">
                    "Managed client accounts and responded to customer inquiries."
                </p>
                <div class="mt-3 text-[11px] text-emerald-700 font-semibold flex items-center gap-1.5">
                    <svg class="w-3.5 h-3.5 text-emerald-600 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z" />
                    </svg>
                    <span>Example only. Add figures or outcomes only when they reflect your actual experience.</span>
                </div>
            </div>
        </div>
    </div>

    <!-- Recent Analyses Section (if any) -->
    @if(isset($recentAnalyses) && $recentAnalyses->isNotEmpty())
        <div class="mb-16">
            <div class="flex items-center justify-between mb-4">
                <h2 class="text-base font-bold text-slate-900">Recent Resume Scans</h2>
                <a href="{{ route('analyses.history') }}" class="text-xs font-bold text-indigo-600 hover:text-indigo-800 transition">
                    View All Past Audits &rarr;
                </a>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
                @foreach($recentAnalyses as $item)
                    <a href="{{ route('analyses.show', $item) }}" class="block p-4 bg-white border border-slate-200 rounded-2xl hover:border-indigo-400 hover:shadow-md transition group">
                        <div class="flex items-start justify-between gap-3">
                            <div class="min-w-0">
                                <h3 class="text-xs sm:text-sm font-bold text-slate-900 group-hover:text-indigo-600 transition-colors truncate">
                                    {{ $item->candidate_name ?: 'Candidate CV' }}
                                </h3>
                                <p class="text-[11px] text-slate-500 mt-0.5 truncate">{{ $item->target_role ?: 'Role not provided' }}</p>
                                <p class="text-[10px] text-slate-400 mt-1">{{ $item->created_at->diffForHumans() }}</p>
                            </div>
                            @if($item->isDone())
                                <span class="inline-flex items-center justify-center w-9 h-9 rounded-xl text-xs font-extrabold shrink-0 {{ $item->overall_score >= 80 ? 'bg-emerald-50 text-emerald-700 border border-emerald-200' : ($item->overall_score >= 65 ? 'bg-amber-50 text-amber-700 border border-amber-200' : 'bg-rose-50 text-rose-700 border border-rose-200') }}">
                                    {{ $item->overall_score }}
                                </span>
                            @else
                                <span class="shrink-0 rounded-lg border border-slate-200 bg-slate-50 px-2.5 py-1 text-[10px] font-semibold text-slate-600">
                                    {{ $item->isFailed() ? 'Failed' : 'In progress' }}
                                </span>
                            @endif
                        </div>
                    </a>
                @endforeach
            </div>
        </div>
    @endif
</div>
@endsection

@push('scripts')
<script>
    // Tab switching between file upload and text paste
    function switchInputMode(mode) {
        const uploadPane = document.getElementById('uploadPane');
        const pastePane = document.getElementById('pastePane');
        const tabUploadBtn = document.getElementById('tabUploadBtn');
        const tabPasteBtn = document.getElementById('tabPasteBtn');

        if (mode === 'upload') {
            uploadPane.classList.remove('hidden');
            pastePane.classList.add('hidden');
            tabUploadBtn.setAttribute('aria-pressed', 'true');
            tabPasteBtn.setAttribute('aria-pressed', 'false');
            tabUploadBtn.className = "px-3.5 sm:px-4 py-2 rounded-lg transition-all bg-white text-indigo-600 shadow-xs font-bold cursor-pointer";
            tabPasteBtn.className = "px-3.5 sm:px-4 py-2 rounded-lg transition-all text-slate-600 hover:text-slate-900 font-medium cursor-pointer";
        } else {
            uploadPane.classList.add('hidden');
            pastePane.classList.remove('hidden');
            tabUploadBtn.setAttribute('aria-pressed', 'false');
            tabPasteBtn.setAttribute('aria-pressed', 'true');
            tabPasteBtn.className = "px-3.5 sm:px-4 py-2 rounded-lg transition-all bg-white text-indigo-600 shadow-xs font-bold cursor-pointer";
            tabUploadBtn.className = "px-3.5 sm:px-4 py-2 rounded-lg transition-all text-slate-600 hover:text-slate-900 font-medium cursor-pointer";
            document.getElementById('resumeTextArea').focus();
        }
    }

    // Toggle job match optional drawer
    function toggleJobMatchSection() {
        const pane = document.getElementById('jobMatchPane');
        const chevron = document.getElementById('jobMatchChevron');
        const isHidden = pane.classList.contains('hidden');
        const toggle = pane.parentElement.querySelector('button[aria-controls="jobMatchPane"]');

        if (isHidden) {
            pane.classList.remove('hidden');
            chevron.classList.add('rotate-180');
            toggle.setAttribute('aria-expanded', 'true');
        } else {
            pane.classList.add('hidden');
            chevron.classList.remove('rotate-180');
            toggle.setAttribute('aria-expanded', 'false');
        }
    }

    // File selected handler
    function handleFileSelected(input) {
        if (!input.files || !input.files[0]) return;
        const file = input.files[0];

        // 2MB client-side check
        if (file.size > 2 * 1024 * 1024) {
            alert('File exceeds 2 MB. Please select a file under 2 MB.');
            input.value = '';
            return;
        }

        document.getElementById('fileNameText').innerText = file.name;
        document.getElementById('fileSizeText').innerText = formatFileSize(file.size);
        document.getElementById('selectedFileBadge').classList.remove('hidden');
        document.getElementById('dropZone').classList.add('border-indigo-400', 'bg-indigo-50/20');
    }

    function clearSelectedFile() {
        const input = document.getElementById('resumeFileInput');
        input.value = '';
        document.getElementById('selectedFileBadge').classList.add('hidden');
        document.getElementById('dropZone').classList.remove('border-indigo-400', 'bg-indigo-50/20');
    }

    function formatFileSize(bytes) {
        if (bytes === 0) return '0 Bytes';
        const k = 1024;
        const sizes = ['Bytes', 'KB', 'MB'];
        const i = Math.floor(Math.log(bytes) / Math.log(k));
        return parseFloat((bytes / Math.pow(k, i)).toFixed(1)) + ' ' + sizes[i];
    }

    function updateCharCount(el) {
        const count = el.value.length;
        document.getElementById('charCount').innerText = count + ' characters';
    }

    function updateJdCharCount(el) {
        const count = el.value.length;
        document.getElementById('jdCharCounter').innerText = count + ' / 8,000 characters';
    }

    // Drag and drop event listeners
    const dropZone = document.getElementById('dropZone');
    ['dragenter', 'dragover'].forEach(eventName => {
        dropZone.addEventListener(eventName, (e) => {
            e.preventDefault();
            e.stopPropagation();
            dropZone.classList.add('border-indigo-600', 'bg-indigo-50/60');
        }, false);
    });

    ['dragleave', 'drop'].forEach(eventName => {
        dropZone.addEventListener(eventName, (e) => {
            e.preventDefault();
            e.stopPropagation();
            dropZone.classList.remove('border-indigo-600', 'bg-indigo-50/60');
        }, false);
    });

    dropZone.addEventListener('drop', (e) => {
        const dt = e.dataTransfer;
        const files = dt.files;
        if (files && files.length) {
            const input = document.getElementById('resumeFileInput');
            input.files = files;
            handleFileSelected(input);
        }
    });

    // Form submission handler
    function handleFormSubmit(e) {
        const fileInput = document.getElementById('resumeFileInput');
        const textArea = document.getElementById('resumeTextArea');

        const hasFile = fileInput.files && fileInput.files.length > 0;
        const hasText = textArea.value.trim().length >= 30;

        if (!hasFile && !hasText) {
            e.preventDefault();
            alert('Please select a resume file (.pdf, .docx, max 2MB) or paste your resume text (at least 30 characters).');
            return false;
        }

        const submitBtn = document.getElementById('submitBtn');
        submitBtn.disabled = true;
        submitBtn.classList.add('opacity-50', 'cursor-not-allowed');
        submitBtn.innerHTML = `
            <svg class="w-5 h-5 animate-spin" fill="none" viewBox="0 0 24 24">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
            </svg>
            <span>Uploading & Queueing...</span>
        `;

        return true;
    }
</script>
@endpush
