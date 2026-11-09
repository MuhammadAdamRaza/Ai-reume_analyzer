<?php

namespace App\Services;

class RuleCheckerService
{
    /**
     * Common standard headings dictionary.
     */
    protected const HEADING_EXPERIENCE = [
        'experience', 'work experience', 'employment history',
        'professional experience', 'work history', 'career history',
    ];

    protected const HEADING_EDUCATION = [
        'education', 'academic background', 'academic history',
        'qualifications', 'education & credentials',
    ];

    protected const HEADING_SKILLS = [
        'skills', 'technical skills', 'core competencies',
        'key skills', 'competencies', 'technologies', 'areas of expertise',
    ];

    protected const HEADING_SUMMARY = [
        'summary', 'professional summary', 'profile', 'about',
        'about me', 'career summary', 'executive summary',
    ];

    /**
     * Run all ATS Readability Rule checks against the extracted resume text.
     *
     * @param string $resumeText
     * @param int $wordCount
     * @param int $pageCount
     * @param bool $isScanned
     * @param string|null $jobDescription
     * @return array
     */
    public function evaluate(
        string $resumeText,
        int $wordCount,
        int $pageCount = 1,
        bool $isScanned = false,
        ?string $jobDescription = null
    ): array {
        $hasJobDesc = !empty(trim((string) $jobDescription));
        $lines = array_values(array_filter(array_map('trim', explode("\n", $resumeText))));
        $loweredText = mb_strtolower($resumeText);

        // 1. Component A: File and text extraction (25 pts)
        $compA = $this->evaluateComponentA($resumeText, $wordCount, $pageCount, $isScanned, $lines);

        // 2. Component B: Standard section headings (20 pts)
        $compB = $this->evaluateComponentB($lines, $loweredText);

        // 3. Component C: Contact information placement (15 pts)
        $compC = $this->evaluateComponentC($lines, $resumeText);

        // 4. Component D: Date and formatting consistency (15 pts)
        $compD = $this->evaluateComponentD($resumeText, $lines);

        // 5. Component E: Character and element safety (10 pts)
        $compE = $this->evaluateComponentE($resumeText, $wordCount, $pageCount);

        // 6. Component F: Keyword coverage (15 pts, only with job description)
        $compF = $this->evaluateComponentF($resumeText, $jobDescription);

        // If no job description is provided, redistribute 15 pts across Components A-E
        if (!$hasJobDesc) {
            $this->redistributeWeights($compA, $compB, $compC, $compD, $compE);
        }

        $components = [
            'file_extraction' => $compA,
            'section_headings' => $compB,
            'contact_placement' => $compC,
            'date_formatting' => $compD,
            'character_safety' => $compE,
        ];

        if ($hasJobDesc) {
            $components['keyword_coverage'] = $compF;
        }

        // Calculate total score out of 100
        $totalEarned = 0;
        $totalMax = 0;
        $allChecks = [];

        foreach ($components as $key => $comp) {
            $totalEarned += $comp['score'];
            $totalMax += $comp['max_points'];
            foreach ($comp['checks'] as $check) {
                $allChecks[] = $check;
            }
        }

        $overallRuleScore = $totalMax > 0 ? (int) round(($totalEarned / $totalMax) * 100) : 0;
        $overallRuleScore = max(0, min(100, $overallRuleScore));

        // Group checks by pass vs fail
        $passedChecks = array_values(array_filter($allChecks, fn($c) => $c['passed']));
        $failedChecks = array_values(array_filter($allChecks, fn($c) => !$c['passed']));

        // Sort failed checks by points lost descending (biggest fixes first)
        usort($failedChecks, fn($a, $b) => ($b['max_points'] - $b['score']) <=> ($a['max_points'] - $a['score']));

        return [
            'overall_rule_score' => $overallRuleScore,
            'job_description_provided' => $hasJobDesc,
            'redistributed' => !$hasJobDesc,
            'components' => $components,
            'checks' => $allChecks,
            'passed_checks' => $passedChecks,
            'failed_checks' => $failedChecks,
            'missing_keywords' => $compF['missing_keywords'] ?? [],
            'detected_keywords' => $compF['matched_keywords'] ?? [],
        ];
    }

    /**
     * Component A: File and text extraction (25 points).
     */
    protected function evaluateComponentA(
        string $resumeText,
        int $wordCount,
        int $pageCount,
        bool $isScanned,
        array $lines
    ): array {
        $checks = [];

        // Check 1: File type is text-based (8 pts)
        $c1Passed = !$isScanned && $wordCount >= 30;
        $checks[] = [
            'id' => 'file_text_readable',
            'component' => 'File & Text Extraction',
            'title' => 'Text Layer Readability',
            'max_points' => 8,
            'score' => $c1Passed ? 8 : 0,
            'passed' => $c1Passed,
            'fix' => $c1Passed ? null : 'Upload a text-based PDF or Word (.docx) document with selectable text, not a flat scanned image.',
            'detail' => $c1Passed ? 'Digital text layer was extracted cleanly.' : 'Very little or no selectable text was found in the file.',
        ];

        // Check 2: Word count in reasonable range (6 pts)
        $c2Score = 6;
        $c2Fix = null;
        if ($wordCount < 150) {
            $c2Score = 2;
            $c2Fix = "Your resume has only {$wordCount} words. Standard resumes are roughly 300 to 900 words to give recruiters and parsers sufficient depth.";
        } elseif ($wordCount > 1300) {
            $c2Score = 3;
            $c2Fix = "Your resume is long ({$wordCount} words). Aim for 400–850 words across 1–2 pages for optimal scan speed.";
        }
        $c2Passed = $c2Score === 6;
        $checks[] = [
            'id' => 'word_count_range',
            'component' => 'File & Text Extraction',
            'title' => 'Resume Length & Word Count',
            'max_points' => 6,
            'score' => $c2Score,
            'passed' => $c2Passed,
            'fix' => $c2Fix,
            'detail' => "Detected {$wordCount} words across estimated {$pageCount} page(s).",
        ];

        // Check 3: Reading order looks coherent (6 pts)
        // Check if top lines have excessive fragmentation or interleaved lines
        $shortLines = 0;
        foreach (array_slice($lines, 0, 30) as $line) {
            if (mb_strlen($line) < 3) {
                $shortLines++;
            }
        }
        $c3Passed = $shortLines <= 8;
        $checks[] = [
            'id' => 'reading_order',
            'component' => 'File & Text Extraction',
            'title' => 'Reading Order & Layout Hierarchy',
            'max_points' => 6,
            'score' => $c3Passed ? 6 : 3,
            'passed' => $c3Passed,
            'fix' => $c3Passed ? null : 'Avoid complex multi-column tables or text boxes that can scramble parsing order.',
            'detail' => $c3Passed ? 'Single-stream reading flow parsed in logical sequence.' : 'Possible multi-column text interleaving detected.',
        ];

        // Check 4: Contact details near start of text (5 pts)
        $topSnippet = mb_strtolower(implode(' ', array_slice($lines, 0, 8)));
        $hasEmailTop = (bool) preg_match('/[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}/', $topSnippet);
        $hasPhoneTop = (bool) preg_match('/(?:\+?\d{1,3}[-.\s]?)?\(?\d{3}\)?[-.\s]?\d{3}[-.\s]?\d{4}/', $topSnippet);
        $c4Passed = $hasEmailTop || $hasPhoneTop;
        $checks[] = [
            'id' => 'contact_header_placement',
            'component' => 'File & Text Extraction',
            'title' => 'Header & Contact Info Placement',
            'max_points' => 5,
            'score' => $c4Passed ? 5 : 2,
            'passed' => $c4Passed,
            'fix' => $c4Passed ? null : 'Place your name and contact details at the very top of your resume body, not inside a separate header or footer margin.',
            'detail' => $c4Passed ? 'Contact details appear at the top of the extracted stream.' : 'Contact details were not found near the beginning of the text.',
        ];

        $earned = array_sum(array_column($checks, 'score'));
        $max = array_sum(array_column($checks, 'max_points'));

        return [
            'title' => 'File & Text Extraction',
            'score' => $earned,
            'max_points' => $max,
            'checks' => $checks,
        ];
    }

    /**
     * Component B: Standard section headings (20 points).
     */
    protected function evaluateComponentB(array $lines, string $loweredText): array
    {
        $checks = [];

        // 1. Experience (7 pts)
        $hasExp = $this->hasAnyHeading($lines, self::HEADING_EXPERIENCE);
        $checks[] = [
            'id' => 'heading_experience',
            'component' => 'Section Headings',
            'title' => 'Experience Section Heading',
            'max_points' => 7,
            'score' => $hasExp ? 7 : 0,
            'passed' => $hasExp,
            'fix' => $hasExp ? null : "Use standard section headings like 'Work Experience' or 'Professional Experience' so ATS parsers recognize your career history.",
            'detail' => $hasExp ? 'Found recognized experience heading.' : 'Standard experience heading was not identified.',
        ];

        // 2. Education (5 pts)
        $hasEdu = $this->hasAnyHeading($lines, self::HEADING_EDUCATION);
        $checks[] = [
            'id' => 'heading_education',
            'component' => 'Section Headings',
            'title' => 'Education Section Heading',
            'max_points' => 5,
            'score' => $hasEdu ? 5 : 0,
            'passed' => $hasEdu,
            'fix' => $hasEdu ? null : "Add an 'Education' or 'Academic Background' section heading.",
            'detail' => $hasEdu ? 'Found recognized education heading.' : 'Education heading was not identified.',
        ];

        // 3. Skills (4 pts)
        $hasSkills = $this->hasAnyHeading($lines, self::HEADING_SKILLS);
        $checks[] = [
            'id' => 'heading_skills',
            'component' => 'Section Headings',
            'title' => 'Skills Section Heading',
            'max_points' => 4,
            'score' => $hasSkills ? 4 : 0,
            'passed' => $hasSkills,
            'fix' => $hasSkills ? null : "Add a dedicated 'Skills' or 'Technical Skills' section heading.",
            'detail' => $hasSkills ? 'Found recognized skills heading.' : 'Skills heading was not identified.',
        ];

        // 4. Summary / Profile (4 pts)
        $hasSummary = $this->hasAnyHeading($lines, self::HEADING_SUMMARY);
        $checks[] = [
            'id' => 'heading_summary',
            'component' => 'Section Headings',
            'title' => 'Summary or Profile Heading',
            'max_points' => 4,
            'score' => $hasSummary ? 4 : 1,
            'passed' => $hasSummary,
            'fix' => $hasSummary ? null : "Include a 2–3 sentence 'Professional Summary' at the top to orient recruiters.",
            'detail' => $hasSummary ? 'Found professional summary heading.' : 'Summary heading was not identified (minor penalty).',
        ];

        $earned = array_sum(array_column($checks, 'score'));
        $max = array_sum(array_column($checks, 'max_points'));

        return [
            'title' => 'Standard Section Headings',
            'score' => $earned,
            'max_points' => $max,
            'checks' => $checks,
        ];
    }

    /**
     * Component C: Contact information placement (15 points).
     */
    protected function evaluateComponentC(array $lines, string $resumeText): array
    {
        $checks = [];

        // 1. Email (5 pts)
        $hasEmail = (bool) preg_match('/[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}/', $resumeText);
        $checks[] = [
            'id' => 'contact_email',
            'component' => 'Contact Information',
            'title' => 'Email Address in Body Text',
            'max_points' => 5,
            'score' => $hasEmail ? 5 : 0,
            'passed' => $hasEmail,
            'fix' => $hasEmail ? null : 'Include an active, professional email address in body text.',
            'detail' => $hasEmail ? 'Valid email format detected.' : 'No email address found in extracted text.',
        ];

        // 2. Phone (4 pts)
        $hasPhone = (bool) preg_match('/(?:\+?\d{1,3}[-.\s]?)?\(?\d{3}\)?[-.\s]?\d{3}[-.\s]?\d{4}/', $resumeText);
        $checks[] = [
            'id' => 'contact_phone',
            'component' => 'Contact Information',
            'title' => 'Phone Number in Body Text',
            'max_points' => 4,
            'score' => $hasPhone ? 4 : 0,
            'passed' => $hasPhone,
            'fix' => $hasPhone ? null : 'Include a direct phone number with country/area code.',
            'detail' => $hasPhone ? 'Phone number pattern detected.' : 'No phone number found in extracted text.',
        ];

        // 3. Name near top (3 pts)
        $topThree = array_slice($lines, 0, 4);
        $nameFound = false;
        foreach ($topThree as $line) {
            $words = explode(' ', trim($line));
            if (count($words) >= 2 && count($words) <= 4 && !preg_match('/[0-9@:]/', $line)) {
                $nameFound = true;
                break;
            }
        }
        $checks[] = [
            'id' => 'contact_name_top',
            'component' => 'Contact Information',
            'title' => 'Candidate Name Prominence',
            'max_points' => 3,
            'score' => $nameFound ? 3 : 1,
            'passed' => $nameFound,
            'fix' => $nameFound ? null : 'Ensure your full name is the first prominent text line at the very top.',
            'detail' => $nameFound ? 'Candidate name detected at top of resume.' : 'Name pattern was not clearly identified at top.',
        ];

        // 4. Location or link (3 pts)
        $hasLink = (bool) preg_match('/(?:linkedin\.com|github\.com|portfolio|http[s]?:\/\/)/i', $resumeText);
        $hasLocation = (bool) preg_match('/(?:[A-Z][a-zA-Z\s]+,\s*[A-Z]{2}|[A-Z][a-zA-Z]+,\s*[A-Za-z\s]+)/', $resumeText);
        $c4Passed = $hasLink || $hasLocation;
        $checks[] = [
            'id' => 'contact_location_link',
            'component' => 'Contact Information',
            'title' => 'Location or Professional Profile Link',
            'max_points' => 3,
            'score' => $c4Passed ? 3 : 1,
            'passed' => $c4Passed,
            'fix' => $c4Passed ? null : 'Add your location (City, Country) and a professional profile link (LinkedIn or GitHub).',
            'detail' => $c4Passed ? 'Location or online profile URL detected.' : 'Neither location nor LinkedIn/portfolio link was detected.',
        ];

        $earned = array_sum(array_column($checks, 'score'));
        $max = array_sum(array_column($checks, 'max_points'));

        return [
            'title' => 'Contact Information Placement',
            'score' => $earned,
            'max_points' => $max,
            'checks' => $checks,
        ];
    }

    /**
     * Component D: Date and formatting consistency (15 points).
     */
    protected function evaluateComponentD(string $resumeText, array $lines): array
    {
        $checks = [];

        // 1. Consistent date format (8 pts)
        // Check for common patterns: Month Year (Jan 2021), Year (2021), Date ranges
        preg_match_all('/(?:Jan(?:uary)?|Feb(?:ruary)?|Mar(?:ch)?|Apr(?:il)?|May|Jun(?:e)?|Jul(?:y)?|Aug(?:ust)?|Sep(?:tember)?|Oct(?:ober)?|Nov(?:ember)?|Dec(?:ember)?)\s+\d{4}/i', $resumeText, $monthYearMatches);
        preg_match_all('/\b(?:19|20)\d{2}\s*[-–—]\s*(?:(?:19|20)\d{2}|Present|Current)\b/i', $resumeText, $yearRangeMatches);

        $hasMonthYear = count($monthYearMatches[0] ?? []) >= 2;
        $hasYearRanges = count($yearRangeMatches[0] ?? []) >= 2;
        $hasAnyDates = $hasMonthYear || $hasYearRanges;

        $c1Score = 8;
        $c1Fix = null;
        if (!$hasAnyDates) {
            $c1Score = 2;
            $c1Fix = "Include start and end dates (Month/Year) for every work experience and degree.";
        }

        $checks[] = [
            'id' => 'dates_consistency',
            'component' => 'Date & Formatting Consistency',
            'title' => 'Date Format Consistency',
            'max_points' => 8,
            'score' => $c1Score,
            'passed' => $c1Score === 8,
            'fix' => $c1Fix,
            'detail' => $hasAnyDates ? 'Dates follow clear month/year or year range patterns.' : 'Standard date patterns were sparse or missing.',
        ];

        // 2. Dates per role (4 pts)
        $c2Passed = (count($monthYearMatches[0] ?? []) + count($yearRangeMatches[0] ?? [])) >= 2;
        $checks[] = [
            'id' => 'dates_per_role',
            'component' => 'Date & Formatting Consistency',
            'title' => 'Date Ranges for Work History',
            'max_points' => 4,
            'score' => $c2Passed ? 4 : 1,
            'passed' => $c2Passed,
            'fix' => $c2Passed ? null : "Ensure every role clearly displays a date range (e.g. 'Jan 2021 – Present').",
            'detail' => $c2Passed ? 'Multiple job date ranges identified.' : 'Fewer date ranges than expected for a complete career history.',
        ];

        // 3. Bullet style consistency (3 pts)
        $bulletCount = 0;
        foreach ($lines as $line) {
            if (preg_match('/^[\x{2022}\x{25E6}\x{2043}\x{2219}\-\*▪⁃]\s+/u', $line)) {
                $bulletCount++;
            }
        }
        $c3Passed = $bulletCount >= 3;
        $checks[] = [
            'id' => 'bullet_consistency',
            'component' => 'Date & Formatting Consistency',
            'title' => 'Bullet Point Structure',
            'max_points' => 3,
            'score' => $c3Passed ? 3 : 1,
            'passed' => $c3Passed,
            'fix' => $c3Passed ? null : "Use structured bullet points (such as round bullets •) to list your accomplishments under each role.",
            'detail' => $c3Passed ? "Detected {$bulletCount} formatted bullet items." : 'Few or no bullet point items detected.',
        ];

        $earned = array_sum(array_column($checks, 'score'));
        $max = array_sum(array_column($checks, 'max_points'));

        return [
            'title' => 'Date & Formatting Consistency',
            'score' => $earned,
            'max_points' => $max,
            'checks' => $checks,
        ];
    }

    /**
     * Component E: Character and element safety (10 points).
     */
    protected function evaluateComponentE(string $resumeText, int $wordCount, int $pageCount): array
    {
        $checks = [];

        // 1. No emojis or decorative icons (4 pts)
        // Match common emojis
        preg_match_all('/[\x{1F600}-\x{1F64F}\x{1F300}-\x{1F5FF}\x{1F680}-\x{1F6FF}\x{2600}-\x{26FF}\x{2700}-\x{27BF}]/u', $resumeText, $emojiMatches);
        $emojiCount = count($emojiMatches[0] ?? []);
        $c1Passed = $emojiCount === 0;
        $checks[] = [
            'id' => 'character_safety_emojis',
            'component' => 'Character & Element Safety',
            'title' => 'Icon & Emoji Safety',
            'max_points' => 4,
            'score' => $c1Passed ? 4 : ($emojiCount <= 2 ? 2 : 0),
            'passed' => $c1Passed,
            'fix' => $c1Passed ? null : 'Remove decorative icons or emojis. ATS parsers often drop them or turn them into garbled symbols.',
            'detail' => $c1Passed ? 'No hazardous emojis or unparseable glyphs found.' : "Found {$emojiCount} decorative symbols/emojis.",
        ];

        // 2. Text density safety (3 pts)
        $avgWordsPerPage = $pageCount > 0 ? (int) ($wordCount / $pageCount) : $wordCount;
        $c2Passed = $avgWordsPerPage >= 150;
        $checks[] = [
            'id' => 'character_safety_density',
            'component' => 'Character & Element Safety',
            'title' => 'Text Density & No Image Text',
            'max_points' => 3,
            'score' => $c2Passed ? 3 : 1,
            'passed' => $c2Passed,
            'fix' => $c2Passed ? null : 'Avoid placing text inside images or graphical shapes. Use selectable text throughout.',
            'detail' => $c2Passed ? 'Healthy text density per page.' : 'Low text density per page; check for unparseable image-based content.',
        ];

        // 3. No skill bars or star charts (3 pts)
        $hasSkillBars = (bool) preg_match('/(?:[★☆●○◆◇■□]{3,}|\b\d{1,2}\s*\/\s*10\b|\b\d{1,2}\s*\/\s*5\b)/u', $resumeText);
        $c3Passed = !$hasSkillBars;
        $checks[] = [
            'id' => 'character_safety_skill_bars',
            'component' => 'Character & Element Safety',
            'title' => 'No Visual Skill Bars or Charts',
            'max_points' => 3,
            'score' => $c3Passed ? 3 : 0,
            'passed' => $c3Passed,
            'fix' => $c3Passed ? null : "Remove visual skill bars or ratings (e.g. 'Python: 4/5'). List your years of experience or key accomplishments instead.",
            'detail' => $c3Passed ? 'No arbitrary rating bars or symbol scales detected.' : 'Detected skill rating scales or repeated visual characters.',
        ];

        $earned = array_sum(array_column($checks, 'score'));
        $max = array_sum(array_column($checks, 'max_points'));

        return [
            'title' => 'Character & Element Safety',
            'score' => $earned,
            'max_points' => $max,
            'checks' => $checks,
        ];
    }

    /**
     * Component F: Keyword coverage (15 points, only with job description).
     */
    protected function evaluateComponentF(string $resumeText, ?string $jobDescription): array
    {
        if (empty(trim((string) $jobDescription))) {
            return [
                'title' => 'Keyword Coverage (Job Match)',
                'score' => 0,
                'max_points' => 0,
                'checks' => [],
                'missing_keywords' => [],
                'matched_keywords' => [],
            ];
        }

        $keywords = $this->extractKeywordsFromJobDescription($jobDescription);
        $loweredResume = mb_strtolower($resumeText);

        $matched = [];
        $missing = [];

        foreach ($keywords as $kw) {
            if (str_contains($loweredResume, mb_strtolower($kw))) {
                $matched[] = $kw;
            } else {
                $missing[] = $kw;
            }
        }

        $totalKw = count($keywords);
        $matchRatio = $totalKw > 0 ? (count($matched) / $totalKw) : 1.0;
        $score = (int) round($matchRatio * 15);
        $score = max(0, min(15, $score));

        $passed = $score >= 10;
        $missingSample = array_slice($missing, 0, 5);
        $fixMessage = count($missing) > 0
            ? 'Incorporate missing keywords (e.g. ' . implode(', ', $missingSample) . ') naturally into your work accomplishments where you have real experience.'
            : null;

        $checks = [
            [
                'id' => 'keyword_coverage_job_match',
                'component' => 'Keyword Coverage (Job Match)',
                'title' => 'Target Job Keywords Match',
                'max_points' => 15,
                'score' => $score,
                'passed' => $passed,
                'fix' => $fixMessage,
                'detail' => "Matched " . count($matched) . " of {$totalKw} primary job keywords (" . (int) round($matchRatio * 100) . "% match).",
            ],
        ];

        return [
            'title' => 'Keyword Coverage (Job Match)',
            'score' => $score,
            'max_points' => 15,
            'checks' => $checks,
            'missing_keywords' => $missing,
            'matched_keywords' => $matched,
        ];
    }

    /**
     * Redistribute 15 points across components A-E when no job description is given.
     */
    protected function redistributeWeights(
        array &$compA,
        array &$compB,
        array &$compC,
        array &$compD,
        array &$compE
    ): void {
        // Boost max points: A: 25->29 (+4), B: 20->23 (+3), C: 15->18 (+3), D: 15->18 (+3), E: 10->12 (+2) = +15
        $boosts = [
            'A' => 4,
            'B' => 3,
            'C' => 3,
            'D' => 3,
            'E' => 2,
        ];

        $compA['score'] = (int) round($compA['score'] * (29 / 25));
        $compA['max_points'] = 29;

        $compB['score'] = (int) round($compB['score'] * (23 / 20));
        $compB['max_points'] = 23;

        $compC['score'] = (int) round($compC['score'] * (18 / 15));
        $compC['max_points'] = 18;

        $compD['score'] = (int) round($compD['score'] * (18 / 15));
        $compD['max_points'] = 18;

        $compE['score'] = (int) round($compE['score'] * (12 / 10));
        $compE['max_points'] = 12;
    }

    /**
     * Helper to detect if any heading in list exists on its own line.
     */
    protected function hasAnyHeading(array $lines, array $acceptedHeadings): bool
    {
        foreach ($lines as $line) {
            $cleaned = mb_strtolower(trim(preg_replace('/[^a-z\s&]/i', '', $line)));
            if (empty($cleaned)) {
                continue;
            }

            foreach ($acceptedHeadings as $target) {
                if ($cleaned === $target || str_starts_with($cleaned, $target . ' ') || str_ends_with($cleaned, ' ' . $target)) {
                    return true;
                }
            }
        }

        return false;
    }

    /**
     * Extract key terms from job description.
     */
    protected function extractKeywordsFromJobDescription(string $jd): array
    {
        $stopWords = [
            'about', 'above', 'across', 'after', 'again', 'against', 'all', 'almost', 'alone',
            'along', 'already', 'also', 'although', 'always', 'among', 'and', 'another', 'any',
            'anybody', 'anyone', 'anything', 'anywhere', 'are', 'area', 'areas', 'around', 'ask',
            'asked', 'asking', 'asks', 'back', 'backed', 'backing', 'backs', 'became', 'because',
            'become', 'becomes', 'becoming', 'been', 'before', 'began', 'behind', 'being', 'beings',
            'best', 'better', 'between', 'big', 'both', 'but', 'came', 'can', 'cannot', 'case',
            'cases', 'certain', 'certainly', 'clear', 'clearly', 'come', 'could', 'did', 'differ',
            'different', 'differently', 'does', 'done', 'down', 'downed', 'downing', 'downs', 'during',
            'each', 'early', 'either', 'end', 'ended', 'ending', 'ends', 'enough', 'even', 'evenly',
            'ever', 'every', 'everybody', 'everyone', 'everything', 'everywhere', 'face', 'faces',
            'fact', 'facts', 'far', 'felt', 'few', 'fewer', 'find', 'finds', 'first', 'for',
            'four', 'from', 'full', 'fully', 'further', 'furthered', 'furthering', 'furthers',
            'gave', 'general', 'generally', 'get', 'gets', 'getting', 'give', 'given', 'gives',
            'good', 'goods', 'great', 'greater', 'greatest', 'group', 'grouped', 'grouping', 'groups',
            'had', 'has', 'have', 'having', 'her', 'here', 'herself', 'high', 'higher', 'highest',
            'him', 'himself', 'his', 'how', 'however', 'important', 'interest', 'interested',
            'interesting', 'interests', 'into', 'just', 'keep', 'keeps', 'kind', 'knew', 'know',
            'known', 'knows', 'large', 'largely', 'last', 'later', 'latest', 'least', 'less',
            'let', 'lets', 'like', 'likely', 'made', 'make', 'making', 'man', 'many', 'may',
            'me', 'member', 'members', 'men', 'might', 'more', 'most', 'mostly', 'much', 'must',
            'my', 'myself', 'name', 'named', 'names', 'need', 'needed', 'needing', 'needs',
            'never', 'new', 'newer', 'newest', 'next', 'non', 'not', 'nobody', 'noone', 'nothing',
            'now', 'nowhere', 'number', 'numbers', 'off', 'often', 'old', 'older', 'oldest',
            'once', 'one', 'only', 'open', 'opened', 'opening', 'opens', 'order', 'ordered',
            'ordering', 'orders', 'other', 'others', 'our', 'out', 'over', 'part', 'parted',
            'parting', 'parts', 'per', 'place', 'places', 'point', 'pointed', 'pointing', 'points',
            'possible', 'present', 'presented', 'presenting', 'presents', 'problem', 'problems',
            'put', 'puts', 'quite', 'rather', 'really', 'right', 'room', 'rooms', 'said', 'same',
            'saw', 'say', 'says', 'second', 'seconds', 'see', 'seem', 'seemed', 'seeming', 'seems',
            'sees', 'several', 'shall', 'she', 'should', 'show', 'showed', 'showing', 'shows',
            'side', 'sides', 'since', 'small', 'smaller', 'smallest', 'some', 'somebody', 'someone',
            'something', 'somewhere', 'state', 'states', 'still', 'such', 'sure', 'take', 'taken',
            'than', 'that', 'the', 'their', 'them', 'then', 'there', 'therefore', 'these', 'they',
            'thing', 'things', 'think', 'thinks', 'this', 'those', 'though', 'thought', 'thoughts',
            'three', 'through', 'thus', 'together', 'too', 'took', 'toward', 'turn', 'turned',
            'turning', 'turns', 'two', 'under', 'until', 'upon', 'use', 'used', 'uses', 'very',
            'want', 'wanted', 'wanting', 'wants', 'was', 'way', 'ways', 'well', 'wells', 'went',
            'were', 'what', 'when', 'where', 'whether', 'which', 'while', 'who', 'whole', 'whose',
            'why', 'will', 'with', 'within', 'without', 'work', 'worked', 'working', 'works', 'year',
            'years', 'yet', 'you', 'young', 'younger', 'youngest', 'your', 'yours', 'responsibilities',
            'requirements', 'qualifications', 'experience', 'candidate', 'ideal', 'looking', 'role',
            'position', 'team', 'company', 'join', 'help', 'skills', 'ability', 'must', 'plus',
        ];

        // Clean text and extract words/phrases
        $cleaned = mb_strtolower($jd);
        $cleaned = preg_replace('/[^a-z0-9+#.\s]/i', ' ', $cleaned);

        $tokens = preg_split('/\s+/', $cleaned, -1, PREG_SPLIT_NO_EMPTY) ?: [];
        $candidates = [];

        foreach ($tokens as $token) {
            $t = trim($token);
            if (mb_strlen($t) >= 3 && !in_array($t, $stopWords, true) && !is_numeric($t)) {
                $candidates[$t] = ($candidates[$t] ?? 0) + 1;
            }
        }

        arsort($candidates);

        // Take top 15 most frequent unique keywords
        return array_slice(array_keys($candidates), 0, 15);
    }
}