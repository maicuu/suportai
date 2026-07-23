<?php

namespace App\Providers;

use App\Ai\AiProvider;
use App\Ai\Providers\FakeAiProvider;
use App\Ai\Providers\GroqAiProvider;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\ServiceProvider;
use Illuminate\Validation\Rules\Password;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        // Amarra o PORT a um ADAPTER conforme a config (≈ @Bean/@Conditional
        // do Spring). O domínio pede AiProvider; o container entrega o certo.
        $this->app->bind(AiProvider::class, function ($app): AiProvider {
            $config = $app['config']->get('services.ai');

            return match ($config['provider']) {
                'groq' => new GroqAiProvider(
                    apiKey: (string) $config['groq']['key'],
                    model: (string) $config['groq']['model'],
                    baseUrl: (string) $config['groq']['base_url'],
                ),
                default => new FakeAiProvider(),
            };
        });
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        $this->configureDefaults();
    }

    /**
     * Configure default behaviors for production-ready applications.
     */
    protected function configureDefaults(): void
    {
        Date::use(CarbonImmutable::class);

        DB::prohibitDestructiveCommands(
            app()->isProduction(),
        );

        Password::defaults(fn (): ?Password => app()->isProduction()
            ? Password::min(12)
                ->mixedCase()
                ->letters()
                ->numbers()
                ->symbols()
                ->uncompromised()
            : null,
        );
    }
}
