<?php

namespace App\Livewire\Admin;

use App\Models\AuditLog;
use App\Models\User;
use App\Services\ImageOptimizer;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;
use Livewire\WithFileUploads;

#[Layout('layouts.admin')]
class UserForm extends Component
{
    use WithFileUploads;

    public ?User $user = null;

    public string $name = '';

    public string $username = '';

    public string $email = '';

    public string $role = 'reporter';

    public string $password = '';

    public string $bio = '';

    public string $avatar = '';

    public ?TemporaryUploadedFile $avatarUpload = null;

    public bool $isActive = true;

    public function mount(?User $user = null): void
    {
        $this->guard();
        if (! $user?->exists) {
            return;
        }

        $this->user = $user;
        $this->name = $user->name;
        $this->username = $user->username;
        $this->email = $user->email;
        $this->role = $user->role;
        $this->bio = $user->bio ?? '';
        $this->avatar = $user->avatar ?? '';
        $this->isActive = $user->is_active;
    }

    public function save(ImageOptimizer $imageOptimizer): void
    {
        $this->guard();
        $data = $this->validate([
            'name' => ['required', 'string', 'max:100'],
            'username' => ['required', 'alpha_dash', 'max:50', Rule::unique('users')->ignore($this->user?->id)],
            'email' => ['required', 'email', 'max:255', Rule::unique('users')->ignore($this->user?->id)],
            'role' => ['required', Rule::in(['super_admin', 'admin', 'editor', 'reporter', 'contributor'])],
            'password' => [$this->user ? 'nullable' : 'required', 'string', 'min:8'],
            'bio' => ['nullable', 'string', 'max:1000'],
            'avatarUpload' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
            'isActive' => ['boolean'],
        ]);
        $oldAvatar = $this->avatar;
        $avatarPath = $this->avatarUpload ? $imageOptimizer->store($this->avatarUpload, 'avatars')['path'] : null;
        $attributes = [
            'name' => $data['name'], 'username' => $data['username'], 'email' => $data['email'],
            'role' => $data['role'], 'bio' => $data['bio'] ?: null,
            'avatar' => $avatarPath ? Storage::disk('public')->url($avatarPath) : ($this->avatar ?: null),
            'is_active' => $data['isActive'],
        ];
        if ($data['password']) {
            $attributes['password'] = $data['password'];
        }

        $user = User::query()->updateOrCreate(['id' => $this->user?->id], $attributes);
        if ($avatarPath) {
            $imageOptimizer->deletePublicUrl($oldAvatar, 'avatars');
        }
        AuditLog::record($this->user ? 'user.updated' : 'user.created', $user, [], collect($attributes)->except('password')->all());
        session()->flash('success', 'Pengguna berhasil disimpan.');

        $this->redirectRoute('admin.users.index', navigate: true);
    }

    private function guard(): void
    {
        abort_unless(auth()->user()->hasPermission('users.manage'), 403);
    }

    public function render(): View
    {
        return view('livewire.admin.user-form');
    }
}
