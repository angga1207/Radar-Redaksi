<?php

namespace App\Console\Commands;

use App\Enums\ArticleStatus;
use App\Models\Article;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

#[Signature('articles:publish-scheduled')]
#[Description('Publish every scheduled article whose publication time has arrived')]
class PublishScheduledArticles extends Command
{
    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $published = 0;
        Article::query()->where('status', ArticleStatus::Scheduled)->where('scheduled_at', '<=', now())->chunkById(100, function ($articles) use (&$published): void {
            foreach ($articles as $article) {
                $article->update(['status' => ArticleStatus::Published, 'published_at' => $article->scheduled_at ?? now()]);
                $article->statusHistories()->create(['actor_id' => $article->editor_id ?? $article->author_id, 'from_status' => ArticleStatus::Scheduled->value, 'to_status' => ArticleStatus::Published->value, 'note' => 'Diterbitkan otomatis oleh scheduler.']);
                $published++;
            }
        });
        Log::info('Scheduled publishing completed', ['published' => $published]);
        if ($published > 0) {
            Cache::forget('public.home-content.v2');
        }
        $this->info("{$published} artikel diterbitkan.");

        return self::SUCCESS;
    }
}
