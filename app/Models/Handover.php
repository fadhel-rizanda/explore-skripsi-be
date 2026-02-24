<?php

namespace App\Models;

use App\Traits\InteractsWithAttachments;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Handover extends Model
{
    use HasUuids, InteractsWithAttachments;

    public const TABLE = 'tr_adoption_handover';

    protected $table = self::TABLE;

    public $incrementing = false;

    protected $keyType = 'string';

    protected $primaryKey = 'id';

    protected $fillable = [
        'id',
        'adoption_id',
        'meet_n_greet_id',
        'status_id',

        'adopter_finalized',
        'provider_finalized',
        'admin_finalized',
        'adopter_finalized_at',
        'provider_finalized_at',
        'admin_finalized_at',

        'created_by',
        'updated_by',
        'is_active',
    ];

    protected $casts = [
        'adopter_finalized' => 'boolean',
        'provider_finalized' => 'boolean',
        'admin_finalized' => 'boolean',

        'adopter_finalized_at' => 'datetime',
        'provider_finalized_at' => 'datetime',
        'admin_finalized_at' => 'datetime',

        'is_active' => 'boolean',
    ];

    public function meetNGreet(): BelongsTo
    {
        return $this->belongsTo(MeetNGreet::class, 'meet_n_greet_id');
    }

    public function attachments(): BelongsToMany
    {
        return $this->belongsToMany(Attachment::class, 'tr_adoption_handover_attachment', 'handover_id', 'attachment_id')
            ->withPivot('uploaded_by_role');
    }

    public function status(): BelongsTo
    {
        return $this->belongsTo(Status::class, 'status_id');
    }
}
