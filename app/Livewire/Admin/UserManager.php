<?php

namespace App\Livewire\Admin;

use App\Models\User;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('layouts.admin')]
class UserManager extends Component
{
    use WithPagination;

    public string $search = '';

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function render(): View
    {
        abort_unless(auth()->user()->hasPermission('users.manage'), 403);

        return view('livewire.admin.user-manager', [
            'users' => User::query()
                ->when($this->search, fn ($query) => $query->where(function ($query): void {
                    $query->where('name', 'ilike', '%'.$this->search.'%')
                        ->orWhere('username', 'ilike', '%'.$this->search.'%')
                        ->orWhere('email', 'ilike', '%'.$this->search.'%');
                }))
                ->latest()
                ->paginate(12),
        ]);
    }
}
