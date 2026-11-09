# Resume Review

Resume Review is a Laravel application for checking resume readability and drafting a resume from a chosen layout. The checker accepts PDF and DOCX uploads or pasted text. The builder offers four layouts, an editable live preview, optional AI writing suggestions, and PDF downloads.

Scores and AI suggestions are estimates to review, not guarantees of ATS parsing, interviews, or hiring outcomes. Resume analysis uses the configured AI provider. When you request AI assistance in the builder, resume content is sent to that provider; the builder does not save that content as a resume analysis report.

## Features

- Upload a PDF or DOCX resume (up to 2 MB) or paste resume text for analysis.
- View analysis results and past analyses; delete analyses you no longer need.
- Optionally compare resume text with a job description.
- Choose from four resume layouts: Classic, Modern, Compact, and Minimal.
- Edit resume details with a live preview and optionally request AI writing suggestions.
- Download a text-based PDF from the resume builder.
- Configure Gemini or OpenRouter as the AI provider.

## Requirements

- PHP 8.3 or later
- Composer
- Node.js and npm
- A database supported by Laravel (the example environment is configured for MySQL)
- A Gemini or OpenRouter API key for AI-powered analysis and builder suggestions

## Installation

1. Install PHP dependencies and create the environment file:

   ```bash
   composer install
   ```

   On PowerShell, copy the example environment file if `.env` does not already exist:

   ```powershell
   Copy-Item .env.example .env
   ```

2. Configure `.env` with your database connection. The default example uses MySQL; create the `resume_analyzer` database (or update `DB_DATABASE`) before running migrations.

3. Generate an application key, migrate the database, and build frontend assets:

   ```bash
   php artisan key:generate
   php artisan migrate
   npm install
   npm run build
   ```

4. Start the application:

   ```bash
   php artisan serve
   ```

   In another terminal, run the queue worker so background resume analyses can be processed:

   ```bash
   php artisan queue:work
   ```

   Open the URL printed by `php artisan serve` (by default, `http://127.0.0.1:8000`).

For frontend development with Vite, use `npm run dev` in a separate terminal instead of relying only on the built assets.

## AI provider configuration

Set `DEFAULT_AI_PROVIDER` to `gemini` or `openrouter` and provide the matching API key in `.env`:

```dotenv
DEFAULT_AI_PROVIDER=gemini
GEMINI_API_KEY=your_gemini_api_key
```

Or:

```dotenv
DEFAULT_AI_PROVIDER=openrouter
OPENROUTER_API_KEY=your_openrouter_api_key
```

Optional model and API base URL settings are documented in `.env.example`. Do not commit real API keys. AI features will not work until a valid provider key is configured.

## Using the application

### Resume checker

Open `/` to upload a PDF or DOCX file or switch to pasted text. Submit the resume to start an analysis. The report is available when the queued analysis completes. You can optionally provide a job description for comparison. Open `/analyses/history` to revisit or delete past analyses.

### What the resume readability score measures

The report's **Resume Readability (ATS) score** is a rule-based estimate of how clearly the extracted resume text follows common parsing-friendly conventions. It is not a score from an employer's ATS and does not predict whether an application will pass screening.

The app checks these areas:

| Area | Points when a job description is provided | What is checked |
|---|---:|---|
| File and text extraction | 25 | Selectable text, approximate length, extracted reading order, and whether contact details appear near the beginning |
| Standard section headings | 20 | Recognizable experience, education, skills, and summary/profile headings |
| Contact information | 15 | Email, phone, name near the top, and a location or professional link |
| Dates and formatting | 15 | Recognizable date ranges and structured bullet points |
| Character and element safety | 10 | Decorative symbols, text density, and text that may be hard to parse |
| Job-description keyword coverage | 15 | Overlap between extracted resume text and important terms identified in the supplied job description |

When no job description is supplied, keyword points are redistributed across the other five areas, so the score remains based on 100 points. The report's separate **overall score** also includes AI assessments of impact, clarity, and relevance; if a job description is provided, it additionally includes the AI-estimated job match. These estimates depend on the text extracted from the submitted resume, so parsing issues or missing text can affect the results.

### Resume builder

Open `/resume-builder`, select one of the four visual layouts, and fill in the editor fields. The preview updates as you type. AI suggestions are optional and should be checked for accuracy before use. Select **Download resume PDF** to download the finished resume.

## Tests

Run the test suite with:

```bash
php artisan test
```

Or use the Composer script:

```bash
composer test
```

## Project structure

- `app/Http/Controllers/` — request handling for resume analysis and the builder.
- `app/Jobs/` — queued resume analysis.
- `app/Services/` — document parsing, analysis, AI provider integrations, and PDF generation.
- `resources/views/` — Blade pages and layouts.
- `routes/web.php` — web routes.
- `tests/Feature/` — feature tests for the application flows.

## Notes

- Resume analysis records are stored in the application's database and appear in analysis history until deleted.
- The resume builder does not save builder input as an analysis report. If you request AI suggestions, resume text is sent to the configured AI provider.
- A resume score or template cannot guarantee how a specific employer's system will parse a file or predict a hiring decision.
