<?php

namespace App\Services;

/**
 * Per-page SEO values passed to the layout. Anything left null falls back
 * to the company-level SEO settings.
 */
class SeoData
{
    public function __construct(
        public ?string $title = null,
        public ?string $description = null,
        public ?string $image = null,
        public ?string $canonical = null,
        public string $type = 'website',
        public bool $noindex = false,
        /** @var array<int, array<string,mixed>> JSON-LD blocks */
        public array $schema = [],
    ) {}

    public static function make(?string $title = null, ?string $description = null, bool $noindex = false): self
    {
        return new self(title: $title, description: $description, noindex: $noindex);
    }
}
