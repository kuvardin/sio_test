<?php

declare(strict_types=1);

namespace App\DataFixtures;

use App\Entity\Coupon;
use App\Entity\Product;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;

class AppFixtures extends Fixture
{
    private const PRODUCTS = [
        'iPhone' => 100,
        'Наушники' => 20,
        'Чехол' => 10,
    ];

    private const COUPONS = [
        'P10' => 10,
        'P100' => 100,
    ];

    public function load(ObjectManager $manager): void
    {
        foreach (self::PRODUCTS as $product_name => $price) {
            $product = new Product(
                name: $product_name,
                price: $price,
                available: true,
            );

            $manager->persist($product);
        }

        foreach (self::COUPONS as $coupon_code => $coupon_discount_percents) {
            $coupon = new Coupon(
                value: $coupon_code,
                discount_percents: $coupon_discount_percents,
                discount_value: null,
                activations_max: null,
                active_until: null,
            );

            $manager->persist($coupon);
        }

        $manager->flush();
    }
}
