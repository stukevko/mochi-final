<?php

namespace Database\Seeders;

use App\Models\CmsPage;
use App\Models\ShopLink;
use App\Models\SiteSetting;
use App\Models\User;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        User::query()->updateOrCreate(
            ['email' => 'admin@mochicards.test'],
            [
                'name' => 'Mochi Admin',
                'password' => 'password',
                'role' => 'admin',
                'is_active' => true,
            ],
        );

        foreach (
            [
                [
                    'title' => 'Impressum',
                    'slug' => 'impressum',
                    'body' => '<p><strong>Mochi Cards</strong><br>Maximilianstraße 42<br>67346 Speyer<br>Deutschland</p><p>E-Mail: bitte im Admin unter Händler-Einstellungen pflegen.</p><p><em>Rechtstext vom Inhaber/Anwalt ergänzen.</em></p>',
                ],
                [
                    'title' => 'Widerruf',
                    'slug' => 'widerruf',
                    'body' => '<p>Widerrufsbelehrung für Verbraucherbestellungen im Online-Shop.</p><p><em>Finalen Widerrufstext vom Inhaber/Anwalt einfügen.</em></p>',
                ],
                [
                    'title' => 'Datenschutz',
                    'slug' => 'datenschutz',
                    'body' => <<<'HTML'
<p>Diese Datenschutzerklärung informiert über die Verarbeitung personenbezogener Daten beim Besuch unserer Website und Nutzung unseres Online-Shops.</p>

<h2>Verantwortlicher</h2>
<p>Verantwortlich im Sinne der DSGVO ist der in unserem Impressum genannte Anbieter. Kontaktdaten findest du dort.</p>

<h2>Hosting &amp; Server-Logfiles</h2>
<p>Beim Aufruf unserer Seiten werden technisch notwendige Daten verarbeitet (z.&nbsp;B. IP-Adresse, Zeitpunkt, angeforderte URL, Browsertyp). Rechtsgrundlage: berechtigtes Interesse an einem sicheren und stabilen Betrieb (Art.&nbsp;6 Abs.&nbsp;1 lit.&nbsp;f DSGVO).</p>

<h2>Cookies &amp; lokale Speicherung</h2>
<h3>Technisch notwendig (ohne Einwilligung)</h3>
<ul>
<li><strong>Sitzungs-Cookie</strong> — Warenkorb, Checkout-Fortschritt, Sicherheit (CSRF), Livewire. Rechtsgrundlage: Art.&nbsp;6 Abs.&nbsp;1 lit.&nbsp;f DSGVO bzw. lit.&nbsp;b bei Vertragsschließung.</li>
<li><strong>Cookie-Einstellung</strong> — Speicherung deiner Banner-Auswahl im Browser (<code>localStorage</code>, Schlüssel <code>mochi_cookie_consent_v1</code>). Rechtsgrundlage: Art.&nbsp;6 Abs.&nbsp;1 lit.&nbsp;f DSGVO (Nachweis der Einwilligung).</li>
<li><strong>Cloudflare Turnstile</strong> (nur Kontaktformular, falls aktiviert) — Schutz vor Spam/Missbrauch. Rechtsgrundlage: Art.&nbsp;6 Abs.&nbsp;1 lit.&nbsp;f DSGVO.</li>
</ul>
<h3>Schriftarten</h3>
<p>Web-Schriften werden von unserem eigenen Server geladen (self-hosted) — es erfolgt keine Einbindung über Google Fonts oder Bunny.net.</p>
<h3>Optional (nur mit Einwilligung „Alle akzeptieren“)</h3>
<ul>
<li><strong>Google Maps (Einbettung)</strong> — interaktive Karte auf der Kontaktseite. Anbieter: Google Ireland Ltd. Rechtsgrundlage: Art.&nbsp;6 Abs.&nbsp;1 lit.&nbsp;a DSGVO. Ohne Einwilligung zeigen wir nur einen Link zu Google Maps.</li>
</ul>
<p>Du kannst deine Einwilligung jederzeit über den Link „Cookies“ im Footer widerrufen und „Nur notwendige“ wählen.</p>

<h2>Online-Shop &amp; Bestellung</h2>
<p>Bei Bestellungen verarbeiten wir die von dir angegebenen Daten (Name, Adresse, E-Mail, Bestellinhalt) zur Vertragserfüllung (Art.&nbsp;6 Abs.&nbsp;1 lit.&nbsp;b DSGVO). Zahlungen werden über den konfigurierten Zahlungsdienstleister (z.&nbsp;B. Stripe) abgewickelt; dabei gelten zusätzlich dessen Datenschutzbestimmungen.</p>

<h2>Kontaktformular</h2>
<p>Daten aus dem Kontaktformular verwenden wir zur Bearbeitung deiner Anfrage (Art.&nbsp;6 Abs.&nbsp;1 lit.&nbsp;b bzw. lit.&nbsp;f DSGVO).</p>

<h2>Adress-Vorschläge im Checkout</h2>
<p>PLZ- und Straßenvorschläge werden serverseitig über einen Geocoding-Dienst (Photon/OpenStreetMap) abgefragt — es werden keine Karten-Skripte im Browser geladen.</p>

<h2>Deine Rechte</h2>
<p>Du hast das Recht auf Auskunft, Berichtigung, Löschung, Einschränkung, Widerspruch und Datenübertragbarkeit sowie Beschwerde bei einer Aufsichtsbehörde.</p>

<p><em>Bitte lasse diese Erklärung vor Go-Live von deinem Anwalt auf Vollständigkeit prüfen und ergänze fehlende Angaben (z.&nbsp;B. Auftragsverarbeiter, Speicherdauer, vollständige Kontaktdaten).</em></p>
HTML,
                ],
            ] as $row
        ) {
            CmsPage::query()->updateOrCreate(
                ['slug' => $row['slug']],
                ['title' => $row['title'], 'body' => $row['body']],
            );
        }

        ShopLink::query()->updateOrCreate(
            ['label' => 'Zum Online-Shop'],
            ['url' => '/shop', 'sort_order' => 0],
        );

        $settings = SiteSetting::current();
        if (blank($settings->hero_headline)) {
            $settings->hero_headline = 'Dein TCG WOHNZIMMER IN Speyer';
        }
        if (blank($settings->about_page_title)) {
            $settings->forceFill([
                'about_page_title' => 'Über uns',
                'about_hero_subtitle' => 'Dein TCG Wohnzimmer in Speyer',
                'about_intro' => 'Mochi Cards ist mehr als ein Kartenladen — wir sind Treffpunkt für TCG-Fans, Sammler und Spieler aus Speyer und der Region.',
                'about_story' => '<p>Seit unserer Eröffnung in der Maximilianstraße 43 in Speyer bieten wir Pokémon, Magic, Yu-Gi-Oh! und vieles mehr — mit Beratung, Events und einer Community, die zusammenwächst.</p><p>Im Laden findest du frische Booster, Displays, Singles und Zubehör. Online kannst du ausgewählte Highlights bestellen und dich über News & Events auf dem Laufenden halten.</p>',
                'about_highlight_1_title' => 'Laden & Community',
                'about_highlight_1_body' => 'Spieltische, Events und ein Team, das TCG wirklich lebt.',
                'about_highlight_2_title' => 'Sortiment',
                'about_highlight_2_body' => 'Neuheiten, Beliebtes und seltene Fundstücke für Sammler.',
                'about_highlight_3_title' => 'Online-Shop',
                'about_highlight_3_body' => 'Ausgewählte Produkte bequem nach Hause bestellen.',
                'about_instagram_heading' => 'Ein Blick in unseren Laden',
                'about_cta_label' => 'Besuch uns in Speyer',
                'about_cta_url' => '/kontakt',
                'about_meta_description' => 'Mochi Cards Speyer — TCG-Laden, Community und Online-Shop. Lerne uns kennen.',
            ]);
        }
        $settings->save();

        $this->call(PresentationSeeder::class);
        $this->call(DemoShopSeeder::class);
    }
}
