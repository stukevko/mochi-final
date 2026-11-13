<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SumUpSale extends Model
{
    protected $table = 'sumup_sales';

    protected $guarded = [];

    protected $casts = [
        'closed_at' => 'datetime',
        'lines' => 'array',
    ];
}
