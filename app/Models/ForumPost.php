<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ForumPost extends Model
{
    use HasFactory;

    protected $table = 'forum_posts';

    protected $fillable = [
        'thread_id',
        'author_id',
        'parent_post_id',
        'konten',
        'author_role_snapshot',
    ];

    protected function casts(): array
    {
        return [
            'author_role_snapshot' => 'array',
        ];
    }

    public function thread(): BelongsTo
    {
        return $this->belongsTo(ForumThread::class, 'thread_id');
    }

    public function author(): BelongsTo
    {
        return $this->belongsTo(User::class, 'author_id');
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(ForumPost::class, 'parent_post_id');
    }

    public function replies(): HasMany
    {
        return $this->hasMany(ForumPost::class, 'parent_post_id');
    }
}
