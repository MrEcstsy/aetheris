<?php

declare(strict_types=1);

namespace ecstsy\AetherisRecode\enchantments\enchants\hoe;

use ecstsy\AetherisRecode\enchantments\CustomEnchantment;
use ecstsy\AetherisRecode\enchantments\CustomEnchantmentManager;
use ecstsy\AetherisRecode\enchantments\handler\BlockBreakEnchantmentHandler;
use ecstsy\AetherisRecode\Loader;
use ecstsy\AetherisRecode\tasks\AutoPlanterTask;
use ecstsy\AetherisRecode\utils\Utils;
use pocketmine\block\Block;
use pocketmine\block\Carrot;
use pocketmine\block\Potato;
use pocketmine\block\Beetroot;
use pocketmine\block\NetherWartPlant;
use pocketmine\block\VanillaBlocks;
use pocketmine\block\Wheat;
use pocketmine\event\block\BlockBreakEvent;
use pocketmine\item\Item;
use pocketmine\item\VanillaItems;
use pocketmine\scheduler\ClosureTask;
use pocketmine\world\particle\HappyVillagerParticle;
use pocketmine\world\sound\BlockPlaceSound;

final class AutoPlanterEnchant implements BlockBreakEnchantmentHandler {

    private static ?AutoPlanterTask $task = null;
    public static function setTask(AutoPlanterTask $t): void {
        self::$task = $t;
    }

    public static function handle(BlockBreakEvent $event, CustomEnchantment $enchant, int $level): void {
        if ($event->isCancelled() || $level < 1) {
            return;
        }

        $player = $event->getPlayer();
        $block  = $event->getBlock();
        $hand   = $player->getInventory()->getItemInHand();

        if (!CustomEnchantmentManager::hasEnchantment($hand, "autoplanter")) {
            return;
        }

        if (!Utils::isFullyGrownCrop($block)) {
            return;
        }

        $seed = self::getSeedForCrop($block);
        if ($seed === null || !$player->getInventory()->contains($seed)) {
            return;
        }

        if (self::$task !== null) {
            self::$task->add($block, $player, $seed);
        }
    }

    private static function getSeedForCrop(Block $block): ?Item {
        return match (true) {
            $block instanceof Wheat =>
                VanillaItems::WHEAT_SEEDS(),

            $block instanceof Carrot =>
                VanillaItems::CARROT(),

            $block instanceof Potato =>
                VanillaItems::POTATO(),

            $block instanceof Beetroot =>
                VanillaItems::BEETROOT_SEEDS(),

            $block instanceof NetherWartPlant =>
                VanillaItems::NETHER_WART(),

            default => null,
        };
    }
}
