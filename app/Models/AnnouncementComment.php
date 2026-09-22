<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AnnouncementComment extends Model
{
    use HasFactory;

    protected $table = 'announcement_comments';

    protected $fillable = [
        'announcement_id',
        'author_id',
        'konten',
    ];

    public function announcement(): BelongsTo
    {
        return $this->belongsTo(Announcement::class, 'announcement_id');
    }

    public function author(): BelongsTo
    {
        return $this->belongsTo(User::class, 'author_id');
    }
}
