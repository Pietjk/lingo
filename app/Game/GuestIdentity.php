<?php

namespace App\Game;

use Illuminate\Contracts\Session\Session;
use Illuminate\Support\Str;

class GuestIdentity
{
    private const SESSION_KEY = 'guest_id';

    public static function id(Session $session): string
    {
        if (! $session->has(self::SESSION_KEY)) {
            $session->put(self::SESSION_KEY, (string) Str::uuid());
        }

        return $session->get(self::SESSION_KEY);
    }
}
