<?php

declare(strict_types=1);

namespace App\Modules\Tracking\Services;

use Illuminate\Validation\ValidationException;

class TrackingPixelValidator
{
    /**
     * @param  array<string, mixed>  $data
     * @return array<string, ?string>
     */
    public function validate(array $data): array
    {
        $errors = [];

        $fbPattern = (string) config('analytics.patterns.facebook_pixel_id', '/^\d{9,20}$/');
        $gaPattern = (string) config('analytics.patterns.google_analytics_id', '/^G-[A-Z0-9]{6,20}$/');
        $gtmPattern = (string) config('analytics.patterns.google_tag_manager_id', '/^GTM-[A-Z0-9]{4,20}$/');
        $tiktokPattern = (string) config('analytics.patterns.tiktok_pixel_id', '/^[A-Za-z0-9]{6,30}$/');

        $fbId = isset($data['facebook_pixel_id']) ? trim((string) $data['facebook_pixel_id']) : null;
        if ($fbId !== null && $fbId !== '' && ! preg_match($fbPattern, $fbId)) {
            $errors['facebook_pixel_id'][] = 'The Facebook Pixel ID must be a 9 to 20 digit numeric identifier.';
        }

        $gaId = isset($data['google_analytics_id']) ? trim((string) $data['google_analytics_id']) : null;
        if ($gaId !== null && $gaId !== '' && ! preg_match($gaPattern, $gaId)) {
            $errors['google_analytics_id'][] = 'The Google Analytics 4 ID must match the G-XXXXXXXX format.';
        }

        $gtmId = isset($data['google_tag_manager_id']) ? trim((string) $data['google_tag_manager_id']) : null;
        if ($gtmId !== null && $gtmId !== '') {
            if (config('evento.is_saas_mode', false) && ! config('analytics.allow_gtm_in_saas', false)) {
                $errors['google_tag_manager_id'][] = 'Google Tag Manager container IDs are disabled in multi-tenant SaaS mode to prevent arbitrary script injection.';
            } elseif (! preg_match($gtmPattern, $gtmId)) {
                $errors['google_tag_manager_id'][] = 'The Google Tag Manager ID must match the GTM-XXXXXX format.';
            }
        }

        $tiktokId = isset($data['tiktok_pixel_id']) ? trim((string) $data['tiktok_pixel_id']) : null;
        if ($tiktokId !== null && $tiktokId !== '' && ! preg_match($tiktokPattern, $tiktokId)) {
            $errors['tiktok_pixel_id'][] = 'The TikTok Pixel ID must be an alphanumeric string between 6 and 30 characters.';
        }

        if (! empty($errors)) {
            throw ValidationException::withMessages($errors);
        }

        return [
            'facebook_pixel_id' => $fbId !== '' ? $fbId : null,
            'google_analytics_id' => $gaId !== '' ? $gaId : null,
            'google_tag_manager_id' => $gtmId !== '' ? $gtmId : null,
            'tiktok_pixel_id' => $tiktokId !== '' ? $tiktokId : null,
        ];
    }
}
