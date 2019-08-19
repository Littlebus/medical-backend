<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class EcgStatistic extends Model
{
    protected $table = 'ecg_statistic';

    protected $fillable = [
        'date', 'count', 'duration','user_id'
    ];

    protected $hidden = ['created_at', 'updated_at', 'id'];
}
