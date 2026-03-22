<?php

namespace App\DataTransferObjects\Theme;

readonly class ResponsiveImageDto
{
    public function __construct(
        public ?string $url,
        public ?string $alt,
        public ?string $srcset,
    ) {}
}
