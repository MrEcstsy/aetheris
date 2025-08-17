<?php

declare(strict_types=1);

namespace ecstsy\AetherisRecode\enchantments\enchants\chestplate;

use ecstsy\AetherisRecode\enchantments\CustomEnchantment;
use ecstsy\AetherisRecode\enchantments\handler\EntityDamageByEntityEnchantmentHandler;
use pocketmine\entity\Living;
use pocketmine\event\entity\EntityDamageByEntityEvent;
use pocketmine\player\Player;

final class BlazedEnchant implements EntityDamageByEntityEnchantmentHandler {

    public static function handle(EntityDamageByEntityEvent $event, CustomEnchantment $enchant, int $level): void {
        $defender = $event->getEntity();
        $attacker = $event->getDamager();

        if ($defender instanceof Player && $attacker instanceof Living) {
            $chance = 0.2 * $level;
            if (mt_rand() / mt_getrandmax() < $chance) {
                $attacker->setOnFire(3 + $level); 
            }
        }
    }
}