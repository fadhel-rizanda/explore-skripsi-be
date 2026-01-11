<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AllTag extends Model
{
    use HasFactory, HasUuids;

    protected $table = 'mt_all_tag';

    public $incrementing = false;

    protected $keyType = 'string';

    protected $primaryKey = 'id';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'id',
        'tag_name',
        'tag_type',
    ];

    /**
     * Get the pet personality records associated with this tag.
     */
    public function pet(): BelongsTo
    {
        return $this->belongsTo(Pet::class, 'pet_id');
    }

    public function allTag(): BelongsTo
    {
        return $this->belongsTo(AllTag::class, 'all_tag_id');
    }

}