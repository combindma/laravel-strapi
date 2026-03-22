<?php

namespace Combindma\Strapi\DataObjects;

use Illuminate\Support\Collection;

readonly class PageDto
{
    public function __construct(
        public ?HeroDto $hero,
        public ?Collection $sections,
    ) {}
}
