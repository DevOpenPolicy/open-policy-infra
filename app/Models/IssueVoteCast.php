<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class IssueVoteCast extends Model
{
    protected $fillable = [
        'issue_id',
        'user_id',
        'is_supported',
    ];
}
