<?php

namespace Tests\Unit;

use App\Models\Order;
use App\Support\PaymentOrderVerifier;
use Tests\TestCase;

class PaymentOrderVerifierTest extends TestCase
{
    public function test_amounts_match_within_one_cent(): void
    {
        $order = new Order;
        $order->forceFill(['total' => 19.99, 'currency' => 'EUR']);

        $this->assertTrue(PaymentOrderVerifier::amountsMatch($order, 19.99, 'EUR'));
        $this->assertTrue(PaymentOrderVerifier::amountsMatch($order, 19.989, 'eur'));
        $this->assertFalse(PaymentOrderVerifier::amountsMatch($order, 9.99, 'EUR'));
        $this->assertFalse(PaymentOrderVerifier::amountsMatch($order, 19.99, 'USD'));
    }

    public function test_paypal_payload_rejects_order_id_mismatch(): void
    {
        $order = new Order;
        $order->forceFill(['id' => 10, 'total' => 5.00, 'currency' => 'EUR']);

        $payload = [
            'status' => 'COMPLETED',
            'purchase_units' => [[
                'custom_id' => '99',
                'payments' => [
                    'captures' => [[
                        'id' => 'CAP-1',
                        'amount' => ['value' => '5.00', 'currency_code' => 'EUR'],
                    ]],
                ],
            ]],
        ];

        $this->assertNull(PaymentOrderVerifier::verifyPayPalOrderPayload($payload, $order));
    }

    public function test_paypal_payload_accepts_matching_order(): void
    {
        $order = new Order;
        $order->forceFill(['id' => 10, 'total' => 5.00, 'currency' => 'EUR']);

        $payload = [
            'status' => 'COMPLETED',
            'id' => 'ORDER-1',
            'purchase_units' => [[
                'custom_id' => '10',
                'payments' => [
                    'captures' => [[
                        'id' => 'CAP-1',
                        'amount' => ['value' => '5.00', 'currency_code' => 'EUR'],
                    ]],
                ],
            ]],
        ];

        $result = PaymentOrderVerifier::verifyPayPalOrderPayload($payload, $order);

        $this->assertSame(['external_id' => 'CAP-1'], $result);
    }
}
