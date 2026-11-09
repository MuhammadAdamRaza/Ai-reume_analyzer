@extends('layouts.app')

@section('title', 'Build a '.$templateLabel.' Resume | Resume Review')
@section('meta_description', 'Add your details to the '.$templateLabel.' resume layout, preview your changes, and download your resume as a PDF.')

@section('content')
<div class="mx-auto max-w-6xl px-4 py-8 sm:px-6 sm:py-12 lg:px-8">
    <div class="mx-auto mb-8 max-w-3xl sm:mb-10">
        <a href="{{ route('resume-builder.index') }}" class="mb-5 inline-flex items-center gap-2 text-sm font-bold text-indigo-700 hover:text-indigo-900 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-indigo-600">
            <i class="fa-solid fa-arrow-left" aria-hidden="true"></i>
            Change layout
        </a>
        <span class="inline-flex items-center gap-2 rounded-full border border-indigo-100 bg-indigo-50 px-3.5 py-1.5 text-xs font-bold tracking-wide text-indigo-700">
            <i class="fa-solid fa-file-circle-check" aria-hidden="true"></i>
            {{ $templateLabel }} layout
        </span>
        <h1 class="mt-4 text-3xl font-extrabold tracking-tight text-slate-950 sm:text-4xl">Add your details to the {{ strtolower($templateLabel) }} layout</h1>
        <p class="mt-3 max-w-2xl text-sm leading-relaxed text-slate-600 sm:text-base">
            Fill in the fields and see your resume preview update as you type. You can edit every detail before downloading.
        </p>
        <p class="mt-3 text-xs text-slate-500">The preview is for layout guidance. Employer systems may parse resumes differently.</p>
    </div>

    <form id="resumeBuilderForm" action="{{ route('resume-builder.download') }}" method="POST" class="space-y-6">
        @csrf
        <input type="hidden" name="template" value="{{ $template }}">

        <div class="grid items-start gap-6 lg:grid-cols-[minmax(0,1.15fr)_minmax(300px,0.85fr)]">
            <div class="space-y-6">

        <section class="rounded-3xl border border-slate-200 bg-white p-5 shadow-sm sm:p-8">
            <div class="mb-5">
                <h2 class="text-lg font-extrabold text-slate-900">1. Your contact details</h2>
                <p class="mt-1 text-xs text-slate-500">Only include information you want to share with employers.</p>
            </div>

            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                <div>
                    <label for="resumeName" class="mb-1.5 block text-xs font-bold text-slate-700">Full name <span class="text-rose-600">*</span></label>
                    <input id="resumeName" name="resume[name]" required maxlength="120" value="{{ old('resume.name') }}" autocomplete="name" class="w-full rounded-xl border border-slate-300 px-3.5 py-3 text-sm text-slate-900 focus:border-indigo-500 focus:outline-none focus:ring-4 focus:ring-indigo-500/10" placeholder="e.g. Jordan Lee">
                    @error('resume.name')<p class="mt-1 text-xs text-rose-600">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label for="resumeRole" class="mb-1.5 block text-xs font-bold text-slate-700">Target role <span class="text-rose-600">*</span></label>
                    <input id="resumeRole" name="resume[target_role]" required maxlength="160" value="{{ old('resume.target_role') }}" autocomplete="organization-title" class="w-full rounded-xl border border-slate-300 px-3.5 py-3 text-sm text-slate-900 focus:border-indigo-500 focus:outline-none focus:ring-4 focus:ring-indigo-500/10" placeholder="e.g. Product Designer">
                    @error('resume.target_role')<p class="mt-1 text-xs text-rose-600">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label for="resumeEmail" class="mb-1.5 block text-xs font-bold text-slate-700">Email</label>
                    <input id="resumeEmail" type="email" name="resume[email]" maxlength="254" value="{{ old('resume.email') }}" autocomplete="email" class="w-full rounded-xl border border-slate-300 px-3.5 py-3 text-sm text-slate-900 focus:border-indigo-500 focus:outline-none focus:ring-4 focus:ring-indigo-500/10" placeholder="you@example.com">
                    @error('resume.email')<p class="mt-1 text-xs text-rose-600">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label for="resumePhone" class="mb-1.5 block text-xs font-bold text-slate-700">Phone</label>
                    <input id="resumePhone" name="resume[phone]" maxlength="60" value="{{ old('resume.phone') }}" autocomplete="tel" class="w-full rounded-xl border border-slate-300 px-3.5 py-3 text-sm text-slate-900 focus:border-indigo-500 focus:outline-none focus:ring-4 focus:ring-indigo-500/10" placeholder="+1 555 0100">
                    @error('resume.phone')<p class="mt-1 text-xs text-rose-600">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label for="resumeLocation" class="mb-1.5 block text-xs font-bold text-slate-700">Location</label>
                    <input id="resumeLocation" name="resume[location]" maxlength="120" value="{{ old('resume.location') }}" autocomplete="address-level2" class="w-full rounded-xl border border-slate-300 px-3.5 py-3 text-sm text-slate-900 focus:border-indigo-500 focus:outline-none focus:ring-4 focus:ring-indigo-500/10" placeholder="City, Country">
                    @error('resume.location')<p class="mt-1 text-xs text-rose-600">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label for="resumeWebsite" class="mb-1.5 block text-xs font-bold text-slate-700">Portfolio or website</label>
                    <input id="resumeWebsite" type="url" name="resume[website]" maxlength="255" value="{{ old('resume.website') }}" autocomplete="url" class="w-full rounded-xl border border-slate-300 px-3.5 py-3 text-sm text-slate-900 focus:border-indigo-500 focus:outline-none focus:ring-4 focus:ring-indigo-500/10" placeholder="https://example.com">
                    @error('resume.website')<p class="mt-1 text-xs text-rose-600">{{ $message }}</p>@enderror
                </div>
                <div class="sm:col-span-2">
                    <label for="resumeLinkedin" class="mb-1.5 block text-xs font-bold text-slate-700">LinkedIn profile</label>
                    <input id="resumeLinkedin" name="resume[linkedin]" maxlength="255" value="{{ old('resume.linkedin') }}" autocomplete="url" class="w-full rounded-xl border border-slate-300 px-3.5 py-3 text-sm text-slate-900 focus:border-indigo-500 focus:outline-none focus:ring-4 focus:ring-indigo-500/10" placeholder="linkedin.com/in/your-name">
                    @error('resume.linkedin')<p class="mt-1 text-xs text-rose-600">{{ $message }}</p>@enderror
                </div>
            </div>
        </section>

        <section class="rounded-3xl border border-slate-200 bg-white p-5 shadow-sm sm:p-8">
            <div class="mb-5">
                <h2 class="text-lg font-extrabold text-slate-900">2. Write your resume</h2>
                <p class="mt-1 text-xs text-slate-500">Use your own facts. AI suggestions are optional and should be checked for accuracy.</p>
            </div>

            <div class="space-y-5">
                <div>
                    <label for="resumeSummary" class="mb-1.5 block text-xs font-bold text-slate-700">Professional summary</label>
                    <textarea id="resumeSummary" name="resume[summary]" rows="4" maxlength="2500" class="w-full rounded-xl border border-slate-300 px-3.5 py-3 text-sm leading-relaxed text-slate-900 focus:border-indigo-500 focus:outline-none focus:ring-4 focus:ring-indigo-500/10" placeholder="Briefly describe your experience, strengths, and the kind of work you do.">{{ old('resume.summary') }}</textarea>
                    @error('resume.summary')<p class="mt-1 text-xs text-rose-600">{{ $message }}</p>@enderror
                </div>

                <div>
                    <label for="resumeExperience" class="mb-1.5 block text-xs font-bold text-slate-700">Work experience</label>
                    <textarea id="resumeExperience" name="resume[experience]" rows="8" maxlength="16000" class="w-full rounded-xl border border-slate-300 px-3.5 py-3 text-sm leading-relaxed text-slate-900 focus:border-indigo-500 focus:outline-none focus:ring-4 focus:ring-indigo-500/10" placeholder="Add one role at a time. Include job title, company, dates, and your responsibilities or achievements. Use a new line for each detail.">{{ old('resume.experience') }}</textarea>
                    @error('resume.experience')<p class="mt-1 text-xs text-rose-600">{{ $message }}</p>@enderror
                </div>

                <div class="grid grid-cols-1 gap-5 sm:grid-cols-2">
                    <div>
                        <label for="resumeEducation" class="mb-1.5 block text-xs font-bold text-slate-700">Education</label>
                        <textarea id="resumeEducation" name="resume[education]" rows="5" maxlength="6000" class="w-full rounded-xl border border-slate-300 px-3.5 py-3 text-sm leading-relaxed text-slate-900 focus:border-indigo-500 focus:outline-none focus:ring-4 focus:ring-indigo-500/10" placeholder="Degree, institution, and dates.">{{ old('resume.education') }}</textarea>
                        @error('resume.education')<p class="mt-1 text-xs text-rose-600">{{ $message }}</p>@enderror
                    </div>
                    <div>
                        <label for="resumeSkills" class="mb-1.5 block text-xs font-bold text-slate-700">Skills</label>
                        <textarea id="resumeSkills" name="resume[skills]" rows="5" maxlength="4000" class="w-full rounded-xl border border-slate-300 px-3.5 py-3 text-sm leading-relaxed text-slate-900 focus:border-indigo-500 focus:outline-none focus:ring-4 focus:ring-indigo-500/10" placeholder="List relevant skills, tools, and technologies.">{{ old('resume.skills') }}</textarea>
                        @error('resume.skills')<p class="mt-1 text-xs text-rose-600">{{ $message }}</p>@enderror
                    </div>
                </div>

                <div>
                    <label for="resumeCertifications" class="mb-1.5 block text-xs font-bold text-slate-700">Certifications or projects <span class="font-normal text-slate-400">(optional)</span></label>
                    <textarea id="resumeCertifications" name="resume[certifications]" rows="3" maxlength="4000" class="w-full rounded-xl border border-slate-300 px-3.5 py-3 text-sm leading-relaxed text-slate-900 focus:border-indigo-500 focus:outline-none focus:ring-4 focus:ring-indigo-500/10" placeholder="Add relevant certifications, volunteer work, or projects.">{{ old('resume.certifications') }}</textarea>
                    @error('resume.certifications')<p class="mt-1 text-xs text-rose-600">{{ $message }}</p>@enderror
                </div>
            </div>

            <div class="mt-6 border-t border-slate-100 pt-5">
                <div class="flex flex-col gap-3 sm:flex-row sm:items-center">
                    <button id="aiAssistButton" type="button" class="inline-flex items-center justify-center gap-2 rounded-xl border border-indigo-200 bg-indigo-50 px-5 py-3 text-sm font-bold text-indigo-700 transition hover:border-indigo-300 hover:bg-indigo-100 focus-visible:outline focus-visible:outline-2 focus-visible:outline-indigo-600">
                        <i class="fa-solid fa-wand-magic-sparkles" aria-hidden="true"></i>
                        Get AI writing suggestions
                    </button>
                    <p class="text-xs leading-relaxed text-slate-500">AI can draft a summary and rewrite experience bullets. It cannot verify your history; check every suggestion.</p>
                </div>

                <div id="aiAssistStatus" class="mt-3 hidden rounded-xl border px-4 py-3 text-sm" role="status" aria-live="polite"></div>
                <div id="aiSuggestionPanel" class="mt-4 hidden space-y-4 rounded-2xl border border-indigo-100 bg-indigo-50/40 p-4 sm:p-5">
                    <div id="summarySuggestion" class="hidden rounded-xl border border-indigo-100 bg-white p-4">
                        <div class="mb-2 flex items-center justify-between gap-3">
                            <h3 class="text-xs font-bold uppercase tracking-wide text-slate-700">Suggested summary</h3>
                            <button type="button" id="useSummarySuggestion" class="text-xs font-bold text-indigo-700 hover:text-indigo-900">Use this summary</button>
                        </div>
                        <p id="summarySuggestionText" class="whitespace-pre-wrap text-sm leading-relaxed text-slate-700"></p>
                    </div>
                    <div id="rewriteSuggestions" class="hidden space-y-3"></div>
                    <p class="text-[11px] leading-relaxed text-slate-500">AI output may be incomplete or incorrect. Edit or remove anything that doesn't accurately describe you.</p>
                </div>
            </div>
        </section>

            </div>

            <aside class="lg:sticky lg:top-6">
                <div class="mb-3 flex items-center justify-between gap-3">
                    <h2 class="text-sm font-extrabold text-slate-900">Live resume preview</h2>
                    <span class="rounded-full bg-slate-100 px-2.5 py-1 text-[11px] font-semibold text-slate-600">{{ $templateLabel }}</span>
                </div>
                <div id="resumeLivePreview" class="min-h-[560px] rounded-2xl border border-slate-200 bg-white p-5 shadow-lg sm:p-7 {{ $template === 'classic' ? 'border-t-4 border-t-slate-800' : ($template === 'modern' ? 'border-l-4 border-l-indigo-600' : ($template === 'compact' ? 'border-t-4 border-t-teal-700' : 'font-serif')) }}">
                    <header class="border-b border-slate-200 pb-4 {{ $template === 'classic' ? 'text-center' : '' }}">
                        <h3 id="previewName" class="break-words text-xl font-extrabold text-slate-900">Your name</h3>
                        <p id="previewRole" class="mt-1 break-words text-sm font-semibold {{ $template === 'modern' ? 'text-indigo-700' : ($template === 'compact' ? 'text-teal-700' : 'text-slate-600') }}">Target role</p>
                        <p id="previewContact" class="mt-2 break-words text-[11px] leading-relaxed text-slate-500"></p>
                    </header>
                    <div class="pt-4 {{ $template === 'compact' ? 'space-y-3' : 'space-y-5' }}">
                        @foreach([
                            ['id' => 'previewSummary', 'title' => 'Professional summary', 'source' => 'resumeSummary'],
                            ['id' => 'previewExperience', 'title' => 'Experience', 'source' => 'resumeExperience'],
                            ['id' => 'previewEducation', 'title' => 'Education', 'source' => 'resumeEducation'],
                            ['id' => 'previewSkills', 'title' => 'Skills', 'source' => 'resumeSkills'],
                            ['id' => 'previewCertifications', 'title' => 'Certifications & projects', 'source' => 'resumeCertifications'],
                        ] as $section)
                            <section>
                                <h4 class="border-b border-slate-200 pb-1 text-[11px] font-extrabold uppercase tracking-wider {{ $template === 'modern' ? 'text-indigo-700' : ($template === 'compact' ? 'text-teal-700' : 'text-slate-800') }}">{{ $section['title'] }}</h4>
                                <p id="{{ $section['id'] }}" class="mt-2 min-h-5 whitespace-pre-wrap break-words text-xs leading-relaxed text-slate-700">Add {{ strtolower($section['title']) }} to see it here.</p>
                            </section>
                        @endforeach
                    </div>
                </div>
                <p class="mt-3 text-xs leading-relaxed text-slate-500">This preview updates as you type. Your PDF keeps the selected {{ strtolower($templateLabel) }} layout.</p>
            </aside>
        </div>

        <div class="flex flex-col gap-4 rounded-2xl border border-slate-200 bg-white p-5 sm:flex-row sm:items-center sm:justify-between sm:px-7">
            <p class="max-w-2xl text-xs leading-relaxed text-slate-500">
                AI assistance sends your summary, experience, education, and skills to the configured AI provider when requested. Contact details aren't included in that request. Resume details aren't saved as a report.
            </p>
            <button type="submit" class="inline-flex shrink-0 items-center justify-center gap-2 rounded-xl bg-indigo-600 px-6 py-3 text-sm font-extrabold text-white transition hover:bg-indigo-700 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-indigo-600">
                <i class="fa-solid fa-file-arrow-down" aria-hidden="true"></i>
                Download resume PDF
            </button>
        </div>
    </form>
</div>
@endsection

@push('scripts')
<script>
    const builderForm = document.getElementById('resumeBuilderForm');
    const aiAssistButton = document.getElementById('aiAssistButton');
    const aiAssistStatus = document.getElementById('aiAssistStatus');
    const aiSuggestionPanel = document.getElementById('aiSuggestionPanel');
    const summarySuggestion = document.getElementById('summarySuggestion');
    const summarySuggestionText = document.getElementById('summarySuggestionText');
    const rewriteSuggestions = document.getElementById('rewriteSuggestions');
    const previewFields = [
        ['resumeName', 'previewName', 'Your name'],
        ['resumeRole', 'previewRole', 'Target role'],
        ['resumeEmail', 'previewEmail'],
        ['resumePhone', 'previewPhone'],
        ['resumeLocation', 'previewLocation'],
        ['resumeWebsite', 'previewWebsite'],
        ['resumeLinkedin', 'previewLinkedin'],
        ['resumeSummary', 'previewSummary', 'Add professional summary to see it here.'],
        ['resumeExperience', 'previewExperience', 'Add experience to see it here.'],
        ['resumeEducation', 'previewEducation', 'Add education to see it here.'],
        ['resumeSkills', 'previewSkills', 'Add skills to see them here.'],
        ['resumeCertifications', 'previewCertifications', 'Add certifications or projects to see them here.'],
    ];

    function updateResumePreview() {
        const contactDetails = ['resumeEmail', 'resumePhone', 'resumeLocation', 'resumeWebsite', 'resumeLinkedin']
            .map((fieldId) => document.getElementById(fieldId).value.trim())
            .filter(Boolean);
        document.getElementById('previewContact').textContent = contactDetails.join(' · ');

        for (const [fieldId, previewId, emptyText] of previewFields) {
            if (['resumeEmail', 'resumePhone', 'resumeLocation', 'resumeWebsite', 'resumeLinkedin'].includes(fieldId)) {
                continue;
            }

            const value = document.getElementById(fieldId).value.trim();
            document.getElementById(previewId).textContent = value || emptyText;
        }
    }

    for (const [fieldId] of previewFields) {
        document.getElementById(fieldId).addEventListener('input', updateResumePreview);
    }
    updateResumePreview();

    function showAiStatus(message, isError = false) {
        aiAssistStatus.textContent = message;
        aiAssistStatus.className = `mt-3 rounded-xl border px-4 py-3 text-sm ${isError
            ? 'border-rose-200 bg-rose-50 text-rose-800'
            : 'border-emerald-200 bg-emerald-50 text-emerald-800'}`;
    }

    function appendSuggestion(text) {
        const experience = document.getElementById('resumeExperience');
        experience.value = experience.value.trim()
            ? `${experience.value.trim()}\n${text}`
            : text;
        experience.dispatchEvent(new Event('input'));
        experience.focus();
    }

    aiAssistButton.addEventListener('click', async () => {
        aiAssistButton.disabled = true;
        aiAssistButton.innerHTML = '<i class="fa-solid fa-spinner fa-spin" aria-hidden="true"></i> Getting suggestions...';
        aiAssistStatus.classList.remove('hidden');
        aiAssistStatus.classList.remove('border-rose-200', 'bg-rose-50', 'text-rose-800');
        aiAssistStatus.classList.add('border-slate-200', 'bg-slate-50', 'text-slate-600');
        aiAssistStatus.textContent = 'Sending resume text to the configured AI provider...';
        aiSuggestionPanel.classList.add('hidden');
        summarySuggestion.classList.add('hidden');
        rewriteSuggestions.classList.add('hidden');
        rewriteSuggestions.replaceChildren();

        try {
            const response = await fetch('{{ route('resume-builder.assist') }}', {
                method: 'POST',
                headers: {
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                },
                body: new FormData(builderForm),
            });
            const result = await response.json();

            if (!response.ok) {
                const validationMessage = result.errors
                    ? Object.values(result.errors).flat()[0]
                    : result.message;
                throw new Error(validationMessage || 'AI assistance could not be completed.');
            }

            let hasSuggestions = false;
            if (result.summary) {
                summarySuggestionText.textContent = result.summary;
                summarySuggestion.classList.remove('hidden');
                document.getElementById('useSummarySuggestion').onclick = () => {
                    const summary = document.getElementById('resumeSummary');
                    summary.value = result.summary;
                    summary.dispatchEvent(new Event('input'));
                    summary.focus();
                };
                hasSuggestions = true;
            }

            for (const [index, rewrite] of (result.rewrites || []).entries()) {
                const card = document.createElement('article');
                card.className = 'rounded-xl border border-indigo-100 bg-white p-4';

                const heading = document.createElement('h3');
                heading.className = 'mb-2 text-xs font-bold uppercase tracking-wide text-slate-700';
                heading.textContent = `Experience suggestion ${index + 1}`;

                const original = document.createElement('p');
                original.className = 'mb-2 text-xs leading-relaxed text-slate-500';
                original.textContent = `Original: ${rewrite.original}`;

                const improved = document.createElement('p');
                improved.className = 'whitespace-pre-wrap text-sm leading-relaxed text-slate-800';
                improved.textContent = rewrite.improved;

                const useButton = document.createElement('button');
                useButton.type = 'button';
                useButton.className = 'mt-3 text-xs font-bold text-indigo-700 hover:text-indigo-900';
                useButton.textContent = 'Add suggestion to experience';
                useButton.addEventListener('click', () => appendSuggestion(rewrite.improved));

                card.append(heading, original, improved, useButton);
                rewriteSuggestions.append(card);
                hasSuggestions = true;
            }

            if (!hasSuggestions) {
                throw new Error('The AI response did not include any suggestions. Add more experience details and try again.');
            }

            rewriteSuggestions.classList.toggle('hidden', (result.rewrites || []).length === 0);
            aiSuggestionPanel.classList.remove('hidden');
            showAiStatus('Suggestions are ready. Review them before adding anything to your resume.');
        } catch (error) {
            showAiStatus(error.message, true);
        } finally {
            aiAssistButton.disabled = false;
            aiAssistButton.innerHTML = '<i class="fa-solid fa-wand-magic-sparkles" aria-hidden="true"></i> Get AI writing suggestions';
        }
    });
</script>
@endpush
