<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use App\Enums\UserRole;

class EnsureUserHasRole
{
    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        $user = $request->user(); 

        if(!$user) {
            return response()->json(['message' => 'Unauthenticated.'], 401);
        }
        
        foreach ($roles as $role) {
            $userRole = UserRole::tryFrom($role);
            if($userRole !== null && $user->role === $userRole){
                return $next($request);
            }
        }

        return response()->json([ 'message' => 'This action is unauthorized.'], 403);
    }

}
