<?php

declare(strict_types=1);

namespace ecstsy\AetherisRecode\tasks;

use pocketmine\block\Beetroot;
use pocketmine\block\Block;
use pocketmine\block\Carrot;
use pocketmine\block\NetherWartPlant;
use pocketmine\block\Potato;
use pocketmine\block\VanillaBlocks;
use pocketmine\block\Wheat;
use pocketmine\item\Item;
use pocketmine\player\Player;
use pocketmine\scheduler\Task;
use pocketmine\world\particle\HappyVillagerParticle;
use pocketmine\world\sound\BlockPlaceSound;

final class AutoPlanterTask extends Task
{
    private array $queue = [];

    public function add(Block $block, Player $player, Item $seed): void {
        $this->queue[] = [$block, $player, $seed];
    }

    public function onRun(): void
    {
        foreach ($this->queue as list($block, $player, $seed)) {
            if ($block instanceof Block) {
                $pos = $block->getPosition();
                $world = $pos->getWorld();

                $newCrop = match(true) {
                    $block instanceof Wheat => VanillaBlocks::WHEAT()->setAge(0),
                    $block instanceof Carrot => VanillaBlocks::CARROTS()->setAge(0),
                    $block instanceof Potato => VanillaBlocks::POTATOES()->setAge(0),
                    $block instanceof Beetroot => VanillaBlocks::BEETROOTS()->setAge(0),
                    $block instanceof NetherWartPlant => VanillaBlocks::NETHER_WART()->setAge(0),
                    default => null,
                };

                if ($newCrop === null) {
                    return;
                }

                $world->setBlock($pos, $newCrop, true, true);
                $player->getInventory()->removeItem($seed);

                $world->addSound($pos->add(0.5, 0.5, 0.5), new BlockPlaceSound($block));

                $center = $pos->add(0.5, 0.5, 0.5);
                $world->addParticle($center, new HappyVillagerParticle());
            }
        }
        $this->queue = [];
    }
}