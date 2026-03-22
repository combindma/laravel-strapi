<?php

namespace Combindma\Strapi\DataObjects;

readonly class ContactDto
{
    public function __construct(
        public ?string $email,
        public ?string $phone,
        public ?string $address,
    ) {}
}
