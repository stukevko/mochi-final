<?php

namespace Tests\Unit;

use App\Support\SecureUrl;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class SecureUrlTest extends TestCase
{
    #[Test]
    public function it_upgrades_http_to_https(): void
    {
        $this->assertSame(
            'https://mochi-cards.de/storage/logo.png',
            SecureUrl::upgrade('http://mochi-cards.de/storage/logo.png')
        );
    }

    #[Test]
    public function it_leaves_https_and_relative_untouched(): void
    {
        $this->assertSame('https://mochi-cards.de/a', SecureUrl::upgrade('https://mochi-cards.de/a'));
        $this->assertSame('/relative', SecureUrl::upgrade('/relative'));
        $this->assertNull(SecureUrl::upgrade(null));
        $this->assertSame('', SecureUrl::upgrade(''));
    }
}
