<?php

namespace Modules\UBPortal\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Modules\Auth\Models\User;
use Modules\UBPortal\Enums\PostStatus;

class Post extends Model
{
    use HasUuids;

    protected $fillable = [
        'id',
        'title',
        'content',
        'cover_image',
        'author_id',
        'status',
        'approved_by',
        'approved_at',
    ];

    protected $casts = [
        'status' => PostStatus::class,
        'approved_at' => 'datetime',
    ];

    public function author(): BelongsTo
    {
        return $this->belongsTo(User::class, 'author_id');
    }

    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function tags(): BelongsToMany
    {
        return $this->belongsToMany(Tag::class, 'post_tags');
    }

    public function likedByUsers(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'post_likes');
    }

    public function bookmarkedByUsers(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'post_bookmarks');
    }

    public function views(): HasMany
    {
        return $this->hasMany(PostView::class);
    }
}
