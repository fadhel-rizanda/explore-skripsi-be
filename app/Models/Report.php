<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Report extends Model
{
    use HasUuids;

    public const TABLE = 'mt_report';

    protected $table = self::TABLE;

    public $incrementing = false;

    public $timestamps = false;

    protected $keyType = 'string';

    protected $primaryKey = 'id';

    protected $fillable = [
        'id',
        'reference_type',
        'reference_id',
        'notes',
        'status_id',
        'created_by',
    ];

    public function tags(): BelongsToMany
    {
        return $this->belongsToMany(AllTag::class, 'tr_all_tag_report_record', 'report_id', 'all_tag_id');
    }

    public function status(): BelongsTo
    {
        return $this->belongsTo(Status::class, 'status_id');
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
