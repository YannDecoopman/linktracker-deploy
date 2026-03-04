<?php

namespace App\Providers;

// use Illuminate\Support\Facades\Gate;
use App\Models\IndexationCampaign;
use App\Policies\IndexationCampaignPolicy;
use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;

class AuthServiceProvider extends ServiceProvider
{
    /**
     * The model to policy mappings for the application.
     *
     * @var array<class-string, class-string>
     */
    protected $policies = [
        IndexationCampaign::class => IndexationCampaignPolicy::class,
    ];

    /**
     * Register any authentication / authorization services.
     */
    public function boot(): void
    {
        //
    }
}
