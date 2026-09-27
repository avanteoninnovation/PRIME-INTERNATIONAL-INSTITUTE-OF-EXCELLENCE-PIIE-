<?php

namespace App\Support;

use App\Models\School;
use App\Models\User;
use Illuminate\Support\Facades\Auth;

/** Central, compatibility-safe read model for a school's tenant configuration. */
class TenantConfiguration
{
    public const SCHOOL_UPDATE_FIELDS = [
        'title', 'address', 'phone', 'school_info', 'school_type', 'education_level',
        'primary_locale', 'country_code', 'timezone', 'academic_calendar_pattern',
        'school_currency', 'currency_position',
    ];

    public function activeTenant(): ?School
    {
        $user = Auth::user();
        $schoolId = $user ? $user->school_id : PublicTenantResolver::resolveSchoolId();

        return $schoolId ? School::query()->find($schoolId) : null;
    }

    public function resolve(?School $tenant = null, ?User $user = null, ?string $platformLocale = null): array
    {
        $tenant ??= $this->activeTenant();
        $type = $tenant ? ($tenant->school_type ?: 'k12') : 'k12';
        $educationLevel = $tenant ? $tenant->education_level : null;
        $defaults = config('tenant.terminology.'.$type, config('tenant.terminology.k12'));
        $overrides = $tenant && is_array($tenant->terminology_overrides)
            ? $tenant->terminology_overrides
            : [];
        $overrides = array_intersect_key($overrides, $defaults);
        $overrides = array_filter($overrides, fn ($value) => is_string($value) && $value !== '' && mb_strlen($value) <= 80);

        return [
            'tenant' => $tenant,
            'institution_type' => $type,
            'education_level' => $educationLevel,
            'primary_locale' => $tenant ? ($tenant->primary_locale ?: null) : null,
            'country_code' => $tenant ? ($tenant->country_code ?: null) : null,
            'timezone' => $tenant && $tenant->timezone ? $tenant->timezone : config('app.timezone', 'UTC'),
            'academic_calendar_pattern' => ($tenant ? $tenant->academic_calendar_pattern : null)
                ?: ($type === 'higher_ed' ? 'semester' : 'term'),
            'terminology_profile' => array_merge($defaults, $overrides),
            'currency' => [
                'code_or_symbol' => $tenant ? ($tenant->school_currency ?: null) : null,
                'position' => $tenant ? ($tenant->currency_position ?: null) : null,
            ],
            'locale' => $this->resolveLocale($user, $tenant, $platformLocale),
        ];
    }

    public function resolveLocale(?User $user = null, ?School $tenant = null, ?string $platformLocale = null): string
    {
        $user ??= Auth::user();
        $supported = config('tenant.locales', ['en']);
        $userLocale = $user ? $user->language : null;

        if ($userLocale && in_array($userLocale, $supported, true)) {
            return $userLocale;
        }

        $tenant ??= $this->activeTenant();
        if ($tenant && $tenant->primary_locale && in_array($tenant->primary_locale, $supported, true)) {
            return $tenant->primary_locale;
        }

        $platformLocale ??= function_exists('get_settings') ? get_settings('language') : null;
        $platformLocale = $this->normalizeLocale($platformLocale) ?: config('app.locale', 'en');

        return in_array($platformLocale, $supported, true) ? $platformLocale : 'en';
    }

    private function normalizeLocale(?string $locale): ?string
    {
        $legacyNames = ['english' => 'en', 'french' => 'fr', 'swahili' => 'sw'];
        $locale = $locale ? strtolower(trim($locale)) : null;

        return $legacyNames[$locale] ?? $locale;
    }

    public function terminology(?School $tenant = null): array
    {
        return $this->resolve($tenant)['terminology_profile'];
    }

    public static function configurationRules(): array
    {
        return [
            'school_type' => ['sometimes', 'required', 'in:k12,higher_ed,mixed'],
            'education_level' => ['nullable', 'in:primary,secondary,tertiary,vocational,mixed'],
            'primary_locale' => ['nullable', 'string', 'max:12', 'in:'.implode(',', config('tenant.locales', ['en']))],
            'country_code' => ['nullable', 'string', 'size:2', 'in:'.implode(',', config('tenant.country_codes', []))],
            'timezone' => ['nullable', 'timezone'],
            'academic_calendar_pattern' => ['nullable', 'in:'.implode(',', config('tenant.calendar_patterns', ['semester', 'term']))],
        ];
    }

    public static function schoolUpdateAttributes(array $validated): array
    {
        return array_intersect_key($validated, array_flip(self::SCHOOL_UPDATE_FIELDS));
    }
}
