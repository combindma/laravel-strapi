<?php

namespace Combindma\Strapi\DataObjects;

use App\DataTransferObjects\Theme\ResponsiveImageDto;
use Illuminate\Support\Carbon;

readonly class ServiceDto
{
    public function __construct(
        public string $title,
        public string $slug,
        public ResponsiveImageDto $image,
        public Carbon $publishedAt,
        public Carbon $updatedAt,
    ) {}
}
