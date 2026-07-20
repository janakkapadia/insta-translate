<?php

namespace InstaRequest\InstaTranslate;

use Closure;

class InstaTranslate
{
    /**
     * The callback that should be used to authenticate InstaTranslate users.
     *
     * @var \Closure|null
     */
    public static $authUsing;

    /**
     * Determine if the given request can access the InstaTranslate dashboard.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return bool
     */
    public static function check($request)
    {
        return (static::$authUsing ?: function () {
            return app()->environment('local');
        })($request);
    }

    /**
     * Set the callback that should be used to authenticate InstaTranslate users.
     *
     * @param  \Closure  $callback
     * @return static
     */
    public static function auth(Closure $callback)
    {
        static::$authUsing = $callback;

        return new static;
    }
}
