<?php

namespace Tests\Unit;

use App\Models\EquipmentFamily;
use App\Models\EquipmentItem;
use App\Services\Equipment\WikiGgEquipmentSource;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class WikiGgEquipmentSourceTest extends TestCase
{
    public function test_weapon_page_is_read_from_mediawiki_wikitext(): void
    {
        Http::fake([
            'https://huntshowdown.wiki.gg/api.php*' => Http::response([
                'parse' => [
                    'wikitext' => <<<'WIKI'
{{Infobox Weapon
|Title=1865 Carbine
|image=Weapon 3D 1865 Carbine.png
|Price=70 {{Hunt Dollars}}
|Size=3
|Ammo Type=Medium
|Update=2.0
|Loaded=7+1
|Extra=21
|Damage=145
|Drop Range=115
|Rate of Fire=22
|Cycle Time=1.8
|Spread=23
|Sway=77
|Vertical Recoil=4
|Reload Speed=8.5
|Muzzle Velocity=340
|Melee Damage=27
|Heavy Melee Damage=54
|Heavy Stamina Consumption=25
}}

== Recommended Traits ==
* {{Trait|Iron Eye}} - Remain in iron sights between shots.

== Ammo Types ==
* {{Ammo|FMJ Ammo}}
* {{Ammo|Subsonic Ammo}}

== Skins ==
{{Infobox Weapon Skin
|Title=Tree Feeder
|Weapon={{Weapon|1865 Carbine}}
|Rarity={{Rarity|Legendary}}
}}
{{Infobox Weapon Skin
|Title=Spirit Caller
|Weapon={{Weapon|1865 Carbine}}
|Rarity={{Rarity|Legendary}}
}}

== Update History ==
{| class="wikitable"
! Update !! Patch Notes
|-
| [[Update/2.8|Update 2.8]] || 1865 Carbine slot size changed from Large to 3
|-
| [[Update/2.4|Update 2.4]] || Minimum rifle damage changed
|}
WIKI,
                ],
            ], 200),
        ]);

        $item = new EquipmentItem;
        $item->forceFill([
            'slug' => '1865-carbine',
            'external_id' => 'test-1865',
            'name' => '1865 Carbine',
            'item_type' => 'weapon',
        ]);

        $data = (new WikiGgEquipmentSource)->preview($item);

        $this->assertSame('Weapons/1865_Carbine', $data['page_title']);
        $this->assertSame(70, $data['price']);
        $this->assertSame(3, $data['slot_size']);
        $this->assertSame('Medium', $data['ammo_type']);
        $this->assertSame('7+1', $data['loaded']);
        $this->assertSame('21', $data['reserve']);
        $this->assertSame(145, $data['stats']['damage']);
        $this->assertSame(115, $data['stats']['dropRange']);
        $this->assertSame(340, $data['stats']['muzzleVelocity']);
        $this->assertSame(['Iron Eye'], $data['recommended_traits']);
        $this->assertSame(['FMJ Ammo', 'Subsonic Ammo'], $data['ammo_types']);
        $this->assertSame(['Tree Feeder', 'Spirit Caller'], $data['skins']);
        $this->assertCount(2, $data['patch_history']);
        $this->assertSame('Update 2.8', $data['patch_history'][0]['patch']);
    }

    public function test_weapon_variant_uses_family_subpage_path(): void
    {
        $family = new EquipmentFamily;
        $family->forceFill(['name' => '1865 Carbine']);

        $item = new EquipmentItem;
        $item->forceFill([
            'name' => '1865 Carbine Aperture',
            'item_type' => 'weapon',
        ]);
        $item->setRelation('family', $family);

        $this->assertSame(
            'Weapons/1865_Carbine/Aperture',
            (new WikiGgEquipmentSource)->pageTitle($item)
        );
    }

    public function test_tool_and_consumable_use_their_wiki_namespaces(): void
    {
        $tool = new EquipmentItem;
        $tool->forceFill(['name' => 'Throwing Spear', 'item_type' => 'tool']);

        $consumable = new EquipmentItem;
        $consumable->forceFill(['name' => 'Frag Bomb', 'item_type' => 'consumable']);

        $source = new WikiGgEquipmentSource;

        $this->assertSame('Tools/Throwing_Spear', $source->pageTitle($tool));
        $this->assertSame('Consumables/Frag_Bomb', $source->pageTitle($consumable));
    }
}
