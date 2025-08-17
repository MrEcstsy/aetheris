<?php

declare(strict_types=1);

namespace ecstsy\AetherisRecode\listeners;

use ecstsy\AetherisRecode\Loader;
use ecstsy\AetherisRecode\skyblock\SkyBlock;
use ecstsy\AetherisRecode\utils\SkyblockSettingTypes;
use ecstsy\MartianUtilities\utils\GeneralUtils;
use pocketmine\block\Chest;
use pocketmine\block\Door;
use pocketmine\event\block\BlockBreakEvent;
use pocketmine\event\block\BlockPlaceEvent;
use pocketmine\event\entity\EntityDamageByEntityEvent;
use pocketmine\event\entity\EntityDamageEvent;
use pocketmine\event\entity\EntityItemPickupEvent;
use pocketmine\event\Listener;
use pocketmine\event\player\PlayerChatEvent;
use pocketmine\utils\TextFormat as C;
use pocketmine\event\player\PlayerInteractEvent;
use pocketmine\item\Food;
use pocketmine\player\Player;

final class SkyblockListener implements Listener
{

    protected function resolveRole(Player $player, SkyBlock $sb): string
    {
        $session = Loader::getPlayerManager()->getSession($player);
        if ($session->getSetting('isVisiting') === true) {
            return 'visitor';
        }
        $uuid = $player->getUniqueId()->toString();
        $member = $sb->getMember($uuid);
        return $member['role'] ?? 'visitor';
    }

    /**
     * @priority MONITOR
     */
    public function onBreak(BlockBreakEvent $event): void
    {
        $player = $event->getPlayer();
        $world  = $player->getWorld();
        $sb     = Loader::getSkyBlockManager()->getSkyBlockByWorld($world);
        if (!$sb instanceof SkyBlock) {
            return;
        }

        $role = $this->resolveRole($player, $sb);
        if (!$sb->canRole($role, 'break')) {
            $player->sendMessage(C::colorize("&r&l&cHey! &r&fYou can't break blocks here."));
            $event->cancel();
            return;
        }

        $config = GeneralUtils::getConfiguration(Loader::getInstance(), "config.yml");
        if ($config->getNested("settings.server.auto-inventory.enabled") === true) {
            $drops = [];
            foreach ($event->getDrops() as $drop) {
                if (!$player->getInventory()->canAddItem($drop)) {
                    $drops[] = $drop;
                } else {
                    $player->getInventory()->addItem($drop);
                }
            }
            $event->setDrops(
                $config->getNested("settings.server.auto-inventory.drop-when-full")
                    ? $drops
                    : []
            );
        }
        if ($config->getNested("settings.server.auto-inventory.xp") === true) {
            $player->getXpManager()->addXp($event->getXpDropAmount());
            $event->setXpDropAmount(0);
        }
    }

    public function onPlace(BlockPlaceEvent $event): void
    {
        $player = $event->getPlayer();
        $sb     = Loader::getSkyBlockManager()->getSkyBlockByWorld($player->getWorld());
        if (!$sb instanceof SkyBlock) {
            return;
        }

        $role = $this->resolveRole($player, $sb);
        if (!$sb->canRole($role, 'place')) {
            $player->sendMessage(C::colorize("&r&l&cHey! &r&fYou can't place blocks here."));
            $event->cancel();
        }
    }

    public function onInteract(PlayerInteractEvent $event): void
    {
        $player = $event->getPlayer();
        $sb     = Loader::getSkyBlockManager()->getSkyBlockByWorld($player->getWorld());
        if (!$sb instanceof SkyBlock) {
            return;
        }

        if ($event->getItem() instanceof Food) {
            return;
        }

        $role = $this->resolveRole($player, $sb);
        $block = $event->getBlock();

        if ($block instanceof Chest && !$sb->canRole($role, 'open-containers')) {
            $player->sendMessage(C::colorize("&r&l&cHey! &r&fYou can't open containers here."));
            $event->cancel();
        } elseif ($block instanceof Door && !$sb->canRole($role, 'interact_door')) {
            $player->sendMessage(C::colorize("&r&l&cHey! &r&fYou can't open doors here."));
            $event->cancel();
        }
    }

    public function onPlayerDamage(EntityDamageEvent $event): void
    {
        $e = $event->getEntity();
        if (!$e instanceof Player) {
            return;
        }
        $sb = Loader::getSkyBlockManager()->getSkyBlockByWorld($e->getWorld());
        if (!$sb instanceof SkyBlock) {
            return;
        }

        $role = $this->resolveRole($e, $sb);

        if ($event instanceof EntityDamageByEntityEvent) {
            if (!$sb->canRole($role, 'kill-mobs')) {
                $event->cancel();
            }
            return;
        }

        $config = GeneralUtils::getConfiguration(Loader::getInstance(), "config.yml");
        $cause  = $event->getCause();
        $shouldCancel = match ($cause) {
            EntityDamageEvent::CAUSE_LAVA       => $config->getNested("settings.skyblock.damage.lava"),
            EntityDamageEvent::CAUSE_DROWNING  => $config->getNested("settings.skyblock.damage.drowning"),
            EntityDamageEvent::CAUSE_FALL      => $config->getNested("settings.skyblock.damage.fall"),
            EntityDamageEvent::CAUSE_PROJECTILE => $config->getNested("settings.skyblock.damage.projectile"),
            EntityDamageEvent::CAUSE_FIRE      => $config->getNested("settings.skyblock.damage.fire"),
            EntityDamageEvent::CAUSE_VOID      => $config->getNested("settings.skyblock.damage.void"),
            EntityDamageEvent::CAUSE_STARVATION => $config->getNested("settings.skyblock.damage.hunger"),
            default                             => $config->getNested("settings.skyblock.damage.default")
        };
        if ($shouldCancel) {
            $event->cancel();
        }
    }

    public function onPickUp(EntityItemPickupEvent $event): void
    {
        $player = $event->getEntity();
        if (!$player instanceof Player) {
            return;
        }
        $sb = Loader::getSkyBlockManager()->getSkyBlockByWorld($player->getWorld());
        if (!$sb instanceof SkyBlock) {
            return;
        }

        $role = $this->resolveRole($player, $sb);
        if (!$sb->canRole($role, 'pickup')) {
            $event->cancel();
        }
    }
}
