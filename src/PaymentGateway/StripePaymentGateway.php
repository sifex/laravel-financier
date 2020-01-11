<?php

namespace Sifex\LaravelFinancier\PaymentGateway;

use Carbon\Carbon;
use Illuminate\Foundation\Auth\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Sifex\LaravelFinancier\Interfaces\ConnectAccount;
use Stripe\Account;
use Stripe\Balance;
use Stripe\Customer;
use Stripe\Error\Api;
use Stripe\Invoice;
use Stripe\Plan;
use Stripe\Stripe;
use Stripe\Subscription;
use Stripe\Token;

class StripePaymentGateway implements PaymentGateway
{
    /**
     * @var string
     */
    private $apiToken;

    /**
     * @var string
     */
    private $organisationAccountID;

    /**
     * @var string
     */
    private $version = '2019-05-16';

    /**
     * Banking Route Number for Australia.
     */
    public const BANK_ROUTE_AUS = '110000';

    /**
     * Banking Account Number for Australia.
     */
    public const BANK_ACCOUNT_AUS = '000123456';

    /**
     * Visa Payment Token.
     */
    public const TOKEN_VISA = 'tok_visa';

    /**
     * Mastercard Payment Token.
     */
    public const TOKEN_MASTERCARD = 'tok_mastercard';

    /**
     * StripePaymentGateway constructor.
     *
     * @param string $apiToken
     * @param string $organisationAccountID
     */
    public function __construct(string $apiToken, string $organisationAccountID = '')
    {
        $this->apiToken = $apiToken;
        $this->organisationAccountID = $organisationAccountID;
        Stripe::setApiKey($this->apiToken);
        Stripe::setApiVersion($this->version);
    }

    /**
     * @param string $organisationAccountID
     * @return StripePaymentGateway
     */
    public function setOrganisationAccountID(string $organisationAccountID): PaymentGateway
    {
        $this->organisationAccountID = $organisationAccountID;

        return $this;
    }

    /**
     * Customer CRU.
     */

    /**
     * @param User $customer
     * @return Collection
     * @throws \Stripe\Exception\ApiErrorException
     */
    public function createCustomer(User $customer): Collection
    {
        $user = $customer->user;

        $customer = Customer::create(
            [
                'email' => $user->email,
                'name' => $user->first_name.' '.$user->last_name,
                'description' => $user->uuid,
                'metadata' => [
                    'user_id' => $user->id,
                    'org_id' => $user->id,
                ],
            ],
            ['stripe_account' => $this->organisationAccountID]
        );

        return (new CustomerTranslation($customer))->translate();
    }

    /**
     * @param User $customer
     * @return Collection
     * @throws \Stripe\Exception\ApiErrorException
     */
    public function getCustomer(User $customer): Collection
    {
        $customer = Customer::retrieve(
            $customer->{config('laravel-financier.user_customer_attribute'},
            ['stripe_account' => $this->organisationAccountID]
        );

        return (new CustomerTranslation($customer))->translate();
    }

    /**
     * @param User $customer
     * @param string $token
     * @return Collection
     * @throws \Stripe\Exception\ApiErrorException
     */
    public function setCustomerDefaultPaymentMethod(User $customer, string $token): Collection
    {
        $card = Customer::createSource(
            $customer->{config('laravel-financier.user_customer_attribute'},
            ['source' => $token],
            ['stripe_account' => $this->organisationAccountID]
        );

        return (new CardTranslation($card))->translate();
    }

    /**
     * Update Customer.
     *
     * @param User $customer
     * @return Collection
     * @throws \Stripe\Exception\ApiErrorException
     */
    public function updateCustomer(User $customer): Collection
    {
        $user = $customer->user;

        $customer = Customer::update(
            $customer->{config('laravel-financier.user_customer_attribute'},
            [
                'email' => $user->email,
                'name' => $user->first_name.' '.$user->last_name,
                'description' => $user->uuid,
                'metadata' => [
                    'user_id' => $user->id,
                    'org_id' => $user->id,
                ],
            ],
            ['stripe_account' => $this->organisationAccountID]
        );

        return (new CustomerTranslation($customer))->translate();
    }

    /**
     * Organisational CRU.
     */

    /**
     * @param ConnectAccount $account
     * @return Collection
     * @throws \Stripe\Exception\ApiErrorException
     */
    public function createOrganisationAccount(ConnectAccount $account): Collection
    {
        /** @var Account $stripeAccount */
        $stripeAccount = Account::create([
            'type' => 'custom',
            'business_type' => 'individual',
            'business_profile[support_email]' => $account->contact_email,
            'business_profile[name]' => $account->longName,
            'business_profile[url]' => $account->contact_website,
            'country' => strtoupper((new ISO3166())->alpha2($account->country)['alpha2']),
            'email' => $account->owner->email,
            'settings[payouts][schedule][interval]' => 'monthly',
            'settings[payouts][schedule][monthly_anchor]' => '4',
            'settings[branding][primary_color]' => $account->customisation_color_primary,
            'tos_acceptance[date]' => Carbon::now()->timestamp, // TODO Remove
            'tos_acceptance[ip]' => '1.1.1.1', // TODO Remove
        ]);

        return (new AccountTranslation($stripeAccount))->translate();
    }

    /**
     * @param ConnectAccount $account
     * @return Collection
     * @throws \Stripe\Exception\ApiErrorException
     */
    public function getOrganisationAccount(ConnectAccount $account): Collection
    {
        /** @var Account $stripeAccount */
        $stripeAccount = Account::retrieve($account->getPaymentGatewayAccountID());

        return (new AccountTranslation($stripeAccount))->translate();
    }

    /**
     * @param ConnectAccount $account
     * @return Collection
     * @throws \Stripe\Exception\ApiErrorException
     */
    public function updateOrganisationAccount(ConnectAccount $account): Collection
    {
        /** @var Account $stripeAccount */
        $stripeAccount = Account::update(
            $account->getPaymentGatewayAccountID(),
            [
                'email' => $account->owner->email,
                'business_profile[support_email]' => $account->contact_email,
                'business_profile[name]' => $account->longName,
                'business_profile[url]' => $account->contact_website,
                'settings[branding][primary_color]' => $account->customisation_color_primary,
            ]
        );

        return (new AccountTranslation($stripeAccount))->translate();
    }

    /**
     * @param MembershipType $membershipType
     * @return Collection
     * @throws \Stripe\Exception\ApiErrorException
     */
    public function createMembershipPlan(MembershipType $membershipType): Collection
    {
        $plan = Plan::create([
            'active' => (bool) $membershipType->active,
            'amount' => $membershipType->cost,
            'interval' => $membershipType->interval,
            'interval_count' => $membershipType->interval_count,
            'product' => [
                'name' => $membershipType->name,
            ],
            'currency' => Str::lower($membershipType->currency),
        ], ['stripe_account' => $this->organisationAccountID]);

        return (new PlanTranslation($plan))->translate();
    }

    /**
     * Delete Connect Plan.
     *
     * @param MembershipType $membershipType
     * @return Collection
     * @throws \Stripe\Exception\ApiErrorException
     */
    public function deleteMembershipPlan(MembershipType $membershipType): Collection
    {
        $plan = Plan::update(
            $membershipType->payment_gateway_plan_id,
            ['active' => false],
            ['stripe_account' => $this->organisationAccountID]
        );

        // TODO Remove all memberships?

        return (new PlanTranslation($plan))->translate();
    }

    /**
     * Create Membership.
     *
     * @param User $customer
     * @param MembershipType $membershipType
     * @return mixed
     * @throws \Stripe\Exception\ApiErrorException
     */
    public function createSubscription(User $customer, MembershipType $membershipType): Collection
    {
        $subscription = Subscription::create([
            'customer' => $customer->{config('laravel-financier.user_customer_attribute'},
            'items' => [
                [
                    'plan' => $membershipType->payment_gateway_plan_id,
                ],
            ],
            'application_fee_percent' => 0,
        ], ['stripe_account' => $this->organisationAccountID]);

        return (new SubscriptionTranslation($subscription))->translate();
    }

    /**
     * Stop a Subscription.
     *
     * @param string $subscriptionID
     * @param bool $cancelAtPeriodEnd
     * @return Collection
     * @throws \Stripe\Exception\ApiErrorException
     */
    public function stopSubscription(string $subscriptionID, bool $cancelAtPeriodEnd): Collection
    {
        if ($cancelAtPeriodEnd) {
            $subscription = Subscription::update($subscriptionID, [
                'cancel_at_period_end' => true,
            ], ['stripe_account' => $this->organisationAccountID]);
        } else {
            $subscription = Subscription::retrieve($subscriptionID, ['stripe_account' => $this->organisationAccountID]);
            $subscription->cancel();
        }

        return (new SubscriptionTranslation($subscription))->translate();
    }

    /**
     * @param ConnectAccount $account
     * @return Collection
     * @throws \Stripe\Exception\ApiErrorException
     */
    public function getAllOrganisationBankAccounts(ConnectAccount $account): Collection
    {
        /** @var \Stripe\Collection $bankAccounts */
        $bankAccounts = Account::allExternalAccounts($account->getPaymentGatewayAccountID(), [
            'limit' => 100,
            'object' => 'bank_account',
        ], ['stripe_account' => $this->organisationAccountID]);

        $collection = collect([]);

        collect($bankAccounts->data)->each(function ($bankAccount) use ($collection) {
            $collection->add((new BankTranslation($bankAccount))->translate());
        });

        return $collection;
    }

    /**
     * @param ConnectAccount $account
     * @param string $bankAccountID
     * @return Collection
     * @throws \Stripe\Exception\ApiErrorException
     */
    public function getOrganisationBankAccount(ConnectAccount $account, string $bankAccountID): Collection
    {
        $bankAccount = Account::retrieveExternalAccount(
            $account->getPaymentGatewayAccountID(),
            $bankAccountID,
            [],
            ['stripe_account' => $this->organisationAccountID]
        );

        return (new BankTranslation($bankAccount))->translate();
    }

    /**
     * @param ConnectAccount $account
     * @param string $token
     * @return Collection
     * @throws \Stripe\Exception\ApiErrorException
     */
    public function addOrganisationBankAccount(ConnectAccount $account, string $token): Collection
    {
        $bankAccount = Account::createExternalAccount($account->getPaymentGatewayAccountID(), [
            'external_account' => $token,
        ], ['stripe_account' => $this->organisationAccountID]);

        return (new BankTranslation($bankAccount))->translate();
    }

    /**
     * @param ConnectAccount $account
     * @param string $bankAccountID
     * @return Collection
     * @throws \Stripe\Exception\ApiErrorException
     */
    public function removeOrganisationBankAccount(ConnectAccount $account, string $bankAccountID): Collection
    {
        $deletedBankAccount = Account::deleteExternalAccount(
            $account->getPaymentGatewayAccountID(),
            $bankAccountID,
            [],
            ['stripe_account' => $this->organisationAccountID]
        );

        return (new DeletedBankTranslation($deletedBankAccount))->translate();
    }

    /**
     * @param ConnectAccount $account
     * @param string $bankAccountID
     * @param bool $defaultForCurrency
     * @return Collection
     * @throws \Stripe\Exception\ApiErrorException
     */
    public function setDefaultOrganisationBankAccount(
        ConnectAccount $account,
        string $bankAccountID,
        bool $defaultForCurrency = true
    ): Collection {
        $bankAccount = Account::updateExternalAccount($account->getPaymentGatewayAccountID(), $bankAccountID, [
            'default_for_currency' => $defaultForCurrency,
        ], ['stripe_account' => $this->organisationAccountID]);

        return (new BankTranslation($bankAccount))->translate();
    }

    /**
     * @param ConnectAccount $account
     * @return Collection
     * @throws \Stripe\Exception\ApiErrorException
     */
    public function getVerificationInformation(ConnectAccount $account): Collection
    {
        /** @var Account $stripeAccount */
        $stripeAccount = Account::retrieve($account->getPaymentGatewayAccountID());

        return (new VerificationTranslation($stripeAccount))->translate(); // TODO Return with Verification Object -> To Collection
    }

    /**
     * @param ConnectAccount $account
     * @param Verification $verificationData
     * @return Collection
     * @throws \Stripe\Exception\ApiErrorException
     */
    public function saveVerificationInformation(ConnectAccount $account, Verification $verificationData): Collection
    {
        $dob = Carbon::parse($verificationData->toCollection()->get('dob'), 'UTC');

        /** @var Account $stripeAccount */
        $stripeAccount = Account::update(
            $account->getPaymentGatewayAccountID(),
            [
                'business_type' => 'individual',
                'individual' => [
                    'address' => [
                        'line1' => $verificationData->toCollection()->get('address.line1'),
                        'city' => $verificationData->toCollection()->get('address.city'),
                        'postal_code' => $verificationData->toCollection()->get('address.postal_code'),
                        'state' => $verificationData->toCollection()->get('address.state'),
                    ],
                    'dob' => [
                        'day' => $dob->day,
                        'month' => $dob->month,
                        'year' => $dob->year,
                    ],
                    'first_name' => $verificationData->toCollection()->get('first_name'),
                    'last_name' => $verificationData->toCollection()->get('last_name'),
                    // TODO Add Phone Number, Meta Data Etc

                ],
                'tos_acceptance' => $verificationData->toCollection()->get('tos_acceptance'),
            ]
        );

        return (new VerificationTranslation($stripeAccount))->translate();
    }

    /**
     * @param User $customer
     * @return Collection
     * @throws \Stripe\Exception\ApiErrorException
     */
    public function getCustomerPaymentMethods(User $customer): Collection
    {
        $customer = Customer::retrieve(
            $customer->{config('laravel-financier.user_customer_attribute'},
            ['stripe_account' => $this->organisationAccountID]
        );

        return (new CustomerTranslation($customer))->getPaymentPreferences();
    }

    /**
     * @param User $customer
     * @param string $paymentSourceID
     * @return Collection
     * @throws \Stripe\Exception\ApiErrorException
     */
    public function removeCustomerPaymentMethod(User $customer, string $paymentSourceID): Collection
    {
        $deletedSource = Customer::deleteSource(
            $customer->{config('laravel-financier.user_customer_attribute'},
            $paymentSourceID,
            [],
            ['stripe_account' => $this->organisationAccountID]
        );

        return (new CardTranslation($deletedSource))->translate();
    }

    /**
     * @param ConnectAccount $account
     * @return Collection
     * @throws \Stripe\Exception\ApiErrorException
     */
    public function getAccountBalance(ConnectAccount $account): Collection
    {
        $balance = Balance::retrieve(
            ['stripe_account' => $account->getPaymentGatewayAccountID()]
        );

        return (new BalanceTranslation($balance))->translate();
    }

    /**
     * @param User $customer
     * @return Collection
     * @throws Api
     * @throws \Stripe\Exception\ApiErrorException
     */
    public function getInvoices(User $customer): Collection
    {
        $invoices = Invoice::all(
            ['customer' => $customer->{config('laravel-financier.user_customer_attribute'}],
            ['stripe_account' => $this->organisationAccountID]
        );

        return collect($invoices->data)->map(function ($stripeInvoice) {
            return (new InvoiceTranslation($stripeInvoice))->translate();
        });
    }

    /**
     * Create a test token (for testing purposes only).
     *
     * @param array $tokenDetails
     * @return Collection
     * @throws \Stripe\Exception\ApiErrorException
     */
    public function createToken(array $tokenDetails): Collection
    {
        $token = Token::create($tokenDetails);

        return collect($token->jsonSerialize());
    }
}
