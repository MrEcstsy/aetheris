<?php

declare(strict_types=1);

namespace ecstsy\AetherisRecode\enchantments\handler;

use ecstsy\AetherisRecode\enchantments\CustomEnchantment;
use pocketmine\event\entity\EntityDamageEvent;

interface EntityDamageEnchantmentHandler {
    
    public static function handle(EntityDamageEvent $event, CustomEnchantment $enchant, int $level): void;
}