<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AceChatSession extends Model
{
    use HasFactory;

    protected $fillable = ['user_id', 'title'];

    public function messages()
    {
        return $this->hasMany(AceChatMessage::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
