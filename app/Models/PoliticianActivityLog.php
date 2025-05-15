<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PoliticianActivityLog extends Model
{
    protected $fillable = [
        'politician_id',
        'election_summary',
        'activity',
        'latest_activity'
    ];

    protected $casts = [
        'activity' => 'array',
        'latest_activity' => 'array'
    ];

    public function politician()
    {
        return $this->belongsTo(Politicians::class, 'politician_id', 'id');
    }
}
