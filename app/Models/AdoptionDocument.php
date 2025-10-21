<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AdoptionDocument extends Model
{
    use HasFactory;

    protected $fillable = [
        'adoption_id',
        'uploaded_by',
        'filename',
        'path',
        'file_size',
        'mime_type',
        'status',
        'uploaded_at'
    ];

    protected $casts = [
        'uploaded_at' => 'datetime',
    ];

    public function adoption()
    {
        return $this->belongsTo(Adoption::class);
    }

    public function uploader()
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }
}
