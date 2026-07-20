<?php

namespace InstaRequest\InstaTranslate\Http\Middleware;

use InstaRequest\InstaTranslate\InstaTranslate;

class Authorize
{
    /**
     * Handle the incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return \Illuminate\Http\Response|null
     */
    public function handle($request, $next)
    {
        return InstaTranslate::check($request) ? $next($request) : abort(403);
    }
}
