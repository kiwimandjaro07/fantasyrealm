<?php

declare(strict_types=1);

namespace Tests;

use App\Core\Csrf;
use PHPUnit\Framework\TestCase;

final class CsrfTest extends TestCase
{
    protected function setUp(): void
    {
        $_SESSION = []; // session simulée : pas besoin de session_start() en ligne de commande
    }

    public function testLeJetonEstStableDansLaSession(): void
    {
        $jeton = Csrf::jeton();
        self::assertSame(64, strlen($jeton)); // 32 octets en hexadécimal
        self::assertSame($jeton, Csrf::jeton());
    }

    public function testVerification(): void
    {
        $jeton = Csrf::jeton();
        self::assertTrue(Csrf::verifier($jeton));
        self::assertFalse(Csrf::verifier('jeton-invente'));
        self::assertFalse(Csrf::verifier(null));
        self::assertFalse(Csrf::verifier(['tableau']));
        self::assertFalse(Csrf::verifier(''));
    }

    public function testSansSessionAucunJetonNEstAccepte(): void
    {
        self::assertFalse(Csrf::verifier(''));
        self::assertFalse(Csrf::verifier(str_repeat('0', 64)));
    }

    public function testRenouvelerInvalideLAncienJeton(): void
    {
        $ancien = Csrf::jeton();
        Csrf::renouveler();
        self::assertNotSame($ancien, Csrf::jeton());
        self::assertFalse(Csrf::verifier($ancien));
    }

    public function testLeChampEstEchappe(): void
    {
        self::assertMatchesRegularExpression('/^<input type="hidden" name="_csrf" value="[a-f0-9]{64}">$/', Csrf::champ());
    }
}
