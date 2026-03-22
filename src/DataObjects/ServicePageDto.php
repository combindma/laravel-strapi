<?php

namespace Combindma\Strapi\DataObjects;

use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

readonly class ServicePageDto
{
    public function __construct(
        public string $title,
        public string $slug,
        public HeroDto $hero,
        public LogosDto $logos,
        public Collection $sections,
        public SeoDto $seo,
        public Carbon $publishedAt,
        public Carbon $updatedAt,
    ) {}
}
