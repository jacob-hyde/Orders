<?php

namespace JacobHyde\Orders\Tests\Unit\Models;

use JacobHyde\Orders\Models\Cart;
use JacobHyde\Orders\Tests\TestCase;

class CartTest extends TestCase
{
    public function testCartCanBeCreated()
    {
        $cart = Cart::factory()->make();

        $this->assertInstanceOf(Cart::class, $cart);
    }
}
