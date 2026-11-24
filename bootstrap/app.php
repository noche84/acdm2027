<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        // La désinscription « en un clic » est appelée par les messageries, sans jeton CSRF.
        $middleware->preventRequestForgery(except: ['newsletter/desinscription/*/un-clic']);
        // OVH place le site derrière ses propres proxys.
        $middleware->trustProxies(at: '*');
        // Adresses e-mail et téléphones cachés aux robots sur les pages publiques.
        $middleware->web(append: [\App\Http\Middleware\ProtectContacts::class]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*') || $request->expectsJson(),
        );
    })->create();
