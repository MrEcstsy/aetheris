<?php

declare(strict_types=1);

namespace ecstsy\AetherisRecode\enchantments\handler;

use ecstsy\AetherisRecode\enchantments\CustomEnchantment;
use pocketmine\event\entity\EntityDamageByEntityEvent;

interface EntityDamageByEntityEnchantmentHandler {
    public static function handle(EntityDamageByEntityEvent $event, CustomEnchantment $enchant, int $level): void;
}