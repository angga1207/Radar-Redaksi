<?php

namespace App\Livewire\Admin;

use App\Models\AuditLog;
use App\Models\Role;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Cache;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.admin')]
class RoleManager extends Component
{
    /** @var array<string, array<int, string>> */
    private const DEFAULTS = [
        'super_admin' => ['*'],
        'admin' => ['articles.manage', 'taxonomy.manage', 'media.manage', 'comments.manage', 'pages.manage', 'menus.manage', 'advertisements.manage'],
        'editor' => ['articles.review', 'articles.publish', 'media.manage'],
        'reporter' => ['articles.own', 'media.upload'],
        'contributor' => ['articles.own', 'media.upload'],
    ];

    public ?int $editingId = null;

    public string $label = '';

    /** @var array<int, string> */
    public array $permissions = [];

    public function mount(): void
    {
        $this->guard();
        foreach (self::DEFAULTS as $name => $permissions) {
            Role::query()->firstOrCreate(['name' => $name], ['label' => str($name)->headline(), 'permissions' => $permissions]);
        }
    }

    public function edit(int $id): void
    {
        $this->guard();
        $role = Role::query()->findOrFail($id);
        $this->editingId = $role->id;
        $this->label = $role->label;
        $this->permissions = $role->permissions;
    }

    public function save(): void
    {
        $this->guard();
        $data = $this->validate([
            'label' => ['required', 'string', 'max:100'],
            'permissions' => ['array'],
            'permissions.*' => [Rule::in(['*', ...array_keys($this->availablePermissions())])],
        ]);
        $role = Role::query()->findOrFail($this->editingId);
        $role->update($data);
        Cache::forget('role-permissions:'.$role->name);
        AuditLog::record('role.updated', $role);
        $this->reset(['editingId', 'label', 'permissions']);
        session()->flash('success', 'Hak akses berhasil diperbarui.');
    }

    public function render(): View
    {
        $this->guard();

        return view('livewire.admin.role-manager', ['roles' => Role::query()->orderBy('id')->get(), 'availablePermissions' => $this->availablePermissions()]);
    }

    /** @return array<string, string> */
    private function availablePermissions(): array
    {
        return [
            'articles.manage' => 'Kelola semua berita', 'articles.review' => 'Review berita', 'articles.publish' => 'Terbitkan berita',
            'articles.own' => 'Kelola berita sendiri', 'taxonomy.manage' => 'Kelola kanal dan tag', 'media.manage' => 'Kelola seluruh media',
            'media.upload' => 'Unggah media', 'comments.manage' => 'Moderasi komentar', 'pages.manage' => 'Kelola halaman',
            'menus.manage' => 'Kelola menu', 'advertisements.manage' => 'Kelola iklan', 'settings.manage' => 'Kelola pengaturan',
            'users.manage' => 'Kelola pengguna', 'roles.manage' => 'Kelola hak akses', 'audit.view' => 'Lihat audit log',
        ];
    }

    private function guard(): void
    {
        abort_unless(auth()->user()->hasPermission('roles.manage'), 403);
    }
}
