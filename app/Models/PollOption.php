<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PollOption extends Model
{
    use HasFactory;

    protected $fillable = [
        'poll_id',
        'option_text',
        'color',
        'order',
        'vote_count',
    ];

    public function poll()
    {
        return $this->belongsTo(Poll::class);
    }
}
