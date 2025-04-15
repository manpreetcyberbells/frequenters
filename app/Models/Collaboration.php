<?php

namespace App\Models;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Collaboration extends Model
{
    use HasFactory;

    protected $table = 'collaborations';
    protected $fillable = [
        'creator_business_id',
        'partner_business_id',
        'target_business_type',
        'description',
        'start_date',
        'end_date',
        'is_active',
        'contact_name',
        'contact_email',
        'contact_phone',
        'social_links',
    ];
    
}
