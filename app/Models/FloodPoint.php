<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FloodPoint extends Model
{
    protected $guarded = [ 'id' ];

    protected $casts = [
        'data_ocorrencia' => 'datetime',
    ];
}
