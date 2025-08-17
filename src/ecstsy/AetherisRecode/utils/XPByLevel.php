<?php
namespace ecstsy\AetherisRecode\utils;

use pocketmine\item\enchantment\Enchantment;
use pocketmine\lang\Language;
use pocketmine\lang\Translatable;
use pocketmine\Server;

final class XPByLevel {
    /**
     * @var int[]  // maps the *lowercased*, human name of the enchant to its per‐level XP cost
     */
    private static array $costPerLevel = [
        // Tools
        "curse of vanishing"   => 8,
        "unbreaking"           => 2,
        "mending"              => 4,
        "sharpness"            => 1,
        "smite"                => 2,
        "efficiency"           => 1,
        "fortune"              => 4,
        "bane of arthropods"   => 2,
        "knockback"            => 2,
        "fire aspect"          => 4,
        "looting"              => 4,
        "sweeping edge"        => 8,
        "silk touch"           => 8,
        // Armor
        "protection"           => 1,
        "fire protection"      => 2,
        "projectile protection"=> 2,
        "blast protection"     => 4,
        "respiration"          => 4,
        "aqua affinity"        => 4,
        "thorns"               => 8,
        "curse of binding"     => 8,
        "feather falling"      => 2,
        "frost walker"         => 4,
        "depth strider"        => 4,
        "soul speed"           => 8,
        // Bow
        "power"                => 1,
        "punch"                => 4,
        "flame"                => 1,
        "infinity"             => 8,
        // Crossbow
        "piercing"             => 1,
        "multishot"            => 4,
        "quick charge"         => 2,
        // Trident
        "impaling"             => 2,
        "loyalty"              => 1,
        "riptide"              => 4,
        "channeling"           => 8,
        // Fishing rod
        "lure"                 => 4,
        "luck of the sea"      => 4,
    ];

    /**
     * Given a number of levels delta (e.g. +1) and an EnchantmentInstance or EnchantmentType,
     * return the XP cost.
     */
    public static function getCost(int $levelDelta, Enchantment $type): int {
        $lang = new Language(Language::FALLBACK_LANGUAGE);
        $raw  = Server::getInstance()->getLanguage()->translate($type->getName()); 
        $human = $raw instanceof Translatable
            ? $lang->translate($raw)
            : $lang->translateString($raw);

        $key = strtolower($human);
        $perLevel = self::$costPerLevel[$key] ?? 1;  
        return $perLevel * $levelDelta;
    }
}
