<?php

namespace Combindma\Strapi\DataObjects;

readonly class ImageDto
{
    public function __construct(
        public ?string $url,
        public ?string $alt,
    ) {}
}
