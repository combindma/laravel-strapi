<?php

namespace Combindma\Strapi\DataObjects;

readonly class SocialDto
{
    public function __construct(
        public ?string $facebook,
        public ?string $linkedin,
        public ?string $instagram,
        public ?string $twitter,
    ) {}
}
