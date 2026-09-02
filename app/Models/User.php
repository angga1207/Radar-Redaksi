<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\Cache;

#[Fillable(['name', 'username', 'email', 'password', 'role', 'bio', 'avatar', 'is_active', 'last_login_at'])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    public function articles(): HasMany
    {
        return $this->hasMany(Article::class, 'author_id');
    }

    public function isAdmin(): bool
    {
        return $this->hasPermission('articles.manage') || $this->hasPermission('articles.review') || $this->hasPermission('articles.publish');
    }

    public function hasPermission(string $permission): bool
    {
        if (! $this->is_active) {
            return false;
        }
        $permissions = Cache::remember('role-permissions:'.$this->role, now()->addHour(), fn () => Role::query()->where('name', $this->role)->value('permissions'));
        $permissions ??= match ($this->role) {
            'super_admin' => ['*'],
            'admin' => ['articles.manage', 'taxonomy.manage', 'media.manage', 'comments.manage', 'pages.manage', 'menus.manage', 'advertisements.manage'],
            'editor' => ['articles.review', 'articles.publish', 'media.manage'],
            'reporter', 'contributor' => ['articles.own', 'media.upload'],
            default => [],
        };
        if (is_string($permissions)) {
            $permissions = json_decode($permissions, true) ?: [];
        }

        return in_array('*', $permissions, true) || in_array($permission, $permissions, true);
    }

    public function canAccessPanel(): bool
    {
        return $this->is_active && ($this->role === 'super_admin' || $this->hasPermission('articles.own') || $this->isAdmin() || $this->hasPermission('media.upload'));
    }

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'is_active' => 'boolean',
            'last_login_at' => 'immutable_datetime',
        ];
    }
}
