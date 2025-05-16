<?php

namespace App\Providers;

use App\Rules\ValidUrl;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Validator;

class ValidationServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     *
     * @return void
     */
    public function register()
    {
        //
    }

    /**
     * Bootstrap services.
     *
     * @return void
     */
    public function boot()
    {
        Validator::extend('valid_url', function ($attribute, $value, $parameters, $validator) {
            return (new ValidUrl)->passes($attribute, $value);
        });

        Validator::replacer('valid_url', function ($message, $attribute, $rule, $parameters) {
            return (new ValidUrl)->message();
        });
    }
} 