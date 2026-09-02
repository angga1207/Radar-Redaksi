<?php

namespace App\Livewire\Admin;

use App\Models\Category;
use App\Models\Tag;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.admin')]
class TaxonomyManager extends Component
{
    public function deleteCategory(int $id): void
    {
        $this->guard();
        $category = Category::query()->withCount('articles')->findOrFail($id);
        if ($category->articles_count > 0) {
            $this->addError('category', 'Kanal masih digunakan berita.');

            return;
        }
        if ($category->children()->exists()) {
            $this->addError('category', 'Kanal masih memiliki subkanal.');

            return;
        }
        $category->delete();
    }

    public function deleteTag(int $id): void
    {
        $this->guard();
        Tag::query()->findOrFail($id)->delete();
    }

    private function guard(): void
    {
        abort_unless(auth()->user()->hasPermission('taxonomy.manage'), 403);
    }

    public function render(): View
    {
        $this->guard();

        return view('livewire.admin.taxonomy-manager', [
            'categories' => Category::query()->with('parent')->withCount('articles')->orderBy('order')->get(),
            'tags' => Tag::query()->withCount('articles')->orderBy('name')->get(),
        ]);
    }
}
