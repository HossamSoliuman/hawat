<?php

use App\Http\Middleware\EnsureApiAccountActive;
use App\Http\Middleware\EnsureApiRole;
use App\Http\Middleware\EnsurePanelAccess;
use App\Http\Middleware\ForceJsonResponse;
use App\Http\Middleware\Idempotent;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->alias([
            'panel' => EnsurePanelAccess::class,
            'api.active' => EnsureApiAccountActive::class,
            'api.role' => EnsureApiRole::class,
            'idempotent' => Idempotent::class,
        ]);

        // واجهة التطبيق تردّ JSON دائمًا، طلب العميل ذلك أم لم يطلبه.
        $middleware->api(prepend: [ForceJsonResponse::class]);

        /*
         * صفحتا دخول على مضيفين: إدارة النظام بنصفيها (admin.* و subadmin.*) على
         * /login في مضيف الوزارة، وتطبيق حوات على /admin/login في النطاق الرئيسي.
         * الزائر يُرسَل إلى صفحة الموضع الذي طرقه، والداخل الذي يطرق صفحة دخول
         * يُعاد إلى رئيسة موضعها.
         */
        $middleware->redirectGuestsTo(fn (Request $request) => Str::startsWith((string) $request->route()?->getName(), ['admin.', 'subadmin.'])
            ? route('login')
            : route('panel.login'));

        $middleware->redirectUsersTo(fn (Request $request) => str_starts_with((string) $request->route()?->getName(), 'panel.')
            ? route('panel.home')
            : route('admin.index'));
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();
