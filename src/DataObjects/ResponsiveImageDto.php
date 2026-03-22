<?php

namespace Combindma\Strapi\DataObjects;

readonly class ResponsiveImageDto
{
    public function __construct(
        public ?string $url,
        public ?string $alt,
        public ?string $srcset,
    ) {}
}
