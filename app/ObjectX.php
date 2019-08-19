<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class ObjectX extends Model
{
    protected $table = 'object';

    protected $fillable = [
        'name', 'meta_id', 'user_id', 'time'
    ];
}
