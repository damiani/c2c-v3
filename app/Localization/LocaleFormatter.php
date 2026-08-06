<?php

namespace App\Localization;

use DateTimeInterface;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Number;

class LocaleFormatter
{
    /**
     * Get formatting defaults for a supported locale.
     *
     * @return array{date: string, date_time: string, number_locale: string, currency: string, area_unit: string}
     */
    public function defaults(?string $locale = null): array
    {
        $locale = $this->resolveLocale($locale);

        return config("localization.formats.{$locale}", config('localization.formats.en'));
    }

    public function formatDate(DateTimeInterface|string $date, ?string $locale = null): string
    {
        $defaults = $this->defaults($locale);

        return $this->carbon($date)
            ->locale($this->resolveLocale($locale))
            ->translatedFormat($defaults['date']);
    }

    public function formatDateTime(DateTimeInterface|string $date, ?string $locale = null): string
    {
        $defaults = $this->defaults($locale);

        return $this->carbon($date)
            ->locale($this->resolveLocale($locale))
            ->translatedFormat($defaults['date_time']);
    }

    public function formatCurrency(int|float $amount, ?string $locale = null, ?string $currency = null): string
    {
        $defaults = $this->defaults($locale);

        return (string) Number::currency(
            $amount,
            in: $currency ?? $defaults['currency'],
            locale: $defaults['number_locale'],
        );
    }

    public function areaUnit(?string $locale = null): string
    {
        return $this->defaults($locale)['area_unit'];
    }

    public function areaUnitLabel(?string $locale = null, ?string $unit = null): string
    {
        $unit ??= $this->areaUnit($locale);

        return __(config("localization.area_units.{$unit}.label", $unit), [], $this->resolveLocale($locale));
    }

    public function formatArea(int|float $area, ?string $locale = null, ?string $unit = null, int $precision = 2): string
    {
        $defaults = $this->defaults($locale);
        $unit ??= $defaults['area_unit'];
        $formattedArea = Number::format($area, precision: $precision, locale: $defaults['number_locale']);

        return trim($formattedArea.' '.$this->areaUnitLabel($locale, $unit));
    }

    public function resolveLocale(?string $locale = null): string
    {
        $locale ??= App::currentLocale();
        $supportedLocales = array_keys(config('localization.supported_locales', []));

        if (in_array($locale, $supportedLocales, true)) {
            return $locale;
        }

        return in_array(config('app.fallback_locale'), $supportedLocales, true)
            ? config('app.fallback_locale')
            : 'en';
    }

    private function carbon(DateTimeInterface|string $date): Carbon
    {
        if ($date instanceof DateTimeInterface) {
            return Carbon::instance($date);
        }

        return Carbon::parse($date);
    }
}
