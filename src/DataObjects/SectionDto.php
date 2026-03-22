<?php

namespace Combindma\Strapi\DataObjects;

use Illuminate\Support\Collection;

readonly class SectionDto
{
    public function __construct(
        public ?string $title,
        public ?string $label,
        public ?string $content,
        public ResponsiveImageDto $image,
        public ?string $video,
        public ?Collection $features,
    ) {}
}
