<?php

declare(strict_types=1);

namespace ecstsy\AetherisRecode\listeners;

use ecstsy\AetherisRecode\enchantments\CustomEnchantment;
use ecstsy\AetherisRecode\enchantments\CustomEnchantmentManager;
use ecstsy\AetherisRecode\enchantments\CustomEnchantments;
use ecstsy\AetherisRecode\enchantments\Groups;
use ecstsy\AetherisRecode\entity\other\FloatingTextEntity;
use ecstsy\AetherisRecode\Loader;
use ecstsy\AetherisRecode\server\items\AetherisItemFactory;
use ecstsy\AetherisRecode\server\items\stardrops\StarDrop;
use ecstsy\AetherisRecode\tasks\PouchRevealTask;
use ecstsy\AetherisRecode\utils\Utils;
use ecstsy\MartianUtilities\utils\GeneralUtils;
use ecstsy\MartianUtilities\utils\PlayerUtils;
use pocketmine\block\tile\Chest;
use pocketmine\block\utils\DyeColor;
use pocketmine\block\VanillaBlocks;
use pocketmine\color\Color;
use pocketmine\entity\Location;
use pocketmine\event\block\BlockBreakEvent;
use pocketmine\utils\TextFormat as C;
use pocketmine\event\block\BlockPlaceEvent;
use pocketmine\event\inventory\InventoryTransactionEvent;
use pocketmine\event\Listener;
use pocketmine\event\player\PlayerInteractEvent;
use pocketmine\event\player\PlayerItemUseEvent;
use pocketmine\event\player\PlayerQuitEvent;
use pocketmine\inventory\transaction\action\SlotChangeAction;
use pocketmine\inventory\transaction\InventoryTransaction;
use pocketmine\item\VanillaItems;
use pocketmine\math\Vector3;
use pocketmine\network\mcpe\protocol\RemoveActorPacket;
use pocketmine\scheduler\ClosureTask;
use pocketmine\world\particle\BlockBreakParticle;
use pocketmine\world\particle\DustParticle;
use pocketmine\world\sound\AnvilFallSound;
use pocketmine\world\sound\AnvilUseSound;
use pocketmine\world\sound\BlockBreakSound;
use pocketmine\world\sound\ChestOpenSound;
use pocketmine\world\sound\XpCollectSound;

final class ItemListener implements Listener
{

    public static array $starDropSessions = []; 
    private static ?Color $black = null;

    public function onBlockPlace(BlockPlaceEvent $event): void
    {
        $player = $event->getPlayer();
        $name = strtolower($player->getName());
        $item = $event->getItem();
        $tag = $item->getNamedTag();

        if ($tag->getTag("Aetheris") !== null) {
            $event->cancel();
        }

        if (isset(ItemListener::$starDropSessions[$name])) {
            $session = ItemListener::$starDropSessions[$name];
            if ($event->getItem()->getBlock()->getPosition()->equals($session['pos'])) {
                $event->cancel();
            }
        }
    }

    public function onBlockBreak(BlockBreakEvent $event): void {
        $player = $event->getPlayer();
        $name = strtolower($player->getName());
        if (isset(ItemListener::$starDropSessions[$name])) {
            $session = ItemListener::$starDropSessions[$name];
            if ($event->getBlock()->getPosition()->equals($session['pos'])) {
                $event->cancel();
            }
        }
    }


    public function onPlayerQuit(PlayerQuitEvent $event): void {
        $player = $event->getPlayer();
        $name = strtolower($player->getName());

        if (!isset(self::$starDropSessions[$name])) {
            return;
        }

        $session = &self::$starDropSessions[$name];

        if (isset($session['floatingText']) && $session['floatingText'] instanceof FloatingTextEntity && !$session['floatingText']->isClosed()) {
            $session['floatingText']->flagForDespawn();
        }
        if (isset($session['tapText']) && $session['tapText'] instanceof FloatingTextEntity && !$session['tapText']->isClosed()) {
            $session['tapText']->flagForDespawn();
        }

        StarDrop::removeStarDropChest($player, $session['pos']);

        $rarity = $session['rarity'];
        $starDrop = StarDrop::$drops[$rarity] ?? null;
        if ($starDrop !== null && !empty($starDrop->rewardPool)) {
            $reward = $starDrop->rollReward();
            $player->getWorld()->dropItem($session['pos']->add(0.5, 1, 0.5), $reward);
        }

        unset(self::$starDropSessions[$name]);
    }

    /**
     * @param PlayerInteractEvent $event
     *
     * Handles player interaction with StarDrop chests.
     */
    public function starDropInteract(PlayerInteractEvent $event): void {
        $player = $event->getPlayer();
        $block = $event->getBlock();
        $name = strtolower($player->getName());

        if (!isset(self::$starDropSessions[$name])) return;

        $session = &self::$starDropSessions[$name];

        if (($session['claimed'] ?? false) === true) {
            $event->cancel();
            return;
        }

        if (!StarDrop::isStarDropBlock($block, $session['pos'])) return;

        $event->cancel();
        $session['taps']++;

        if ($session['taps'] < 3) {
            StarDrop::handleStarDropTap($player, $session);
            return;
        }

        StarDrop::handleStarDropClaim($player, $session, $name);
    }

    public function onItemUse(PlayerItemUseEvent $event): void
    {
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

        if ($itemTag === "enchantment_book") {
            $event->cancel();
            $enchant = $aetherisTag->getString("enchant_book", "");
            $enchantment = CustomEnchantments::getEnchantmentByName($enchant);

            if ($enchantment === null) {
                $player->sendMessage(C::colorize("&r&cUnknown enchantment!"));
                return;
            }

            $level = $aetherisTag->getInt("level") ?: null;

            if ($level === null) {
                $player->sendMessage(C::colorize("&r&cInvalid level!"));
                return;
            }

            $color = Groups::translateGroupToColor($enchantment->getRarity());
            $player->sendMessage(C::colorize("&r&7 * &eEnchantment &7| " . str_replace("{group-color}", $color, CustomEnchantments::getEnchantmentDisplayName($enchantment->getName(), $color))));
            $player->sendMessage(C::colorize("&r&7 * &eApplies to &7| &f" . implode(", ", $enchantment->getApplicableItems())));
            $player->sendMessage(C::colorize("&r&7 * &eMax Level &7| &f" . GeneralUtils::getRomanNumeral($enchantment->getMaxLevel())));
            $player->sendMessage(C::colorize("&r&7 * &eDescription &7| &f" . $enchantment->getDescription()));
            return;
        }

        if ($itemTag === "money_pouch") {
            $event->cancel();

            if ($session->getCooldown("pouch") > 0) { 
                $remainingCooldown = $session->getCooldown("pouch");

                $player->sendMessage(C::RED . C::BOLD . "[!]" . C::RESET . C::GRAY . " Please wait " . GeneralUtils::translateTime($remainingCooldown) . " seconds before opening another pouch.");
                return;
            }
        }

        if ($itemTag === "econote") {
            $amount = $aetherisTag->getInt("worth", 0);
            $count = $item->getCount();

            if ($session->getSetting("quick_claim") && $player->isSneaking() && $count > 1) {
                $total = $amount * $count;
                $session->addBalance($total);
                $player->sendActionBarMessage(C::colorize("&r&l&a+ &r&a$" . number_format($total) . " added to your balance!"));
                $player->getWorld()->addSound($player->getPosition(), new XpCollectSound());
                $item->pop($item->getCount());
                $player->getInventory()->setItemInHand($item);
            } else {
                if ($amount > 0) {
                    $session->addBalance($amount);
                    $player->sendActionBarMessage(C::colorize("&r&l&a+ &r&a$" . number_format($amount) . " added to your balance!"));
                    $player->getWorld()->addSound($player->getPosition(), new XpCollectSound());
                    $item->pop();
                    $player->getInventory()->setItemInHand($item);
                }
            }

            return;
        }

        if ($itemTag === "xpnote") {
            $event->cancel();
            $amount = $aetherisTag->getInt("worth", 0);
            $count = $item->getCount();

            if ($session->getSetting("quick_claim") && $player->isSneaking() && $count > 1) {
                $total = $amount * $count;
                $player->getXpManager()->addXp($total);
                $player->sendMessage(C::colorize("&r&l&a+ &r&a" . number_format($total) . " EXP added!"));
                $player->getWorld()->addSound($player->getPosition(), new XpCollectSound());
                $item->pop($item->getCount());
                $player->getInventory()->setItemInHand($item);
            } else {
                if ($amount > 0) {
                    $player->getXpManager()->addXp($amount);
                    $player->sendMessage(C::colorize("&r&l&a+ &r&a" . number_format($amount) . " EXP added!"));
                    $player->getWorld()->addSound($player->getPosition(), new XpCollectSound());
                    $item->pop();
                    $player->getInventory()->setItemInHand($item);
                }
            }
            return;
        }

        if ($itemTag === "random_book") {
            $event->cancel();
            $group = $aetherisTag->getString("group", "");
            $groupEnchants = CustomEnchantments::getByGroup($group);
            $color = Groups::translateGroupToColor(Groups::getGroupId($group));

            if (empty($groupEnchants)) {
                $player->sendMessage(C::colorize("&r&4Failed to examine '&fRC Book&r&4'"));
                $player->sendMessage(C::colorize("&r&cThere are no enchantments in the group '&f" . $group . "&r&4'"));   
                return;
            }

            $randomEnchant = $groupEnchants[array_rand($groupEnchants)];

            $level = mt_rand(1, $randomEnchant->getMaxLevel());
            $success = mt_rand(1, 100);
            $destroy = mt_rand(1, 100);
            $enchantBook = AetherisItemFactory::enchantmentBook($randomEnchant, $level, $success, $destroy);

            if ($player->getInventory()->canAddItem($enchantBook)) {
                $player->getInventory()->addItem($enchantBook);
            } else {
                $session->createCollectionItem('overflow', [$enchantBook]);
            }
            
            PlayerUtils::playSound($player, "random.levelup");
            $item->pop();
            $player->getInventory()->setItemInHand($item);
            $player->sendMessage(C::colorize(str_replace(["{group-color}", "{group-name}", "{enchant-color}", "{level}"], [$color, $group, CustomEnchantments::getEnchantmentDisplayName($randomEnchant->getName(), $color), GeneralUtils::getRomanNumeral($level)], "&r&7You examined {group-color}{group-name}&r&7 book and found {enchant-color} {level}")));
        }

        if ($itemTag === "kit_token") {
            $kit = $aetherisTag->getString("kit", "");

            if (!in_array($kit, ['initiate', 'explorer', 'champion', 'warden', 'aetherian'])) {
            $player->sendMessage(C::colorize("&r&cInvalid kit token!"));
            return;
            }

            $kitName = ucfirst($kit);
            $items = Utils::getKitRankKitItems($kit);

            $player->sendActionBarMessage(C::colorize("&r&a&l✔ &r&2You have claimed the &2" . $kitName . "&a kit!"));
            PlayerUtils::playSound($player, "random.levelup");

            foreach ($items as $item) {
                if ($player->getInventory()->canAddItem($item)) {
                    $player->getInventory()->addItem($item);
                } else {
                    $session->createCollectionItem('overflow', [$item]);
                }
            }

            $item->pop();
            $player->getInventory()->setItemInHand($item);  
        }

        if ($itemTag === "money_pouch") {
            $event->cancel();
            $tier = $aetherisTag->getInt("tier");

            match ($tier) {
                1 => $amount = mt_rand(1000, 10000),
                2 => $amount = mt_rand(10001, 50000),
                3 => $amount = mt_rand(50001, 200000),
            };

            $session->addCooldown("pouch", 5);
            $player->getWorld()->addSound($player->getPosition(), new ChestOpenSound());
            $task = new PouchRevealTask($player, $amount, "money", $tier);
            Loader::getInstance()->getScheduler()->scheduleRepeatingTask($task, 20);
            $item->pop();
            $player->getInventory()->setItemInHand($item);
        }

        if ($itemTag === "xp_pouch") {
            $event->cancel();
            $tier = $aetherisTag->getInt("tier");

            match ($tier) {
                1 => $amount = mt_rand(20, 400),
                2 => $amount = mt_rand(401, 1000),
                3 => $amount = mt_rand(1001, 5000),
            };

            $session->addCooldown("pouch", 5);
            $player->getWorld()->addSound($player->getPosition(), new ChestOpenSound());
            $task = new PouchRevealTask($player, $amount, "xp", $tier);
            Loader::getInstance()->getScheduler()->scheduleRepeatingTask($task, 20);
            $item->pop();
            $player->getInventory()->setItemInHand($item);
        }

        if ($itemTag === 'star_drop') {
            $event->cancel();
            $rarity = $aetherisTag->getString('rarity');
            $result = StarDrop::spawnStarDropChest($player, $rarity);
            if ($result !== null) {
                $item->pop();
                $player->getInventory()->setItemInHand($item);
            }
        }
    }

    public function onDragNDropEnchant(InventoryTransactionEvent $event): void
    {
        $transaction = $event->getTransaction();
        $actions = array_values($transaction->getActions());

        if (count($actions) === 2) {
            foreach ($actions as $i => $action) {
                if (!$action instanceof SlotChangeAction) continue;

                if (($otherAction = $actions[($i + 1) % 2]) instanceof SlotChangeAction && ($itemClickedWith = $action->getTargetItem())->getTypeId() === VanillaItems::ENCHANTED_BOOK()->getTypeId()) {
                    if (($itemClicked = $action->getSourceItem())->getTypeId() !== VanillaItems::AIR()->getTypeId() && $itemClickedWith->getCount() === 1) {
                        $nbt = $itemClickedWith->getNamedTag()->getCompoundTag("Aetheris");
                        if ($nbt !== null && $nbt->getTag("enchant_book") !== null) {
                            $event->cancel();
                            $enchantName = $nbt->getString("enchant_book");
                            $enchantment = CustomEnchantments::getEnchantmentByName($enchantName);
                            $player = $transaction->getSource();

                            $level     = $nbt->getInt("level");
                            $success   = $nbt->getInt("success");
                            $destroy   = $nbt->getInt("destroy");

                            if ($enchantment === null) {
                                $player->sendMessage(C::colorize("&r&cUnknown enchantment!"));
                                return;
                            }

                            $customEnchantCount = 0;

                            foreach (CustomEnchantmentManager::getEnchantments($itemClicked) as $enchant) {
                                $customEnchantCount++;
                            }


                            $currentMax = 9;

                            if ($customEnchantCount >= $currentMax) {
                                $player->sendMessage(C::colorize("&r&cYou have reached the maximum amount of custom enchantments!"));
                                return;
                            }

                            if (!$enchantment->matches($itemClicked)) {
                                $player->sendMessage(C::colorize("&cThat item can’t receive this enchant."));
                                return;
                            }

                            if (mt_rand(1, 100) <= $success) {
                                $oldLevel = $existing[$enchantName] ?? 0;
                                $newLevel = max($oldLevel, min($level, $enchantment->getMaxLevel()));
                                CustomEnchantmentManager::applyEnchantment($itemClicked, $enchantment, $newLevel);
                                $player->sendMessage(C::colorize("&aEnchantment applied!"));
                            } else {
                                if (mt_rand(1, 100) <= $destroy) {
                                    $player->sendMessage(C::colorize("&cEnchantment failed and book was destroyed."));
                                } else {
                                    $player->sendMessage(C::colorize("&eEnchantment failed, but book survived."));
                                }
                            }

                            $action->getInventory()->setItem($action->getSlot(), $itemClicked);
                            $otherAction->getInventory()->setItem($otherAction->getSlot(), VanillaItems::AIR());
                        }
                    }
                }
            }
        }
    }
}
