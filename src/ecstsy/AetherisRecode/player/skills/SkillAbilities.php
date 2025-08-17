<?php

declare(strict_types=1);

namespace ecstsy\AetherisRecode\player\skills;

final class SkillAbilities 
{
    /**
     * A tiny “blueprint” for each ability:
     *  - name            Human‑readable name
     *  - tiers           How many tiers (levels of the ability)
     *  - startUnlock     Skill‑level you unlock tier 1
     *  - unlockStep      +X skill‑levels between each tier
     *  - effectTemplate  A sprintf‑style template where %d will be replaced by the “effect value”
     *  - effectStart     Base effect value at tier 1
     *  - effectStep      +Y effect‑value per extra tier
     */
    private const BLUEPRINTS = [
        SkillType::FARMING => [
            [
                "key"             => "bountiful_harvest",
                "name"            => "Bountiful Harvest",
                "tiers"           => 97,
                "startUnlock"     => 2,
                "unlockStep"      => 5,
                "effectTemplate"  => "+%d%% chance to drop double crops",
                "effectStart"     => 10,
                "effectStep"      => 5,
                "description"     => "Increases crop yield"
            ],
            [
                "key"             => "farmer",
                "name"            => "Farmer",
                "tiers"           => 97,
                "startUnlock"     => 3,
                "unlockStep"      => 5,
                "effectTemplate"  => "Earn +%d%% more Farming XP",
                "effectStart"     => 10,
                "effectStep"      => 10,
                "description"     => "Grants double Farming XP"
            ],
            [
                "key"             => "scythe_master",
                "name"            => "Scythe Master",
                "tiers"           => 97,
                "startUnlock"     => 4,
                "unlockStep"      => 5,
                "effectTemplate"  => "Increases damage from hoes by +%d%%",
                "effectStart"     => 10,
                "effectStep"      => 2,
                "description"     => "Increases hoe damage"
            ],
            [
                "key" => "geneticist",
                "name" => "Geneticist",
                "tiers" => 97,
                "startUnlock" => 5,
                "unlockStep" => 5,
                "effectTemplate" => "Increases saturation gain from plant-based foods by +%d%%",
                "effectStart" => 1,
                "effectStep" => 2,
                "description" => "Increases saturation gain from plant-based foods"
            ],
            [
                "key" => "triple_harvest",
                "name" => "Triple Harvest",
                "tiers" => 97,
                "startUnlock" => 6,
                "unlockStep" => 5,
                "effectTemplate" => "+%d%% Chance to get triple drops from crops",
                "effectStart" => 5,
                "effectStep" => 3,
                "description" => "Increases saturation gain from plant-based foods"
            ],
        ],
        SkillType::FORAGING => [
            [
                "key"             => "lumberjack",
                "name"            => "Lumberjack",
                "tiers"           => 20,
                "startUnlock"     => 2,
                "unlockStep"      => 5,
                "effectTemplate"  => "+%d%% chance for extra logs",
                "effectStart"     => 4,
                "effectStep"      => 3,
                "description"     => "Grants extra wood when chopping"
            ],
        ],
        // …and so on for MINING, FIGHTING, etc.
    ];

    public static function getAbilities(string $skill): array {
        $out = [];

        foreach (self::BLUEPRINTS[$skill] ?? [] as $bp) {
            $levels = [];
            $effects = [];

            for ($tier = 1; $tier <= $bp['tiers']; $tier++) {
                $levels[$tier] = $bp['startUnlock'] + ($tier - 1) * $bp['unlockStep'];
                $value = $bp['effectStart'] + ($tier - 1) * $bp['effectStep'];
                $effects[$tier] = sprintf($bp['effectTemplate'], $value);
            }

            $out[] = [
                "key" => $bp['key'],
                "name" => $bp['name'],
                "levels" => $levels,
                "effects" => $effects,
                "description" => $bp['description']
            ];
        }
        
        return $out;
    }
}