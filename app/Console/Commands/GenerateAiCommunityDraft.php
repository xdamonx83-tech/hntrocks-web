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
        if ($time->hour < 9 || $time->hour >= 21 || $time->isWeekend()) { return self::SUCCESS; }
        // Vary eligible hours across the week; at most one draft on an eligible hour.
        if (! in_array($time->hour, [10 + $time->dayOfWeekIso, 17], true)) { return self::SUCCESS; }
        Cache::lock('ai-community-schedule', 90)->block(2, function () use ($generator, $time): void {
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
