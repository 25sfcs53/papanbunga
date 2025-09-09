<?php

namespace Tests\Unit;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Services\ComponentStockService;
use App\Models\Component;

class ComponentStockServiceTest extends TestCase
{
    use RefreshDatabase;

    #[\PHPUnit\Framework\Attributes\Test]
    public function parse_message_produces_expected_components()
    {
        // Arrange: create components that will be matched
        // Words
        Component::create(['name' => 'Selamat', 'type' => Component::TYPE_KATA_SAMBUNG, 'quantity_available' => 100]);
        Component::create(['name' => 'Sukses', 'type' => Component::TYPE_KATA_SAMBUNG, 'quantity_available' => 100]);

        // Letters (upper and lower)
        // We'll add both uppercase and lowercase variants that may be matched
    // include both upper/lower where tests expect uppercase letters like 'U'
    $letters = ['S','e','l','a','m','t','U','c','p','n'];
        foreach ($letters as $ltr) {
            $type = mb_strtoupper($ltr) === $ltr && mb_strlen($ltr) === 1 && preg_match('/^\p{Lu}$/u', $ltr) ? Component::TYPE_HURUF_BESAR : Component::TYPE_HURUF_KECIL;
            Component::create(['name' => $ltr, 'type' => $type, 'quantity_available' => 100]);
        }

        $service = new ComponentStockService();

        // Act
        $message = 'Selamat Sukses Ucapannya';
        $result = $service->parseMessage($message);

        // Assert: expected counts
        // Expected: 'Selamat' word once, 'Sukses' once
        $selamatComp = Component::where('name', 'Selamat')->where('type', Component::TYPE_KATA_SAMBUNG)->first();
        $suksesComp = Component::where('name', 'Sukses')->where('type', Component::TYPE_KATA_SAMBUNG)->first();

        $this->assertArrayHasKey($selamatComp->id, $result, 'Selamat should be matched');
        $this->assertEquals(1, $result[$selamatComp->id]);

        $this->assertArrayHasKey($suksesComp->id, $result, 'Sukses should be matched');
        $this->assertEquals(1, $result[$suksesComp->id]);

        // Check a few letter counts: Ucapannya => U c a p a n y a
        $uComp = Component::where('name', 'U')->where('type', Component::TYPE_HURUF_BESAR)->first();
        $this->assertNotNull($uComp);
        $this->assertArrayHasKey($uComp->id, $result);
        $this->assertEquals(1, $result[$uComp->id]);

        $aComp = Component::where('name', 'a')->where('type', Component::TYPE_HURUF_KECIL)->first();
        $this->assertNotNull($aComp);
        $this->assertArrayHasKey($aComp->id, $result);
        // 'Selamat Sukses Ucapannya' has a occurrences: Selamat(2) Sukses(1) Ucapannya(3) total approx 6
        $this->assertGreaterThanOrEqual(1, $result[$aComp->id]);
    }
}
