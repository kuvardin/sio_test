<?php

namespace App\Entity;

use App\Repository\CouponRepository;
use DateTimeImmutable;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: CouponRepository::class)]
class Coupon
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255, unique: true)]
    private string $value;

    #[ORM\Column(nullable: true)]
    private ?int $discount_percents = null;

    #[ORM\Column(nullable: true)]
    private ?float $discount_value = null;

    #[ORM\Column]
    private int $activations_number;

    #[ORM\Column(nullable: true)]
    private ?int $activations_max = null;

    #[ORM\Column(nullable: true)]
    private ?DateTimeImmutable $active_until = null;

    #[ORM\Column]
    private DateTimeImmutable $created_at;

    public function __construct(
        string $value,
        ?int $discount_percents,
        ?float $discount_value,
        ?int $activations_max,
        ?DateTimeImmutable $active_until,
        DateTimeImmutable $created_at = null,
    )
    {
        $this->value = $value;
        $this->discount_percents = $discount_percents;
        $this->discount_value = $discount_value;
        $this->activations_number = 0;
        $this->activations_max = $activations_max;
        $this->active_until = $active_until;
        $this->created_at = $created_at;
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function getValue(): string
    {
        return $this->value;
    }

    public function setValue(string $value): static
    {
        $this->value = $value;
        return $this;
    }

    public function getDiscountPercents(): ?int
    {
        return $this->discount_percents;
    }

    public function getDiscountValue(): ?float
    {
        return $this->discount_value;
    }

    public function setDiscount(?int $percents, ?float $value): static
    {
        if (!(($percents === null) XOR ($value === null))) {
            throw new RuntimeException('Discount must have only percents or value');
        }

        $this->discount_percents = $percents;
        $this->discount_value = $value;
        return $this;
    }

    public function getActivationsNumber(): int
    {
        return $this->activations_number;
    }

    public function setActivationsNumber(int $activations_number): static
    {
        $this->activations_number = $activations_number;
        return $this;
    }

    public function getActivationsMax(): ?int
    {
        return $this->activations_max;
    }

    public function setActivationsMax(?int $activations_max): static
    {
        $this->activations_max = $activations_max;
        return $this;
    }

    public function getActiveUntil(): ?DateTimeImmutable
    {
        return $this->active_until;
    }

    public function setActiveUntil(?DateTimeImmutable $active_until): static
    {
        $this->active_until = $active_until;
        return $this;
    }

    public function getCreatedAt(): ?DateTimeImmutable
    {
        return $this->created_at;
    }

    public function setCreatedAt(DateTimeImmutable $created_at): static
    {
        $this->created_at = $created_at;
        return $this;
    }

    public function isActive(int $current_timestamp = null): bool
    {
        return ($this->active_until === null || $this->active_until->getTimestamp() < ($current_timestamp ?? time()))
            && ($this->activations_max === null || $this->activations_max < $this->activations_number);
    }

}
