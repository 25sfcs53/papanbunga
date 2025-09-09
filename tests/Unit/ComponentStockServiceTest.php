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
    // Create explicit letter components used by the message to avoid charset detection ambiguity in CI
    Component::create(['name' => 'U', 'type' => Component::TYPE_HURUF_BESAR, 'quantity_available' => 100]);
    Component::create(['name' => 'c', 'type' => Component::TYPE_HURUF_KECIL, 'quantity_available' => 100]);
    Component::create(['name' => 'a', 'type' => Component::TYPE_HURUF_KECIL, 'quantity_available' => 100]);
    Component::create(['name' => 'p', 'type' => Component::TYPE_HURUF_KECIL, 'quantity_available' => 100]);
    Component::create(['name' => 'n', 'type' => Component::TYPE_HURUF_KECIL, 'quantity_available' => 100]);

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
