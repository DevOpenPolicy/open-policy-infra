<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ParliamentSession extends Model
{   
    protected $fillable = [
        'name',
        'session',
    ];
}
