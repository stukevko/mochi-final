<?php

namespace App\Console\Commands;

use App\Models\Order;
use App\Services\Payments\PaymentCompletionService;
use Illuminate\Console\Command;
use Throwable;

/**
 * Sicherheitsnetz für den Bezahlablauf (läuft alle 5 Minuten per Scheduler):
 *  - Kunde hat bezahlt, aber Tab geschlossen / Webhook kam nicht an → Bestellung wird bezahlt + Mail + Rechnung
 *  - Kunde hat abgebrochen → Bestellung verfällt, reservierte Ware geht zurück in den Bestand
 */
class ShopReconcilePaymentsCommand extends Command
{
    protected $signature = 'shop:reconcile-payments {--limit=50 : Maximale Anzahl offener Bestellungen pro Lauf}';

    protected $description = 'Gleicht offene SumUp-Bestellungen mit SumUp ab (bezahlt markieren oder Reservierung freigeben).';

    public function handle(PaymentCompletionService $completion): int
    {
        $orders = Order::query()
            ->where('payment_method', 'sumup')
            ->where('payment_status', 'pending')
            ->where('status', 'pending')
            ->where('created_at', '<=', now()->subMinutes(2))
            ->orderBy('id')
            ->limit(max(1, (int) $this->option('limit')))
            ->get();

        $counts = ['paid' => 0, 'cancelled' => 0, 'pending' => 0, 'error' => 0];

        foreach ($orders as $order) {
            try {
                $counts[$completion->reconcileSumUp($order)]++;
            } catch (Throwable $e) {
                $counts['error']++;
                report($e);
            }
        }

        $this->info(sprintf(
            'Geprüft: %d · bezahlt: %d · verfallen: %d · offen: %d · Fehler: %d',
            $orders->count(), $counts['paid'], $counts['cancelled'], $counts['pending'], $counts['error'],
        ));

        return self::SUCCESS;
    }
}
