<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            $table->string('avatar')->nullable();
            $table->timestamp('last_login_at')->nullable();
        });
        Schema::table('articles', function (Blueprint $table): void {
            $table->string('image_caption')->nullable();
        });
        Schema::create('article_revisions', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('article_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->restrictOnDelete();
            $table->string('title');
            $table->text('excerpt');
            $table->longText('body');
            $table->string('change_note')->nullable();
            $table->timestamps();
        });
        Schema::create('article_status_histories', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('article_id')->constrained()->cascadeOnDelete();
            $table->foreignId('actor_id')->constrained('users')->restrictOnDelete();
            $table->string('from_status')->nullable();
            $table->string('to_status');
            $table->string('note')->nullable();
            $table->timestamps();
        });
        Schema::create('comments', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('article_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('parent_id')->nullable()->constrained('comments')->cascadeOnDelete();
            $table->string('name');
            $table->string('email');
            $table->text('body');
            $table->string('status')->default('pending')->index();
            $table->string('ip_hash', 64);
            $table->text('user_agent')->nullable();
            $table->timestamps();
        });
        Schema::create('article_views', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('article_id')->constrained()->cascadeOnDelete();
            $table->string('session_hash', 64);
            $table->string('ip_hash', 64);
            $table->date('viewed_on');
            $table->text('user_agent')->nullable();
            $table->timestamps();
            $table->unique(['article_id', 'session_hash', 'viewed_on']);
        });
        Schema::create('pages', function (Blueprint $table): void {
            $table->id();
            $table->string('title');
            $table->string('slug')->unique();
            $table->longText('body');
            $table->string('status')->default('draft')->index();
            $table->string('seo_title')->nullable();
            $table->string('seo_description', 320)->nullable();
            $table->timestamp('published_at')->nullable();
            $table->timestamps();
        });
        Schema::create('settings', function (Blueprint $table): void {
            $table->id();
            $table->string('group')->index();
            $table->string('key')->unique();
            $table->json('value')->nullable();
            $table->boolean('is_public')->default(false);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('settings');
        Schema::dropIfExists('pages');
        Schema::dropIfExists('article_views');
        Schema::dropIfExists('comments');
        Schema::dropIfExists('article_status_histories');
        Schema::dropIfExists('article_revisions');
        Schema::table('articles', fn (Blueprint $table) => $table->dropColumn('image_caption'));
        Schema::table('users', fn (Blueprint $table) => $table->dropColumn(['avatar', 'last_login_at']));
    }
};
