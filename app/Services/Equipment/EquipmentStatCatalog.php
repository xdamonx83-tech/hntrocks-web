<?php
namespace App\Services\Equipment;

use App\Models\EquipmentStatDefinition;

class EquipmentStatCatalog
{
    public const DEFINITIONS = [
        ['damage','Damage',null,'higher','combat'], ['rateOfFire','Rate of Fire',null,'higher','combat'],
        ['magazine','Loaded',null,'higher','combat'], ['reserve','Reserve Ammo',null,'higher','combat'],
        ['cycleTime','Cycle Time','s','lower','combat'], ['muzzleVelocity','Muzzle Velocity','m/s','higher','ballistics'],
        ['dropRange','Drop Range','m','higher','ballistics'],
        ['effectiveRange','Effective Range','m','higher','ballistics'], ['reload','Reload','s','lower','handling'],
        ['spread','Spread',null,'lower','handling'], ['sway','Sway',null,'lower','handling'],
        ['recoil','Recoil',null,'lower','handling'], ['melee','Melee Damage',null,'higher','melee'],
        ['heavyMelee','Heavy Melee Damage',null,'higher','melee'], ['stamina','Stamina Cost',null,'lower','melee'],
        ['heavyStamina','Heavy Stamina Cost',null,'lower','melee'], ['throwDamage','Throw Damage',null,'higher','combat'],
        ['throwRange','Throw Range','m','higher','handling'], ['fuseTimer','Fuse Timer','s','neutral','handling'],
        ['radius','Radius','m','higher','combat'], ['effectDuration','Effect Duration','s','higher','combat'],
        ['swapSpeed','Swap Speed','s','lower','handling'], ['throwStamina','Throw Stamina Cost',null,'lower','melee'],
        ['price','Price',null,'neutral','economy'], ['slotSize','Slot Size',null,'neutral','economy'],
        ['baseDamage','Base Damage',null,'neutral','ballistics'], ['zoom','Zoom','×','neutral','handling'],
        ['headMultiplier','Head Multiplier','×','neutral','ballistics'],
        ['upperTorsoMultiplier','Upper Torso Multiplier','×','neutral','ballistics'],
        ['torsoMultiplier','Torso Multiplier','×','neutral','ballistics'],
        ['armMultiplier','Arm Multiplier','×','neutral','ballistics'],
        ['legMultiplier','Leg Multiplier','×','neutral','ballistics'],
    ];

    public function ensure(): void
    {
        foreach (self::DEFINITIONS as $order => [$key,$label,$unit,$direction,$group]) {
            EquipmentStatDefinition::updateOrCreate(['key' => $key], [
                'label' => $label, 'unit' => $unit, 'comparison_direction' => $direction,
                'group' => $group, 'sort_order' => ($order + 1) * 10,
            ]);
        }
    }

    public function values(array $row): array
    {
        $raw = $row['stats'] ?? [];
        $flat = [];
        foreach ($raw as $key => $value) {
            if (is_array($value) && $key !== '_sources') $flat += $value;
            elseif (is_numeric($value)) $flat[$key] = $value;
        }
        $flat['price'] = $row['cost'] ?? null;
        $flat['slotSize'] = $row['slots'] ?? null;
        $allowed = array_column(self::DEFINITIONS, 0);
        return array_filter(array_intersect_key($flat, array_flip($allowed)), 'is_numeric');
    }
}
