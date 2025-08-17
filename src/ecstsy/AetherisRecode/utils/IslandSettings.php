<?php

declare(strict_types=1);

namespace ecstsy\AetherisRecode\utils;

final class IslandSettings
{
    // Settings constants
    public const TYPE = 'type';
    public const VALUE_VISIBILITY = 'value_visibility';
    public const LEAF_DECAY = 'leaf_decay';
    public const ICE_FORMING = 'ice_forming';
    public const FIRE_SPREAD = 'fire_spread';
    public const CROP_TRAMPLE = 'crop_trample';
    public const WEATHER = 'weather';
    public const TIME = 'time';
    public const ENTITY_GRIEF = 'entity_grief';
    public const TNT_DAMAGE = 'tnt_damage';
    public const VISITING = 'visiting';

    // Possible values
    public const TYPE_PRIVATE = 'private';
    public const TYPE_PUBLIC = 'public';
    
    public const VISIBILITY_PUBLIC = 'public';
    public const VISIBILITY_PRIVATE = 'private';
    
    public const WEATHER_SERVER = 'server';
    public const WEATHER_SUNNY = 'sunny';
    public const WEATHER_RAINING = 'raining';
    
    public const TIME_SERVER = 'server';
    public const TIME_DAY = 'day';
    public const TIME_NIGHT = 'night';
    public const TIME_CYCLE = 'cycle';

    public static function getDefaults(): array
    {
        return [
            self::TYPE => self::TYPE_PRIVATE,
            self::VALUE_VISIBILITY => self::VISIBILITY_PRIVATE,
            self::LEAF_DECAY => false,
            self::ICE_FORMING => false,
            self::FIRE_SPREAD => false,
            self::CROP_TRAMPLE => false,
            self::WEATHER => self::WEATHER_SERVER,
            self::TIME => self::TIME_SERVER,
            self::ENTITY_GRIEF => false,
            self::TNT_DAMAGE => false,
            self::VISITING => true
        ];
    }

    public static function getHumanName(string $setting): string
    {
        return match($setting) {
            self::TYPE => "Island Type",
            self::VALUE_VISIBILITY => "Value Visibility",
            self::LEAF_DECAY => "Leaf Decay",
            self::ICE_FORMING => "Ice Formation",
            self::FIRE_SPREAD => "Fire Spread",
            self::CROP_TRAMPLE => "Crop Trampling",
            self::WEATHER => "Weather Control",
            self::TIME => "Time Control",
            self::ENTITY_GRIEF => "Entity Griefing",
            self::TNT_DAMAGE => "TNT Damage",
            self::VISITING => "Allow Visiting",
            default => ucwords(str_replace('_', ' ', $setting))
        };
    }

    public static function getPossibleValues(string $setting): array
    {
        return match($setting) {
            self::TYPE => [self::TYPE_PRIVATE, self::TYPE_PUBLIC],
            self::VALUE_VISIBILITY => [self::VISIBILITY_PRIVATE, self::VISIBILITY_PUBLIC],
            self::WEATHER => [self::WEATHER_SERVER, self::WEATHER_SUNNY, self::WEATHER_RAINING],
            self::TIME => [self::TIME_SERVER, self::TIME_DAY, self::TIME_NIGHT, self::TIME_CYCLE],
            default => [true, false] 
        };
    }

    public static function getAllSettings(): array
    {
        return array_keys(self::getDefaults());
    }
}