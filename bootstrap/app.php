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
        $middleware->alias([
            'auth_simple' => \App\Http\Middleware\AuthSimple::class,
            'role' => \App\Http\Middleware\RoleMiddleware::class,
            'audit' => \App\Http\Middleware\AuditRequest::class,
        ]);

        // Trust Railway's reverse proxy headers so Laravel correctly detects HTTPS
        $middleware->trustProxies('*', Request::HEADER_X_FORWARDED_FOR
            | Request::HEADER_X_FORWARDED_HOST
            | Request::HEADER_X_FORWARDED_PORT
            | Request::HEADER_X_FORWARDED_PROTO
            | Request::HEADER_X_FORWARDED_AWS_ELB);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();
