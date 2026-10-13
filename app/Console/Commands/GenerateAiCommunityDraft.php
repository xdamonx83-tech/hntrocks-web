<?php

namespace App\Console\Commands;

use App\Models\AiCommunityCharacter;
use App\Models\AiCommunityDraft;
use App\Services\AiCommunityGenerationService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;

class GenerateAiCommunityDraft extends Command
{
    protected $signature = 'ai-community:scheduled-draft';
    protected $description = 'Optionally create at most eight review drafts per week; never publish';

    public function handle(AiCommunityGenerationService $generator): int
    {
        if (! config('ai-community.enabled') || ! config('ai-community.auto_generate')) { return self::SUCCESS; }
        $time = now('Europe/Berlin');
        // Editorial rhythm: occasional daytime/evening drafts, including weekends.
        // This task NEVER publishes. At most one draft per Berlin calendar day.
        if ($time->hour < 11 || $time->hour >= 23) { return self::SUCCESS; }
        Cache::lock('ai-community-schedule', 90)->block(2, function () use ($generator, $time): void {
            $today = $time->copy()->startOfDay()->utc();
            if (AiCommunityDraft::where('created_at', '>=', $today)->exists()) { return; }
            // With 12 eligible hours/day this yields sporadic drafts, not hourly spam.
            // Slightly more likely on weekends, when Hunt communities are often active.
            if (random_int(1, 100) > ($time->isWeekend() ? 8 : 6)) { return; }
            if (AiCommunityDraft::where('created_at', '>=', $time->copy()->startOfWeek()->utc())->count() >= 8) { return; }
            $characters = AiCommunityCharacter::where('enabled', true)->orderBy('id')->get();
            if ($characters->isEmpty()) { return; }
            $index = AiCommunityDraft::where('created_at', '>=', $time->copy()->startOfWeek()->utc())->count() % $characters->count();
            try { $generator->generate($characters[$index]); }
            catch (\Throwable $e) { $this->warn('Draft generation skipped; check admin configuration.'); }
        });
        return self::SUCCESS;
    }
}
