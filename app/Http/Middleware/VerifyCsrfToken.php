<?php

namespace App\Http\Middleware;

use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken as Middleware;

class VerifyCsrfToken extends Middleware
{
    /**
     * The URIs that should be excluded from CSRF verification.
     *
     * @var array<int, string>
     */
    public static function serialized()
    {
        return true;
    }
    protected $except = [
        'api/integration/create_update_apartment',
        'api/integration/create_update_apartment_complex',
        'api/integration/create_price_list',
        'api/integration/create_apartment_states',
        'api/integration/create_user_debts',
    ];
}
