<?php

namespace App\Providers;

use App\Models\Partner;
use App\Models\ClinicalDepartment;
use App\Models\WebsiteSetting;
use App\Support\SiteContent;
use App\ContextProviders\Livewire3SafeContextProviderDetector;
use App\Renderers\Livewire3SafeErrorPageRenderer;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;
use Spatie\FlareClient\Flare;
use Spatie\LaravelIgnition\Renderers\ErrorPageRenderer;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        ResetPassword::createUrlUsing(function ($user, string $token) {
            $isAdmin = in_array($user->role, ['super_admin', 'website_admin'], true);
            $route = $isAdmin ? 'admin.password.reset' : 'password.reset';

            return url(route($route, [
                'token' => $token,
                'email' => $user->email,
            ], false));
        });

        $shareFrontendGlobals = function ($view = null) {
            static $cached = null;

            if ($cached === null) {
                $settings = WebsiteSetting::first() ?? new WebsiteSetting;
                $cached = [
                    'websiteSettings' => $settings,
                    'academicPrograms' => ClinicalDepartment::where('is_active', true)->orderBy('sort_order')->get(),
                    'siteContent' => SiteContent::for($settings),
                    'footerPartners' => Partner::where('is_active', true)
                        ->orderBy('sort_order')
                        ->orderBy('name')
                        ->get(),
                ];
            }

            if ($view) {
                $view->with($cached);

                return;
            }

            foreach ($cached as $key => $value) {
                View::share($key, $value);
            }
        };

        // Share globally so Livewire full-page layouts always receive these vars.
        try {
            $shareFrontendGlobals();
        } catch (\Throwable $e) {
            View::share('websiteSettings', new WebsiteSetting);
            View::share('academicPrograms', collect());
            View::share('siteContent', SiteContent::defaults());
            View::share('footerPartners', collect());
        }

        View::composer('layouts.frontend', function ($view) use ($shareFrontendGlobals) {
            try {
                $shareFrontendGlobals($view);
            } catch (\Throwable $e) {
                $view->with([
                    'websiteSettings' => new WebsiteSetting,
                    'academicPrograms' => collect(),
                    'siteContent' => SiteContent::defaults(),
                    'footerPartners' => collect(),
                ]);
            }
        });

        // Use a Livewire-3-safe context detector so error pages don't try to resolve
        // Livewire 2's ComponentRegistry (which doesn't exist in Livewire 3).
        $this->app->afterResolving(Flare::class, function (Flare $flare) {
            $flare->setContextProviderDetector(new Livewire3SafeContextProviderDetector());
        });

        $this->app->bind(ErrorPageRenderer::class, Livewire3SafeErrorPageRenderer::class);
    }
}
