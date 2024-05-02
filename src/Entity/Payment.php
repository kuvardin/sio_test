<?php

declare(strict_types=1);

namespace App\Entity;

use App\Repository\PaymentRepository;
use DateTimeImmutable;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: PaymentRepository::class)]
class Payment
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: false)]
    private Product $product;

    #[ORM\Column(length: 255)]
    private string $tax_number;

    #[ORM\Column]
    private float $tax_percent;

    #[ORM\ManyToOne]
    private ?Coupon $coupon;

    #[ORM\Column(length: 255)]
    private string $payment_system_code;

    #[ORM\Column]
    private DateTimeImmutable $created_at;

    public function __construct(
        Product $product,
        string $tax_number,
        float $tax_percent,
        ?Coupon $coupon,
        string $payment_system_code,
        DateTimeImmutable $created_at = null,
    )
    {
        $this->product = $product;
        $this->tax_number = $tax_number;
        $this->tax_percent = $tax_percent;
        $this->coupon = $coupon;
        $this->payment_system_code = $payment_system_code;
        $this->created_at = $created_at ?? new DateTimeImmutable();
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function getProduct(): Product
    {
        return $this->product;
    }

    public function setProduct(Product $product): static
    {
        $this->product = $product;

        return $this;
    }

    public function getTaxNumber(): string
    {
        return $this->tax_number;
    }

    public function setTaxNumber(string $tax_number): static
    {
        $this->tax_number = $tax_number;
        return $this;
    }

    public function getTaxPercent(): float
    {
        return $this->tax_percent;
    }

    public function setTaxPercent(float $tax_percent): static
    {
        $this->tax_percent = $tax_percent;
        return $this;
    }

    public function getCoupon(): ?Coupon
    {
        return $this->coupon;
    }

    public function setCoupon(?Coupon $coupon): static
    {
        $this->coupon = $coupon;
        return $this;
    }

    public function getPaymentSystemCode(): string
    {
        return $this->payment_system_code;
    }

    public function setPaymentSystemCode(string $payment_system_code): static
    {
        $this->payment_system_code = $payment_system_code;
        return $this;
    }

    public function getCreatedAt(): DateTimeImmutable
    {
        return $this->created_at;
    }

    public function setCreatedAt(DateTimeImmutable $created_at): static
    {
        $this->created_at = $created_at;
        return $this;
    }
}
