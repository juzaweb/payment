<?php

namespace Juzaweb\Modules\Payment\Tests\Unit;

use Juzaweb\Modules\Payment\Contracts\PaymentManager;
use Juzaweb\Modules\Payment\Tests\TestCase;

class PaymentManagerTest extends TestCase
{
    public function test_drivers_registered()
    {
        $manager = app(PaymentManager::class);

        $drivers = $manager->drivers();

        $this->assertArrayHasKey('PayPal', $drivers);
        $this->assertArrayHasKey('Stripe', $drivers);
        $this->assertArrayHasKey('Custom', $drivers);
    }
}
