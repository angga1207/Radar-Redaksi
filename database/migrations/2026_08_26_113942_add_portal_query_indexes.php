<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('comments', function (Blueprint $table): void {
            $table->index(['article_id', 'status', 'created_at'], 'comments_article_status_created_idx');
        });
        Schema::table('article_revisions', function (Blueprint $table): void {
            $table->index(['article_id', 'created_at'], 'revisions_article_created_idx');
        });
        Schema::table('article_status_histories', function (Blueprint $table): void {
            $table->index(['article_id', 'created_at'], 'histories_article_created_idx');
        });
        if (DB::connection()->getDriverName() === 'pgsql') {
            DB::statement("CREATE INDEX articles_published_latest_idx ON articles (published_at DESC) WHERE status = 'published' AND deleted_at IS NULL");
            DB::statement("CREATE INDEX articles_scheduled_due_idx ON articles (scheduled_at) WHERE status = 'scheduled' AND deleted_at IS NULL");
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (DB::connection()->getDriverName() === 'pgsql') {
            DB::statement('DROP INDEX IF EXISTS articles_published_latest_idx');
            DB::statement('DROP INDEX IF EXISTS articles_scheduled_due_idx');
        }
        Schema::table('article_status_histories', function (Blueprint $table): void {
            $table->dropIndex('histories_article_created_idx');
        });
        Schema::table('article_revisions', function (Blueprint $table): void {
            $table->dropIndex('revisions_article_created_idx');
        });
        Schema::table('comments', function (Blueprint $table): void {
            $table->dropIndex('comments_article_status_created_idx');
        });
    }
};
