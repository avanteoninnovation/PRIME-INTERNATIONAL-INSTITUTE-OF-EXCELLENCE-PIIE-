<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\Schema;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        //
    }

    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
        Paginator::useBootstrap();
        $this->applySystemSmtpSettings();
    }

    /**
     * Every Mailable in the app (NewUserEmail, StudentPortalActivationEmail,
     * etc.) only used get_settings('smtp_*') as an on/off gate and for the
     * ->from() address — nothing applied it to the actual mail transport, so
     * sending always used whatever .env happened to contain. This makes the
     * Super Admin → Settings → SMTP Settings values (stored in
     * global_settings) the single source of truth for the live mailer,
     * without adding any new settings screen/table.
     */
    private function applySystemSmtpSettings(): void
    {
        if (! Schema::hasTable('global_settings')) {
            return;
        }

        try {
            $host = get_settings('smtp_host');

            if (empty($host)) {
                return;
            }

            config([
                'mail.mailers.smtp.host'       => $host,
                'mail.mailers.smtp.port'       => get_settings('smtp_port') ?: config('mail.mailers.smtp.port'),
                'mail.mailers.smtp.username'   => get_settings('smtp_user') ?: config('mail.mailers.smtp.username'),
                'mail.mailers.smtp.password'   => get_settings('smtp_pass') ?: config('mail.mailers.smtp.password'),
                'mail.mailers.smtp.encryption' => get_settings('smtp_crypto') ?: config('mail.mailers.smtp.encryption'),
            ]);
        } catch (\Throwable $e) {
            report($e);
        }
    }
}
