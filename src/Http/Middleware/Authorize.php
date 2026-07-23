<?php

declare(strict_types=1);

namespace JanakKapadia\InstaTranslate\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use JanakKapadia\InstaTranslate\InstaTranslate;
use Symfony\Component\HttpFoundation\Response;

class Authorize
{
    /**
     * Handle the incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        return InstaTranslate::check($request) ? $next($request) : abort(403);
    }
}
