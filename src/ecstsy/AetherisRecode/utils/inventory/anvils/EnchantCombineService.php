<?php
namespace ecstsy\AetherisRecode\utils\inventory\anvils;

use ecstsy\AetherisRecode\utils\XPByLevel;
use pocketmine\item\enchantment\EnchantmentInstance;
use pocketmine\item\Item;

final class EnchantCombineService {
    public function applyEnchants(Item $source, Item $material): int {
        $xp = 0;
        foreach ($material->getEnchantments() as $inst) {
            $type  = $inst->getType();
            $level = $inst->getLevel();

            if ($source->hasEnchantment($type)) {
                $old = $source->getEnchantment($type)->getLevel();
                if ($old === $level && $old + 1 <= $type->getMaxLevel()) {
                    $level++;
                }
                $xp += XPByLevel::getCost($level - $old, $type);
            } else {
                $xp += XPByLevel::getCost($level, $type);
            }
            $source->addEnchantment(new EnchantmentInstance($type, $level));
        }
        return $xp;
    }
}
