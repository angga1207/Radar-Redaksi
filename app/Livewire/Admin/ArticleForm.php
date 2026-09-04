<?php

namespace App\Livewire\Admin;

use App\Actions\SaveArticleAction;
use App\Enums\ArticleStatus;
use App\Models\Article;
use App\Models\Category;
use App\Models\Media;
use App\Models\Tag;
use App\Services\ImageOptimizer;
use App\Support\HtmlSanitizer;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Locked;
use Livewire\Component;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;
use Livewire\WithFileUploads;

#[Layout('layouts.admin')]
class ArticleForm extends Component
{
    use WithFileUploads;

    public ?Article $article = null;

    public string $title = '';

    public string $slug = '';

    public string $excerpt = '';

    public string $body = '';

    public string $status = 'draft';

    public string $contentType = 'article';

    public int $carouselOrder = 0;

    public string $categoryId = '';

    public array $tagIds = [];

    public string $featuredImage = '';

    public string $selectedMediaId = '';

    public ?TemporaryUploadedFile $featuredImageUpload = null;

    public string $imageAlt = '';

    public string $imageCaption = '';

    public string $imageCredit = '';

    public bool $isFeatured = false;

    public bool $isHeadline = false;

    public bool $isBreaking = false;

    public bool $allowComments = true;

    public string $scheduledAt = '';

    public string $seoTitle = '';

    public string $seoDescription = '';

    public string $changeNote = '';

    public ?string $lastAutosavedAt = null;

    #[Locked]
    public string $lastAutosaveHash = '';

    public function mount(?Article $article = null): void
    {
        if ($article?->exists) {
            $this->authorize('update', $article);
            $this->article = $article;
            $this->title = $article->title;
            $this->slug = $article->slug;
            $this->excerpt = $article->excerpt;
            $this->body = $article->body;
            $this->status = $article->status->value;
            $this->contentType = $article->content_type ?? 'article';
            $this->carouselOrder = $article->carousel_order ?? 0;
            $this->categoryId = (string) $article->category_id;
            $this->tagIds = $article->tags()->pluck('tags.id')->map(fn($id) => (string) $id)->all();
            $this->featuredImage = $article->featured_image ?? '';
            $this->imageAlt = $article->image_alt ?? '';
            $this->imageCaption = $article->image_caption ?? '';
            $this->imageCredit = $article->image_credit ?? '';
            $this->isFeatured = $article->is_featured ?? false;
            $this->isHeadline = $article->is_headline ?? false;
            $this->isBreaking = $article->is_breaking ?? false;
            $this->allowComments = $article->allow_comments ?? true;
            $this->scheduledAt = $article->scheduled_at?->format('Y-m-d\TH:i') ?? '';
            $this->seoTitle = $article->seo_title ?? '';
            $this->seoDescription = $article->seo_description ?? '';
            $this->lastAutosaveHash = $this->autosaveHash();
        } else {
            $this->authorize('create', Article::class);
        }
    }

    public function save(SaveArticleAction $saveArticle, ImageOptimizer $imageOptimizer): void
    {
        $this->article ? $this->authorize('update', $this->article) : $this->authorize('create', Article::class);
        $this->slug = $this->article?->slug ?? $this->uniqueSlug($this->title);
        $validated = $this->validate([
            'title' => ['required', 'string', 'max:255'],
            // 'slug' => ['required', 'alpha_dash', 'max:255', Rule::unique('articles', 'slug')->ignore($this->article?->id)],
            'excerpt' => ['nullable', 'string', 'max:500'],
            'body' => ['required', 'string', 'min:0'],
            'status' => ['required', Rule::enum(ArticleStatus::class)],
            'contentType' => ['required', Rule::in(['article', 'photo', 'video'])],
            'carouselOrder' => ['integer', 'min:0', 'max:999'],
            'categoryId' => ['required', 'exists:categories,id'],
            'tagIds' => ['array'],
            'tagIds.*' => ['exists:tags,id'],
            'featuredImageUpload' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:51200'],
            'imageAlt' => ['nullable', 'string', 'max:255'],
            'imageCaption' => ['nullable', 'string', 'max:500'],
            'imageCredit' => ['nullable', 'string', 'max:255'],
            'isFeatured' => ['boolean'],
            'isHeadline' => ['boolean'],
            'isBreaking' => ['boolean'],
            'allowComments' => ['boolean'],
            'scheduledAt' => ['nullable', 'date', 'after:now'],
            'seoTitle' => ['nullable', 'string', 'max:255'],
            'seoDescription' => ['nullable', 'string', 'max:320'],
            'changeNote' => ['nullable', 'string', 'max:255'],
        ]);
        $status = ArticleStatus::from($validated['status']);
        if (! auth()->user()->isAdmin() && ! in_array($status, [ArticleStatus::Draft, ArticleStatus::InReview], true)) {
            abort(403);
        }
        $oldFeaturedImage = $this->article?->featured_image;
        $featuredImage = $this->featuredImageUpload ? $imageOptimizer->store($this->featuredImageUpload, 'articles')['path'] : null;
        $saveArticle->execute($this->article, auth()->user(), [
            'author_id' => $this->article?->author_id ?? auth()->id(),
            'editor_id' => in_array($status, [ArticleStatus::Published, ArticleStatus::Scheduled], true) ? auth()->id() : $this->article?->editor_id,
            'category_id' => $validated['categoryId'],
            'title' => $validated['title'],
            // 'slug' => $validated['slug'],
            'slug' => $this->article?->slug ?? $this->uniqueSlug($validated['title']),
            'content_type' => $validated['contentType'],
            'carousel_order' => $validated['carouselOrder'],
            'excerpt' => $validated['excerpt'],
            'body' => HtmlSanitizer::clean($validated['body']),
            'status' => $status,
            'featured_image' => $featuredImage ? Storage::disk('public')->url($featuredImage) : ($this->featuredImage ?: null),
            'image_alt' => $validated['imageAlt'] ?: null,
            'image_caption' => $validated['imageCaption'] ?: null,
            'image_credit' => $validated['imageCredit'] ?: null,
            'is_featured' => $validated['isFeatured'],
            'is_headline' => $validated['isHeadline'],
            'is_breaking' => $validated['isBreaking'],
            'published_at' => $status === ArticleStatus::Published ? ($this->article?->published_at ?? now()) : $this->article?->published_at,
            'scheduled_at' => $status === ArticleStatus::Scheduled ? $validated['scheduledAt'] : null,
            'allow_comments' => $validated['allowComments'],
            'seo_title' => $validated['seoTitle'] ?: null,
            'seo_description' => $validated['seoDescription'] ?: null,
        ], $validated['tagIds'], $validated['changeNote'] ?: null);
        if ($featuredImage) {
            $imageOptimizer->deletePublicUrl($oldFeaturedImage, 'articles');
        }
        session()->flash('success', 'Berita berhasil disimpan.');

        $this->redirectRoute('admin.articles.index', navigate: true);
    }

    public function updatedSelectedMediaId(string $mediaId): void
    {
        if ($mediaId === '') {
            return;
        }
        $media = Media::query()->findOrFail($mediaId);
        $this->featuredImage = Storage::disk($media->disk)->url($media->path);
        $this->imageAlt = $media->alt_text;
        $this->imageCaption = $media->caption ?? '';
        $this->imageCredit = $media->credit ?? '';
    }

    public function autosave(): void
    {
        if (! $this->article || ! in_array($this->article->status, [ArticleStatus::Draft, ArticleStatus::InReview], true)) {
            return;
        }
        $this->authorize('update', $this->article);
        $hash = $this->autosaveHash();
        if ($hash === $this->lastAutosaveHash) {
            return;
        }
        $data = $this->validate([
            'title' => ['required', 'string', 'max:255'],
            'excerpt' => ['nullable', 'string', 'max:500'],
            'body' => ['required', 'string', 'min:50'],
            'categoryId' => ['required', 'exists:categories,id'],
            'tagIds' => ['array'],
            'tagIds.*' => ['exists:tags,id'],
        ]);
        $this->article->update([
            'title' => $data['title'],
            'excerpt' => $data['excerpt'],
            'body' => HtmlSanitizer::clean($data['body']),
            'category_id' => $data['categoryId'],
        ]);
        $this->article->tags()->sync($data['tagIds']);
        $this->lastAutosaveHash = $hash;
        $this->lastAutosavedAt = now()->format('H:i:s');
    }

    public function loadRevision(int $revisionId): void
    {
        abort_unless($this->article, 404);
        $this->authorize('update', $this->article);
        $revision = $this->article->revisions()->findOrFail($revisionId);
        $this->title = $revision->title;
        $this->excerpt = $revision->excerpt;
        $this->body = $revision->body;
        $this->changeNote = 'Memulihkan revisi ' . $revision->created_at->format('d/m/Y H:i');
        $this->dispatch('rich-text:set', property: 'body', value: $this->body);
    }

    private function autosaveHash(): string
    {
        return hash('sha256', json_encode([$this->title, $this->excerpt, $this->body, $this->categoryId, $this->tagIds]) ?: '');
    }

    private function uniqueSlug(string $title): string
    {
        $baseSlug = Str::slug($title) ?: 'berita';
        $slug = $baseSlug;
        $suffix = 2;

        while (Article::query()->where('slug', $slug)->exists()) {
            $slug = $baseSlug . '-' . $suffix++;
        }

        return $slug;
    }

    public function render(): View
    {
        return view('livewire.admin.article-form', [
            'categories' => Category::query()->where('is_active', true)->orderBy('name')->get(),
            'tags' => Tag::query()->orderBy('name')->get(),
            'statuses' => auth()->user()->isAdmin() ? ArticleStatus::cases() : [ArticleStatus::Draft, ArticleStatus::InReview],
            'revisions' => $this->article?->revisions()->with('user')->latest()->limit(10)->get() ?? collect(),
            'statusHistories' => $this->article?->statusHistories()->with('actor')->latest()->limit(10)->get() ?? collect(),
            'mediaItems' => Media::query()->latest()->limit(50)->get(),
        ]);
    }
}
