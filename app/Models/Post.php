<?php

namespace App\Models;

use App\Traits\InteractsWithAttachments;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Post extends Model
{
    use HasUuids, InteractsWithAttachments;

    protected $table = 'mt_post';

    public $incrementing = false;

    protected $keyType = 'string';

    protected $primaryKey = 'id';

    protected $fillable = [
        'id',
        'title',
        'content',
        'attachment_id',
        'community_id',
        'created_by',
        'is_active',
    ];

    public function community(): BelongsTo
    {
        return $this->belongsTo(Community::class, 'community_id', 'id');
    }

    public function attachment(): BelongsTo
    {
        return $this->belongsTo(Attachment::class, 'attachment_id', 'id');
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by', 'id');
    }

    public function tags(): BelongsToMany
    {
        return $this->belongsToMany(AllTag::class, 'tr_tag_post_record', 'post_id', 'tag_id');
    }

    public function likes(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'tr_like_post', 'post_id', 'user_id');
    }

    public function comments(): HasMany
    {
        return $this->hasMany(Comment::class, 'post_id', 'id');
    }
}
