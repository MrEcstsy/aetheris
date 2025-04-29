<?php

declare(strict_types=1);

namespace ecstsy\AetherisRecode\listeners;

use ecstsy\AetherisRecode\Loader;
use ecstsy\AetherisRecode\utils\Utils;
use ecstsy\MartianUtilities\utils\PlayerUtils;
use pocketmine\utils\TextFormat as C;
use pocketmine\event\block\BlockPlaceEvent;
use pocketmine\event\Listener;
use pocketmine\event\player\PlayerItemUseEvent;
use pocketmine\world\sound\XpCollectSound;

final class ItemListener implements Listener {

    public function onBlockPlace(BlockPlaceEvent $event): void {
        $item = $event->getItem();
        $tag = $item->getNamedTag();

        if ($tag->getTag("Aetheris") !== null) {
            $event->cancel();
        }
    }

    public function onItemUse(PlayerItemUseEvent $event): void {
        $player = $event->getPlayer();
        $item = $event->getItem();
        $nbt = $item->getNamedTag();
        $aetherisTag = $nbt->getCompoundTag("Aetheris");
        $session = Loader::getPlayerManager()->getSession($player);

        if ($aetherisTag === null || !$aetherisTag->getTag("aetherisItem")) {
            return;
        }

        $itemTag = $aetherisTag->getString("aetherisItem");

        if ($itemTag === "debugstick") {
            //$entity = new TestEntity($player->getLocation());
            //$entity->spawnToAll();
            $player->sendMessage("§r§c§l** §r§cYou have spawned a test entity.");
            return;
        }

        $item->pop();
        $player->getInventory()->setItemInHand($item);

        if ($itemTag === "banknote") {
            $amount = $aetherisTag->getInt("worth", 0);

            if ($amount > 0) {
                $session->addBalance($amount);
                $player->sendActionBarMessage(C::colorize("&r&l&a+ &r&a$" . number_format($amount) . " added to your balance!"));
                $player->getWorld()->addSound($player->getPosition(), new XpCollectSound());
            }
            return;
        }

        if ($itemTag === "xpnote") {
            $amount = $aetherisTag->getInt("worth", 0);

            if ($amount > 0) {
                $player->getXpManager()->addXp($amount);
                $player->sendMessage(C::colorize("&r&l&a+ &r&a" . number_format($amount) . " EXP added!"));
                $player->getWorld()->addSound($player->getPosition(), new XpCollectSound());
            }
            return;
        }

        $kits = ['initiate_kit', 'explorer_kit', 'champion_kit', 'warden_kit', 'aetherian_kit'];

        foreach ($kits as $kit) {
            if ($itemTag === $kit) {
                $kitName = str_replace("_kit", "", $kit);
                $items = Utils::getKitRankKitItems($kitName);

                $player->sendActionBarMessage(C::colorize("&r&a&l✔ &r&2You have claimed the &2" . $kitName . "&a kit!"));
                PlayerUtils::playSound($player, "random.levelup");

                foreach ($items as $item) {
                    if ($player->getInventory()->canAddItem($item)) {
                        $player->getInventory()->addItem($item);
                    } else {
                        $session->createCollectionItem('overflow', [$item]);
                    }
                }

            }
        }
    }
}