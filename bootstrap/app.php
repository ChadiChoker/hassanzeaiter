<?php

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        //
    })
    ->withSchedule(function (Schedule $schedule): void {
        $schedule->command('olx:clear-cache --force')
            ->daily()
            ->at('00:00')
            ->timezone('UTC')
            ->onSuccess(function () {
                \Illuminate\Support\Facades\Log::info('Scheduled OLX cache clearing completed successfully');
            })
            ->onFailure(function () {
                \Illuminate\Support\Facades\Log::error('Scheduled OLX cache clearing failed');
            });
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();
