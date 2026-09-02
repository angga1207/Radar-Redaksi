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
        Schema::create('roles', function (Blueprint $table): void {
            $table->id();
            $table->string('name')->unique();
            $table->string('label');
            $table->json('permissions');
            $table->timestamps();
        });

        Schema::table('categories', function (Blueprint $table): void {
            $table->string('icon')->nullable();
        });

        Schema::table('articles', function (Blueprint $table): void {
            $table->string('content_type')->default('article')->index();
            $table->unsignedSmallInteger('carousel_order')->default(0)->index();
        });
        if (DB::connection()->getDriverName() === 'pgsql') {
            DB::statement("CREATE INDEX articles_fulltext_idx ON articles USING GIN (to_tsvector('simple', coalesce(title, '') || ' ' || coalesce(excerpt, '') || ' ' || coalesce(body, '')))");
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (DB::connection()->getDriverName() === 'pgsql') {
            DB::statement('DROP INDEX IF EXISTS articles_fulltext_idx');
        }
        Schema::table('articles', function (Blueprint $table): void {
            $table->dropColumn(['content_type', 'carousel_order']);
        });
        Schema::table('categories', function (Blueprint $table): void {
            $table->dropColumn('icon');
        });
        Schema::dropIfExists('roles');
    }
};
