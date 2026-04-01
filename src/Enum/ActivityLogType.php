<?php

namespace App\Enum;

/**
 * Enum representing all activity log event types within the system.
 *
 * This enum centralizes all business-relevant events that can be recorded
 * in the ActivityLog entity, such as subscription lifecycle events,
 * payments, invoices, user actions, and security-related events.
 *
 * It is used to:
 * - Categorize activity log entries
 * - Provide consistent labels for UI display
 * - Enable filtering and grouping of activities
 */
enum ActivityLogType: string
{
    // ========================
    // SUBSCRIPTIONS
    // ========================

    /** A new subscription has been created. */
    case SUBSCRIPTION_CREATED = 'subscription_created';

    /** An existing subscription has been cancelled. */
    case SUBSCRIPTION_CANCELLED = 'subscription_cancelled';

    /** A subscription has been successfully renewed. */
    case SUBSCRIPTION_RENEWED = 'subscription_renewed';

    /** A subscription has expired after its billing period. */
    case SUBSCRIPTION_EXPIRED = 'subscription_expired';

    /** The subscription plan has been changed (e.g., upgrade/downgrade). */
    case SUBSCRIPTION_PLAN_CHANGED = 'subscription_plan_changed';


    // ========================
    // PAYMENTS
    // ========================

    /** A payment has been successfully processed. */
    case PAYMENT_SUCCEEDED = 'payment_succeeded';

    /** A payment attempt has failed. */
    case PAYMENT_FAILED = 'payment_failed';

    /** A payment has been refunded to the user. */
    case PAYMENT_REFUNDED = 'payment_refunded';


    // ========================
    // INVOICES
    // ========================

    /** A new invoice has been generated. */
    case INVOICE_CREATED = 'invoice_created';

    /** An invoice has been successfully paid. */
    case INVOICE_PAID = 'invoice_paid';

    /** An invoice payment has failed or was not completed. */
    case INVOICE_FAILED = 'invoice_failed';


    // ========================
    // PLANS
    // ========================

    /** A new subscription plan has been created. */
    case PLAN_CREATED = 'plan_created';

    /** An existing subscription plan has been updated. */
    case PLAN_UPDATED = 'plan_updated';

    /** An existing subscription plan has been toggled. */
    case PLAN_TOGGLED = 'plan_toggled';


    // ========================
    // USER / PROFILE
    // ========================

    /** A user has updated their profile information. */
    case PROFILE_UPDATED = 'profile_updated';

    /** A user has changed their password. */
    case PASSWORD_CHANGED = 'password_changed';

    /** A user has registered in the system. */
    case USER_REGISTERED = 'user_registered';

    /** A customer profile has registered in the system. */
    case CUSTOMER_PROFILE_REGISTERED = 'customer_profile_registered';

    // ========================
    // AUTH / SECURITY
    // ========================

    /** A login attempt has failed. */
    case LOGIN_FAILED = 'login_failed';

    /** A user account has been locked due to security reasons. */
    case ACCOUNT_LOCKED = 'account_locked';

    /** An attempt to register an already existing email occurred. */
    case EMAIL_DUPLICATE_ATTEMPT = 'email_duplicate_attempt';

    /**
     * Get a human-readable label for the activity type.
     *
     * This is typically used in the UI (e.g., dashboard or activity feed).
     *
     * @return string
     */
    public function getLabel(): string
    {
        return match ($this) {
            self::SUBSCRIPTION_CREATED => 'Subscription created',
            self::SUBSCRIPTION_CANCELLED => 'Subscription cancelled',
            self::SUBSCRIPTION_RENEWED => 'Subscription renewed',
            self::SUBSCRIPTION_EXPIRED => 'Subscription expired',
            self::SUBSCRIPTION_PLAN_CHANGED => 'Subscription plan changed',

            self::PAYMENT_SUCCEEDED => 'Payment succeeded',
            self::PAYMENT_FAILED => 'Payment failed',
            self::PAYMENT_REFUNDED => 'Payment refunded',

            self::INVOICE_CREATED => 'Invoice created',
            self::INVOICE_PAID => 'Invoice paid',
            self::INVOICE_FAILED => 'Invoice failed',

            self::PLAN_CREATED => 'Plan created',
            self::PLAN_UPDATED => 'Plan updated',
            self::PLAN_TOGGLED => 'Plan toggled',

            self::PROFILE_UPDATED => 'Profile updated',
            self::PASSWORD_CHANGED => 'Password changed',

            self::USER_REGISTERED => 'User registered',
            self::CUSTOMER_PROFILE_REGISTERED => 'Customer profile registered',

            self::LOGIN_FAILED => 'Login failed',
            self::ACCOUNT_LOCKED => 'Account locked',
            self::EMAIL_DUPLICATE_ATTEMPT => 'Email duplicate attempt',
        };
    }

    /**
     * Get a UI color identifier for the activity type.
     *
     * This can be used with CSS frameworks (e.g., Bootstrap, Tailwind)
     * to visually distinguish activity types.
     *
     * @return string
     */
    public function getColor(): string
    {
        return match ($this) {
            self::PAYMENT_FAILED,
            self::INVOICE_FAILED,
            self::LOGIN_FAILED,
            self::ACCOUNT_LOCKED => 'danger',

            self::PAYMENT_SUCCEEDED,
            self::INVOICE_PAID,
            self::SUBSCRIPTION_CREATED => 'success',

            self::PAYMENT_REFUNDED => 'info',

            self::SUBSCRIPTION_CANCELLED,
            self::SUBSCRIPTION_EXPIRED => 'warning',

            default => 'secondary',
        };
    }

    /**
     * Get the default related type for this activity type.
     *
     * This can be used when creating an ActivityLog entry to automatically
     * set the related entity type based on the event.
     *
     * @return string|null Returns a string like 'User', 'Subscription', etc.
     */
    public function getRelatedType(): ?string
    {
        return match ($this) {
            self::SUBSCRIPTION_CREATED,
            self::SUBSCRIPTION_CANCELLED,
            self::SUBSCRIPTION_RENEWED,
            self::SUBSCRIPTION_EXPIRED,
            self::SUBSCRIPTION_PLAN_CHANGED => 'Subscription',

            self::PAYMENT_SUCCEEDED,
            self::PAYMENT_FAILED,
            self::PAYMENT_REFUNDED => 'Payment',

            self::INVOICE_CREATED,
            self::INVOICE_PAID,
            self::INVOICE_FAILED => 'Invoice',

            self::PLAN_CREATED,
            self::PLAN_UPDATED,
            self::PLAN_TOGGLED => 'Plan',

            self::PROFILE_UPDATED,
            self::PASSWORD_CHANGED => 'User',

            self::USER_REGISTERED,
            self::CUSTOMER_PROFILE_REGISTERED => 'User',

            self::LOGIN_FAILED,
            self::ACCOUNT_LOCKED => 'User',

            default => null,
        };
    }

    /**
     * Determine whether the activity represents a failure event.
     *
     * @return bool
     */
    public function isFailure(): bool
    {
        return in_array($this, [
            self::PAYMENT_FAILED,
            self::INVOICE_FAILED,
            self::LOGIN_FAILED,
        ], true);
    }

    /**
     * Determine whether the activity is related to billing.
     *
     * Billing-related activities include subscriptions, payments, and invoices.
     *
     * @return bool
     */
    public function isBillingRelated(): bool
    {
        return str_starts_with($this->value, 'payment_')
            || str_starts_with($this->value, 'invoice_')
            || str_starts_with($this->value, 'subscription_');
    }

}