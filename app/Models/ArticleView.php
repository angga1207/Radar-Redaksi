<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;

#[Fillable(['article_id', 'session_hash', 'ip_hash', 'viewed_on', 'user_agent'])]
class ArticleView extends Model
{
    protected function casts(): array
    {
        return ['viewed_on' => 'date'];
    }
}
