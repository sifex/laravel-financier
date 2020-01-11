<?php

namespace Sifex\LaravelFinancier;

use Illuminate\Support\Facades\Facade;

/**
 * @see \Sifex\LaravelFinancier\Skeleton\SkeletonClass
 */
class LaravelFinancierFacade extends Facade
{
    /**
     * Get the registered name of the component.
     *
     * @return string
     */
    protected static function getFacadeAccessor()
    {
        return 'laravel-financier';
    }
}
