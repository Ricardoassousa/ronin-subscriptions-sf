<?php

namespace App\Entity;

/**
 * Class SubscriptionPlanSearch
 *
 * Data Transfer Object used to filter subscription plans.
 *
 * This DTO is used in combination with a Symfony Form (GET method)
 * and passed to the repository to build dynamic queries.
 */
class SubscriptionPlanSearch
{
    /**
     * Search term (name or slug).
     *
     * @var string|null
     */
    private ?string $search = null;

    /**
     * Filter by active status.
     *
     * @var bool|null
     */
    private ?bool $isActive = null;

    /**
     * Filter by billing interval (month/year).
     *
     * @var string|null
     */
    private ?string $billingInterval = null;

    /**
     * Minimum price filter.
     *
     * @var float|null
     */
    private ?float $minPrice = null;

    /**
     * Maximum price filter.
     *
     * @var float|null
     */
    private ?float $maxPrice = null;

    /**
     * Filter featured plans.
     *
     * @var bool|null
     */
    private ?bool $isFeatured = null;

    /**
     * Minimum trial days.
     *
     * @var int|null
     */
    private ?int $minTrialDays = null;

    /**
     * Maximum trial days.
     *
     * @var int|null
     */
    private ?int $maxTrialDays = null;

    /**
     * Get search term (name or slug).
     *
     * @return string|null
     */
    public function getSearch(): ?string
    {
        return $this->search;
    }

    /**
     * Set search term.
     *
     * @param string|null $search
     * @return static
     */
    public function setSearch(?string $search): static
    {
        $this->search = $search;
        return $this;
    }

    /**
     * Get active status filter.
     *
     * @return bool|null
     */
    public function getIsActive(): ?bool
    {
        return $this->isActive;
    }

    /**
     * Set active status filter.
     *
     * @param bool|null $isActive
     * @return static
     */
    public function setIsActive(?bool $isActive): static
    {
        $this->isActive = $isActive;
        return $this;
    }

    /**
     * Get billing interval filter.
     *
     * @return string|null
     */
    public function getBillingInterval(): ?string
    {
        return $this->billingInterval;
    }

    /**
     * Set billing interval filter.
     *
     * @param string|null $billingInterval
     * @return static
     */
    public function setBillingInterval(?string $billingInterval): static
    {
        $this->billingInterval = $billingInterval;
        return $this;
    }

    /**
     * Get minimum price filter.
     *
     * @return float|null
     */
    public function getMinPrice(): ?float
    {
        return $this->minPrice;
    }

    /**
     * Set minimum price filter.
     *
     * @param float|null $minPrice
     * @return static
     */
    public function setMinPrice(?float $minPrice): static
    {
        $this->minPrice = $minPrice;
        return $this;
    }

    /**
     * Get maximum price filter.
     *
     * @return float|null
     */
    public function getMaxPrice(): ?float
    {
        return $this->maxPrice;
    }

    /**
     * Set maximum price filter.
     *
     * @param float|null $maxPrice
     * @return static
     */
    public function setMaxPrice(?float $maxPrice): static
    {
        $this->maxPrice = $maxPrice;
        return $this;
    }

    /**
     * Get featured filter.
     *
     * @return bool|null
     */
    public function getIsFeatured(): ?bool
    {
        return $this->isFeatured;
    }

    /**
     * Set featured filter.
     *
     * @param bool|null $isFeatured
     * @return static
     */
    public function setIsFeatured(?bool $isFeatured): static
    {
        $this->isFeatured = $isFeatured;
        return $this;
    }

    /**
     * Get minimum trial days filter.
     *
     * @return int|null
     */
    public function getMinTrialDays(): ?int
    {
        return $this->minTrialDays;
    }

    /**
     * Set minimum trial days filter.
     *
     * @param int|null $minTrialDays
     * @return static
     */
    public function setMinTrialDays(?int $minTrialDays): static
    {
        $this->minTrialDays = $minTrialDays;
        return $this;
    }

    /**
     * Get maximum trial days filter.
     *
     * @return int|null
     */
    public function getMaxTrialDays(): ?int
    {
        return $this->maxTrialDays;
    }

    /**
     * Set maximum trial days filter.
     *
     * @param int|null $maxTrialDays
     * @return static
     */
    public function setMaxTrialDays(?int $maxTrialDays): static
    {
        $this->maxTrialDays = $maxTrialDays;
        return $this;
    }

}