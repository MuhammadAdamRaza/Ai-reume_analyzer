<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class ResumeAnalysis extends Model
{
    use HasFactory;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'resume_analyses';

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'uuid',
        'status',
        'file_path',
        'mime_type',
        'resume_file_name',
        'resume_text',
        'word_count',
        'page_count',
        'candidate_name',
        'target_role',
        'job_description',
        'ai_provider',
        'ai_model',
        'overall_score',
        'rule_score',
        'ats_score',
        'impact_score',
        'clarity_score',
        'relevance_score',
        'job_match_score',
        'rule_results',
        'result_json',
        'error_message',
    ];

    /**
     * Get the route key for the model.
     */
    public function getRouteKeyName(): string
    {
        return 'uuid';
    }

    /**
     * The attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'overall_score' => 'integer',
            'rule_score' => 'integer',
            'ats_score' => 'integer',
            'impact_score' => 'integer',
            'clarity_score' => 'integer',
            'relevance_score' => 'integer',
            'job_match_score' => 'integer',
            'word_count' => 'integer',
            'page_count' => 'integer',
            'rule_results' => 'array',
            'result_json' => 'array',
        ];
    }

    /**
     * Bootstrap the model and its traits.
     */
    protected static function booted(): void
    {
        static::creating(function (ResumeAnalysis $analysis): void {
            if (empty($analysis->uuid)) {
                $analysis->uuid = (string) Str::uuid();
            }
            if (empty($analysis->status)) {
                $analysis->status = 'queued';
            }
        });
    }

    /**
     * Status checkers.
     */
    public function isPending(): bool
    {
        return in_array($this->status, ['queued', 'processing'], true);
    }

    public function isDone(): bool
    {
        return $this->status === 'done';
    }

    public function isFailed(): bool
    {
        return $this->status === 'failed';
    }

    /**
     * Readability Score Band (as specified in Section 4).
     * 85-100: Strong readability
     * 70-84: Good, with minor risks
     * 50-69: Moderate risk
     * Below 50: High risk
     */
    public function getReadabilityBandAttribute(): array
    {
        $score = $this->ats_score;

        return match (true) {
            $score === null => [
                'label' => 'Score unavailable',
                'badge' => 'bg-slate-50 text-slate-600 border-slate-200',
                'color' => '#64748b',
                'description' => 'No readability score was stored for this report.',
            ],
            $score >= 85 => [
                'label' => 'Strong readability',
                'badge' => 'bg-emerald-50 text-emerald-700 border-emerald-200',
                'color' => '#10b981',
                'description' => 'High score on this application\'s readability checks.',
            ],
            $score >= 70 => [
                'label' => 'Good, with minor risks',
                'badge' => 'bg-blue-50 text-blue-700 border-blue-200',
                'color' => '#3b82f6',
                'description' => 'The checks flagged a few areas to review.',
            ],
            $score >= 50 => [
                'label' => 'Moderate risk',
                'badge' => 'bg-amber-50 text-amber-700 border-amber-200',
                'color' => '#f59e0b',
                'description' => 'The checks flagged several areas to review.',
            ],
            default => [
                'label' => 'High risk',
                'badge' => 'bg-rose-50 text-rose-700 border-rose-200',
                'color' => '#ef4444',
                'description' => 'Many of the included readability checks were not met.',
            ],
        };
    }

    /**
     * Category scores (Impact, Clarity, Relevance).
     */
    public function getCategoryScoresAttribute(): array
    {
        return [
            'impact' => $this->impact_score ?? $this->result_json['category_scores']['impact'] ?? null,
            'clarity' => $this->clarity_score ?? $this->result_json['category_scores']['clarity'] ?? null,
            'relevance' => $this->relevance_score ?? $this->result_json['category_scores']['relevance'] ?? null,
        ];
    }

    /**
     * Priority issues ranked high, medium, low.
     */
    public function getPriorityIssuesAttribute(): array
    {
        $raw = $this->result_json['priority_issues'] ?? $this->result_json['weaknesses'] ?? [];

        // Ensure order: high first, then medium, then low
        $severityWeight = ['high' => 3, 'medium' => 2, 'low' => 1];

        usort($raw, function ($a, $b) use ($severityWeight) {
            $wa = $severityWeight[strtolower($a['severity'] ?? 'medium')] ?? 1;
            $wb = $severityWeight[strtolower($b['severity'] ?? 'medium')] ?? 1;
            return $wb <=> $wa;
        });

        return $raw;
    }

    public function getStrengthsAttribute(): array
    {
        return (array) ($this->result_json['strengths'] ?? []);
    }

    public function getBulletPointRewritesAttribute(): array
    {
        return (array) ($this->result_json['bullet_point_rewrites'] ?? []);
    }

    public function getMissingKeywordsAttribute(): array
    {
        return (array) ($this->result_json['missing_keywords'] ?? $this->result_json['ats_check']['missing_critical_keywords'] ?? []);
    }

    public function getSummaryVerdictAttribute(): string
    {
        return (string) ($this->result_json['summary_verdict'] ?? '');
    }

    public function getInterviewPrepAttribute(): array
    {
        return (array) ($this->result_json['interview_prep'] ?? []);
    }

    public function getSkillTagsAttribute(): array
    {
        return (array) ($this->result_json['skill_tags'] ?? []);
    }
}