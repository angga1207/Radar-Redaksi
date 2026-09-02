<?php

namespace App\Livewire\Admin;

use App\Models\AuditLog;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('layouts.admin')]
class AuditLogIndex extends Component
{
    use WithPagination;

    public function render(): View
    {
        abort_unless(auth()->user()->hasPermission('audit.view'), 403);

        return view('livewire.admin.audit-log-index', ['logs' => AuditLog::query()->with('actor')->latest()->paginate(25)]);
    }
}
