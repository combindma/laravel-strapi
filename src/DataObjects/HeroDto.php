<?php

namespace Combindma\Strapi\DataObjects;

use App\DataTransferObjects\Theme\ResponsiveImageDto;

readonly class HeroDto
{
    public function __construct(
        public ?string $title,
        public ?string $label,
        public ?string $description,
        public ?string $cta,
        public ResponsiveImageDto $image,
        public ?string $video,
    ) {}
}
