<?php

namespace App\Providers;

use App\Exceptions\ApiException;
use App\Models\PersonalAccessToken;
use App\Models\User;
use App\Services\Role\RoleService;
use App\Services\Role\RoleServiceInterface;
use App\Services\User\UserService;
use App\Services\User\UserServiceInterface;
use Carbon\CarbonInterval;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Contracts\Container\BindingResolutionException;
use Illuminate\Database\Connection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Events\QueryExecuted;
use Illuminate\Foundation\Http\Kernel as HttpKernel;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;
use Laravel\Sanctum\Sanctum;
use RuntimeException;
use Symfony\Component\HttpFoundation\Response;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        // Telescope только для локальной среды
        if ($this->app->environment('local')) {
            $this->app->register(\Laravel\Telescope\TelescopeServiceProvider::class);
            $this->app->register(TelescopeServiceProvider::class);
        }
        $this->app->bind(UserServiceInterface::class, UserService::class);
        $this->app->bind(RoleServiceInterface::class,RoleService::class);

    }

    /**
     * Bootstrap any application services.
     *
     * @throws BindingResolutionException
     */
    public function boot(): void
    {
        Sanctum::usePersonalAccessTokenModel(PersonalAccessToken::class);

        App::setLocale('ru');

        $this->defineGates();
        $this->configureEloquent();
        $this->setupMonitoring();
        $this->configureRateLimiting();
    }

    /**
     * Конфигурация Eloquent ORM.
     */
    protected function configureEloquent(): void
    {
        Model::preventLazyLoading(! app()->isProduction());
        Model::preventSilentlyDiscardingAttributes(! $this->app->isProduction());
        Model::preventAccessingMissingAttributes();

        Model::handleLazyLoadingViolationUsing(function (Model $model, string $relation) {
            $class = get_class($model);

            throw new ApiException("Attempted to lazy load [{$relation}] on model [{$class}].");
        });

    }

    /**
     * Настройка мониторинга запросов (DB и HTTP).
     *
     * @throws BindingResolutionException
     */
    protected function setupMonitoring(): void
    {
        // Мониторинг долгих запросов к БД
        DB::whenQueryingForLongerThan(3000, fn (Connection $connection, QueryExecuted $event) => $this->logLongOperation("Database query exceeded 3s on [{$connection->getName()}]", [
            'sql' => $event->sql,
            'bindings' => $event->bindings,
            'time' => $event->time,
        ]));

        // Мониторинг отдельных долгих SQL-запросов
        DB::listen(function (QueryExecuted $query) {
            if ($query->time > 1000) {
                $this->logLongOperation('Individual database query exceeded 1s', [
                    'sql' => $query->sql,
                    'bindings' => $query->bindings,
                    'time' => $query->time,
                ]);
            }
        });

        // Мониторинг долгих HTTP-запросов
        $this->app->make(HttpKernel::class)
            ->whenRequestLifecycleIsLongerThan(
                CarbonInterval::seconds(4),
                fn () => $this->logLongOperation('HTTP request exceeded 4s', ['url' => request()->fullUrl()])
            );
    }

    /**
     * Логирование долгих операций с единым подходом.
     */
    protected function logLongOperation(string $message, array $context): void
    {
        Log::warning($message, $context);
        Log::channel('telegram')->debug($message, $context);
    }

    /**
     * Конфигурация ограничений скорости запросов.
     */
    protected function configureRateLimiting(): void
    {
        $byUserOrIp = static fn (Request $request) => $request->user()?->id ?? $request->ip();

        RateLimiter::for('api', static fn (Request $request) => Limit::perMinute(100)->by($byUserOrIp($request)));
        RateLimiter::for('global', static fn (Request $request) => Limit::perMinute(5000)
            ->by($byUserOrIp($request))
            ->response(fn (Request $request, array $headers) => response('Take it easy', Response::HTTP_TOO_MANY_REQUESTS, $headers)));
        RateLimiter::for('login', static fn (Request $request) => Limit::perMinute(15)->by($request->ip()));
    }


    protected function defineGates(): void
    {
        Gate::before(static function ($user) {

            // Проверяем, что $user не null и является экземпляром User
            if ($user instanceof User && $user->hasRole('Admin')) {
                return true;
            }

            return null; // Возвращаем null, чтобы продолжить проверки других правил
        });
    }
}
