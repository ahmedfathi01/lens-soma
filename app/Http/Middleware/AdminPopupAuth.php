<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class AdminPopupAuth
{
    protected $username;
    protected $password;

    public function __construct()
    {
        // Get credentials from environment variables or config
        $this->username = config('admin.popup_auth.username', env('ADMIN_AUTH_USERNAME', 'admin'));
        $this->password = config('admin.popup_auth.password', env('ADMIN_AUTH_PASSWORD', 'password'));
    }

    public function handle(Request $request, Closure $next)
    {
        $authHeader = $request->header('Authorization');

        if (!$authHeader || !$this->validateAuth($authHeader)) {
            return $this->unauthorized();
        }

        return $next($request);
    }

    protected function validateAuth($authHeader)
    {
        if (strpos($authHeader, 'Basic ') !== 0) {
            return false;
        }

        $credentials = base64_decode(substr($authHeader, 6));
        list($username, $password) = explode(':', $credentials, 2);

        return $username === $this->username && $password === $this->password;
    }

    protected function unauthorized()
    {
        return new Response('Unauthorized', 401, [
            'WWW-Authenticate' => 'Basic realm="Admin Access Required"',
            'Content-Type' => 'text/plain'
        ]);
    }
}
