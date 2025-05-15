<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Politicians extends Model
{
    protected $fillable = [
        'name',
        'constituency_offices',
        'email',
        'voice',
        'party_name',
        'party_short_name',
        'province_name',
        'province_short_name',
        'province_location',
        'politician_url',
        'politician_image',
        'politician_json',
    ];
    
}
