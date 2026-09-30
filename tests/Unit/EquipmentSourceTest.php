<?php
namespace Tests\Unit;

use App\Services\Equipment\{EquipmentDescriptionGenerator,EquipmentStatCatalog,EquipmentSyncService,HuntifyEquipmentSource};
use Illuminate\Support\Facades\Http;
use RuntimeException;
use Tests\TestCase;

class EquipmentSourceTest extends TestCase
{
    public function test_structured_feeds_are_decoded_without_source_prose(): void
    {
        foreach (['Skins'=>'skins','Weapons'=>'weapons','Tools'=>'tools','Consumables'=>'consumables'] as $module=>$dataset) {
            $rows = $module === 'Skins' ? [['id'=>'skin-1','name'=>'Test Skin','rarity'=>'Legendary']]
                : [['id'=>$dataset.'-1','name'=>'Sample','lore'=>'Source prose','skinIds'=>$module === 'Weapons' ? ['skin-1'] : []]];
            Http::fake(['https://wiki.huntify.win/Hunt/modules/'.$module.'/data.js' => Http::response(
                'window.Hunt.data.register("'.$dataset.'", '.json_encode($rows).');',200
            )]);
        }
        $items = iterator_to_array((new HuntifyEquipmentSource)->items());
        $this->assertCount(3,$items);
        $this->assertSame(['weapon','tool','consumable'],array_column($items,'_item_type'));
        $this->assertSame('Test Skin',$items[0]['_skins'][0]['name']);
        $this->assertStringNotContainsString('Source prose',(new EquipmentDescriptionGenerator)->generate($items[0]));
    }

    public function test_stat_directions_and_description_use_facts(): void
    {
        $definitions = collect(EquipmentStatCatalog::DEFINITIONS)->keyBy(0);
        $this->assertSame('higher',$definitions['damage'][3]);
        $this->assertSame('lower',$definitions['reload'][3]);
        $this->assertSame('neutral',$definitions['price'][3]);
        $text = (new EquipmentDescriptionGenerator)->generate(['name'=>'Test Rifle','weaponType'=>'Rifle','caliber'=>'Medium',
            '_item_type'=>'weapon','stats'=>['combat'=>['damage'=>145,'magazine'=>8],'ballistics'=>['muzzleVelocity'=>340]]]);
        $this->assertStringContainsString('145',$text);
        $this->assertStringContainsString('340',$text);
        $this->assertStringContainsString('8',$text);
    }

    public function test_changed_feed_wrapper_is_rejected(): void
    {
        Http::fake([
            'https://wiki.huntify.win/Hunt/modules/Skins/data.js' => Http::response('window.Hunt.data.register("skins", []);',200),
            'https://wiki.huntify.win/Hunt/modules/Weapons/data.js' => Http::response('<html>changed</html>',200),
        ]);
        $this->expectException(RuntimeException::class);
        iterator_to_array((new HuntifyEquipmentSource)->items());
    }

    public function test_german_descriptions_use_structured_facts_and_clean_labels(): void
    {
        $generator = new EquipmentDescriptionGenerator;
        $rifle = ['name'=>'1865 Carbine','_item_type'=>'weapon','weaponType'=>'Rifle','caliber'=>'Medium',
            'lore'=>'Source prose must not appear','stats'=>['combat'=>['damage'=>145],'ballistics'=>['muzzleVelocity'=>340]]];
        $de = $generator->generate($rifle,'de');
        $en = $generator->generate($rifle,'en');
        $this->assertStringContainsString('Gewehr',$de);
        $this->assertStringContainsString('Mittel',$de);
        $this->assertStringContainsString('145',$de);
        $this->assertStringContainsString('340 m/s',$de);
        $this->assertStringNotContainsString('mediumer Munition',$de);
        $this->assertStringNotContainsString('ein Rifle',$de);
        $this->assertStringNotContainsString('Source prose',$de);
        $this->assertStringNotContainsString('Source prose',$en);
        $tool = $generator->generate(['name'=>'First Aid Kit','_item_type'=>'tool','category'=>'Healing',
            'description'=>'Do not copy this'], 'de');
        $this->assertStringContainsString('Werkzeugkategorie',$tool);
        $this->assertStringContainsString('Heilung',$tool);
        $this->assertStringNotContainsString('Do not copy',$tool);
        $consumable = $generator->generate(['name'=>'Frag Bomb','_item_type'=>'consumable','category'=>'Explosive',
            'description'=>'Do not copy this','stats'=>['throwDamage'=>150]], 'de');
        $this->assertStringContainsString('Verbrauchsgegenstand',$consumable);
        $this->assertStringContainsString('150',$consumable);
        $this->assertStringNotContainsString('Do not copy',$consumable);
    }

    public function test_comparison_group_uses_structured_labels_not_translated_names(): void
    {
        $service = app(EquipmentSyncService::class);
        $this->assertSame('weapon:rifle',$service->comparisonGroup(['category'=>'Rifle'],'weapon','Rifle'));
        $this->assertSame('weapon:pistol',$service->comparisonGroup(['category'=>'Pistol'],'weapon','Pistol'));
        $this->assertSame('tool:melee',$service->comparisonGroup(['category'=>'Melee / Throwable'],'tool','Melee / Throwable'));
        $this->assertSame('tool:healing',$service->comparisonGroup(['category'=>'Healing'],'tool','Healing'));
        $this->assertSame('consumable:shot',$service->comparisonGroup(['category'=>'Poison','labels'=>['Boon']],'consumable','Poison'));
        $this->assertSame('consumable:shot',$service->comparisonGroup(['category'=>'Restoration','gameId'=>'2econsumableboostwc0003'],'consumable','Restoration'));
        $this->assertSame('consumable:explosive',$service->comparisonGroup(['category'=>'Explosive','name'=>'Some Shot'],'consumable','Explosive'));
    }
}
