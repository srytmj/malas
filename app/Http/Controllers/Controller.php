<?php

namespace App\Http\Controllers;

use Illuminate\Foundation\Auth\Access\AuthorizesRequests;

abstract class Controller
{
    use AuthorizesRequests;

    /**
     * Resolve the paginator page size from the `per_page` query param,
     * restricted to a fixed whitelist to prevent abuse via arbitrary values.
     */
    protected function perPage(int $default = 20): int
    {
        $allowed = [5, 10, 25, 50, 100];
        $value = (int) request('per_page', $default);

        return in_array($value, $allowed, true) ? $value : $default;
    }
}
