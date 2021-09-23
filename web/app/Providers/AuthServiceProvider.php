<?php

namespace App\Providers;

use App\Models\Client;
use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;
use Laravel\Passport\Passport;

class AuthServiceProvider extends ServiceProvider
{
    /**
     * The policy mappings for the application.
     *
     * @var array
     */
    protected $policies = [
        'App\Models\Model' => 'App\Policies\ModelPolicy',
    ];

    /**
     * Register any authentication / authorization services.
     *
     * @return void
     */
    public function boot()
    {
        $this->registerPolicies();

        // Add passport routes
        // Set custom slug in routes and enable needed endpoints
        Passport::routes(
            function ($router) {
                $router->forAuthorization();
                $router->forAccessTokens();
//                $router->forTransientTokens();
//                $router->forClients();
//                $router->forPersonalAccessTokens();
            },
            [
                'prefix' => 'oauth2',
            ]
        );

        // Set secret keys path
        Passport::loadKeysFrom(base_path('keys'));

        // Set Client Secret Hashing
        Passport::hashClientSecrets();

        // Set Token Lifetimes
        Passport::tokensExpireIn(now()->addDays(15));
        Passport::refreshTokensExpireIn(now()->addDays(30));
        Passport::personalAccessTokensExpireIn(now()->addMonths(6));

        // Overriding Default Models
        //Passport::useTokenModel(Token::class);
        Passport::useClientModel(Client::class);
        //Passport::useAuthCodeModel(AuthCode::class);
        //Passport::usePersonalAccessClientModel(PersonalAccessClient::class);
    }
}
