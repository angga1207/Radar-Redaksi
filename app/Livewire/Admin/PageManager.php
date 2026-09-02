<?php

namespace App\Livewire\Admin;

use App\Models\Page;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('layouts.admin')]
class PageManager extends Component
{
    use WithPagination;

    public string $search = '';

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function render(): View
    {
        abort_unless(auth()->user()->hasPermission('pages.manage'), 403);

        return view('livewire.admin.page-manager', [
            'pages' => Page::query()
                ->when($this->search, fn ($query) => $query->where(function ($query): void {
                    $query->where('title', 'ilike', '%'.$this->search.'%')->orWhere('slug', 'ilike', '%'.$this->search.'%');
                }))
                ->latest()
                ->paginate(15),
        ]);
    }
}
