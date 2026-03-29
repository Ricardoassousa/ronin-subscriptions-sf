<?php

namespace App\Entity;

use App\Entity\CustomerProfile;
use App\Entity\Payment;
use App\Entity\Subscription;
use App\Repository\UserRepository;
use DateTimeImmutable;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\UserInterface;

/**
 * Class User
 *
 * Represents a user in the system.
 *
 * Note: ORM mapping is defined in XML (User.orm.xml)
 */
class User implements UserInterface, PasswordAuthenticatedUserInterface
{
    /**
     * The unique identifier of the user.
     *
     * @var int|null
     */
    private ?int $id = null;

    /**
     * The email address of the user. Must be unique.
     *
     * @var string|null
     */
    private ?string $email = null;

    /**
     * The roles assigned to the user.
     * Always contains at least 'ROLE_USER'.
     *
     * @var string[]
     */
    private array $roles = [];

    /**
     * The hashed password of the user.
     *
     * @var string|null
     */
    private ?string $password = null;

    /**
     * Timestamp of when the user was created.
     *
     * @var DateTimeImmutable|null
     */
    private ?DateTimeImmutable $createdAt = null;

    /**
     * Timestamp of the last update of the user.
     *
     * @var DateTimeImmutable|null
     */
    private ?DateTimeImmutable $updatedAt = null;

    /**
     * Timestamp of the last login of the user.
     *
     * @var DateTimeImmutable|null
     */
    private ?DateTimeImmutable $lastLoginAt = null;

    /**
     * @var CustomerProfile
     */
    private $customerProfile;

    /**
     * @var Collection<int, Subscription>
     */
    private Collection $subscriptions;

    /**
     * @var Collection<int, Payment>
     */
    private Collection $payments;

    /**
     * Constructor.
     *
     * Initializes default values for roles and createdAt timestamp.
     */
    public function __construct()
    {
        $this->roles = ['ROLE_USER'];
        $this->createdAt = new DateTimeImmutable();
        $this->subscriptions = new ArrayCollection();
        $this->payments = new ArrayCollection();
    }

    /**
     * Get the user ID.
     */
    public function getId(): ?int
    {
        return $this->id;
    }

    /**
     * Get the email of the user.
     */
    public function getEmail(): ?string
    {
        return $this->email;
    }

    /**
     * Set the email of the user.
     *
     * @param string $email
     * @return static
     */
    public function setEmail(string $email): static
    {
        $this->email = $email;
        return $this;
    }

    /**
     * Get the roles of the user.
     *
     * Always guarantees ROLE_USER.
     */
    public function getRoles(): array
    {
        $roles = $this->roles;
        $roles[] = 'ROLE_USER';

        return array_unique($roles);
    }

    /**
     * Set the roles of the user.
     *
     * @param string[] $roles
     * @return static
     */
    public function setRoles(array $roles): static
    {
        $this->roles = $roles;
        return $this;
    }

    /**
     * Get the hashed password.
     */
    public function getPassword(): ?string
    {
        return $this->password;
    }

    /**
     * Set the hashed password.
     *
     * @param string $password
     * @return static
     */
    public function setPassword(string $password): static
    {
        $this->password = $password;
        return $this;
    }

    /**
     * Returns the identifier for Symfony security.
     *
     * @return string
     */
    public function getUserIdentifier(): string
    {
        return (string) $this->email;
    }

    /**
     * Returns the username.
     * Required by UserInterface for backwards compatibility.
     *
     * @deprecated since Symfony 5.3, use getUserIdentifier() instead
     */
    public function getUsername(): string
    {
        return $this->email ?? '';
    }

    /**
     * Returns the salt that was originally used to encode the password.
     *
     * Not needed when using modern algorithms (bcrypt, sodium, argon2i).
     */
    public function getSalt(): ?string
    {
        return null;
    }

    /**
     * Removes sensitive data from the user.
     */
    public function eraseCredentials(): void
    {
        // e.g. clear plainPassword if stored temporarily
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
     *
     * @param DateTimeImmutable|null $updatedAt
     * @return static
     */
    public function setUpdatedAt(?DateTimeImmutable $updatedAt): static
    {
        $this->updatedAt = $updatedAt;
        return $this;
    }

    /**
     * Get the last login timestamp.
     */
    public function getLastLoginAt(): ?DateTimeImmutable
    {
        return $this->lastLoginAt;
    }

    /**
     * Set the last login timestamp.
     *
     * @param DateTimeImmutable|null $lastLoginAt
     * @return static
     */
    public function setLastLoginAt(?DateTimeImmutable $lastLoginAt): static
    {
        $this->lastLoginAt = $lastLoginAt;
        return $this;
    }

    /**
     * @return CustomerProfile|null
     */
    public function getCustomerProfile(): ?CustomerProfile
    {
        return $this->customerProfile;
    }

    /**
     * @param CustomerProfile|null $customerProfile
     *
     * @return $this
     */
    public function setCustomerProfile(?CustomerProfile $customerProfile): self
    {
        if ($customerProfile->getUser() != $this) {
            $customerProfile->setUser($this);
        }
        $this->customerProfile = $customerProfile;

        return $this;
    }

    /**
     * @return Collection<int, Subscription>
     */
    public function getSubscriptions(): Collection
    {
        return $this->subscriptions;
    }

    public function addSubscription(Subscription $subscription): static
    {
        if (!$this->subscriptions->contains($subscription)) {
            $this->subscriptions->add($subscription);
            $subscription->setUser($this);
        }

        return $this;
    }

    public function removeSubscription(Subscription $subscription): static
    {
        if ($this->subscriptions->removeElement($subscription)) {
            // set the owning side to null (unless already changed)
            if ($subscription->getUser() === $this) {
                $subscription->setUser(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, Payment>
     */
    public function getPayments(): Collection
    {
        return $this->payments;
    }

    public function addPayment(Payment $payment): static
    {
        if (!$this->payments->contains($payment)) {
            $this->payments->add($payment);
            $payment->setUser($this);
        }

        return $this;
    }

    public function removePayment(Payment $payment): static
    {
        if ($this->payments->removeElement($payment)) {
            // set the owning side to null (unless already changed)
            if ($payment->getUser() === $this) {
                $payment->setUser(null);
            }
        }

        return $this;
    }

}