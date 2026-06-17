<?php

namespace App\Models\Connect;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ConnectBusinessSetting extends Model
{
    protected $table = 'connect_business_settings';

    protected $guarded = ['id'];

    protected $casts = [
        'connect_enabled' => 'boolean',
        'auto_text_enabled' => 'boolean',
        'owner_sms_relay_enabled' => 'boolean',
        'require_code_when_multiple_active' => 'boolean',
        'send_owner_notification_on_customer_reply' => 'boolean',
        'pause_mode_enabled' => 'boolean',
        'duplicate_text_window_hours' => 'integer',
        'active_conversation_window_minutes' => 'integer',
    ];

    public function hours(): HasMany
    {
        return $this->hasMany(ConnectBusinessHour::class, 'business_id', 'business_id');
    }

    public function templates(): HasMany
    {
        return $this->hasMany(ConnectMessageTemplate::class, 'business_id', 'business_id');
    }

    public function conversations(): HasMany
    {
        return $this->hasMany(ConnectConversation::class, 'business_id', 'business_id');
    }

    public function customers(): HasMany
    {
        return $this->hasMany(ConnectCustomer::class, 'business_id', 'business_id');
    }

    public function isActive(): bool
    {
        return $this->status === 'active';
    }

    public function getBusinessName(): string
    {
        // =====================================================================
        // FREQUENTERS INTEGRATION POINT — business name
        // ---------------------------------------------------------------------
        // V1 ships with a generic fallback. Wire this to your existing
        // Frequenters business profile so customer-facing texts say the real
        // business name. Example:
        //
        //   return optional(\App\Models\Business::find($this->business_id))->name
        //       ?? 'our business';
        //
        // Grep the codebase for "FREQUENTERS INTEGRATION POINT" to find every
        // spot that must be wired before launch.
        // =====================================================================
        // return $this->attributes['business_name'] ?? 'our business';
        return optional(\App\Models\User::find($this->business_id))->businessProfile->business_name ?? 'our business';
    }
}
