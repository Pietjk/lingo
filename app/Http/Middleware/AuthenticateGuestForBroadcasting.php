<?php

namespace App\Http\Middleware;

use App\Game\GuestIdentity;
use App\Models\RoomPlayer;
use Closure;
use Illuminate\Auth\GenericUser;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Lets session-based guests authorize against broadcast channels.
 */
class AuthenticateGuestForBroadcasting
{
    public function handle(Request $request, Closure $next): Response
    {
        if ($request->user() === null && $request->hasSession()) {
            $guestId = GuestIdentity::id($request->session());
            $nickname = RoomPlayer::query()->where('guest_id', $guestId)->latest('id')->value('nickname');

            if ($nickname !== null) {
                $request->setUserResolver(fn () => new GenericUser([
                    'id' => $guestId,
                    'name' => $nickname,
                ]));
            }
        }

        return $next($request);
    }
}
