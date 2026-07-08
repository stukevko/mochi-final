@php
    use App\Models\Setting;
    use App\Support\MoneyFormatter;
    use App\Support\ShopBranding;

    $order->loadMissing('items');

    $shopName = ShopBranding::displayName();
    $shopAddress = trim((string) Setting::get('shop_address', ''));
    $shopEmail = trim((string) Setting::get('shop_email', ''));
    $shopPhone = trim((string) Setting::get('shop_phone', ''));
    $taxId = trim((string) Setting::get('tax_id', ''));
    $logoPath = ShopBranding::logoPathForPdf();

    $format = static fn (float|string|null $amount): string => MoneyFormatter::format((float) $amount);

    $billing = is_array($order->billing_address) ? $order->billing_address : [];
    $shipping = is_array($order->shipping_address) ? $order->shipping_address : $billing;

    $customerName = trim(implode(' ', array_filter([
        $billing['first_name'] ?? $shipping['first_name'] ?? '',
        $billing['last_name'] ?? $shipping['last_name'] ?? '',
    ])));

    $customerEmail = $order->customerEmail() ?? ($billing['email'] ?? $shipping['email'] ?? '');

    $addressLine = static function (array $address): string {
        $parts = array_filter([
            trim((string) ($address['street'] ?? '')),
            trim(implode(' ', array_filter([
                trim((string) ($address['zip'] ?? '')),
                trim((string) ($address['city'] ?? '')),
            ]))),
            strtoupper(trim((string) ($address['country'] ?? ''))),
        ]);

        return implode(', ', $parts);
    };

    $paymentLabel = match ((string) $order->payment_method) {
        'sumup' => 'SumUp',
        'prepayment' => 'Vorkasse',
        default => ucfirst((string) $order->payment_method),
    };
@endphp
<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="utf-8">
    <title>Rechnung {{ $order->getInvoiceNumber() }}</title>
    <style>
        * { box-sizing: border-box; }
        body {
            font-family: DejaVu Sans, sans-serif;
            font-size: 11px;
            color: #1a1a1a;
            line-height: 1.45;
            margin: 0;
            padding: 32px 36px;
        }
        .header {
            width: 100%;
            margin-bottom: 28px;
            border-bottom: 2px solid #ff7a1f;
            padding-bottom: 16px;
        }
        .header td { vertical-align: top; }
        .shop-name {
            font-size: 18px;
            font-weight: bold;
            color: #111;
            margin: 0 0 6px;
        }
        .muted { color: #555; }
        .title {
            font-size: 22px;
            font-weight: bold;
            margin: 0 0 4px;
            text-align: right;
        }
        .meta-table {
            width: 100%;
            margin: 20px 0 24px;
        }
        .meta-table td {
            width: 50%;
            vertical-align: top;
            padding: 0 8px 0 0;
        }
        .box {
            border: 1px solid #ddd;
            border-radius: 6px;
            padding: 12px 14px;
            background: #fafafa;
        }
        .box h3 {
            margin: 0 0 8px;
            font-size: 11px;
            text-transform: uppercase;
            letter-spacing: 0.04em;
            color: #666;
        }
        .items {
            width: 100%;
            border-collapse: collapse;
            margin-top: 8px;
        }
        .items th {
            background: #f3f3f3;
            border-bottom: 1px solid #ccc;
            padding: 8px 6px;
            text-align: left;
            font-size: 10px;
            text-transform: uppercase;
            letter-spacing: 0.03em;
        }
        .items td {
            border-bottom: 1px solid #e8e8e8;
            padding: 8px 6px;
            vertical-align: top;
        }
        .items .num { text-align: right; white-space: nowrap; }
        .totals {
            width: 42%;
            margin-left: auto;
            margin-top: 16px;
            border-collapse: collapse;
        }
        .totals td {
            padding: 5px 0;
        }
        .totals .label { color: #555; }
        .totals .amount { text-align: right; font-weight: bold; }
        .totals .grand td {
            border-top: 2px solid #333;
            padding-top: 8px;
            font-size: 13px;
        }
        .footer {
            margin-top: 36px;
            padding-top: 12px;
            border-top: 1px solid #ddd;
            font-size: 9px;
            color: #666;
        }
        .logo { max-height: 52px; max-width: 180px; }
    </style>
</head>
<body>
    <table class="header" cellpadding="0" cellspacing="0">
        <tr>
            <td style="width: 55%;">
                @if ($logoPath)
                    <img src="{{ $logoPath }}" alt="{{ $shopName }}" class="logo">
                @else
                    <p class="shop-name">{{ $shopName }}</p>
                @endif
                @if ($shopAddress !== '')
                    <p class="muted" style="margin: 8px 0 0; white-space: pre-line;">{{ $shopAddress }}</p>
                @endif
                @if ($shopEmail !== '' || $shopPhone !== '')
                    <p class="muted" style="margin: 6px 0 0;">
                        @if ($shopEmail !== ''){{ $shopEmail }}@endif
                        @if ($shopEmail !== '' && $shopPhone !== '') · @endif
                        @if ($shopPhone !== ''){{ $shopPhone }}@endif
                    </p>
                @endif
                @if ($taxId !== '')
                    <p class="muted" style="margin: 4px 0 0;">USt-IdNr.: {{ $taxId }}</p>
                @endif
            </td>
            <td style="width: 45%; text-align: right;">
                <p class="title">Rechnung</p>
                <p class="muted" style="margin: 0;">Nr. {{ $order->getInvoiceNumber() }}</p>
                <p class="muted" style="margin: 4px 0 0;">Bestellnr. {{ $order->order_number }}</p>
                <p class="muted" style="margin: 4px 0 0;">Datum: {{ $order->created_at?->format('d.m.Y') }}</p>
            </td>
        </tr>
    </table>

    <table class="meta-table" cellpadding="0" cellspacing="0">
        <tr>
            <td>
                <div class="box">
                    <h3>Rechnungsempfänger</h3>
                    @if ($customerName !== '')
                        <p style="margin: 0 0 4px; font-weight: bold;">{{ $customerName }}</p>
                    @endif
                    @if ($customerEmail !== '')
                        <p style="margin: 0 0 4px;" class="muted">{{ $customerEmail }}</p>
                    @endif
                    <p style="margin: 0;" class="muted">{{ $addressLine($billing !== [] ? $billing : $shipping) }}</p>
                </div>
            </td>
            <td>
                <div class="box">
                    <h3>Lieferadresse</h3>
                    @if ($customerName !== '')
                        <p style="margin: 0 0 4px; font-weight: bold;">{{ $customerName }}</p>
                    @endif
                    <p style="margin: 0;" class="muted">{{ $addressLine($shipping) }}</p>
                </div>
            </td>
        </tr>
    </table>

    <table class="items" cellpadding="0" cellspacing="0">
        <thead>
            <tr>
                <th style="width: 42%;">Artikel</th>
                <th style="width: 14%;">SKU</th>
                <th class="num" style="width: 10%;">Menge</th>
                <th class="num" style="width: 17%;">Einzelpreis</th>
                <th class="num" style="width: 17%;">Summe</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($order->items as $item)
                <tr>
                    <td>
                        <strong>{{ $item->product_name }}</strong>
                        @if (filled($item->variant_name))
                            <br><span class="muted">{{ $item->variant_name }}</span>
                        @endif
                    </td>
                    <td class="muted">{{ $item->sku ?: '—' }}</td>
                    <td class="num">{{ (int) $item->quantity }}</td>
                    <td class="num">{{ $format($item->unit_price) }}</td>
                    <td class="num">{{ $format($item->total_price) }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="5" class="muted">Keine Positionen vorhanden.</td>
                </tr>
            @endforelse
        </tbody>
    </table>

    <table class="totals" cellpadding="0" cellspacing="0">
        <tr>
            <td class="label">Zwischensumme (netto)</td>
            <td class="amount">{{ $format($order->subtotal) }}</td>
        </tr>
        <tr>
            <td class="label">MwSt.</td>
            <td class="amount">{{ $format($order->tax) }}</td>
        </tr>
        @if ((float) $order->shipping_cost > 0)
            <tr>
                <td class="label">Versand</td>
                <td class="amount">{{ $format($order->shipping_cost) }}</td>
            </tr>
        @endif
        @if ((float) $order->discount > 0)
            <tr>
                <td class="label">Rabatt</td>
                <td class="amount">−{{ $format($order->discount) }}</td>
            </tr>
        @endif
        <tr class="grand">
            <td>Gesamtbetrag</td>
            <td class="amount">{{ $format($order->total) }}</td>
        </tr>
    </table>

    <p style="margin: 24px 0 0;" class="muted">
        Zahlungsart: {{ $paymentLabel }} ·
        Zahlungsstatus: {{ $order->payment_status_label }}
    </p>

    @if (filled($order->notes))
        <p style="margin: 12px 0 0;" class="muted">
            <strong>Kundennotiz:</strong> {{ $order->notes }}
        </p>
    @endif

    <div class="footer">
        <p style="margin: 0;">
            Vielen Dank für deinen Einkauf bei {{ $shopName }}.
            Bei Fragen zu dieser Rechnung wende dich bitte an
            {{ $shopEmail !== '' ? $shopEmail : 'unseren Kundenservice' }}.
        </p>
    </div>
</body>
</html>
