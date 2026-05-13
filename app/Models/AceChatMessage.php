<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AceChatMessage extends Model
{
    use HasFactory;

    protected $fillable = ['ace_chat_session_id', 'role', 'content'];

    public function session()
    {
        return $this->belongsTo(AceChatSession::class, 'ace_chat_session_id');
    }
}
