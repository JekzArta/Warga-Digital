<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ForumThread extends Model
{
    use HasFactory;

    protected $table = 'forum_threads';

    protected $fillable = [
        'category_id',
        'author_id',
        'judul',
        'konten',
        'status',
        'is_pinned',
        'author_role_snapshot',
    ];

    protected function casts(): array
    {
        return [
            'is_pinned' => 'boolean',
            'author_role_snapshot' => 'array',
        ];
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(ForumCategory::class, 'category_id');
    }

    public function author(): BelongsTo
    {
        return $this->belongsTo(User::class, 'author_id');
    }

    public function posts(): HasMany
    {
        return $this->hasMany(ForumPost::class, 'thread_id');
    }
}
