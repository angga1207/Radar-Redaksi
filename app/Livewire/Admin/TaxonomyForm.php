<?php

namespace App\Livewire\Admin;

use App\Models\Category;
use App\Models\Tag;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.admin')]
class TaxonomyForm extends Component
{
    public ?Category $category = null;

    public ?Tag $tag = null;

    public string $type = 'category';

    public string $name = '';

    public string $description = '';

    public string $parentId = '';

    public string $color = '#F15A24';

    public string $icon = '';

    public int $order = 0;

    public bool $isActive = true;

    public function mount(?Category $category = null, ?Tag $tag = null): void
    {
        $this->guard();
        $this->type = request()->routeIs('admin.taxonomy.tags.*') ? 'tag' : 'category';
        $this->category = $category?->exists ? $category : null;
        $this->tag = $tag?->exists ? $tag : null;
        $taxonomy = $this->category ?? $this->tag;
        if (! $taxonomy) {
            return;
        }

        $this->name = $taxonomy->name;
        if ($this->category) {
            $this->description = $this->category->description ?? '';
            $this->parentId = (string) ($this->category->parent_id ?? '');
            $this->color = $this->category->color ?? '#F15A24';
            $this->icon = $this->category->icon ?? '';
            $this->order = $this->category->order ?? 0;
            $this->isActive = $this->category->is_active ?? true;
        }
    }

    public function save(): void
    {
        $this->guard();
        if ($this->type === 'tag') {
            $this->saveTag();
        } else {
            $this->saveCategory();
        }

        session()->flash('success', $this->type === 'tag' ? 'Tag berhasil disimpan.' : 'Kanal berhasil disimpan.');
        $this->redirectRoute('admin.taxonomy.index', navigate: true);
    }

    private function saveCategory(): void
    {
        $data = $this->validate([
            'name' => ['required', 'string', 'max:100', Rule::unique('categories', 'name')->ignore($this->category?->id)],
            'description' => ['nullable', 'string', 'max:1000'],
            'parentId' => ['nullable', 'integer', 'exists:categories,id', Rule::notIn(array_filter([$this->category?->id]))],
            'color' => ['required', 'regex:/^#[0-9A-Fa-f]{6}$/'],
            'icon' => ['nullable', 'string', 'max:100'],
            'order' => ['integer', 'min:0', 'max:999'],
            'isActive' => ['boolean'],
        ]);
        $this->ensureParentDoesNotCreateCycle($data['parentId']);
        Category::query()->updateOrCreate(['id' => $this->category?->id], [
            'parent_id' => $data['parentId'] ?: null, 'name' => $data['name'], 'slug' => Str::slug($data['name']),
            'description' => $data['description'] ?: null, 'color' => $data['color'], 'icon' => $data['icon'] ?: null,
            'order' => $data['order'], 'is_active' => $data['isActive'],
        ]);
    }

    private function saveTag(): void
    {
        $data = $this->validate([
            'name' => ['required', 'string', 'max:100', Rule::unique('tags', 'name')->ignore($this->tag?->id)],
        ]);
        Tag::query()->updateOrCreate(['id' => $this->tag?->id], ['name' => $data['name'], 'slug' => Str::slug($data['name'])]);
    }

    private function guard(): void
    {
        abort_unless(auth()->user()->hasPermission('taxonomy.manage'), 403);
    }

    private function ensureParentDoesNotCreateCycle(string|int|null $parentId): void
    {
        if (! $this->category || ! $parentId) {
            return;
        }

        $visited = [];
        $candidate = Category::query()->find($parentId);
        while ($candidate && ! in_array($candidate->id, $visited, true)) {
            if ($candidate->id === $this->category->id) {
                throw ValidationException::withMessages(['parentId' => 'Kanal induk tidak boleh berasal dari subkanal kanal ini.']);
            }
            $visited[] = $candidate->id;
            $candidate = $candidate->parent_id ? Category::query()->find($candidate->parent_id) : null;
        }
    }

    public function render(): View
    {
        return view('livewire.admin.taxonomy-form', [
            'categories' => $this->type === 'category'
                ? Category::query()->when($this->category, fn ($query) => $query->whereKeyNot($this->category->id))->orderBy('order')->orderBy('name')->get()
                : collect(),
        ]);
    }
}
