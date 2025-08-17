<?php

declare(strict_types=1);

namespace ecstsy\AetherisRecode\listeners;

use ecstsy\AetherisRecode\Loader;
use ecstsy\AetherisRecode\player\skills\SkillType;
use ecstsy\AetherisRecode\utils\Utils;
use ecstsy\MartianUtilities\utils\GeneralUtils;
use pocketmine\block\VanillaBlocks;
use pocketmine\event\block\BlockBreakEvent;
use pocketmine\event\Listener;
use pocketmine\item\StringToItemParser;
use pocketmine\player\Player;

final class SkillsListener implements Listener
{
    /** @var array<int, string> blockTypeId */
    private array $blockMap;

    public function __construct()
    {
        $this->blockMap = [
            VanillaBlocks::WHEAT()->getTypeId() => 'wheat',
            VanillaBlocks::POTATOES()->getTypeId() => 'potato',
            VanillaBlocks::CARROTS()->getTypeId() => 'carrot',
            VanillaBlocks::BEETROOTS()->getTypeId() => 'beetroot',
            VanillaBlocks::NETHER_WART()->getTypeId() => 'nether_wart',
            VanillaBlocks::PUMPKIN()->getTypeId() => 'pumpkin',
            VanillaBlocks::MELON()->getTypeId() => 'melon',
            VanillaBlocks::SUGARCANE()->getTypeId() => 'sugar_cane',
            VanillaBlocks::BAMBOO()->getTypeId() => 'bamboo',
            VanillaBlocks::COCOA_POD()->getTypeId() => 'cocoa',
            VanillaBlocks::CACTUS()->getTypeId() => 'cactus',
            VanillaBlocks::BROWN_MUSHROOM()->getTypeId() => 'brown_mushroom',
            VanillaBlocks::RED_MUSHROOM()->getTypeId() => 'red_mushroom',
            StringToItemParser::getInstance()->parse("kelp")->getTypeId() => 'kelp',
            VanillaBlocks::SEA_PICKLE()->getTypeId() => 'sea_pickle',
            VanillaBlocks::SWEET_BERRY_BUSH()->getTypeId()=> 'sweet_berry_bush',
            StringToItemParser::getInstance()->parse("glow_berries")->getTypeId() => 'glow_berries',
        ];
    }

    public function onFarmingSkill(BlockBreakEvent $event): void {
        $player = $event->getPlayer();
        $block = $event->getBlock();

        if (!$player instanceof Player) return;

        if (!Utils::isFullyGrownCrop($block)) return;

        $typeId = $block->getTypeId();
        $key = $this->blockMap[$typeId] ?? null;
        if ($key === null) return;

        $config = GeneralUtils::getConfiguration(Loader::getInstance(), "config.yml");
        $xpMap = $config->getNested("settings.skills.farming");
        $floatXp = $xpMap[$key] ?? null;

        if ($floatXp <= 0.0) return;

        $xpToAdd = round($floatXp, 2);
        $session = Loader::getInstance()->getPlayerManager()->getSession($player);

        $session->addSkillXp(SkillType::FARMING, $xpToAdd);
    }
}