<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Laravel\Sanctum\HasApiTokens;
use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;


class User extends Authenticatable
{
    use HasApiTokens, Notifiable, HasFactory, SoftDeletes;

    protected $fillable = ['name', 'email', 'password', 'role','last_login_at', 'phone', 'profile_image', 'gender', 'status', 'parent_business_id', 'providers', 'provider_id', 'timezone'];
    protected $primaryKey = 'id';
    protected $hidden = ['password', 'remember_token'];
    protected $dates = ['deleted_at'];

    public function getAuthPassword()
    {
        return $this->password;
    }

    public function scopeByRole($query, $role)
    {
        return $query->where('role', $role);
    }

    // OLD FUNCATION NOT IN USE
    // public function businessProfile()
    // {
    //     return $this->hasOne(BusinessProfile::class, 'business_id', 'user_id');
    // }

    /**
     * Define the relationship to the CustomerProfile model.
     *
     * @return HasOne
     */
    public function profile(): HasOne
    {
        return $this->hasOne(CustomerProfile::class, 'customer_id', 'user_id'); // Ensure 'customer_id' is the foreign key

    }

    // Relationship with BusinessProfile
    public function businessProfile()
    {
        return $this->hasOne(BusinessProfile::class, 'business_id')->withDefault();
    }

    // Relationship with BusinessAddress
    public function businessAddress()
    {
        return $this->hasOne(BusinessAddress::class, 'business_id')->withDefault();
    }

    // Relationship with BusinessImages
    public function businessImage()
    {
        return $this->hasMany(BusinessImage::class, 'business_id');
    }

    // Relationship with BusinessHour
    public function businessHours()
    {
        return $this->hasMany(BusinessHour::class, 'business_id');
    }

    // Relationship with CustomerProfile
    public function customerProfile()
    {
        return $this->hasOne(CustomerProfile::class, 'customer_id')->withDefault();
    }

    // Relationship with CustomerAddress
    public function customerAddress()
    {
        return $this->hasOne(CustomerAddress::class, 'customer_id')->withDefault();
    }

    // Relationship with CustomerBadges
    public function customerBadges()
    {
        return $this->hasOne(CustomerBadge::class, 'customer_id');
    }

    // Contests
    public function businessContest()
    {
        return $this->hasMany(Contest::class, 'business_id');
    }

    // Events
    public function businessEvent()
    {
        return $this->hasMany(Event::class, 'business_id');
    }

    // Offers
    public function businessOffer()
    {
        return $this->hasMany(Offer::class, 'business_id');
    }

    // Rewards
    public function businessReward()
    {
        return $this->hasMany(Reward::class, 'business_id');
    }

    // Menu
    public function businessMenu()
    {
        return $this->hasMany(Menu::class, 'business_id');
    }

    // Services
    public function businessService()
    {
        return $this->hasMany(Service::class, 'business_id');
    }

    // CheckIn
    public function customerCheckIn()
    {
        return $this->hasMany(CheckIn::class, 'customer_id');
    }

    // reward_redemptions
    public function customerRewardRedemptions()
    {
        return $this->hasMany(RewardRedemption::class, 'customer_id');
    }

    // History
    public function customerHistory()
    {
        return $this->hasMany(SearchHistory::class, 'customer_id');
    }

    // Wishlist
    public function customerWishlist()
    {
        return $this->hasMany(Wishlist::class, 'customer_id');
    }

    // Complaint
    public function customerComplaints()
    {
        return $this->hasMany(Complaint::class, 'customer_id');
    }

    // Invited
    public function friendsInvited()
    {
        return $this->hasMany(Invite::class, 'customer_id');
    }

    // Invited
    public function cusomerSubmitReviews()
    {
        return $this->hasMany(Review::class, 'customer_id');
    }

    // Contest Participant
    public function customerParticipant()
    {
        return $this->hasMany(ContestParticipant::class, 'user_id');
    }
    
    // Contest Participant
    public function customerEvents()
    {
        return $this->hasMany(EventReservation::class, 'customer_id');
    }

    // Interactions
    public function customerBusinessInteractions()
    {
        return $this->hasMany(CustomerBusinessInteraction::class, 'customer_id');
    }


    public function effectiveBusinessId(): ?int
    {
        return match ($this->role) {
            'business'           => $this->id,
            'manager', 'employee' => $this->parent_business_id,
            default              => null,
        };
    }

    // Whether user is the actual business owner (not staff).
    public function isBusinessOwner(): bool
    {
        return $this->role === 'business';
    }

    // Whether user is staff under a business.
    public function isBusinessStaff(): bool
    {
        return in_array($this->role, ['manager', 'employee'], true) && !is_null($this->parent_business_id);
    }

    //  Whether user is allowed to act as the business (owner or staff) || Use this for QR generation, customer check-in scans, etc.
    public function canActAsBusiness(): bool
    {
        return $this->isBusinessOwner() || $this->isBusinessStaff();
    }
}
