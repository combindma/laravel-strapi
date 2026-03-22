<?php

namespace Combindma\Strapi\DataObjects;

readonly class SeoDto
{
    public function __construct(
        public string $metaTitle,
        public ?string $metaDescription,
        public ?string $metaImage,
        public bool $noIndex = false,
    ) {}
}
