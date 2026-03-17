<?php

namespace App\Entity;

use App\Entity\User;
use App\Repository\CustomerProfileRepository;
use DateTimeImmutable;

/**
 * Class CustomerProfile
 *
 * Represents the extended personal profile of a user.
 *
 * Stores personal information such as name, phone number,
 * and address details associated with a user account.
 *
 * Note: ORM mapping is defined in XML (CustomerProfile.orm.xml)
 */
class CustomerProfile
{
    /**
     * The unique identifier of the customer profile.
     *
     * @var int|null
     */
    private ?int $id = null;

    /**
     * The first name of the customer.
     *
     * @var string
     */
    private string $firstName = '';

    /**
     * The surname (last name) of the customer.
     *
     * @var string
     */
    private string $surname = '';

    /**
     * The phone number of the customer.
     *
     * @var string
     */
    private string $phone = '';

    /**
     * The international country prefix code for the phone number.
     * Example: +351, +44, +1
     *
     * @var string
     */
    private string $countryPrefixCode = '';

    /**
     * The primary address line.
     *
     * @var string
     */
    private string $primaryAddress = '';

    /**
     * The secondary address line (apartment, suite, unit, etc.).
     *
     * @var string|null
     */
    private ?string $secondaryAddress = null;

    /**
     * The city of the address.
     *
     * @var string
     */
    private string $city = '';

    /**
     * The state, region, or district of the address.
     *
     * @var string|null
     */
    private ?string $state = null;

    /**
     * The postal or ZIP code of the address.
     *
     * @var string
     */
    private string $postalCode = '';

    /**
     * The country of the address (ISO code or full name).
     *
     * @var string
     */
    private string $country = '';

    /**
     * @var User
     */
    private $user;

    /**
     * Timestamp of when the profile was created.
     *
     * @var DateTimeImmutable|null
     */
    private ?DateTimeImmutable $createdAt = null;

    /**
     * Timestamp of the last update of the profile.
     *
     * @var DateTimeImmutable|null
     */
    private ?DateTimeImmutable $updatedAt = null;

    /**
     * Constructor.
     *
     * Initializes the createdAt timestamp.
     */
    public function __construct()
    {
        $this->createdAt = new DateTimeImmutable();
    }

    /**
     * Get the profile ID.
     */
    public function getId(): ?int
    {
        return $this->id;
    }

    /**
     * Get the first name.
     */
    public function getFirstName(): string
    {
        return $this->firstName;
    }

    /**
     * Set the first name.
     */
    public function setFirstName(string $firstName): static
    {
        $this->firstName = $firstName;

        return $this;
    }

    /**
     * Get the surname.
     */
    public function getSurname(): string
    {
        return $this->surname;
    }

    /**
     * Set the surname.
     */
    public function setSurname(string $surname): static
    {
        $this->surname = $surname;

        return $this;
    }

    /**
     * Get the phone number.
     */
    public function getPhone(): string
    {
        return $this->phone;
    }

    /**
     * Set the phone number.
     */
    public function setPhone(string $phone): static
    {
        $this->phone = $phone;

        return $this;
    }

    /**
     * Get the country prefix code.
     */
    public function getCountryPrefixCode(): string
    {
        return $this->countryPrefixCode;
    }

    /**
     * Set the country prefix code.
     */
    public function setCountryPrefixCode(string $countryPrefixCode): static
    {
        $this->countryPrefixCode = $countryPrefixCode;

        return $this;
    }

    /**
     * Get the primary address.
     */
    public function getPrimaryAddress(): string
    {
        return $this->primaryAddress;
    }

    /**
     * Set the primary address.
     */
    public function setPrimaryAddress(string $primaryAddress): static
    {
        $this->primaryAddress = $primaryAddress;

        return $this;
    }

    /**
     * Get the secondary address.
     */
    public function getSecondaryAddress(): ?string
    {
        return $this->secondaryAddress;
    }

    /**
     * Set the secondary address.
     */
    public function setSecondaryAddress(?string $secondaryAddress): static
    {
        $this->secondaryAddress = $secondaryAddress;

        return $this;
    }

    /**
     * Get the city.
     */
    public function getCity(): string
    {
        return $this->city;
    }

    /**
     * Set the city.
     */
    public function setCity(string $city): static
    {
        $this->city = $city;

        return $this;
    }

    /**
     * Get the state.
     */
    public function getState(): ?string
    {
        return $this->state;
    }

    /**
     * Set the state.
     */
    public function setState(?string $state): static
    {
        $this->state = $state;

        return $this;
    }

    /**
     * Get the postal code.
     */
    public function getPostalCode(): string
    {
        return $this->postalCode;
    }

    /**
     * Set the postal code.
     */
    public function setPostalCode(string $postalCode): static
    {
        $this->postalCode = $postalCode;

        return $this;
    }

    /**
     * Get the country.
     */
    public function getCountry(): string
    {
        return $this->country;
    }

    /**
     * Set the country.
     */
    public function setCountry(string $country): static
    {
        $this->country = $country;

        return $this;
    }

    /**
     * Get the created timestamp.
     */
    public function getCreatedAt(): ?DateTimeImmutable
    {
        return $this->createdAt;
    }

    /**
     * Get the updated timestamp.
     */
    public function getUpdatedAt(): ?DateTimeImmutable
    {
        return $this->updatedAt;
    }

    /**
     * Set the updated timestamp.
     */
    public function setUpdatedAt(?DateTimeImmutable $updatedAt): static
    {
        $this->updatedAt = $updatedAt;

        return $this;
    }

    /**
     * @return User
     */
    public function getUser(): User
    {
        return $this->user;
    }

    /**
     * @param string $user
     *
     * @return $this
     */
    public function setUser(User $user): self
    {
        $this->user = $user;
        return $this;
    }

}