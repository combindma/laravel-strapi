<?php

namespace App\DataTransferObjects\Theme;

readonly class ImageDto
{
    public function __construct(
        public ?string $url,
        public ?string $alt,
    ) {}
}
