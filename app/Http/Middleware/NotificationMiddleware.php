<?php

namespace App\Http\Middleware;

use App\Models\User;
use App\Services\FirebaseServices;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class NotificationMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */

    protected $firebaseService;

    public function __construct(FirebaseServices $firebaseServices)
    {
        $this->firebaseService = $firebaseServices;
    }

    public function handle(Request $request, Closure $next): Response
    {

        $response = $next($request);

        $action = $request->route()->getActionMethod();
        $user = Auth::user();

        if ($user) {
                 if ($action === 'checkIn' || $action === 'checkOut' ) {
                    $this->firebaseService->sendNotificationToAll($action);
                } else {
                    $this->firebaseService->sendNotification($user, $action);
                }
            }


        return $response;

    }
}
