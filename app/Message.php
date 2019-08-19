<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Message extends Model
{
    protected $table = 'messages';

    protected $fillable = [
        'from_user_id', 'to_user_id', 'type', 'content'
    ];
}
