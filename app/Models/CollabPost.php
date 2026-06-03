<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Builder;

class CollabPost extends Model
{
    use HasFactory, SoftDeletes;
    protected $table = 'collab_posts';
    protected $fillable = [
        'business_id',
        'post_type',
        'headline',
        'description',
        'target_industries',
        'duration_days',
        'expires_at',
        'contact_type',
        'contact_name',
        'contact_email',
        'contact_phone',
        'latitude',
        'longitude',
        'is_active',
    ];

    protected $casts = [
        'target_industries' => 'array',
        'expires_at'        => 'datetime',
        'is_active'         => 'boolean',
        'duration_days'     => 'integer',
    ];

    // Relationships
    public function business()
    {
        return $this->belongsTo(User::class, 'business_id');
    }

    public function businessProfile()
    {
        return $this->hasOneThrough(
            BusinessProfile::class,
            User::class,
            'id',          // users.id
            'business_id', // business_profiles.business_id
            'business_id', // collab_posts.business_id
            'id'           // users.id
        );
    }

    public function responses()
    {
        return $this->hasMany(CollabPostResponse::class, 'collab_post_id');
    }

    public function conversations()
    {
        return $this->hasMany(B2BConversation::class, 'collab_post_id');
    }

    // Scopes
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true)->where('expires_at', '>', now());
    }

    /**
     * Haversine formula — filter by radius in miles
     */
    public function scopeWithinRadius(Builder $query, float $lat, float $lng, float $radiusMiles = 5): Builder
    {
        return $query->selectRaw("
                *,
                (3959 * ACOS(
                    COS(RADIANS(?)) * COS(RADIANS(latitude)) *
                    COS(RADIANS(longitude) - RADIANS(?)) +
                    SIN(RADIANS(?)) * SIN(RADIANS(latitude))
                )) AS distance_miles
            ", [$lat, $lng, $lat])
            ->having('distance_miles', '<=', $radiusMiles)
            ->orderBy('distance_miles');
    }

    // Helpers
    public function isExpired(): bool
    {
        return $this->expires_at->isPast();
    }

    public function hasRespondedBy(int $businessId): bool
    {
        return $this->responses()->where('responder_business_id', $businessId)->exists();
    }
}