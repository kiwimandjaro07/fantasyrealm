<?php

declare(strict_types=1);

namespace Tests;

use App\Core\Security;
use App\Core\Validator;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class ValidatorTest extends TestCase
{
    /** @return array<string, array{string, bool}> */
    public static function motsDePasse(): array
    {
        return [
            'conforme'                  => ['Joueur#Fantasy2026', true],
            'accents et espace'         => ['Éléphant rose 42', true],
            'trop court'                => ['Ab#1defgh', false],
            'sans caractère spécial'    => ['JoueurFantasy2026', false],
            'sans majuscule'            => ['joueur#fantasy2026', false],
            'sans minuscule'            => ['JOUEUR#FANTASY2026', false],
            'sans chiffre'              => ['Joueur#Fantasyyy', false],
        ];
    }

    #[DataProvider('motsDePasse')]
    public function testMotDePasseConformeCnil(string $mdp, bool $attendu): void
    {
        self::assertSame($attendu, Validator::motDePasse($mdp));
    }

    public function testPseudo(): void
    {
        self::assertTrue(Validator::pseudo('Lyra'));
        self::assertTrue(Validator::pseudo('dark_knight.42'));
        self::assertTrue(Validator::pseudo('Élodie-7'));
        self::assertFalse(Validator::pseudo('ab'));                      // trop court
        self::assertFalse(Validator::pseudo('mon pseudo'));              // espace
        self::assertFalse(Validator::pseudo('<script>'));
        self::assertFalse(Validator::pseudo("admin' OR '1'='1"));
    }

    public function testNomDePersonnage(): void
    {
        self::assertTrue(Validator::nomPersonnage('Aelwen'));
        self::assertTrue(Validator::nomPersonnage("Élandor l'Ancien"));
        self::assertTrue(Validator::nomPersonnage('Anne-Sophie'));
        self::assertFalse(Validator::nomPersonnage('A'));
        self::assertFalse(Validator::nomPersonnage('R2D2'));            // chiffres interdits
        self::assertFalse(Validator::nomPersonnage('<b>Gras</b>'));
        self::assertFalse(Validator::nomPersonnage('-Tiret'));          // doit commencer par une lettre
        self::assertFalse(Validator::nomPersonnage('Deux  espaces'));
        self::assertFalse(Validator::nomPersonnage(str_repeat('a', 41)));
    }

    public function testEmailDateCouleurId(): void
    {
        self::assertTrue(Validator::email('lyra@exemple.fr'));
        self::assertFalse(Validator::email('lyra@'));
        self::assertTrue(Validator::date('2026-11-19'));
        self::assertFalse(Validator::date('2026-02-30'));
        self::assertTrue(Validator::couleur('#d4a63a'));
        self::assertFalse(Validator::couleur('red'));
        self::assertFalse(Validator::couleur('#d4a63a;background:url(x)'));
        self::assertSame(12, Validator::id('12'));
        self::assertNull(Validator::id('0'));
        self::assertNull(Validator::id('12abc'));
        self::assertNull(Validator::id(['12']));
    }

    public function testListeBlanche(): void
    {
        self::assertTrue(Validator::parmi('feminin', ['feminin', 'masculin']));
        self::assertFalse(Validator::parmi('Feminin', ['feminin', 'masculin']));
        self::assertFalse(Validator::parmi(['feminin'], ['feminin']));
    }

    public function testEntreesNonUtf8Refusees(): void
    {
        self::assertTrue(Security::entreesValides(['nom' => 'Élandor', 'liste' => ['a', 'é']]));
        self::assertFalse(Security::entreesValides(['nom' => "Caf\xE9"]));          // Latin-1, pas UTF-8
        self::assertFalse(Security::entreesValides(['liste' => ['ok', "\xC3\x28"]])); // séquence invalide
    }
}
