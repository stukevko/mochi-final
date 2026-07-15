<?php

namespace App\Http\Middleware;

use Illuminate\Routing\Middleware\ValidateSignature as Middleware;

class ValidateSignature extends Middleware
{
    /**
     * Provider callbacks append these after we sign the return URL.
     *
     * @var array<int, string>
     */
    protected $except = [
        'session_id',
        'token',
        'PayerID',
        'checkout_id',
    ];
}
