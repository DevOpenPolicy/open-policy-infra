<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Organization extends Model
{
    protected $fillable = [
        'name',
        'company_size',
        'country',
        'industries',
        'use_cases',
        'policy_interests',
        'alert_preference',
        'user_id',
    ];

    protected $casts = [
        'industries' => 'array',
        'use_cases' => 'array',
        'policy_interests' => 'array',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
