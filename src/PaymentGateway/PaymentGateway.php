<?php

namespace Sifex\StripeConnect\PaymentGateway;

use Illuminate\Foundation\Auth\User;
use Illuminate\Support\Collection;
use Sifex\StripeConnect\Interfaces\ConnectAccount;


interface PaymentGateway
{
    public function __construct(string $apiToken);

    /**
     * Create Customer.
     *
     * @param  User  $customer
     * @return Collection
     */
    public function createCustomer(User $customer): Collection;

    /**
     * @param  User  $customer
     * @return Collection
     */
    public function getCustomer(User $customer): Collection;

    /**
     * Update Customer.
     *
     * @param  User  $customer
     * @return Collection
     */
    public function updateCustomer(User $customer): Collection;

    /**
     * Create Payment Gateway Connect Account.
     *
     * @param ConnectAccount $account
     * @return Collection
     */
    public function createOrganisationAccount(ConnectAccount $account): Collection;

    /**
     * Get Payment Gateway Connect Account.
     *
     * @param ConnectAccount $account
     * @return Collection
     */
    public function getOrganisationAccount(ConnectAccount $account): Collection;

    /**
     * Update Payment Gateway Connect Account.
     *
     * @param ConnectAccount $account
     * @return Collection
     */
    public function updateOrganisationAccount(ConnectAccount $account): Collection;

    /**
     * Create Connect Plan.
     *
     * @param  MembershipType  $membershipType
     * @return Collection
     */
    public function createMembershipPlan(MembershipType $membershipType): Collection;

    /**
     * Delete Connect Plan.
     *
     * @param  MembershipType  $membershipType
     * @return Collection
     */
    public function deleteMembershipPlan(MembershipType $membershipType): Collection;

    /**
     * Sets the default payment method for a customer.
     *
     * @param  User  $customer
     * @param  string  $token
     * @return Collection
     */
    public function setCustomerDefaultPaymentMethod(User $customer, string $token): Collection;

    /**
     * Create Subscription within the Payment Gateway.
     *
     * @param  User  $customer
     * @param  MembershipType  $membershipType
     * @return mixed
     */
    public function createSubscription(User $customer, MembershipType $membershipType): Collection;

    /**
     * Stop a Subscription.
     *
     * @param  string  $subscriptionID
     * @param  bool  $cancelAtPeriodEnd
     * @return Collection
     *
     * We want to cancel the
     * // TODO Customise so an organisation is able to control whether it ends immediately or ends after it expires
     * https://github.com/sifex/platform/issues/59
     */
    public function stopSubscription(string $subscriptionID, bool $cancelAtPeriodEnd): Collection;

    /**
     * @param  string  $organisationAccountID
     * @return PaymentGateway
     */
    public function setOrganisationAccountID(string $organisationAccountID): self;

    /**
     * @param ConnectAccount $account
     * @return Collection
     */
    public function getAllOrganisationBankAccounts(ConnectAccount $account): Collection;

    /**
     * @param ConnectAccount $account
     * @param string $bankAccountID
     * @return Collection
     */
    public function getOrganisationBankAccount(ConnectAccount $account, string $bankAccountID): Collection;

    /**
     * @param ConnectAccount $account
     * @param string $token
     * @return Collection
     */
    public function addOrganisationBankAccount(ConnectAccount $account, string $token): Collection;

    /**
     * @param ConnectAccount $account
     * @param string $bankAccountID
     * @return Collection
     */
    public function removeOrganisationBankAccount(ConnectAccount $account, string $bankAccountID): Collection;

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
    ): Collection;

    /**
     * @param ConnectAccount $account
     * @return Collection
     */
    public function getVerificationInformation(ConnectAccount $account): Collection;

    /**
     * @param ConnectAccount $account
     * @param Verification $verificationData
     * @return Collection
     */
    public function saveVerificationInformation(ConnectAccount $account, Verification $verificationData): Collection;

    /**
     * @param  User  $customer
     * @return Collection
     */
    public function getCustomerPaymentMethods(User $customer): Collection;

    /**
     * @param  User  $customer
     * @param  string  $paymentSourceID
     * @return Collection
     */
    public function removeCustomerPaymentMethod(User $customer, string $paymentSourceID): Collection;

    /**
     * @param ConnectAccount $account
     * @return Collection
     */
    public function getAccountBalance(ConnectAccount $account): Collection;

    /**
     * @param  User  $customer
     * @return Collection
     */
    public function getInvoices(User $customer): Collection;

    /**
     * Create a test token for testing purposes.
     *
     * @param  array  $tokenDetails
     * @return Collection
     */
    public function createToken(array $tokenDetails): Collection;
}
