<?php

declare(strict_types=1);

namespace ecstsy\AetherisRecode\player\skills;

use ReflectionClass;

final class SkillType
{
    public const FARMING = 'farming';
    public const FORAGING = 'foraging';
    public const MINING = 'mining';
    public const FISHING = 'fishing';
    public const EXCAVATION = 'excavation';
    public const ARCHERY = 'archery';
    public const DEFENSE = 'defense';
    public const FIGHTING = 'fighting';
    public const ENDURANCE = 'endurance';
    public const AGILITY = 'agility';
    public const ALCHEMY = 'alchemy';
    public const ENCHANTING = 'enchanting';
    public const SORCERY = 'sorcery';
    public const HEALING = 'healing';
    public const FORGING = 'forging';

    public static function getAllSkillNames(): array {
        $ref = new ReflectionClass(SkillType::class);
        return array_values($ref->getConstants());
    }   

}
