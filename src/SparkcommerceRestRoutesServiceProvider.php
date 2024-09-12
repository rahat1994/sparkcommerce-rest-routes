<?php

namespace Rahat1994\SparkcommerceRestRoutes;

use App\Models\User;
use Illuminate\Auth\Notifications\ResetPassword;
use Spatie\LaravelPackageTools\Package;
use Spatie\LaravelPackageTools\PackageServiceProvider;
use Rahat1994\SparkcommerceRestRoutes\Commands\SparkcommerceRestRoutesCommand;

class SparkcommerceRestRoutesServiceProvider extends PackageServiceProvider
{
    public function configurePackage(Package $package): void
    {
        /*
         * This class is a Package Service Provider
         *
         * More info: https://github.com/spatie/laravel-package-tools
         */

        $package
            ->name('sparkcommerce-rest-routes')
            ->hasConfigFile()
            ->hasViews()
            ->hasRoutes($this->getRoutes())
            ->hasMigration('create_sparkcommerce-rest-routes_table');

        $this->passwordResetLinkChangeForSpa();
    }

    protected function passwordResetLinkChangeForSpa()
    {
        ResetPassword::createUrlUsing(function (User $user, $token) {
            return config('sparkcommerce-rest-routes.frontend_url') . '/reset-password?token=' . $token . '&email=' . $user->getEmailForPasswordReset();
        });
    }

    protected function getRoutes(): array {
        // check if the class SparkcommerceMultivendorRestRoutesServiceProvider exists

        if (class_exists('Rahat1994\SparkcommerceMultivendorRestRoutes\SparkcommerceMultivendorRestRoutesServiceProvider')) {
            return ['api'];
        }
        return ['api', 'commerce_apis'];
    }
}
