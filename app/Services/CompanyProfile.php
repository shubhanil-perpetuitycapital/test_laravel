<?php

namespace App\Services;

/**
 * Read model of the business's public identity, shared with every view as
 * `$company`. Keeps formatting logic (address lines, social list, logos)
 * out of Blade templates.
 */
class CompanyProfile
{
    public function __construct(private SettingService $settings) {}

    public function name(): string
    {
        return (string) $this->settings->get('company_name');
    }

    public function legalName(): string
    {
        return (string) ($this->settings->get('legal_name') ?: $this->name());
    }

    public function tagline(): ?string
    {
        return $this->settings->get('tagline');
    }

    public function about(): ?string
    {
        return $this->settings->get('about_short');
    }

    public function establishedYear(): ?string
    {
        return $this->settings->get('established_year');
    }

    public function logoUrl(): ?string
    {
        return media_url($this->settings->raw('logo'), null);
    }

    public function logoLightUrl(): ?string
    {
        return media_url($this->settings->raw('logo_light'), null) ?? $this->logoUrl();
    }

    public function faviconUrl(): string
    {
        return media_url($this->settings->raw('favicon'), 'favicon.ico');
    }

    public function ogImageUrl(): string
    {
        return media_url($this->settings->raw('og_image'), 'images/placeholders/og-default.svg');
    }

    public function phone(): ?string
    {
        return $this->settings->get('phone');
    }

    public function phoneAlt(): ?string
    {
        return $this->settings->get('phone_alt');
    }

    public function phoneHref(?string $phone = null): ?string
    {
        $phone ??= $this->phone();

        return $phone ? 'tel:'.preg_replace('/[^0-9+]/', '', $phone) : null;
    }

    public function email(): ?string
    {
        return $this->settings->get('email');
    }

    public function businessHours(): array
    {
        $hours = (string) $this->settings->get('business_hours', '');

        return array_values(array_filter(array_map('trim', preg_split('/\r\n|\r|\n/', $hours) ?: [])));
    }

    public function showFullAddress(): bool
    {
        return $this->settings->bool('show_address_publicly');
    }

    /** Full postal address as lines (invoices, contact page). */
    public function addressLines(): array
    {
        $cityLine = trim(implode(' ', array_filter([
            $this->settings->get('city'),
            $this->settings->get('pincode') ? '– '.$this->settings->get('pincode') : null,
        ])));

        return array_values(array_filter([
            $this->settings->get('address_line1'),
            $this->settings->get('address_line2'),
            $this->settings->get('landmark') ? 'Near '.$this->settings->get('landmark') : null,
            $cityLine,
            trim(implode(', ', array_filter([$this->settings->get('state'), $this->settings->get('country')]))),
        ]));
    }

    /** What the public site shows, honouring the "hide address" switch. */
    public function publicAddressLines(): array
    {
        if ($this->showFullAddress()) {
            return $this->addressLines();
        }

        return [trim(implode(', ', array_filter([$this->settings->get('city'), $this->settings->get('state')])))];
    }

    public function city(): string
    {
        return (string) $this->settings->get('city');
    }

    public function state(): string
    {
        return (string) $this->settings->get('state');
    }

    public function mapsUrl(): ?string
    {
        return $this->showFullAddress() ? $this->settings->get('google_maps_url') : null;
    }

    public function gstin(): ?string
    {
        return $this->settings->get('gstin');
    }

    public function fssai(): ?string
    {
        return $this->settings->get('fssai_number');
    }

    public function showFssai(): bool
    {
        return $this->settings->bool('show_fssai') && filled($this->fssai());
    }

    public function instagramHandle(): ?string
    {
        $handle = $this->settings->get('instagram_handle');

        return $handle ? '@'.ltrim($handle, '@') : null;
    }

    public function instagramUrl(): ?string
    {
        return $this->settings->get('instagram_url');
    }

    /** @return array<int, array{key:string,label:string,icon:string,url:string}> */
    public function socialLinks(): array
    {
        $map = [
            'instagram_url' => ['Instagram', 'bi-instagram'],
            'facebook_url' => ['Facebook', 'bi-facebook'],
            'youtube_url' => ['YouTube', 'bi-youtube'],
            'x_url' => ['X', 'bi-twitter-x'],
            'pinterest_url' => ['Pinterest', 'bi-pinterest'],
            'linkedin_url' => ['LinkedIn', 'bi-linkedin'],
            'google_business_url' => ['Google reviews', 'bi-google'],
        ];

        $links = [];
        foreach ($map as $key => [$label, $icon]) {
            $url = $this->settings->get($key);
            if (filled($url)) {
                $links[] = ['key' => $key, 'label' => $label, 'icon' => $icon, 'url' => $url];
            }
        }

        return $links;
    }

    public function metaTitle(): string
    {
        return (string) ($this->settings->get('meta_title') ?: $this->name());
    }

    public function metaDescription(): string
    {
        return (string) ($this->settings->get('meta_description') ?: $this->about());
    }

    public function googleSiteVerification(): ?string
    {
        return $this->settings->get('google_site_verification');
    }

    public function googleAnalyticsId(): ?string
    {
        return $this->settings->get('google_analytics_id');
    }

    public function initials(): string
    {
        $letters = '';
        foreach (array_slice(preg_split('/\s+/', trim($this->name())) ?: [], 0, 2) as $word) {
            $letters .= mb_strtoupper(mb_substr($word, 0, 1));
        }

        return $letters ?: 'C';
    }
}
