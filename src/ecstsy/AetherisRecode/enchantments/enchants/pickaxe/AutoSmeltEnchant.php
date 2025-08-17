<?php

declare(strict_types=1);

namespace ecstsy\AetherisRecode\enchantments\enchants\pickaxe;

use ecstsy\AetherisRecode\enchantments\CustomEnchantment;
use ecstsy\AetherisRecode\enchantments\CustomEnchantmentManager;
use ecstsy\AetherisRecode\enchantments\handler\BlockBreakEnchantmentHandler;
use ecstsy\AetherisRecode\Loader;
use ecstsy\MartianUtilities\utils\GeneralUtils;
use pocketmine\block\VanillaBlocks;
use pocketmine\event\block\BlockBreakEvent;
use pocketmine\item\VanillaItems;

final class AutoSmeltEnchant implements BlockBreakEnchantmentHandler {

    public static function handle(BlockBreakEvent $event, CustomEnchantment $enchant, int $level): void
    {
        if ($event->isCancelled()) {
            return;
        }

        $player = $event->getPlayer();
        $item = $player->getInventory()->getItemInHand();

        if (!CustomEnchantmentManager::hasEnchantment($item, "autosmelt")) {
            return;
        }

        $newDrops = $event->getDrops();

        foreach ($newDrops as $k => $drop) {
            $newItem = match (true) {
                $drop->equals(VanillaItems::RAW_COPPER()) => VanillaItems::COPPER_INGOT(),
                $drop->equals(VanillaItems::RAW_IRON()) => VanillaItems::IRON_INGOT(),
                $drop->equals(VanillaItems::RAW_GOLD()) => VanillaItems::GOLD_INGOT(),
                $drop->equals(VanillaBlocks::COBBLESTONE()->asItem()) => VanillaBlocks::STONE()->asItem(),
                default => null
            };

            if ($newItem !== null) {
                $newItem->setCount($drop->getCount());
                $newDrops[$k] = $newItem;
            }
        }

        $config = GeneralUtils::getConfiguration(Loader::getInstance(), "config.yml");
        $autoInventory = $config->getNested("settings.server.auto-inventory.enabled") === true;

        if ($autoInventory) {
            $remaining = [];
            foreach ($newDrops as $drop) {
                if (!$player->getInventory()->canAddItem($drop)) {
                    $remaining[] = $drop;
                } else {
                    $player->getInventory()->addItem($drop);
                }
            }
            $event->setDrops($remaining);
        } else {
            $event->setDrops($newDrops);
        }
    }
}