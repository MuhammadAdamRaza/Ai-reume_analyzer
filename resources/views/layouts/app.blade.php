<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="h-full bg-slate-50 text-slate-900 antialiased">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>@yield('title', 'Free Resume Checker | Get Your Resume Score')</title>
    <meta name="description" content="@yield('meta_description', 'Review resume readability, format, and content with practical feedback. Add a job description for an optional comparison.')">

    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=instrument-sans:400,500,600,700,800" rel="stylesheet" />

    <!-- Font Awesome Icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" integrity="sha512-DTOQO9RWCH3ppGqcWaEA1BIZOC6xxalwEsw9c2QQeAIftl+Vegovlnee1c9QX4TctnWMn13TZye+giMm8e2LwA==" crossorigin="anonymous" referrerpolicy="no-referrer" />

    <!-- Styles & Scripts -->
    @vite(['resources/css/app.css', 'resources/js/app.js'])

    <style>
        [x-cloak] { display: none !important; }
        @keyframes pulse-subtle {
            0%, 100% { opacity: 1; transform: scale(1); }
            50% { opacity: 0.95; transform: scale(1.01); }
        }
        .pulse-subtle {
            animation: pulse-subtle 4s ease-in-out infinite;
        }
    </style>
    @stack('styles')
</head>
<body class="min-h-full flex flex-col font-sans bg-[#FBFBFE] text-slate-900 selection:bg-indigo-500 selection:text-white">
    <header class="sticky top-0 z-50 bg-white/95 backdrop-blur-md border-b border-slate-200/80">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex items-center justify-between h-16">
                <div class="flex items-center gap-8">
                    <a href="{{ route('home') }}" class="flex items-center gap-2.5 group" aria-label="Resume Review home">
                        <div class="w-9 h-9 rounded-xl bg-indigo-600 flex items-center justify-center text-white shadow-sm shadow-indigo-600/20 group-hover:bg-indigo-700 transition-colors">
                            <i class="fa-solid fa-file-lines text-base" aria-hidden="true"></i>
                        </div>
                        <div class="leading-tight">
                            <span class="block text-base font-extrabold tracking-tight text-slate-900">Resume Review</span>
                            <span class="block text-[10px] font-semibold text-slate-500 tracking-wide">CHECKER</span>
                        </div>
                    </a>

                    <nav class="hidden md:flex items-center gap-1" aria-label="Main navigation">
                        <a href="{{ route('home') }}" class="px-3.5 py-2 text-sm font-semibold rounded-lg transition-colors {{ request()->routeIs('home') ? 'text-indigo-700 bg-indigo-50' : 'text-slate-600 hover:text-slate-900 hover:bg-slate-50' }}">
                            Resume checker
                        </a>
                        <a href="{{ route('analyses.history') }}" class="px-3.5 py-2 text-sm font-semibold rounded-lg transition-colors {{ request()->routeIs('analyses.history') ? 'text-indigo-700 bg-indigo-50' : 'text-slate-600 hover:text-slate-900 hover:bg-slate-50' }}">
                            Scan history
                        </a>
                        <a href="{{ route('resume-builder.index') }}" class="px-3.5 py-2 text-sm font-semibold rounded-lg transition-colors {{ request()->routeIs('resume-builder.*') ? 'text-indigo-700 bg-indigo-50' : 'text-slate-600 hover:text-slate-900 hover:bg-slate-50' }}">
                            Resume builder
                        </a>
                    </nav>
                </div>

                <div class="flex items-center gap-2">
                    <a href="{{ route('home') }}" class="hidden sm:inline-flex items-center gap-2 px-4 py-2.5 rounded-xl bg-indigo-600 hover:bg-indigo-700 text-white text-sm font-bold transition-colors">
                        <i class="fa-solid fa-arrow-up-from-bracket text-xs" aria-hidden="true"></i>
                        <span>Check a resume</span>
                    </a>
                    <button type="button" id="mobileNavigationToggle" class="md:hidden inline-flex items-center justify-center w-10 h-10 rounded-xl border border-slate-200 text-slate-700 hover:bg-slate-50 focus-visible:outline focus-visible:outline-2 focus-visible:outline-indigo-600" aria-label="Open navigation menu" aria-expanded="false" aria-controls="mobileNavigation">
                        <i class="fa-solid fa-bars" aria-hidden="true"></i>
                    </button>
                </div>
            </div>

            <nav id="mobileNavigation" class="hidden md:hidden border-t border-slate-100 py-3" aria-label="Mobile navigation">
                <a href="{{ route('home') }}" class="flex items-center gap-3 rounded-xl px-3 py-3 text-sm font-semibold {{ request()->routeIs('home') ? 'text-indigo-700 bg-indigo-50' : 'text-slate-700 hover:bg-slate-50' }}">
                    <i class="fa-solid fa-file-circle-check w-5 text-center" aria-hidden="true"></i>
                    Resume checker
                </a>
                <a href="{{ route('analyses.history') }}" class="flex items-center gap-3 rounded-xl px-3 py-3 text-sm font-semibold {{ request()->routeIs('analyses.history') ? 'text-indigo-700 bg-indigo-50' : 'text-slate-700 hover:bg-slate-50' }}">
                    <i class="fa-solid fa-clock-rotate-left w-5 text-center" aria-hidden="true"></i>
                    Scan history
                </a>
                <a href="{{ route('resume-builder.index') }}" class="flex items-center gap-3 rounded-xl px-3 py-3 text-sm font-semibold {{ request()->routeIs('resume-builder.*') ? 'text-indigo-700 bg-indigo-50' : 'text-slate-700 hover:bg-slate-50' }}">
                    <i class="fa-solid fa-wand-magic-sparkles w-5 text-center" aria-hidden="true"></i>
                    Resume builder
                </a>
                <a href="{{ route('home') }}" class="mt-2 flex items-center justify-center gap-2 rounded-xl bg-indigo-600 px-4 py-3 text-sm font-bold text-white hover:bg-indigo-700">
                    <i class="fa-solid fa-arrow-up-from-bracket text-xs" aria-hidden="true"></i>
                    Check a resume
                </a>
            </nav>
        </div>
    </header>

    <!-- Global Flash Alerts -->
    @if(session('status'))
        <div class="bg-emerald-600 text-white text-xs sm:text-sm py-2.5 px-4 shadow-sm">
            <div class="max-w-7xl mx-auto flex items-center justify-between">
                <div class="flex items-center gap-2">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.2" d="M5 13l4 4L19 7" />
                    </svg>
                    <span>{{ session('status') }}</span>
                </div>
                <button onclick="this.parentElement.parentElement.remove()" class="text-emerald-100 hover:text-white">&times;</button>
            </div>
        </div>
    @endif

    @if($errors->has('analysis_error'))
        <div class="bg-rose-50 border-b border-rose-200 text-rose-900 text-xs sm:text-sm py-3 px-4">
            <div class="max-w-7xl mx-auto flex items-start gap-3">
                <div class="w-6 h-6 rounded-full bg-rose-200 text-rose-700 flex items-center justify-center shrink-0 mt-0.5 font-bold">!</div>
                <div class="flex-1">
                    <p class="font-bold">Resume Scan Notice</p>
                    <p class="text-rose-700 mt-0.5 text-xs">{{ $errors->first('analysis_error') }}</p>
                </div>
            </div>
        </div>
    @endif

    <!-- Main Content -->
    <main class="flex-1">
        @yield('content')
    </main>

    <footer class="mt-auto border-t border-slate-200 bg-white py-10 text-xs text-slate-500">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="grid grid-cols-1 md:grid-cols-4 gap-8 mb-8">
                <!-- Col 1: Brand Info -->
                <div class="md:col-span-2 space-y-3">
                    <div class="flex items-center gap-2">
                        <div class="w-6 h-6 rounded-md bg-indigo-600 text-white flex items-center justify-center shadow-xs">
                            <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5" />
                            </svg>
                        </div>
                        <span class="text-base font-extrabold tracking-tight text-slate-900">Resume Review</span>
                    </div>
                    <p class="text-slate-600 text-xs leading-relaxed max-w-md">
                        Review resume readability and content, then use the findings as a starting point for your own edits. Scores are estimates and do not predict hiring decisions.
                    </p>
                </div>

                <!-- Col 2: Audit Features -->
                <div>
                    <h3 class="text-xs font-bold text-slate-900 uppercase tracking-wider mb-3">CV Audit Features</h3>
                    <ul class="space-y-2 text-slate-600">
                        <li><span>Resume format checks</span></li>
                        <li><span>Content feedback</span></li>
                        <li><span>Optional job description comparison</span></li>
                        <li><span>Suggested bullet rewrites</span></li>
                    </ul>
                </div>

                <!-- Col 3: Quick Navigation -->
                <div>
                    <h3 class="text-xs font-bold text-slate-900 uppercase tracking-wider mb-3">Quick Links</h3>
                    <ul class="space-y-2 text-slate-600">
                        <li><a href="{{ route('home') }}" class="hover:text-indigo-600 transition">Resume checker</a></li>
                        <li><a href="{{ route('analyses.history') }}" class="hover:text-indigo-600 transition">Scan history</a></li>
                        <li><a href="{{ route('resume-builder.index') }}" class="hover:text-indigo-600 transition">Resume builder</a></li>
                    </ul>
                </div>
            </div>

            <!-- Bottom Copyright Strip -->
            <div class="pt-6 border-t border-slate-100 text-slate-400">
                <p>&copy; {{ date('Y') }} Resume Review</p>
            </div>
        </div>
    </footer>

    @stack('scripts')
</body>
</html>