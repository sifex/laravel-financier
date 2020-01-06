<?php

namespace Sifex\StripeConnect;

use Illuminate\Support\Facades\Facade;

/**
 * @see \Sifex\StripeConnect\Skeleton\SkeletonClass
 */
class StripeConnectFacade extends Facade
{
    /**
     * Get the registered name of the component.
     *
     * @return string
     */
    protected static function getFacadeAccessor()
    {
        return 'stripe-connect';
    }
}
