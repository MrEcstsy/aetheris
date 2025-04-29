<?php

declare(strict_types=1);

namespace ecstsy\AetherisRecode\utils\ui;

use ecstsy\AetherisRecode\Loader;
use ecstsy\AetherisRecode\utils\inventory\CustomSizedInvMenu;
use ecstsy\AetherisRecode\utils\ui\skyblock\IslandSettingsScreen;
use ecstsy\MartianUtilities\utils\InventoryUtils;
use ecstsy\MartianUtilities\utils\PlayerUtils;
use ecstsy\MartianUtilities\utils\screens\BaseScreen;
use IvanCraft623\RankSystem\RankSystem;
use muqsit\invmenu\InvMenu;
use muqsit\invmenu\transaction\DeterministicInvMenuTransaction;
use pocketmine\block\utils\DyeColor;
use pocketmine\block\VanillaBlocks;
use pocketmine\item\VanillaItems;
use pocketmine\player\Player;
use pocketmine\utils\TextFormat as C;

final class MainMenuScreen extends BaseScreen {

    private InvMenu $menu;

    public function __construct(Player $player)
    {
        $this->menu = CustomSizedInvMenu::create(45);
        $rankSessionManager = RankSystem::getInstance()->getSessionManager();
        $rankSession = $rankSessionManager->get($player);
        $inventory = $this->menu->getInventory();
        $purplePane = VanillaBlocks::STAINED_GLASS_PANE()->setColor(DyeColor::PURPLE())->asItem()->setCustomName(" ");
        $blackPane = VanillaBlocks::STAINED_GLASS_PANE()->setColor(DyeColor::BLACK())->asItem()->setCustomName(" ");
        $session = Loader::getPlayerManager()->getSession($player);

        $purpleSlots = [0, 1, 9, 7, 8, 17, 27, 36, 37, 35, 44, 43];
        $menuItems = [
            4 => VanillaItems::TOTEM()->setCustomName(C::colorize("&r&e&l" . $player->getName() . "'s Profile:"))->setLore([
                "",
                C::colorize("&r&6» &7Rank: " . $rankSession->getHighestRank()->getName()),
                C::colorize("&r&6» &7Balance: &e$" . number_format($session->getBalance())),
                "",
                C::colorize("&r&a&l(!) &r&2Click to view more!")
            ]),
            10 => VanillaBlocks::GRASS()->asItem()->setCustomName(C::colorize("&r&l&eSpawn"))->setLore([
                C::colorize("&r&7Go to Spawn and meet the"),
                C::colorize("&r&7available things to do!"),
                "",
                C::colorize("&r&a&l(!) &r&2Click to view more!")
            ]),
            12 => VanillaBlocks::BEACON()->asItem()->setCustomName(C::colorize("&r&e&lWarps"))->setLore([
                C::colorize("&r&7See all Available"),
                C::colorize("&r&7public Warps!"),
                "",
                C::colorize("&r&a&l(!) &r&2Click to view more!")
            ]),
            14 => VanillaBlocks::ANVIL()->asItem()->setCustomName(C::colorize("&r&l&eIsland Upgrades"))->setLore([
                C::colorize("&r&7Upgrade Your island to get"),
                C::colorize("&r&7a best gaming experience."),
                "",
                C::colorize("&r&a&l(!) &r&2Click to view more!")
            ]),
            16 => VanillaBlocks::BANNER()->setColor(DyeColor::WHITE())->asItem()->setCustomName(C::colorize("&r&l&eVote"))->setLore([
                C::colorize("&r&7Vote for us to get epic"),
                C::colorize("&r&7Rewards!"),
                "",
                C::colorize("&r&a&l(!) &r&2Click to view more!")
            ]),
            28 => VanillaBlocks::REDSTONE_REPEATER()->asItem()->setCustomName(C::colorize("&r&l&eIsland Settings"))->setLore([
                C::colorize("&r&7Upgrade and add more features"),
                C::colorize("&r&7to your Skyblock Island"),
                "",
                C::colorize("&r&a&l(!) &r&2Click to view more!")
            ]),
            30 => VanillaItems::EXPERIENCE_BOTTLE()->setCustomName(C::colorize("&r&l&eSkills"))->setLore([
                C::colorize("&r&7Play and Unlock Skills to"),
                C::colorize("&r&7upgrade Your Gaming Experience!"),
                "",
                C::colorize("&r&a&l(!) &r&2Click to view more!")
            ]),
            32 => VanillaBlocks::SHULKER_BOX()->asItem()->setCustomName(C::colorize("&r&l&eKits"))->setLore([
                C::colorize("&r&7Open the kit menu"),
                "",
                C::colorize("&r&a&l(!) &r&2Click to view more!")
            ]),
            34 => VanillaBlocks::BELL()->asItem()->setCustomName(C::colorize("&r&l&eTop"))->setLore([
                C::colorize("&r&7Check the best Skyblock"),
                C::colorize("&r&7Players, maybe you are there!"),
                "",
                C::colorize("&r&a&l(!) &r&2Click to view more!")
            ])
        ];


        $this->menu->setName(C::colorize("&r&5Main &r&f| Menu"));
        InventoryUtils::fillInventory($inventory, $blackPane, $purpleSlots);

        foreach ($purpleSlots as $slot) {
            $inventory->setItem($slot, $purplePane);
        }

        foreach ($menuItems as $slot => $item) {
            $inventory->setItem($slot, $item);
        }

        $this->menu->setListener(InvMenu::readonly(function (DeterministicInvMenuTransaction $transaction) use ($session): void {
            $player = $transaction->getPlayer();
            $slots = [4, 10, 12, 14, 16, 28, 30, 32, 34];
            $slot = $transaction->getAction()->getSlot();

            foreach ($slots as $itemSlot) {
                if ($slot === $itemSlot) {
                    PlayerUtils::playSound($player, "random.levelup");
                }
            }

            if ($slot === 10) {
                $player->teleport($player->getWorld()->getSpawnLocation());
                $player->removeCurrentWindow();
                return;
            }

            if ($slot === 28) {
                $player->removeCurrentWindow();
                $transaction->then(function (Player $player) use($session): void {
                    IslandSettingsScreen::displayForm($player, $session->getIsland());           
                });
                return;
            }

            if ($slot === 32) {
                $player->removeCurrentWindow();

                if ($session->getSetting("chest_inventories") === true) {
                    KitScreen::display($player);
                } else {
                    $transaction->then(function (Player $player): void {
                        
                    });
                }
            }
        
        }));        
    }

    public static function display(Player $player): void {
        $menu = new self($player);
        $menu->getMenu()->send($player);
    }

    public function getMenu(): InvMenu {
        return $this->menu;
    }
}