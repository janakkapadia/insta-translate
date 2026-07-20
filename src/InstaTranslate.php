<?php

declare(strict_types=1);

namespace InstaRequest\InstaTranslate;

use Closure;
use Illuminate\Http\Request;

class InstaTranslate
{
    /**
     * The callback that should be used to authenticate InstaTranslate users.
     */
    public static ?Closure $authUsing = null;

    /**
     * Determine if the given request can access the InstaTranslate dashboard.
     */
    public static function check(Request $request): bool
    {
        return (static::$authUsing ?: function () {
            return app()->environment('local');
        })($request);
    }

    /**
     * Set the callback that should be used to authenticate InstaTranslate users.
     */
    public static function auth(Closure $callback): self
    {
        static::$authUsing = $callback;

        return new self;
    }
}
