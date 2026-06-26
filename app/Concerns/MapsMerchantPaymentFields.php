<?php

namespace App\Concerns;

use App\Models\PaymentGateway;

/**
 * Formular-Hilfsfelder (merchant_*) ↔ verschlüsseltes config[] auf dem PaymentGateway.
 */
trait MapsMerchantPaymentFields
{
    /**
     * @return array<string, mixed>
     */
    protected function mapPaymentGatewayFormDataBeforeFill(array $data, ?PaymentGateway $record): array
    {
        if (! $record instanceof PaymentGateway) {
            return $data;
        }

        $data['merchant_stripe_secret'] = (string) (
            $record->getConfigValue('secret_key')
            ?? $record->getConfigValue('secret')
            ?? $record->getConfigValue('api_key')
            ?? ''
        );

        $data['merchant_paypal_client_id'] = (string) ($record->getConfigValue('client_id') ?? '');
        $data['merchant_paypal_secret'] = (string) (
            $record->getConfigValue('client_secret')
            ?? $record->getConfigValue('secret')
            ?? ''
        );

        $data['merchant_sumup_key'] = (string) (
            $record->getConfigValue('api_key')
            ?? $record->getConfigValue('token')
            ?? ''
        );

        $data['merchant_sumup_merchant_code'] = (string) ($record->getConfigValue('merchant_code') ?? '');

        return $data;
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function mapPaymentGatewayFormDataBeforeSave(array $data): array
    {
        $config = is_array($data['config'] ?? null) ? $data['config'] : [];
        $code = strtolower((string) ($data['code'] ?? ''));

        if ($code === 'stripe' && filled($data['merchant_stripe_secret'] ?? null)) {
            $config['secret_key'] = trim((string) $data['merchant_stripe_secret']);
        }

        if ($code === 'paypal') {
            if (filled($data['merchant_paypal_client_id'] ?? null)) {
                $config['client_id'] = trim((string) $data['merchant_paypal_client_id']);
            }
            if (filled($data['merchant_paypal_secret'] ?? null)) {
                $config['client_secret'] = trim((string) $data['merchant_paypal_secret']);
            }
        }

        if ($code === 'sumup') {
            if (filled($data['merchant_sumup_key'] ?? null)) {
                $config['api_key'] = trim((string) $data['merchant_sumup_key']);
            }
            if (filled($data['merchant_sumup_merchant_code'] ?? null)) {
                $config['merchant_code'] = trim((string) $data['merchant_sumup_merchant_code']);
            }
        }

        unset(
            $data['merchant_stripe_secret'],
            $data['merchant_paypal_client_id'],
            $data['merchant_paypal_secret'],
            $data['merchant_sumup_key'],
            $data['merchant_sumup_merchant_code'],
        );

        $data['config'] = $config;

        return $data;
    }
}
