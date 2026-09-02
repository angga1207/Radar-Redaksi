<?php

namespace App\Actions;

use App\Enums\ArticleStatus;
use App\Models\Article;
use App\Models\AuditLog;
use App\Models\User;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

final class SaveArticleAction
{
    /** @param array<string, mixed> $attributes @param array<int, string|int> $tagIds */
    public function execute(?Article $article, User $actor, array $attributes, array $tagIds, ?string $changeNote = null): Article
    {
        return DB::transaction(function () use ($article, $actor, $attributes, $tagIds, $changeNote): Article {
            $previousStatus = $article?->status;
            if ($article?->exists && $article->status === ArticleStatus::Published) {
                $article->revisions()->create(['user_id' => $actor->id, 'title' => $article->title, 'excerpt' => $article->excerpt, 'body' => $article->body, 'change_note' => $changeNote]);
            }
            $article ??= new Article(['author_id' => $actor->id]);
            $article->fill($attributes);
            $article->save();
            $article->tags()->sync($tagIds);
            if ($previousStatus !== $article->status) {
                $article->statusHistories()->create(['actor_id' => $actor->id, 'from_status' => $previousStatus?->value, 'to_status' => $article->status->value, 'note' => $changeNote]);
            }
            AuditLog::record($article->wasRecentlyCreated ? 'article.created' : 'article.updated', $article, [], ['title' => $article->title, 'status' => $article->status->value]);
            Cache::forget('public.home-content.v2');

            return $article->refresh();
        });
    }
}
