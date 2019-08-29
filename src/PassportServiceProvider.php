<?php

namespace SalKhimani\LumenPassport;

use SalKhimani\LumenPassport\Console\Commands\Purge;
use Illuminate\Support\ServiceProvider;
use Illuminate\Database\Connection;

/**
 * Class CustomQueueServiceProvider
 *
 * @package App\Providers
 */
class PassportServiceProvider extends ServiceProvider
{

    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
        //$this->app->configure('api');
        // pull dynamic configurations
        if ((string)config('api.oauth_prefix') == '')
            config(['api' => ['oauth_prefix' => env('API_OAUTH_PREFIX', 'api/oauth')]]);

        $this->app->register(\SalKhimani\LaravelPassportMongoDB\PassportServiceProvider::class);

        $this->app->singleton(Connection::class, function () {
            return $this->app['db.connection'];
        });

        if ($this->app->runningInConsole()) {
            $this->commands([
                Purge::class
            ]);
        }

        $this->registerRoutes();
    }

    /**
     * @return void
     */
    public function register()
    {
    }

    /**
     * Register routes for transient tokens, clients, and personal access tokens.
     *
     * @return void
     */
    public function registerRoutes()
    {
        $this->forAccessTokens();
        $this->forTransientTokens();
        $this->forClients();
        $this->forPersonalAccessTokens();
    }

    /**
     * Register the routes for retrieving and issuing access tokens.
     *
     * @return void
     */
    public function forAccessTokens()
    {
        // without middleware
        $this->app->router->group(['prefix' => config('api.oauth_prefix')], function () {
            $this->app->router->post('/token', [
                'uses' => '\SalKhimani\LumenPassport\Http\Controllers\AccessTokenController@issueToken'
            ]);
        });

        // with middleware
        $this->app->router->group(['prefix' => config('api.oauth_prefix'), 'middleware' => ['auth']], function () {
            $this->app->router->get('/tokens', [
                'uses' => '\SalKhimani\LaravelPassportMongoDB\Http\Controllers\AuthorizedAccessTokenController@forUser',
            ]);

            $this->app->router->delete('/tokens/{token_id}', [
                'uses' => '\SalKhimani\LaravelPassportMongoDB\Http\Controllers\AuthorizedAccessTokenController@destroy',
            ]);
        });
    }

    /**
     * Register the routes needed for refreshing transient tokens.
     *
     * @return void
     */
    public function forTransientTokens()
    {
        $this->app->router->group(['prefix' => config('api.oauth_prefix')], function () {
            $this->app->router->post('/token/refresh', [
                'middleware' => ['auth'],
                'uses' => '\SalKhimani\LaravelPassportMongoDB\Http\Controllers\TransientTokenController@refresh',
            ]);
        });
    }

    /**
     * Register the routes needed for managing clients.
     *
     * @return void
     */
    public function forClients()
    {
        $this->app->router->group(['prefix' => config('api.oauth_prefix'), 'middleware' => ['auth']], function () {
            $this->app->router->get('/clients', [
                'uses' => '\SalKhimani\LaravelPassportMongoDB\Http\Controllers\ClientController@forUser',
            ]);

            $this->app->router->post('/clients', [
                'uses' => '\SalKhimani\LaravelPassportMongoDB\Http\Controllers\ClientController@store',
            ]);

            $this->app->router->put('/clients/{client_id}', [
                'uses' => '\SalKhimani\LaravelPassportMongoDB\Http\Controllers\ClientController@update',
            ]);

            $this->app->router->delete('/clients/{client_id}', [
                'uses' => '\SalKhimani\LaravelPassportMongoDB\Http\Controllers\ClientController@destroy',
            ]);
        });
    }

    /**
     * Register the routes needed for managing personal access tokens.
     *
     * @return void
     */
    public function forPersonalAccessTokens()
    {
        $this->app->router->group(['prefix' => config('api.oauth_prefix'), 'middleware' => ['auth']], function () {
            $this->app->router->get('/scopes', [
                'uses' => '\SalKhimani\LaravelPassportMongoDB\Http\Controllers\ScopeController@all',
            ]);

            $this->app->router->get('/personal-access-tokens', [
                'uses' => '\SalKhimani\LaravelPassportMongoDB\Http\Controllers\PersonalAccessTokenController@forUser',
            ]);

            $this->app->router->post('/personal-access-tokens', [
                'uses' => '\SalKhimani\LaravelPassportMongoDB\Http\Controllers\PersonalAccessTokenController@store',
            ]);

            $this->app->router->delete('/personal-access-tokens/{token_id}', [
                'uses' => '\SalKhimani\LaravelPassportMongoDB\Http\Controllers\PersonalAccessTokenController@destroy',
            ]);
        });
    }
}
