<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RepresentativeIssue extends Model
{
    
    protected $fillable = [
        'representative_id',
        'name',
        'summary',
        'description',
    ];
}
