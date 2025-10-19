<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Debate extends Model
{
    protected $fillable = [
        'date',
        'number',
        'most_frequent_word',
        'debate_url'
    ];
}
