<?php

namespace Tests\Feature;

use App\Providers\AppServiceProvider;
use Illuminate\Support\Facades\DB;
use Tests\Feature\Support\AdmissionsTestHelper;
use Tests\TestCase;

/**
 * Covers the fix that makes the Super Admin → Settings → SMTP Settings
 * values (global_settings) the single source of truth for every Mailable's
 * actual transport, instead of only gating whether to attempt a send.
 */
class SystemSmtpSettingsTest extends TestCase
{
    use AdmissionsTestHelper;

    protected function setUp(): void
    {
        parent::setUp();
        $this->bootAdmissionsTestSchema();
    }

    public function test_saved_smtp_settings_are_applied_to_the_actual_mailer_config(): void
    {
        DB::table('global_settings')->insert([
            ['key' => 'smtp_host', 'value' => 'smtp.mailtrap.io', 'created_at' => now(), 'updated_at' => now()],
            ['key' => 'smtp_port', 'value' => '2525', 'created_at' => now(), 'updated_at' => now()],
            ['key' => 'smtp_user', 'value' => 'realuser', 'created_at' => now(), 'updated_at' => now()],
            ['key' => 'smtp_pass', 'value' => 'realpass', 'created_at' => now(), 'updated_at' => now()],
            ['key' => 'smtp_crypto', 'value' => 'tls', 'created_at' => now(), 'updated_at' => now()],
        ]);

        (new AppServiceProvider($this->app))->boot();

        $this->assertSame('smtp.mailtrap.io', config('mail.mailers.smtp.host'));
        $this->assertSame('2525', config('mail.mailers.smtp.port'));
        $this->assertSame('realuser', config('mail.mailers.smtp.username'));
        $this->assertSame('realpass', config('mail.mailers.smtp.password'));
        $this->assertSame('tls', config('mail.mailers.smtp.encryption'));
    }

    public function test_missing_smtp_settings_leave_the_default_mailer_config_untouched(): void
    {
        $originalHost = config('mail.mailers.smtp.host');

        // No global_settings rows inserted — nothing to apply.
        (new AppServiceProvider($this->app))->boot();

        $this->assertSame($originalHost, config('mail.mailers.smtp.host'));
    }
}
