<?php

namespace Database\Seeders;

use App\Enums\GameType;
use App\Models\Category;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\Setting;
use App\Models\SiteSetting;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * Premium-Demo: 6 TCG-Produkte (korrekte GameTypes / Neon), Bilder, 4 Bestellungen für Dashboard & Shop.
 * Idempotent über Produkt-Slugs; Bestellungen mit order_number DEMO-* werden vorher entfernt.
 */
class DemoShopSeeder extends Seeder
{
    private const DEMO_ORDER_PREFIX = 'DEMO-';

    /**
     * Stabile HTTPS-Platzhalter (funktionieren ohne storage:link). Keys = Dateiname unter demo-products/.
     *
     * @var array<string, non-empty-string>
     */
    private const IMAGE_PACK = [
        'demo-products/pokemon-zenit.jpg' => 'https://images.unsplash.com/photo-1542751371-adc38448a05e?w=1200&q=88&auto=format&fit=crop',
        'demo-products/onepiece-display.jpg' => 'https://images.unsplash.com/photo-1578662996442-48f60103fc96?w=1200&q=88&auto=format&fit=crop',
        'demo-products/lorcana-chapter.jpg' => 'https://images.unsplash.com/photo-1618005182384-a83a8bd57fbe?w=1200&q=88&auto=format&fit=crop',
        'demo-products/magic-booster.jpg' => 'https://images.unsplash.com/photo-1615688904398-2331bbf98bc0?w=1200&q=88&auto=format&fit=crop',
        'demo-products/yugioh-gold.jpg' => 'https://images.unsplash.com/photo-1606166188517-0b0ea8b8531b?w=1200&q=88&auto=format&fit=crop',
        'demo-products/mochi-case.jpg' => 'https://images.unsplash.com/photo-1606761568499-6d2451b23b66?w=1200&q=88&auto=format&fit=crop',
    ];

    public function run(): void
    {
        $this->downloadDemoImages();
        $categories = $this->seedCategories();

        $catalog = $this->productDefinitions();
        $productsBySlug = [];

        foreach ($catalog as $row) {
            /** @var GameType $game */
            $game = $row['game'];
            $slug = $row['slug'];
            $imageStoragePath = $row['image_path'];

            $storageKey = 'demo-products/'.$imageStoragePath;
            /** Immer öffentliche HTTPS-URLs: /storage/ bricht ohne Symlink oder bei leeren Downloads häufig; so bleibt die Demo knallig. */
            $imageUrl = self::IMAGE_PACK[$storageKey] ?? null;

            $productsBySlug[$slug] = Product::query()->updateOrCreate(
                ['slug' => $slug],
                [
                    'name' => $row['name'],
                    'short_description' => $row['short'],
                    'description' => $row['description'],
                    'price' => $row['price'],
                    'sale_price' => null,
                    'sku' => $row['sku'],
                    'stock' => $row['stock'],
                    'category_id' => $categories[$game->value]->id,
                    'images' => $imageUrl ? [$imageUrl] : [],
                    'is_active' => true,
                    'is_featured' => (bool) ($row['featured'] ?? false),
                    'has_variants' => false,
                ],
            );
        }

        $this->purgeDemoOrders();
        $demoUser = $this->seedDemoUser();
        $this->seedDemoOrders($productsBySlug, $demoUser);
        $this->syncFeaturedHero($productsBySlug['zenit-der-koenige-top-trainer-box']);
    }

    /**
     * @return array<string, Category>
     */
    private function seedCategories(): array
    {
        $defs = [
            GameType::Pokemon->value => ['Pokémon TCG', 'pokemon-tcg', GameType::Pokemon],
            GameType::OnePiece->value => ['One Piece Card Game', 'one-piece-cg', GameType::OnePiece],
            GameType::Lorcana->value => ['Disney Lorcana', 'disney-lorcana', GameType::Lorcana],
            GameType::Magic->value => ['Magic: The Gathering', 'magic-the-gathering', GameType::Magic],
            GameType::Yugioh->value => ['Yu-Gi-Oh! TCG', 'yu-gi-oh-tcg', GameType::Yugioh],
            GameType::Custom->value => ['Zubehör & Supplies', 'zubehoer-supplies', GameType::Custom],
        ];

        $out = [];
        $sort = 0;
        foreach ($defs as $key => [$name, $slug, $game]) {
            $out[$key] = Category::query()->updateOrCreate(
                ['slug' => $slug],
                [
                    'name' => $name,
                    'game_type' => $game,
                    'description' => $name.' — kuratiert bei MochiCards Speyer.',
                    'image' => null,
                    'parent_id' => null,
                    'is_active' => true,
                    'sort_order' => $sort++,
                ],
            );
        }

        return $out;
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function productDefinitions(): array
    {
        return [
            [
                'name' => 'Zenit der Könige — Top-Trainer-Box',
                'slug' => 'zenit-der-koenige-top-trainer-box',
                'sku' => 'MOCHI-SV-ZEN-TTB',
                'price' => '54.99',
                'stock' => 7,
                'game' => GameType::Pokemon,
                'image_path' => 'pokemon-zenit.jpg',
                'featured' => true,
                'short' => 'Limitierte Pokémon TCG Premium-Box mit Promos, Boostern & exklusiven Full-Art-Karten — perfekt für Sammler:innen.',
                'description' => '<p>Die <strong>Top-Trainer-Box «Zenit der Könige»</strong> liefert alles für den nächsten Release-Abend: ausgewogene Booster-Auswahl, stilvolle Aufbewahrung und begehrte Promo-Karten im hochwertigen Finish. Ideal, um Hits zu jagen oder eure Sammlung wertvoll zu erweitern.</p><p><em>Hinweis:</em> Original versiegelt; begrenzte Stückzahl — wer zögert, verpasst oft den zweiten Druck.</p>',
            ],
            [
                'name' => 'Romance Dawn Booster Display [OP-01]',
                'slug' => 'romance-dawn-booster-display-op-01',
                'sku' => 'MOCHI-OP01-DISPLAY',
                'price' => '199.00',
                'stock' => 5,
                'game' => GameType::OnePiece,
                'image_path' => 'onepiece-display.jpg',
                'featured' => false,
                'short' => 'Komplettes Display der ersten One Piece OP-01 Edition — das Flagship für Sammler und Constructed-Spieler.',
                'description' => '<p>Das <strong>Romance Dawn Booster Display</strong> ist einer der begehrtesten Großformate für die frühe One Piece TCG-Ära: 24 Booster für kompromissloses Öffnen oder zum Halten als Ankerstück eurer Sammlung.</p><p>Perfekt für Gruppen-Breaks, Trophy-Jäger und alle, die OP-01 noch zu fairen Konditionen sichern wollen.</p>',
            ],
            [
                'name' => 'Disney Lorcana — Das erste Kapitel Booster Box',
                'slug' => 'lorcana-erstes-kapitel-booster-box',
                'sku' => 'MOCHI-LOR-CH1-BOX',
                'price' => '145.00',
                'stock' => 6,
                'game' => GameType::Lorcana,
                'image_path' => 'lorcana-chapter.jpg',
                'featured' => false,
                'short' => 'Die Einstiegs-Ära von Lorcana: nostalgische Disney-Illustrationen, klare Mechanik, hoher Sammlerwert.',
                'description' => '<p>Die <strong>Booster Box «Das erste Kapitel»</strong> gehört zu den Referenz-Sets von Disney Lorcana: wunderschöne Kartenoptik, starke Lore und eine eingängige Ink-Kurve für neue und erfahrene Spieler:innen.</p><p>Geeignet fürs Cube-Öffnen am Stammtisch oder als langer Atem für eure Binder-Highlights.</p>',
            ],
            [
                'name' => 'Modern Horizons 3 — Play Booster',
                'slug' => 'modern-horizons-3-play-booster',
                'sku' => 'MOCHI-MH3-PLAY-1',
                'price' => '9.99',
                'stock' => 10,
                'game' => GameType::Magic,
                'image_path' => 'magic-booster.jpg',
                'featured' => false,
                'short' => 'Power-Level pur: Play Booster für Modern Horizons 3 — ideal zum Draften, Spielen und Spekulieren.',
                'description' => '<p><strong>Modern Horizons 3</strong> liefert formatprägende Staples und vergnügliche Limited-Synergien zugleich. Ein einzelner Play Booster ist der perfekte Low-Risk-Einstieg, um das Set zu «spüren», bevor ihr in Displays geht.</p><p>Für Commander- und Modern-Spieler:innen ein klassisches Must-Issue-Set.</p>',
            ],
            [
                'name' => 'Yu-Gi-Oh! — 25th Anniversary Rarity Collection',
                'slug' => 'yu-gi-oh-25th-anniversary-rarity-collection',
                'sku' => 'MOCHI-YGO-25-RAC',
                'price' => '79.00',
                'stock' => 8,
                'game' => GameType::Yugioh,
                'image_path' => 'yugioh-gold.jpg',
                'featured' => false,
                'short' => 'Anniversary-Set mit Premium-Rarities und Glanz-Finishes — made für Binder-Goals und High-Roll-Opens.',
                'description' => '<p>Die <strong>25th Anniversary Rarity Collection</strong> bündelt bekannte Archetypen und Iconic-Karten mit spektakulären Rarity-Treatments — genau das Richtige, wenn ihr Yukai-Highlights in Gold und Prisma suchen.</p><p>Klare Empfehlung für Sammler:innen, die Wert auf Präsentation und Langzeit-Relevanz legen.</p>',
            ],
            [
                'name' => 'Mochi Premium Magnetic Card Case',
                'slug' => 'mochi-premium-magnetic-card-case',
                'sku' => 'MOCHI-SUPPLY-MAG-1',
                'price' => '4.50',
                'stock' => 10,
                'game' => GameType::Custom,
                'image_path' => 'mochi-case.jpg',
                'featured' => false,
                'short' => 'Magnetische One-Touch-Hülle mit klarer Front — schützt Graded-feeling für eure Chase-Karten.',
                'description' => '<p>Unser <strong>Mochi Premium Magnetic Case</strong> hält Standard-Karten sicher und klar lesbar: UV-Schutz, sauberer Druckpunkt der Magnete und ein Rahmen, der im Regal sofort wirkt.</p><p>Perfekt für Toploader-Ersatz im Showcase oder als Geschenk an eure Trade-Partner.</p>',
            ],
        ];
    }

    private function downloadDemoImages(): void
    {
        if (app()->environment('testing')) {
            return;
        }

        foreach (self::IMAGE_PACK as $relativePath => $url) {
            if (Storage::disk('public')->exists($relativePath)) {
                continue;
            }
            try {
                $response = Http::timeout(30)
                    ->withHeaders(['User-Agent' => 'MochiCardsCMS-DemoShopSeeder/1.0'])
                    ->get($url);
                if ($response->successful()) {
                    Storage::disk('public')->put($relativePath, $response->body());
                }
            } catch (\Throwable) {
                //
            }
        }
    }

    private function purgeDemoOrders(): void
    {
        $ids = Order::query()
            ->where('order_number', 'like', self::DEMO_ORDER_PREFIX.'%')
            ->pluck('id');

        if ($ids->isEmpty()) {
            return;
        }

        OrderItem::query()->whereIn('order_id', $ids)->delete();
        Order::query()->whereIn('id', $ids)->delete();
    }

    private function seedDemoUser(): User
    {
        return User::query()->updateOrCreate(
            ['email' => 'demo.sammler@mochicards.test'],
            [
                'name' => 'Lena Krämer',
                'password' => 'password',
                'role' => 'customer',
                'is_active' => true,
            ],
        );
    }

    /**
     * @param  array<string, Product>  $bySlug
     */
    private function seedDemoOrders(array $bySlug, User $demoUser): void
    {
        $addr = static fn (string $first, string $last, string $email): array => [
            'first_name' => $first,
            'last_name' => $last,
            'email' => $email,
            'phone' => '+49 6232 000000',
            'street' => 'Maximilianstr. 42',
            'zip' => '67346',
            'city' => 'Speyer',
            'country' => 'DE',
        ];

        $specs = [
            [
                'number_suffix' => 'A1',
                'user_id' => $demoUser->id,
                'status' => 'processing',
                'payment_status' => 'paid',
                'payment_method' => 'stripe',
                'created_at' => now()->subHours(3),
                'customer' => $addr('Lena', 'Krämer', 'demo.sammler@mochicards.test'),
                'lines' => [
                    ['slug' => 'romance-dawn-booster-display-op-01', 'qty' => 1],
                    ['slug' => 'mochi-premium-magnetic-card-case', 'qty' => 3],
                ],
            ],
            [
                'number_suffix' => 'A2',
                'user_id' => null,
                'status' => 'pending',
                'payment_status' => 'paid',
                'payment_method' => 'paypal',
                'created_at' => now()->subHour(),
                'customer' => $addr('Tim', 'Schulz', 'tim.schulz.example@mail.test'),
                'lines' => [
                    ['slug' => 'zenit-der-koenige-top-trainer-box', 'qty' => 1],
                    ['slug' => 'modern-horizons-3-play-booster', 'qty' => 4],
                ],
            ],
            [
                'number_suffix' => 'A3',
                'user_id' => null,
                'status' => 'shipped',
                'payment_status' => 'paid',
                'payment_method' => 'prepayment',
                'created_at' => now()->subMinutes(40),
                'shipping_carrier' => 'dhl',
                'tracking_number' => '003404341610940159',
                'customer' => $addr('Sofia', 'Martens', 'sofia.martens.example@mail.test'),
                'lines' => [
                    ['slug' => 'lorcana-erstes-kapitel-booster-box', 'qty' => 1],
                ],
            ],
            [
                'number_suffix' => 'A4',
                'user_id' => null,
                'status' => 'processing',
                'payment_status' => 'paid',
                'payment_method' => 'prepayment',
                'created_at' => now()->subDay()->setTime(16, 20),
                'customer' => $addr('Jonas', 'Weiß', 'jonas.weiss.example@mail.test'),
                'lines' => [
                    ['slug' => 'yu-gi-oh-25th-anniversary-rarity-collection', 'qty' => 1],
                    ['slug' => 'modern-horizons-3-play-booster', 'qty' => 2],
                ],
            ],
        ];

        foreach ($specs as $spec) {
            DB::transaction(function () use ($bySlug, $spec): void {
                $gross = 0.0;
                $lines = [];

                foreach ($spec['lines'] as $line) {
                    $product = $bySlug[$line['slug']];
                    $qty = (int) $line['qty'];
                    $unit = (float) $product->current_price;
                    $lineTotal = round($unit * $qty, 2);
                    $gross += $lineTotal;
                    $lines[] = [$product, $qty, $unit, $lineTotal];
                }

                $amounts = $this->splitNetTax($gross);

                $placedAt = $spec['created_at'];

                $order = new Order;
                $order->forceFill([
                    'order_number' => self::DEMO_ORDER_PREFIX.$spec['number_suffix'].'-'.strtoupper(Str::random(4)),
                    'user_id' => $spec['user_id'] ?? null,
                    'status' => $spec['status'],
                    'payment_status' => $spec['payment_status'],
                    'payment_method' => $spec['payment_method'],
                    'payment_id' => 'demo_tx_'.Str::lower(Str::random(10)),
                    'subtotal' => $amounts['subtotal'],
                    'tax' => $amounts['tax'],
                    'shipping_cost' => 0,
                    'discount' => 0,
                    'total' => $amounts['total'],
                    'billing_address' => $spec['customer'],
                    'shipping_address' => $spec['customer'],
                    'notes' => '[Mochi Demo] Demo-Bestellung für Admin-Dashboard & Tests.',
                    'currency' => (string) Setting::get('currency', 'EUR'),
                    'shipping_carrier' => $spec['shipping_carrier'] ?? null,
                    'tracking_number' => $spec['tracking_number'] ?? null,
                ]);
                $order->save();

                DB::table('orders')->where('id', $order->id)->update([
                    'created_at' => $placedAt,
                    'updated_at' => $placedAt,
                ]);

                foreach ($lines as [$product, $qty, $unit, $lineTotal]) {
                    OrderItem::query()->create([
                        'order_id' => $order->id,
                        'product_id' => $product->id,
                        'product_variant_id' => null,
                        'product_name' => $product->name,
                        'variant_name' => '',
                        'sku' => $product->sku,
                        'quantity' => $qty,
                        'unit_price' => $unit,
                        'total_price' => $lineTotal,
                    ]);
                }
            });
        }
    }

    /**
     * @return array{subtotal: float, tax: float, total: float}
     */
    private function splitNetTax(float $grossTotal): array
    {
        $taxRate = max(0.0, (float) Setting::get('tax_rate', 19));
        $divisor = 1 + ($taxRate / 100);
        $net = round($grossTotal / ($divisor > 0 ? $divisor : 1), 2);
        $tax = round($grossTotal - $net, 2);

        return [
            'subtotal' => $net,
            'tax' => $tax,
            'total' => round($grossTotal, 2),
        ];
    }

    private function syncFeaturedHero(Product $flagship): void
    {
        $settings = SiteSetting::current();
        $settings->fill([
            'featured_product_id' => (string) $flagship->sku,
            'featured_product_title' => $flagship->name,
            'featured_product_description' => strip_tags((string) $flagship->short_description),
            'featured_product_price' => number_format((float) $flagship->price, 2, ',', '.').' €',
            'featured_product_url' => url('/product/'.$flagship->slug),
            'shop_cta_url' => url('/shop'),
        ]);

        $thumb = $flagship->safeImageUrl(0);
        if ($thumb !== null) {
            if (str_starts_with($thumb, '/storage/')) {
                $settings->featured_product_image_path = ltrim(substr($thumb, strlen('/storage/')), '/');
            } elseif (preg_match('#^https?://#i', $thumb) === 1) {
                $settings->featured_product_image_path = $thumb;
            }
        }

        $settings->save();
    }
}
