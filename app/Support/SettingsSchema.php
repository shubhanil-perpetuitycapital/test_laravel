<?php

namespace App\Support;

/**
 * Single source of truth for admin-editable settings.
 *
 * Each section becomes a tab on an admin settings screen; each field drives
 * the form input, its validation rules and its default value. Adding a new
 * setting means adding one entry here — the form, validation, saving and
 * the `setting()` helper pick it up automatically.
 *
 * Field keys:
 *   label, type (text|textarea|email|url|tel|number|boolean|image|select),
 *   rules (array), default, help, placeholder, col (Bootstrap col class), options
 */
class SettingsSchema
{
    public const IMAGE_RULES = ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'];

    /**
     * Company setup screen: who the business is and how to reach it.
     *
     * @return array<string, array{title:string, icon:string, intro:string, fields:array<string,array<string,mixed>>}>
     */
    public static function company(): array
    {
        return [
            'brand' => [
                'title' => 'Brand',
                'icon' => 'bi-stars',
                'intro' => 'Your name and logo appear in the header, footer, invoices, WhatsApp messages and browser tab.',
                'fields' => [
                    'company_name' => [
                        'label' => 'Brand name', 'type' => 'text', 'col' => 'col-md-6',
                        'rules' => ['required', 'string', 'max:120'],
                        'default' => config('store.brand_name'),
                        'help' => 'Shown to customers everywhere on the site.',
                    ],
                    'tagline' => [
                        'label' => 'Tagline', 'type' => 'text', 'col' => 'col-md-6',
                        'rules' => ['nullable', 'string', 'max:160'],
                        'default' => 'Handmade chocolates from Kolkata',
                        'placeholder' => 'Small-batch chocolates, made by hand',
                    ],
                    'about_short' => [
                        'label' => 'Short introduction', 'type' => 'textarea', 'col' => 'col-12',
                        'rules' => ['nullable', 'string', 'max:600'],
                        'default' => 'We temper, mould and wrap every chocolate by hand in our Kolkata kitchen, in small batches, using couverture chocolate and fillings we make ourselves.',
                        'help' => 'Used in the homepage introduction and footer. Up to 600 characters.',
                    ],
                    'established_year' => [
                        'label' => 'Year started', 'type' => 'number', 'col' => 'col-md-4',
                        'rules' => ['nullable', 'integer', 'min:1900', 'max:'.date('Y')],
                        'default' => null,
                    ],
                    'logo' => [
                        'label' => 'Logo (for light backgrounds)', 'type' => 'image', 'col' => 'col-md-4',
                        'rules' => self::IMAGE_RULES,
                        'default' => null,
                        'help' => 'PNG or WebP with transparent background, about 400 × 120 px. Without a logo, the brand name is shown as text.',
                    ],
                    'logo_light' => [
                        'label' => 'Logo (for dark backgrounds)', 'type' => 'image', 'col' => 'col-md-4',
                        'rules' => self::IMAGE_RULES,
                        'default' => null,
                        'help' => 'A light-coloured version used on the dark footer and hero. Falls back to the main logo.',
                    ],
                    'favicon' => [
                        'label' => 'Browser icon (favicon)', 'type' => 'image', 'col' => 'col-md-4',
                        'rules' => ['nullable', 'image', 'mimes:png', 'max:512', 'dimensions:min_width=32,min_height=32,max_width=1024,max_height=1024'],
                        'default' => null,
                        'help' => 'Square PNG, 192 × 192 px or larger.',
                    ],
                ],
            ],

            'contact' => [
                'title' => 'Contact',
                'icon' => 'bi-telephone',
                'intro' => 'How customers reach you. The WhatsApp number powers every "Chat on WhatsApp" button on the site.',
                'fields' => [
                    'phone' => [
                        'label' => 'Primary phone', 'type' => 'tel', 'col' => 'col-md-6',
                        'rules' => ['required', 'string', 'max:20', 'regex:/^[0-9+\-\s()]{8,20}$/'],
                        'default' => config('store.business_phone'),
                    ],
                    'phone_alt' => [
                        'label' => 'Alternate phone', 'type' => 'tel', 'col' => 'col-md-6',
                        'rules' => ['nullable', 'string', 'max:20', 'regex:/^[0-9+\-\s()]{8,20}$/'],
                        'default' => null,
                    ],
                    'whatsapp_number' => [
                        'label' => 'WhatsApp number', 'type' => 'tel', 'col' => 'col-md-6',
                        'rules' => ['required', 'string', 'regex:/^\+?[0-9\s]{10,16}$/'],
                        'default' => config('store.business_whatsapp'),
                        'help' => 'With country code, e.g. 91 98300 12345. A 10-digit number is treated as Indian (+91).',
                    ],
                    'email' => [
                        'label' => 'Email', 'type' => 'email', 'col' => 'col-md-6',
                        'rules' => ['nullable', 'email:rfc', 'max:150'],
                        'default' => config('store.business_email'),
                    ],
                    'whatsapp_default_message' => [
                        'label' => 'Default WhatsApp greeting', 'type' => 'text', 'col' => 'col-12',
                        'rules' => ['nullable', 'string', 'max:300'],
                        'default' => 'Hi! I found you on your website and would like to know more about your chocolates.',
                        'help' => 'Pre-filled when a customer taps the floating WhatsApp button.',
                    ],
                    'business_hours' => [
                        'label' => 'Business hours', 'type' => 'textarea', 'col' => 'col-12',
                        'rules' => ['nullable', 'string', 'max:300'],
                        'default' => "Monday to Saturday: 10 am – 8 pm\nSunday: 11 am – 5 pm",
                        'help' => 'One line per row of hours.',
                    ],
                ],
            ],

            'address' => [
                'title' => 'Address',
                'icon' => 'bi-geo-alt',
                'intro' => 'Printed on invoices. You can hide it from the public site if you work from home.',
                'fields' => [
                    'address_line1' => [
                        'label' => 'Address line 1', 'type' => 'text', 'col' => 'col-md-6',
                        'rules' => ['required', 'string', 'max:200'],
                        'default' => 'Your street address',
                    ],
                    'address_line2' => [
                        'label' => 'Address line 2', 'type' => 'text', 'col' => 'col-md-6',
                        'rules' => ['nullable', 'string', 'max:200'],
                        'default' => null,
                    ],
                    'landmark' => [
                        'label' => 'Landmark', 'type' => 'text', 'col' => 'col-md-6',
                        'rules' => ['nullable', 'string', 'max:150'],
                        'default' => null,
                    ],
                    'city' => [
                        'label' => 'City', 'type' => 'text', 'col' => 'col-md-6',
                        'rules' => ['required', 'string', 'max:80'],
                        'default' => 'Kolkata',
                    ],
                    'state' => [
                        'label' => 'State', 'type' => 'text', 'col' => 'col-md-4',
                        'rules' => ['required', 'string', 'max:80'],
                        'default' => 'West Bengal',
                    ],
                    'pincode' => [
                        'label' => 'PIN code', 'type' => 'text', 'col' => 'col-md-4',
                        'rules' => ['required', 'digits:6'],
                        'default' => '700001',
                    ],
                    'country' => [
                        'label' => 'Country', 'type' => 'text', 'col' => 'col-md-4',
                        'rules' => ['required', 'string', 'max:60'],
                        'default' => 'India',
                    ],
                    'google_maps_url' => [
                        'label' => 'Google Maps link', 'type' => 'url', 'col' => 'col-12',
                        'rules' => ['nullable', 'url:https', 'max:500'],
                        'default' => null,
                        'help' => 'Open your business in Google Maps → Share → Copy link.',
                    ],
                    'show_address_publicly' => [
                        'label' => 'Show full address on the website', 'type' => 'boolean', 'col' => 'col-12',
                        'rules' => ['boolean'],
                        'default' => '1',
                        'help' => 'When off, the site shows only the city and state.',
                    ],
                ],
            ],

            'legal' => [
                'title' => 'Legal & tax',
                'icon' => 'bi-patch-check',
                'intro' => 'Food businesses selling online in India must display their FSSAI licence or registration number.',
                'fields' => [
                    'legal_name' => [
                        'label' => 'Registered business name', 'type' => 'text', 'col' => 'col-md-6',
                        'rules' => ['nullable', 'string', 'max:160'],
                        'default' => null,
                        'help' => 'If different from the brand name. Used on invoices.',
                    ],
                    'gstin' => [
                        'label' => 'GSTIN', 'type' => 'text', 'col' => 'col-md-6',
                        'rules' => ['nullable', 'string', 'size:15', 'regex:/^[0-9]{2}[A-Z]{5}[0-9]{4}[A-Z][1-9A-Z]Z[0-9A-Z]$/'],
                        'default' => null,
                        'placeholder' => '19ABCDE1234F1Z5',
                    ],
                    'fssai_number' => [
                        'label' => 'FSSAI licence / registration no.', 'type' => 'text', 'col' => 'col-md-6',
                        'rules' => ['nullable', 'digits:14'],
                        'default' => null,
                    ],
                    'show_fssai' => [
                        'label' => 'Show FSSAI number in the footer', 'type' => 'boolean', 'col' => 'col-md-6',
                        'rules' => ['boolean'],
                        'default' => '1',
                    ],
                ],
            ],

            'social' => [
                'title' => 'Social links',
                'icon' => 'bi-share',
                'intro' => 'Leave a field empty to hide that icon. Links open in a new tab.',
                'fields' => [
                    'instagram_url' => self::socialField('Instagram', 'https://instagram.com/yourbrand'),
                    'instagram_handle' => [
                        'label' => 'Instagram handle', 'type' => 'text', 'col' => 'col-md-6',
                        'rules' => ['nullable', 'string', 'max:40', 'regex:/^@?[A-Za-z0-9._]+$/'],
                        'default' => null, 'placeholder' => '@yourbrand',
                        'help' => 'Shown above the homepage photo grid.',
                    ],
                    'facebook_url' => self::socialField('Facebook', 'https://facebook.com/yourbrand'),
                    'youtube_url' => self::socialField('YouTube', 'https://youtube.com/@yourbrand'),
                    'x_url' => self::socialField('X (Twitter)', 'https://x.com/yourbrand'),
                    'pinterest_url' => self::socialField('Pinterest', 'https://pinterest.com/yourbrand'),
                    'linkedin_url' => self::socialField('LinkedIn', 'https://linkedin.com/company/yourbrand'),
                    'google_business_url' => self::socialField('Google reviews page', 'https://g.page/r/...'),
                ],
            ],

            'seo' => [
                'title' => 'SEO',
                'icon' => 'bi-search',
                'intro' => 'Defaults used by search engines and link previews when a page has no text of its own.',
                'fields' => [
                    'meta_title' => [
                        'label' => 'Homepage title', 'type' => 'text', 'col' => 'col-12',
                        'rules' => ['nullable', 'string', 'max:70'],
                        'default' => 'Handmade Chocolates in Kolkata | Gift Boxes & Custom Orders',
                        'help' => 'Up to 70 characters.',
                    ],
                    'meta_description' => [
                        'label' => 'Homepage description', 'type' => 'textarea', 'col' => 'col-12',
                        'rules' => ['nullable', 'string', 'max:170'],
                        'default' => 'Small-batch handmade chocolate bars, truffles and gift boxes. Same-day delivery in Kolkata and courier across India.',
                        'help' => 'Up to 170 characters.',
                    ],
                    'og_image' => [
                        'label' => 'Link preview image', 'type' => 'image', 'col' => 'col-md-6',
                        'rules' => self::IMAGE_RULES,
                        'default' => null,
                        'help' => '1200 × 630 px, shown when your link is shared on WhatsApp or Facebook.',
                    ],
                    'google_site_verification' => [
                        'label' => 'Google Search Console code', 'type' => 'text', 'col' => 'col-md-6',
                        'rules' => ['nullable', 'string', 'max:100', 'regex:/^[A-Za-z0-9_\-]+$/'],
                        'default' => null,
                        'help' => 'Only the content value of the verification meta tag.',
                    ],
                    'google_analytics_id' => [
                        'label' => 'Google Analytics ID', 'type' => 'text', 'col' => 'col-md-6',
                        'rules' => ['nullable', 'string', 'regex:/^G-[A-Z0-9]{4,12}$/'],
                        'default' => null, 'placeholder' => 'G-XXXXXXXXXX',
                    ],
                ],
            ],
        ];
    }

    /**
     * Flat map of field key => definition (with its section name).
     *
     * @return array<string, array<string,mixed>>
     */
    public static function companyFields(): array
    {
        $flat = [];
        foreach (self::company() as $group => $section) {
            foreach ($section['fields'] as $key => $field) {
                $flat[$key] = $field + ['group' => $group];
            }
        }

        return $flat;
    }

    /** @return array<string, mixed> key => default */
    public static function defaults(): array
    {
        $defaults = [];
        foreach (self::companyFields() as $key => $field) {
            $defaults[$key] = $field['default'] ?? null;
        }

        return $defaults;
    }

    private static function socialField(string $label, string $placeholder): array
    {
        return [
            'label' => $label, 'type' => 'url', 'col' => 'col-md-6',
            'rules' => ['nullable', 'url:https,http', 'max:300'],
            'default' => null, 'placeholder' => $placeholder,
        ];
    }
}
