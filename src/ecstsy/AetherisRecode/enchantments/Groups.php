<?php

namespace ecstsy\AetherisRecode\enchantments;

use ecstsy\AetherisRecode\Loader;
use pocketmine\item\enchantment\StringToEnchantmentParser;
use pocketmine\utils\TextFormat as C;

final class Groups {

    private const GROUPS = [
        'SIMPLE'    => ['id' => 1, 'global_color' => "&7", 'group_name' => "Simple"],
        'UNIQUE'    => ['id' => 2, 'global_color' => "&a", 'group_name' => "Unique"],
        'ELITE'     => ['id' => 3, 'global_color' => "&b", 'group_name' => "Elite"],
        'EXOTIC'  => ['id' => 4, 'global_color' => "&e", 'group_name' => "Exotic"],
        'LEGENDARY' => ['id' => 5, 'global_color' => "&6", 'group_name' => "Legendary"],
    ];

    private static ?string $fallbackGroup = 'SIMPLE';

    public static function getGroupData(string $groupName): ?array {
        return self::GROUPS[strtoupper($groupName)] ?? self::GROUPS[self::$fallbackGroup] ?? null;
    }

    public static function getFallbackGroup(): string {
        return self::$fallbackGroup;
    }

    public static function getGroupId(string $groupName): ?int {
        return self::GROUPS[strtoupper($groupName)]['id'] ?? null;
    }

    public static function translateGroupToColor(int $groupId): string {
        foreach (self::GROUPS as $group) {
            if ($group['id'] === $groupId) {
                return C::colorize($group['global_color']);
            }
        }
        return C::colorize(self::GROUPS[self::$fallbackGroup]['global_color']);
    }

    public static function getGroupName(int $groupId): ?string {
        foreach (self::GROUPS as $group) {
            if ($group['id'] === $groupId) {
                return $group['group_name'];
            }
        }
        return self::GROUPS[self::$fallbackGroup]['group_name'];
    }

    public static function getGroupNameById(int $groupId): ?string {
        foreach (self::GROUPS as $name => $group) {
            if ($group['id'] === $groupId) {
                return $name;
            }
        }
        return self::$fallbackGroup;
    }
}