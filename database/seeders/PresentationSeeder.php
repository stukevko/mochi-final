<?php

namespace Database\Seeders;

use App\Enums\EventStatus;
use App\Enums\GameType;
use App\Enums\PostType;
use App\Models\Event;
use App\Models\Post;
use App\Models\PostCategory;
use App\Models\ShopLink;
use App\Models\SiteSetting;
use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * Gold-Master-Demo: TCG-Inhalte, 6 Events (2 Wochen), 3 News, Shop-Renner.
 * Überschreibt Events & Posts — für Demo/Staging.
 */
class PresentationSeeder extends Seeder
{
    private const IMAGE_PACK = [
        'presentation/featured-product.jpg' => 'https://images.unsplash.com/photo-1614732414444-096e5f1122d5?w=1400&q=88&auto=format&fit=crop',
        'presentation/news-lorcana.jpg' => 'https://images.unsplash.com/photo-1618005182384-a83a8bd57fbe?w=1200&q=88&auto=format&fit=crop',
        'presentation/news-community.jpg' => 'https://images.unsplash.com/photo-1511512578047-dfb367046420?w=1200&q=88&auto=format&fit=crop',
    ];

    public function run(): void
    {
        $this->downloadPresentationImages();

        $cat = PostCategory::query()->firstOrCreate(
            ['slug' => 'meta-set-news'],
            ['name' => 'Meta & Set-News', 'sort_order' => 0],
        );

        Post::query()->delete();
        Event::query()->delete();

        $this->seedSiteSettings();
        $this->seedShopLink();
        $this->seedEvents();
        $this->seedPosts($cat);
    }

    private function downloadPresentationImages(): void
    {
        foreach (['featured-product.jpg', 'news-lorcana.jpg', 'news-community.jpg'] as $file) {
            $src = public_path('images/presentation/'.$file);
            $dest = 'presentation/'.$file;
            if (is_file($src) && ! Storage::disk('public')->exists($dest)) {
                Storage::disk('public')->put($dest, (string) file_get_contents($src));
            }
        }

        if (app()->environment('testing')) {
            return;
        }

        foreach (self::IMAGE_PACK as $relativePath => $url) {
            if (Storage::disk('public')->exists($relativePath)) {
                continue;
            }
            try {
                $response = Http::timeout(25)
                    ->withHeaders(['User-Agent' => 'MochiCardsCMS-PresentationSeeder/1.0'])
                    ->get($url);
                if ($response->successful()) {
                    Storage::disk('public')->put($relativePath, $response->body());
                }
            } catch (\Throwable) {
                //
            }
        }
    }

    private function seedSiteSettings(): void
    {
        $settings = SiteSetting::current();
        $shopUrl = 'https://www.cardmarket.com/de/Pokemon';

        $featuredPath = is_file(public_path('images/presentation/featured-product.jpg'))
            ? 'images/presentation/featured-product.jpg'
            : null;

        $settings->fill([
            'hero_headline' => 'DEIN WOHNZIMMER FÜR TCG IN SPEYER',
            'shop_cta_url' => $shopUrl,
            'featured_product_id' => 'BESTSELLER-151-2024',
            'featured_product_title' => 'Pokémon TCG: «Scarlet & Violet — 151» Booster-Bundle',
            'featured_product_description' => 'Beliebter Klassiker-Block mit starternahen Hits — perfekt zum Öffnen am Tisch oder für eure Sammlung. Hochwertige Kartenoptik, frisch eingetroffen.',
            'featured_product_price' => '119,99 €',
            'featured_product_url' => $shopUrl,
            'featured_product_image_path' => $featuredPath,
            'background_animations' => true,
            'benefit_1_title' => 'Blitzversand',
            'benefit_1_body' => 'In 24h bei dir (DE)',
            'benefit_1_icon' => 'bolt',
            'benefit_2_title' => 'Community',
            'benefit_2_body' => 'Dein TCG-Wohnzimmer in Speyer',
            'benefit_2_icon' => 'heart',
            'benefit_3_title' => 'Turniere',
            'benefit_3_body' => 'Wöchentliche Locals & Cups',
            'benefit_3_icon' => 'trophy',
            'benefit_4_title' => 'Safe Pay',
            'benefit_4_body' => 'SSL-verschlüsselt & Käuferschutz',
            'benefit_4_icon' => 'lock',
        ]);
        $settings->save();
    }

    private function seedShopLink(): void
    {
        $link = ShopLink::query()->orderBy('sort_order')->first();
        if ($link) {
            $link->update([
                'label' => 'Zum Online-Shop',
                'url' => 'https://www.cardmarket.com/de/Pokemon',
            ]);
        }
    }

    private function seedEvents(): void
    {
        $rows = [
            [1, [14, 0], 'Pokémon TCG — Standard Cup', '<p>Swiss Rounds, Best-of-3. Check-in ab 13:30 Uhr, Start 14:00. Top-8-Preise & Playmat für den Sieger.</p>', GameType::Pokemon, '#2563eb', 18.00],
            [3, [18, 0], 'One Piece Card Game — Locals', '<p>Lockere Runde mit Meta- und Casual-Decks. Tauschecke & Snacks — ideal zum Reinkommen.</p>', GameType::OnePiece, '#dc2626', 8.00],
            [5, [19, 0], 'Magic: The Gathering — Commander Night', '<p>Multiplayer-Pods à 4, Casual Power Level. Bitte Decklisten mitbringen.</p>', GameType::Magic, '#16a34a', 5.00],
            [7, [17, 30], 'Yu-Gi-Oh! — Freitags-Lokal', '<p>Konami-konform, Judge vor Ort. Deckliste bis 17:00 einreichen.</p>', GameType::Yugioh, '#7e22ce', 10.00],
            [10, [11, 0], 'Disney Lorcana — Casual Saturday', '<p>Offene Tische, Einsteiger:innen willkommen — wir erklären gern Ink und Glimmer.</p>', GameType::Lorcana, '#0891b2', 0.00],
            [13, [15, 30], 'Pokémon — League Challenge', '<p>Championship Points für die Saison. Best-of-3, Swiss + Top Cut je nach Teilnehmerzahl.</p>', GameType::Pokemon, '#1d4ed8', 12.00],
        ];

        foreach ($rows as $row) {
            [$dayOffset, [$h, $m], $title, $description, $gameType, $color, $price] = $row;
            $when = Carbon::now()->startOfDay()->addDays($dayOffset)->setTime($h, $m);
            if ($when->lte(Carbon::now())) {
                $when->addWeek();
            }

            Event::query()->create([
                'title' => $title,
                'slug' => 'mochi-'.Str::lower(Str::random(12)),
                'description' => $description,
                'starts_at' => $when,
                'price' => $price,
                'game_type' => $gameType,
                'calendar_color' => $color,
                'image_path' => null,
                'status' => EventStatus::Active,
            ]);
        }
    }

    private function coverPublic(string $file): ?string
    {
        return is_file(public_path('images/presentation/'.$file))
            ? 'images/presentation/'.$file
            : null;
    }

    private function seedPosts(PostCategory $cat): void
    {
        $lorcana = $this->coverPublic('news-lorcana.jpg');
        $community = $this->coverPublic('news-community.jpg');
        $featured = $this->coverPublic('featured-product.jpg');

        Post::query()->create([
            'title' => 'Lorcana Release: Neue Kapitel am Tisch!',
            'slug' => 'lorcana-release-neue-kapitel',
            'type' => PostType::News,
            'post_category_id' => $cat->id,
            'game_type' => GameType::Lorcana,
            'excerpt' => 'Frische Ink, neue Strategien — ab sofort bei uns im Regal und in den Casual-Runden.',
            'body' => '<p>Das Set ist da: wir haben Booster und Displays für euch bereitgestellt. Kommt vorbei, öffnet die ersten Packs mit uns und plant eure nächsten Decks.</p><p>Für Einsteiger:innen: Samstags Casual-Tisch — wir helfen beim ersten Auflegen.</p>',
            'cover_image_path' => $lorcana,
            'published_at' => now()->subDays(2),
            'is_published' => true,
        ]);

        Post::query()->create([
            'title' => 'Community-Update: Mehr Platz, mehr Mochi-Vibes',
            'slug' => 'community-update-mehr-platz',
            'type' => PostType::News,
            'post_category_id' => null,
            'game_type' => null,
            'excerpt' => 'Kaffeeküche, neue Tische und längere Öffnungszeiten für eure Commander-Nächte.',
            'body' => '<p>Wir haben nachgerüstet: mehr Spielfläche, bessere Beleuchtung an den Playmats und eine Kaffeeküche für lange Sessions.</p><p>Gruppen ab 6 Personen: kurz vorher per Instagram Bescheid geben — dann reservieren wir euch eine Insel.</p>',
            'cover_image_path' => $community,
            'published_at' => now()->subDays(4),
            'is_published' => true,
        ]);

        Post::query()->create([
            'title' => 'Turnier-Rückblick: Pokémon-Cup mit Herz & High-Rolls',
            'slug' => 'turnier-rueckblick-pokemon-cup',
            'type' => PostType::Blog,
            'post_category_id' => $cat->id,
            'game_type' => GameType::Pokemon,
            'excerpt' => 'Top 8, faire Matches, eine Community zum Anfassen — danke an alle!',
            'body' => '<p>Was für ein Samstag: Swiss lief smooth, die Finals hatten Drama bis zur letzten Prize-Karte. Gratulation an alle Platzierten!</p><p>Shoutout ans Team hinter der Theke — ohne euch kein Event. Nächster Cup ist schon in Planung.</p>',
            'cover_image_path' => $featured ?? $community,
            'published_at' => now()->subDays(7),
            'is_published' => true,
        ]);
    }
}
