<?php

namespace Modules\Auth\Models;

use Laravel\Sanctum\PersonalAccessToken as SanctumPersonalAccessToken;

/**
 * Keeps Sanctum tokens on the same connection as {@see User} (pgsql).
 * If the app default connection differs, the stock model would query the wrong DB and every bearer request would 401.
 */
class PersonalAccessToken extends SanctumPersonalAccessToken
{
    protected $connection = 'pgsql';
}
