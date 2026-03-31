<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ShopLink extends Model
{
    protected $fillable = [
        'label',
        'url',
        'sort_order',
    ];
}
