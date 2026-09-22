<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ForumCategory extends Model
{
    use HasFactory;

    protected $table = 'forum_categories';

    protected $fillable = [
        'scope_type',
        'scope_id',
        'nama',
        'deskripsi',
        'urutan',
    ];

    public function threads(): HasMany
    {
        return $this->hasMany(ForumThread::class, 'category_id');
    }
}
