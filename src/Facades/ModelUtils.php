<?php

namespace HindBiswas\ModelUtils\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * @see \HindBiswas\ModelUtils\ModelUtils
 */
class ModelUtils extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return \HindBiswas\ModelUtils\ModelUtils::class;
    }
}
