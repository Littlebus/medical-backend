<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Device extends Model
{
    protected $table = 'device_info';

    protected $fillable = [
        'user_id', 'token', 'device_info'
    ];

    protected $hidden = ['user_id', 'id'];
}
