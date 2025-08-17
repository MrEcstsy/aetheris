<?php

declare(strict_types=1);

namespace ecstsy\AetherisRecode\utils\ui;

use ecstsy\MartianUtilities\utils\screens\BaseScreen;
use muqsit\invmenu\InvMenu;
use muqsit\invmenu\transaction\DeterministicInvMenuTransaction;
use muqsit\invmenu\type\InvMenuTypeIds;
use pocketmine\block\utils\DyeColor;
use pocketmine\block\VanillaBlocks;
use pocketmine\item\VanillaItems;
use pocketmine\item\Item;
use pocketmine\player\Player;
use pocketmine\utils\TextFormat as C;

final class TradeScreen extends BaseScreen {

    private const BORDER_SLOTS = [
        4, 13, 22, 31, 40, 49, // vertical divider (middle column)
    ];
    private const PLAYER_A_SLOTS = [0,1,2,3,9,10,11,12,18,19,20,21,27,28,29,30,36,37,38,39,45,46,47,48];
    private const PLAYER_B_SLOTS = [5,6,7,8,14,15,16,17,23,24,25,26,32,33,34,35,41,42,43,44,50,51,52,53];

    private InvMenu $menu;
    private Player $playerA;
    private Player $playerB;
    private bool $aReady = false;
    private bool $bReady = false;
    private Item $dividerPane;
    private Item $readyPane;
    private Item $notReadyPane;

    public function __construct(Player $playerA, Player $playerB) {
        $this->playerA = $playerA;
        $this->playerB = $playerB;

        $this->dividerPane = VanillaBlocks::STAINED_GLASS_PANE()->setColor(DyeColor::GRAY())->asItem()->setCustomName(" ");
        $this->readyPane = VanillaItems::DYE()->setColor(DyeColor::LIME())->setCustomName(C::colorize("&aReady!"));
        $this->notReadyPane = VanillaItems::DYE()->setColor(DyeColor::RED())->setCustomName(C::colorize("&cNot Ready"));

        $this->menu = InvMenu::create(InvMenuTypeIds::TYPE_CHEST);
        $this->menu->setName(C::colorize("&dTrade: &b{$playerA->getName()} &7| &b{$playerB->getName()}"));
        $this->drawScreen();

        $this->menu->setListener(function(DeterministicInvMenuTransaction $tx) {
            $player = $tx->getPlayer();
            $slot = $tx->getAction()->getSlot();

            // Only allow each player to interact with their side
            if ($player === $this->playerA && !in_array($slot, self::PLAYER_A_SLOTS, true)) {
                return $tx->discard();
            }
            if ($player === $this->playerB && !in_array($slot, self::PLAYER_B_SLOTS, true)) {
                return $tx->discard();
            }
            // Prevent divider/ready slots from being changed
            if (in_array($slot, self::BORDER_SLOTS, true)) {
                return $tx->discard();
            }
            return $tx->continue();
        });
    }

    private function drawScreen(): void {
        $inv = $this->menu->getInventory();
        $inv->clearAll();

        // Divider
        foreach (self::BORDER_SLOTS as $slot) {
            $inv->setItem($slot, $this->dividerPane);
        }
        // Ready/Not Ready indicators (bottom left/right corners)
        $inv->setItem(45, $this->aReady ? $this->readyPane : $this->notReadyPane);
        $inv->setItem(53, $this->bReady ? $this->readyPane : $this->notReadyPane);
    }

    public function getMenu(): InvMenu {
        return $this->menu;
    }

    public static function display(Player $a, Player $b): void {
        $screen = new self($a, $b);
        $screen->getMenu()->send($a);
        $screen->getMenu()->send($b);
    }

    // Add methods for ready/confirm, trade completion, and cancel as needed
}