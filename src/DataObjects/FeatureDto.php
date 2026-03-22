<?php

namespace Combindma\Strapi\DataObjects;

use App\DataTransferObjects\Theme\ResponsiveImageDto;

readonly class FeatureDto
{
    public function __construct(
        public ?string $title,
        public ?string $label,
        public ?string $description,
        public ResponsiveImageDto $image,
    ) {}
}
