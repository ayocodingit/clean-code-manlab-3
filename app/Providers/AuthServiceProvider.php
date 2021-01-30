<?php

namespace App\Providers;

use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;
use Illuminate\Support\Facades\Gate;
class AuthServiceProvider extends ServiceProvider
{
    /**
     * The policy mappings for the application.
     *
     * @var array
     */
    protected $policies = [
        // 'App\Model' => 'App\Policies\ModelPolicy',
    ];

    /**
     * Register any authentication / authorization services.
     *
     * @return void
     */
    public function boot()
    {
        $this->registerPolicies();
        
        Gate::define('isAdmin', function($user) {
           return $user->role_id == ROLE_ADMIN;
        });

        Gate::define('isAdminRegister', function($user) {
           return $user->role_id == ROLE_REGISTER || $user->role_id == ROLE_ADMIN;
        });

        Gate::define('isAdminSampel', function($user) {
           return $user->role_id == ROLE_SAMPEL || $user->role_id == ROLE_ADMIN;
        });

        Gate::define('isAdminEkstraksi', function($user) {
           return $user->role_id == ROLE_EKSTRAKSI || $user->role_id == ROLE_ADMIN;
        });
        
        Gate::define('isAdminPCR', function($user) {
           return $user->role_id == ROLE_PCR || $user->role_id == ROLE_ADMIN;
        });

        Gate::define('isAdminVerifikator', function($user) {
           return $user->role_id == ROLE_VERIFIKATOR || $user->role_id == ROLE_ADMIN;
        });

        Gate::define('isAdminValidator', function($user) {
           return $user->role_id == ROLE_VALIDATOR || $user->role_id == ROLE_ADMIN;
        });
    }
}
