<?php

namespace Combindma\Strapi\DataObjects;

readonly class LogosDto
{
    public function __construct(
        public ?string $title,
        public ?array $media,
    ) {}
}
