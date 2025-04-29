<?php

declare(strict_types=1);

namespace ecstsy\AetherisRecode\listeners;

use ecstsy\AetherisRecode\Loader;
use ecstsy\AetherisRecode\player\skills\Skill;
use ecstsy\AetherisRecode\player\skills\SkillType;
use ecstsy\AetherisRecode\utils\Utils;
use pocketmine\block\Beetroot;
use pocketmine\block\BlockTypeIds;
use pocketmine\block\Crops;
use pocketmine\event\block\BlockBreakEvent;
use pocketmine\event\entity\EntityDamageEvent;
use pocketmine\event\entity\EntityDeathEvent;
use pocketmine\event\Listener;
use pocketmine\event\player\PlayerInteractEvent;
use pocketmine\player\Player;
use pocketmine\Server;
use pocketmine\world\WorldManager;

final class SkillsListener implements Listener {

    public static array $lastKills = [];

    public function onBlockBreak(BlockBreakEvent $event): void {
        $player = $event->getPlayer();
        $block = $event->getBlock();
        $skillManager = Loader::getSkillManager();
        $skills = $skillManager->getSkillsByPlayerUuid($player->getUniqueId()->toString());
        $foragingSkill = $skills[SkillType::FORAGING] ?? null;
        $miningSkill = $skills[SkillType::MINING] ?? null;
        $session = Loader::getPlayerManager()->getSession($player);

        if (!$foragingSkill instanceof Skill) {
            $foragingSkill = new Skill($player->getUniqueId(), SkillType::FORAGING, 0, 0);  
            $skills[SkillType::FORAGING] = $foragingSkill;        
        }

        if (!$miningSkill instanceof Skill) {
            $miningSkill = new Skill($player->getUniqueId(), SkillType::MINING, 0, 0);  
            $skills[SkillType::MINING] = $miningSkill;        
        } 

        $logs = [
            BlockTypeIds::OAK_LOG,
            BlockTypeIds::BIRCH_LOG,
            BlockTypeIds::JUNGLE_LOG,
            BlockTypeIds::ACACIA_LOG,
            BlockTypeIds::DARK_OAK_LOG,
            BlockTypeIds::SPRUCE_LOG,
            BlockTypeIds::CHERRY_LOG,
            BlockTypeIds::MANGROVE_LOG,
            BlockTypeIds::CRIMSON_STEM,
            BlockTypeIds::WARPED_STEM,
        ];

        $ores = [
            BlockTypeIds::COAL_ORE,
            BlockTypeIds::IRON_ORE,
            BlockTypeIds::GOLD_ORE,
            BlockTypeIds::REDSTONE_ORE,
            BlockTypeIds::DIAMOND_ORE,
            BlockTypeIds::EMERALD_ORE,
            BlockTypeIds::LAPIS_LAZULI_ORE,
            BlockTypeIds::NETHER_GOLD_ORE,
            BlockTypeIds::NETHER_QUARTZ_ORE,
            BlockTypeIds::COPPER_ORE,
            BlockTypeIds::DEEPSLATE_COAL_ORE,
            BlockTypeIds::DEEPSLATE_IRON_ORE,
            BlockTypeIds::DEEPSLATE_GOLD_ORE,
            BlockTypeIds::DEEPSLATE_REDSTONE_ORE,
            BlockTypeIds::DEEPSLATE_DIAMOND_ORE,
            BlockTypeIds::DEEPSLATE_EMERALD_ORE,
            BlockTypeIds::DEEPSLATE_LAPIS_LAZULI_ORE,
            BlockTypeIds::DEEPSLATE_COPPER_ORE,
        ];

        foreach ($logs as $log) {
            if ($block->getTypeId() === $log) {
                $foragingSkill->addExperience();
            }
        }

        if ($session->getSkyblock() === null) {
            return;
        }

        var_dump("session sb: " . $session->getSkyblock());

        $worldName = Loader::getSkyBlockManager()->getSkyBlockByUuid($session->getSkyblock())->getWorld();
        
        if ($player->getWorld()->getFolderName() === Server::getInstance()->getWorldManager()->getWorldByName($worldName)->getFolderName()) {
            foreach ($ores as $ore) {
                if ($block->getTypeId() === $ore) {
                    $$miningSkill->addExperience();
                }
            }
        }
    }

    public function onPlayerInteract(PlayerInteractEvent $event): void {
        $player = $event->getPlayer();
        $action = $event->getAction();
        $block = $event->getBlock();
        $item = $event->getItem();

        $skillManager = Loader::getSkillManager();
        $skills = $skillManager->getSkillsByPlayerUuid($player->getUniqueId()->toString());
        $farmingSkill = $skills[SkillType::FARMING] ?? null;
    
        if (!$farmingSkill instanceof Skill) {
            $farmingSkill = new Skill($player->getUniqueId(), SkillType::FARMING, 0, 0);  
            $skills[SkillType::FARMING] = $farmingSkill;
        }

        $crops = [
            BlockTypeIds::WHEAT,
            BlockTypeIds::CARROTS,
            BlockTypeIds::POTATOES,
            BlockTypeIds::BEETROOTS,
            BlockTypeIds::MELON,
            BlockTypeIds::PUMPKIN,
            BlockTypeIds::SUGARCANE,
            BlockTypeIds::CACTUS,
        ];

        if ($action === PlayerInteractEvent::RIGHT_CLICK_BLOCK) {
            foreach ($crops as $crop) {
                if ($block->getTypeId() === $crop) {
                    if ($block instanceof Crops) {
                        if ($block->getAge() <= 8) {
                            return;
                        }

                        if ($block instanceof Beetroot) {
                            if ($block->getAge() <= 4) {
                                return;
                            }
                        }
                    }

                    $farmingSkill->addExperience();
                }
            }
        }
    }

    public function onEntityDeath(EntityDeathEvent $event): void {
        $entity = $event->getEntity();


        $cause = $entity->getLastDamageCause();

        if ($cause instanceof EntityDamageEvent) {
            $player = $cause->getEntity();
            if ($player instanceof Player) {
                $skillManager = Loader::getSkillManager();
                $skills = $skillManager->getSkillsByPlayerUuid($player->getUniqueId()->toString());
                $combatSkill = $skills[SkillType::COMBAT];

                if (!$combatSkill instanceof Skill) {
                    return;
                }

                if ($entity instanceof Player) {
                    if (Utils::hasPlayerKillSkillCooldown($player, $entity)) {
                        return;
                    }

                    $combatSkill->addExperience(mt_rand(4, 5));
                    Utils::updateLastKillTime($player, $entity);
                } else {
                    $combatSkill->addExperience();
                }
            }
        }
    }
}