<?php

namespace App\Livewire\Admin;

use App\Models\Comment;
use Illuminate\Contracts\View\View;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('layouts.admin')]
class CommentManager extends Component
{
    use WithPagination;

    public string $status = 'pending';

    public function moderate(int $commentId, string $status): void
    {
        abort_unless(auth()->user()->hasPermission('comments.manage'), 403);
        validator(['status' => $status], ['status' => [Rule::in(['approved', 'rejected', 'spam'])]])->validate();
        Comment::query()->findOrFail($commentId)->update(['status' => $status]);
    }

    public function render(): View
    {
        abort_unless(auth()->user()->hasPermission('comments.manage'), 403);

        return view('livewire.admin.comment-manager', ['comments' => Comment::query()->with('article')->when($this->status, fn ($query) => $query->where('status', $this->status))->latest()->paginate(15)]);
    }
}
