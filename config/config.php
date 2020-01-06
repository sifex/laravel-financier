<?php

use Illuminate\Foundation\Auth\User;
use Sifex\StripeConnect\Interfaces\ConnectAccount;

return [
    /**
     * Which version of the Stripe API to use
     */
    'version' => '2019-05-16',

    /**
     * User Model that contains the customer attribute
     */
    'user_class' => User::class,

    /**
     * Customer Attribute on User
     */
    'user_customer_attribute' => 'stripe_customer_id',

    /**
     * Customer Attribute on User
     */
    'connect_account_class' => ConnectAccount::class,

    /**
     * Customer Attribute on User
     */
    'connect_account_id_attribute' => 'stripe_account_id',
];
