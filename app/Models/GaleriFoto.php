<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class GaleriFoto extends Model
{
    use HasFactory;

    protected $table = 'galeri_foto';

    protected $fillable = [
        'album_id',
        'foto_url',
        'uploaded_by',
    ];

    public function album(): BelongsTo
    {
        return $this->belongsTo(GaleriAlbum::class, 'album_id');
    }

    public function uploader(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }
}
