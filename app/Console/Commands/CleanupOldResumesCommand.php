<?php

namespace App\Console\Commands;

use App\Models\ResumeAnalysis;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;

class CleanupOldResumesCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'resumes:cleanup {--days=7 : Number of days to retain analysis records}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Deletes resume analysis records and leftover storage files older than the specified retention period (default 7 days).';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $days = (int) $this->option('days');
        $cutoff = Carbon::now()->subDays($days);

        $this->info("Cleaning up resume analyses and files created before {$cutoff->toDateTimeString()}...");

        $oldRecords = ResumeAnalysis::where('created_at', '<', $cutoff)->get();
        $count = $oldRecords->count();

        foreach ($oldRecords as $record) {
            if ($record->file_path && Storage::disk('local')->exists($record->file_path)) {
                Storage::disk('local')->delete($record->file_path);
            }
            $record->delete();
        }

        $this->info("Successfully deleted {$count} old resume analysis record(s).");

        return self::SUCCESS;
    }
}