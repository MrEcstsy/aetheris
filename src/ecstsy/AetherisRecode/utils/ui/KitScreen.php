<?php

declare(strict_types=1);

namespace ecstsy\AetherisRecode\utils\ui;

use ecstsy\AetherisRecode\Loader;
use ecstsy\AetherisRecode\utils\inventory\CustomSizedInvMenu;
use ecstsy\AetherisRecode\utils\Utils;
use ecstsy\MartianUtilities\utils\GeneralUtils;
use ecstsy\MartianUtilities\utils\InventoryUtils;
use ecstsy\MartianUtilities\utils\PlayerUtils;
use ecstsy\MartianUtilities\utils\screens\BaseScreen;
use muqsit\invmenu\InvMenu;
use muqsit\invmenu\transaction\DeterministicInvMenuTransaction;
use pocketmine\block\utils\DyeColor;
use pocketmine\block\VanillaBlocks;
use pocketmine\item\VanillaItems;
use pocketmine\player\Player;
use pocketmine\utils\TextFormat as C;

final class KitScreen extends BaseScreen {

    private static InvMenu $menu;

    public function __construct(Player $player) {
        self::$menu = CustomSizedInvMenu::create(45);
        $inventory = self::$menu->getInventory();
        $session = Loader::getPlayerManager()->getSession($player);

        self::$menu->setName(c::colorize("&r&8Kits"));

        $blackPane = VanillaBlocks::STAINED_GLASS_PANE()->setColor(DyeColor::BLACK())->asItem();
        $purplePane = VanillaBlocks::STAINED_GLASS_PANE()->setColor(DyeColor::PURPLE())->asItem();

        $purpleSlots = [0, 1, 9, 35, 44, 43];

        InventoryUtils::fillInventory($inventory, $blackPane, $purpleSlots);
        
        foreach ($purpleSlots as $slot) {
            $inventory->setItem($slot, $purplePane);
        }

        $kits = [
            13 => ["id" => "member", "name" => "&l&7Member Kit", "cooldown" => 60, "content" => ["Protection I", "Unbreaking I"]],
            20 => ["id" => "initiate", "name" => "&l&9Initiate Kit", "cooldown" => 120, "content" => ["Protection I", "Feather Falling I", "Sharpness I"]],
            21 => ["id" => "explorer", "name" => "&l&cExplorer Kit", "cooldown" => 180, "content" => ["Protection II", "Depth Strider I", "Efficiency II"]],
            22 => ["id" => "champion", "name" => "&l&6Champion Kit", "cooldown" => 300, "content" => ["Protection III", "Thorns I", "Fire Aspect I"]],
            23 => ["id" => "warden", "name" => "&l&aWarden Kit", "cooldown" => 600, "content" => ["Protection IV", "Soul Speed I", "Looting II"]],
            24 => ["id" => "aetherian", "name" => "&l&dAetherian Kit", "cooldown" => 1200, "content" => ["Protection V", "Frost Walker II", "Fortune IV"]]
        ];
    
        foreach ($kits as $slot => $kit) {
            $cooldownRemaining = $session->getCooldown($kit['id']);
            $item = $cooldownRemaining > 0 
                ? VanillaBlocks::STAINED_GLASS_PANE()->setColor(DyeColor::ORANGE())->asItem() 
                : ($kit['id'] === "member" ? VanillaItems::BOOK() : VanillaItems::ENCHANTED_BOOK());
            $item->setCustomName(C::colorize("&r&7" . $kit['name']))
                ->setLore(array_merge([
                    "",
                    C::colorize("&r&8✦ &7Contents:")
                ], array_map(fn($line) => C::colorize("&r&8 » $line"), $kit['content']), [
                    "",
                    $cooldownRemaining > 0
                        ? C::colorize("&r&c⚠ On Cooldown! Ends in: &6" . GeneralUtils::translateTime($cooldownRemaining))
                        : C::colorize("&r&a✓ Available! Click to claim.")
                ]));
            $inventory->setItem($slot, $item);
        }

        self::$menu->setListener(InvMenu::readonly(function (DeterministicInvMenuTransaction $transaction) use ($kits, $session, $inventory) {
            $player = $transaction->getPlayer();
            $action = $transaction->getAction();
            $slot = $action->getSlot();
        
            if (!isset($kits[$slot])) return;
        
            $kit = $kits[$slot];
            $cooldownRemaining = $session->getCooldown($kit['id']);
        
            if ($cooldownRemaining > 0) {
                $currentItem = $inventory->getItem($slot);
                if (
                    $currentItem->getTypeId() !== VanillaBlocks::STAINED_GLASS_PANE()->asItem()->getTypeId() || 
                    $currentItem->getCustomName() !== C::colorize("&r&7" . $kit['name'])
                ) {
                    $cooldownItem = VanillaBlocks::STAINED_GLASS_PANE()->setColor(DyeColor::ORANGE())->asItem()
                        ->setCustomName(C::colorize("&r&7" . $kit['name']))
                        ->setLore([
                            "",
                            C::colorize("&r&c⚠ On Cooldown! Ends in: &6" . GeneralUtils::translateTime($cooldownRemaining)),
                            C::colorize("&r&8 » Come back later!")
                        ]);
                    $inventory->setItem($slot, $cooldownItem);
                }
        
                $player->sendToastNotification(
                    C::colorize(Loader::SERVER_TITLE),
                    C::colorize("&r&c⚠ Kit on cooldown! Ends in " . GeneralUtils::translateTime($cooldownRemaining))
                );
                PlayerUtils::playSound($player, "note.bass");
                return;
            }
        
            $session->addCooldown($kit['id'], $kit['cooldown']);
            PlayerUtils::playSound($player, "random.levelup");
        
            $player->getInventory()->addItem(Utils::createKitToken(strtolower($kit['id'])));
            $player->sendToastNotification(
                C::colorize(Loader::SERVER_TITLE),
                C::colorize("&r&a✔ {$kit['name']} successfully claimed!")
            );
        
            $cooldownItem = VanillaBlocks::STAINED_GLASS_PANE()->setColor(DyeColor::ORANGE())->asItem()
                ->setCustomName(C::colorize("&r&7" . $kit['name']))
                ->setLore([
                    "",
                    C::colorize("&r&c⚠ On Cooldown! Ends in: &6" . GeneralUtils::translateTime($kit['cooldown'])),
                    C::colorize("&r&8 » Come back later!")
                ]);
            $inventory->setItem($slot, $cooldownItem);
        }));        
    }

    public static function display(Player $player): void {
        $kitScreen = new self($player);
        $kitScreen->getMenu()->send($player);
    }
    
    public function getMenu(): InvMenu
    {
        return self::$menu;
    }
}