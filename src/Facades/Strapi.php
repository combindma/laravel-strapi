<?php

namespace Combindma\Strapi\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * @see \Combindma\Strapi\Strapi
 */
class Strapi extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return \Combindma\Strapi\Strapi::class;
    }
}
