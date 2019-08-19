<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Chat extends Model
{
    protected $table = 'chats';

    protected $fillable = [
        'from_user_id', 'to_user_id', 'unread', 'last', 'last_message_time'
    ];

    protected $hidden = ['from_user_id', 'id'];

    public function to_user() {
        return $this->hasOne(User::class,'id','to_user_id');
    }
}
