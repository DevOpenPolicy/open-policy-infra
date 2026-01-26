<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Bill extends Model
{
    protected $fillable = [
        'session',
        'introduced',
        'short_name',
        'name',
        'number',
        'politician_id',
        'bill_url',
        'is_government_bill',
        'bills_json',
    ];

    /**
     * Get the comments for the bill.
     */
    public function comments(): HasMany
    {
        return $this->hasMany(BillComment::class, 'bill_id');
    }
}
