<?php

namespace App\Entity;

use Symfony\Component\Serializer\Annotation\Groups;

class Product
{
    /**
     * @var int
     * @Groups({"product:read"})
     */
    private $id;

    /**
     * @var string
     * @Groups({"product:read", "product:write"})
     */
    private $name;

    /**
     * @var float
     * @Groups({"product:read", "product:write"})
     */
    private $price;

    public function __construct(int $id = 0, string $name = '', float $price = 0.0)
    {
        $this->id = $id;
        $this->name = $name;
        $this->price = $price;
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getPrice(): float
    {
        return $this->price;
    }

    public function setName(string $name): void
    {
        $this->name = $name;
    }

    public function setPrice(float $price): void
    {
        $this->price = $price;
    }

}