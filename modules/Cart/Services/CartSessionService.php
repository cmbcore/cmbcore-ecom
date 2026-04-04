<?php

declare(strict_types=1);

namespace Modules\Cart\Services;

use Illuminate\Support\Facades\Cookie;
use Illuminate\Support\Str;

class CartSessionService
{
    private const SESSION_KEY = 'cart.guest_token';

    public function currentGuestToken(): ?string
    {
        $token = request()->session()->get(self::SESSION_KEY)
            ?? request()->cookie(config('cart.guest_cookie', 'cmbcore_guest_cart'));

        return is_string($token) && $token !== '' ? $token : null;
    }

    public function ensureGuestToken(): string
    {
        $token = $this->currentGuestToken() ?? (string) Str::uuid();
        request()->session()->put(self::SESSION_KEY, $token);
        Cookie::queue(
            config('cart.guest_cookie', 'cmbcore_guest_cart'),
            $token,
            (int) config('cart.guest_cookie_minutes', 43200),
        );

        return $token;
    }
}
