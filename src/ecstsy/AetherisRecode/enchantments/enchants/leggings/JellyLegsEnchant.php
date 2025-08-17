<?php

declare(strict_types=1);

namespace ecstsy\AetherisRecode\enchantments\enchants\leggings;

use ecstsy\AetherisRecode\enchantments\CustomEnchantment;
use ecstsy\AetherisRecode\enchantments\CustomEnchantmentManager;
use ecstsy\AetherisRecode\enchantments\handler\EntityDamageEnchantmentHandler;
use pocketmine\entity\Living;
use pocketmine\event\entity\EntityDamageEvent;

final class JellyLegsEnchant implements EntityDamageEnchantmentHandler {

    public static function handle(EntityDamageEvent $event, CustomEnchantment $enchant, int $level): void
    {
    if ($event->isCancelled()) {
            return;
        }

        $entity = $event->getEntity();

        if ($event->getCause() !== EntityDamageEvent::CAUSE_FALL) {
            return;
        }

        if (!$entity instanceof Living) {
            return;
        }

        $legs = $entity->getArmorInventory()->getLeggings();

        if (!CustomEnchantmentManager::hasEnchantment($legs, "jellylegs")) {
            return;
        }

        $level = CustomEnchantmentManager::getLevel($legs, "jellylegs");
        $chance = 100;

        match ($level) {
            1 => $chance = 40,
            2 => $chance = 80,
            3 => $chance = 100,
            default => $chance = 0,
        };

        if (mt_rand(1, 100) <= $chance) {
            $event->cancel();
        }
    }
}