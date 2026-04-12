<?php

namespace App\Models;

use App\Traits\InteractsWithAttachments;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Community extends Model
{
    use HasUuids, InteractsWithAttachments;

    public const TABLE = 'mt_community';

    protected $table = self::TABLE;

    public $incrementing = false;

    protected $keyType = 'string';

    protected $primaryKey = 'id';

    protected $fillable = [
        'id',
        'name',
        'description',
        'website',
        'attachment_id',
        'address_id',
        'created_by',
        'is_active',
    ];

    protected $casts = [
        'is_member' => 'boolean',
    ];

    public function address(): BelongsTo
    {
        return $this->belongsTo(Address::class, 'address_id', 'id');
    }

    public function attachment(): BelongsTo
    {
        return $this->belongsTo(Attachment::class, 'attachment_id', 'id');
    }

    public function tags(): BelongsToMany
    {
        return $this->belongsToMany(AllTag::class, 'tr_tag_community_record', 'community_id', 'tag_id');
    }

    public function members(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'tr_follow_community', 'community_id', 'user_id');
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by', 'id');
    }

    public function scopeWithMemberStatus($query, ?string $userId)
    {
        return $query->when($userId, function ($q) use ($userId) {
            $q->withCount([
                'members as is_member' => fn ($sub) => $sub->where('user_id', $userId),
            ]);
        });
    }
}
