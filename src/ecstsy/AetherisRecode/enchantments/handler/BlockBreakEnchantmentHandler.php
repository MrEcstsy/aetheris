<?php

declare(strict_types=1);

namespace ecstsy\AetherisRecode\enchantments\handler;

use ecstsy\AetherisRecode\enchantments\CustomEnchantment;
use pocketmine\event\block\BlockBreakEvent;

interface BlockBreakEnchantmentHandler {
    
    public static function handle(BlockBreakEvent $event, CustomEnchantment $enchant, int $level): void;
}