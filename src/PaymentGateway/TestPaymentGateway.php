<?php

namespace Sifex\StripeConnect\PaymentGateway;

use Illuminate\Foundation\Auth\User;
use Sifex\StripeConnect\Interfaces\ConnectAccount;
use Sifex\StripeConnect\PaymentGateway\PaymentGateway;

class TestPaymentGateway implements PaymentGateway
{
    /**
     * @var string
     */
    private $apiToken;

    public function __construct(string $apiToken = '')
    {
        $this->apiToken = $apiToken;
    }

    /**
     * @param  string  $organisationAccountID
     * @return TestPaymentGateway
     */
    public function setOrganisationAccountID(string $organisationAccountID): PaymentGateway
    {
        return $this;
    }

    /**
     * Update Customer.
     *
     * @param User $customer
     * @return Collection
     */
    public function getCustomer(User $customer): Collection
    {
        return collect([
            'payment_gateway_customer_id' => 'cus_123456789ABCDEF',
            'email' => $customer->user->email,
            'sources' => collect([
                collect([
                    'payment_gateway_method_id' => 'card_test_'.'ABCDEF_CARD01',
                    'payment_gateway_customer_id' => $customer->payment_gateway_customer_id,
                    'last4' => '1234',
                    'country' => collect(Countries::STRIPE_SUPPORTED)->keys()->random(),
                    'brand' => 'Visa',
                    'default' => true,
                ]),
                collect([
                    'payment_gateway_method_id' => 'card_test_'.'ABCDEF_CARD02',
                    'payment_gateway_customer_id' => $customer->payment_gateway_customer_id,
                    'last4' => '4321',
                    'country' => collect(Countries::STRIPE_SUPPORTED)->keys()->random(),
                    'brand' => 'Mastercard',
                    'default' => false,
                ]),
                collect([
                    'payment_gateway_method_id' => 'card_test_'.'ABCDEF_CARD03',
                    'payment_gateway_customer_id' => $customer->payment_gateway_customer_id,
                    'last4' => '5678',
                    'country' => collect(Countries::STRIPE_SUPPORTED)->keys()->random(),
                    'brand' => 'Unknown',
                    'default' => false,
                ]),
            ]),
        ]);
    }

    /**
     * @param User $customer
     * @return Collection
     */
    public function createCustomer(User $customer): Collection
    {
        return collect([
            'payment_gateway_customer_id' => 'cus_123456789ABCDEF',
        ]);
    }

    public function createOrganisationAccount(ConnectAccount $account): Collection
    {
        return collect([
            'payment_gateway_account_id' => 'acct_123456789ABCDEF',
        ]);
    }

    public function createMembershipPlan(MembershipType $membershipType): Collection
    {
        return collect([
            'active' => true,
            'payment_gateway_plan_id' => 'plan_123456789ABCDEF',
        ]);
    }

    /**
     * @param User $customer
     * @param string $token
     * @return Collection
     */
    public function setCustomerDefaultPaymentMethod(User $customer, string $token): Collection
    {
        return collect([
            'payment_gateway_method_id' => 'card_123456789ABCDEF',
            'payment_gateway_customer_id' => $customer->payment_gateway_customer_id,
            'last4' => '1234',
            'country' => 'AU',
            'brand' => 'Visa',
        ]);
    }

    /**
     * Update Customer.
     *
     * @param User $customer
     * @return Collection
     */
    public function updateCustomer(User $customer): Collection
    {
        return collect([
            'payment_gateway_customer_id' => 'cus_123456789ABCDEF',
            'email' => $customer->user->email,
        ]);
    }

    /**
     * Get Payment Gateway Connect Account.
     *
     * @param ConnectAccount $account
     * @return Collection
     */
    public function getOrganisationAccount(ConnectAccount $account): Collection
    {
        return collect([
            'payment_gateway_account_id' => 'acct_123456789ABCDEF',
        ]);
    }

    /**
     * Update Payment Gateway Connect Account.
     *
     * @param ConnectAccount $account
     * @return Collection
     */
    public function updateOrganisationAccount(ConnectAccount $account): Collection
    {
        return collect([
            'payment_gateway_account_id' => 'acct_123456789ABCDEF',
        ]);
    }

    /**
     * Delete Connect Plan.
     *
     * @param  MembershipType  $membershipType
     * @return Collection
     */
    public function deleteMembershipPlan(MembershipType $membershipType): Collection
    {
        return collect([
            'active' => false,
            'payment_gateway_plan_id' => 'plan_123456789ABCDEF',
        ]);
    }

    /**
     * Create a Subscription.
     *
     * @param User $customer
     * @param MembershipType $membershipType
     * @return mixed
     */
    public function createSubscription(User $customer, MembershipType $membershipType): Collection
    {
        return collect([
            'status' => 'active',
            'payment_gateway_customer_id' => $customer->payment_gateway_customer_id,
            'payment_gateway_subscription_id' => 'sub_123456789ABCDEF',
            'current_period_start' => Carbon::now()->timestamp,
            'current_period_end' => Carbon::now()->add(
                CarbonInterval::fromString($membershipType->interval_count.' '.$membershipType->interval)
            )->timestamp,
            'cancel_at_period_end' => false,
        ]);
    }

    /**
     * Stop a Subscription.
     *
     * @param  string  $subscriptionID
     * @param  bool  $cancelAtPeriodEnd
     * @return Collection
     */
    public function stopSubscription(string $subscriptionID, bool $cancelAtPeriodEnd): Collection
    {
        return collect([
            'status' => 'canceled',
            'payment_gateway_customer_id' => 'cus_123456789ABCDEF',
            'payment_gateway_subscription_id' => $subscriptionID,
            'current_period_start' => Carbon::now()->timestamp,
            'current_period_end' => Carbon::now()->addWeek(2)->timestamp,
            'cancel_at_period_end' => $cancelAtPeriodEnd,
        ]);
    }

    /**
     * @param ConnectAccount $account
     * @return Collection
     */
    public function getAllOrganisationBankAccounts(ConnectAccount $account): Collection
    {
        return collect([
            collect([
                'payment_gateway_bank_account_id' => 'ba_123456789',
                'bank_name' => 'Commbank',
                'country' => 'AU',
                'currency' => 'aud',
                'account_number' => null,
                'last4' => '4567',
                'routing_number' => '123456',
                'default_for_currency' => true,
            ]),
            collect([
                'payment_gateway_bank_account_id' => 'ba_987654321',
                'bank_name' => 'Westpac',
                'country' => 'AU',
                'currency' => 'aud',
                'account_number' => null,
                'last4' => '7654',
                'routing_number' => '654321',
                'default_for_currency' => false,
            ]),
        ]);
    }

    /**
     * @param ConnectAccount $account
     * @param string $bankAccountID
     * @return Collection
     */
    public function getOrganisationBankAccount(ConnectAccount $account, string $bankAccountID): Collection
    {
        return collect([
            'payment_gateway_bank_account_id' => $bankAccountID,
            'bank_name' => 'Commbank',
            'country' => 'AU',
            'currency' => 'aud',
            'account_number' => null,
            'last4' => '4567',
            'routing_number' => '123456',
            'default_for_currency' => true,
        ]);
    }

    /**
     * @param ConnectAccount $account
     * @param string $token
     * @return Collection
     */
    public function addOrganisationBankAccount(ConnectAccount $account, string $token): Collection
    {
        return collect([
            'payment_gateway_bank_account_id' => 'ba_123456789',
            'bank_name' => 'Commbank',
            'country' => 'AU',
            'currency' => 'aud',
            'account_number' => null,
            'last4' => '4567',
            'routing_number' => '123456',
            'default_for_currency' => true,
        ]);
    }

    /**
     * @param ConnectAccount $account
     * @param string $bankAccountID
     * @return Collection
     */
    public function removeOrganisationBankAccount(ConnectAccount $account, string $bankAccountID): Collection
    {
        return collect([
            'payment_gateway_bank_account_id' => 'ba_123456789',
            'object' => 'bank_account',
            'deleted' => true,
        ]);
    }

    /**
     * @param ConnectAccount $account
     * @param string $bankAccountID
     * @param bool $defaultForCurrency
     * @return Collection
     */
    public function setDefaultOrganisationBankAccount(
        ConnectAccount $account,
        string $bankAccountID,
        bool $defaultForCurrency = true
    ): Collection {
        return collect([
            'payment_gateway_bank_account_id' => 'ba_123456789',
            'bank_name' => 'Commbank',
            'country' => 'AU',
            'currency' => 'aud',
            'account_number' => null,
            'last4' => '4567',
            'routing_number' => '123456',
            'default_for_currency' => $defaultForCurrency,
        ]);
    }

    /**
     * @param ConnectAccount $account
     * @return Collection
     */
    public function getVerificationInformation(ConnectAccount $account): Collection
    {
        return collect([
            'payouts_enabled' => true,
            'verified' => null,
            'disabled_reason' => null,
            'address.line1' => '7 Test Tester Lane',
            'address.city' => 'Canberra',
            'address.postal_code' => '2901',
            'address.state' => 'Australian Capital Territory',
            'first_name' => 'Testy',
            'last_name' => 'Tester',
            'dob' => Carbon::now()->subtract('year', 21),
        ]);
    }

    /**
     * @param ConnectAccount $account
     * @param Verification $verificationData
     * @return Collection
     */
    public function saveVerificationInformation(ConnectAccount $account, Verification $verificationData): Collection
    {
        return collect([
            'payouts_enabled' => true,
            'verified' => (bool) rand(0, 2),
            'disabled_reason' => null,
            'address.line1' => $verificationData->toCollection()->get('address.line1'),
            'address.city' => $verificationData->toCollection()->get('address.city'),
            'address.postal_code' => $verificationData->toCollection()->get('address.postal_code'),
            'address.state' => $verificationData->toCollection()->get('address.state'),
            'first_name' => $verificationData->toCollection()->get('first_name'),
            'last_name' => $verificationData->toCollection()->get('last_name'),
            'dob' => Carbon::now()->subtract('year', 21),
        ]);
    }

    /**
     * @param User $customer
     * @return Collection
     */
    public function getCustomerPaymentMethods(User $customer): Collection
    {
        return $this->getCustomer($customer)->get('sources');
    }

    /**
     * @param User $customer
     * @param string $paymentSourceID
     * @return Collection
     */
    public function removeCustomerPaymentMethod(User $customer, string $paymentSourceID): Collection
    {
        /** @var Collection $sources */
        $sources = $this->getCustomer($customer)->get('sources');

        return $sources->filter(function (Collection $source) use ($paymentSourceID) {
            return $source->get('payment_gateway_method_id') !== $paymentSourceID;
        });
    }

    /**
     * @param ConnectAccount $account
     * @return Collection
     */
    public function getAccountBalance(ConnectAccount $account): Collection
    {
        return collect([
            'available' => [
                [
                    'amount' => 12345678,
                    'currency' => 'aud',
                    'source_types' => [
                        'card' => 12345678,
                    ],
                ],
            ],
            'pending' => [
                [
                    'amount' => 12345678,
                    'currency' => 'aud',
                    'source_types' => [
                        'card' => 12345678,
                    ],
                ],
            ],
        ]);
    }

    /**
     * @param User $customer
     * @return Collection
     */
    public function getInvoices(User $customer): Collection
    {
        return collect([
            collect([
                'created' => 123456789,
                'amount_paid' => 10000,
                'hosted_invoice_url' => 'https://www.youtube.com/watch?v=dQw4w9WgXcQ',
                'currency' => 'aud',
            ]),
        ]);
    }

    /**
     * Create a test token for testing purposes.
     *
     * @param  array  $tokenDetails
     * @return Collection
     */
    public function createToken(array $tokenDetails): Collection
    {
        /** @var string $type */
        $type = collect($tokenDetails)->keys()->first();

        switch ($type) {
            case 'bank_account':
                return collect([
                    'id' => 'btok_12345676543234567654',
                    'object' => 'token',
                    'bank_account' => [
                        'id' => 'ba_123456787654345',
                        'object' => 'bank_account',
                        'account_holder_name' => null,
                        'account_holder_type' => null,
                        'bank_name' => 'STRIPE TEST BANK',
                        'country' => $tokenDetails['bank_account']['country'],
                        'currency' => $tokenDetails['bank_account']['currency'],
                        'last4' => '3456',
                        'name' => null,
                        'routing_number' => '11 0000',
                        'status' => 'new',
                    ],
                    'client_ip' => '1.2.3.4',
                    'created' => 1562200284,
                    'livemode' => false,
                    'type' => 'bank_account',
                    'used' => false,
                ]);
        }
    }
}
