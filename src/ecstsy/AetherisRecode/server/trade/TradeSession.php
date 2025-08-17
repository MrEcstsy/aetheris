<?php

declare(strict_types=1);

namespace ecstsy\AetherisRecode\server\trade;

use muqsit\invmenu\InvMenu;
use muqsit\invmenu\type\InvMenuTypeIds;
use pocketmine\item\VanillaItems;
use pocketmine\player\Player;
use pocketmine\utils\TextFormat as C;

final class TradeSession {
    private Player $a;
    private Player $b;
    private InvMenu $menu;
    private bool $aReady = false;
    private bool $bReady = false;

    public function __construct(Player $a, Player $b) {
        $this->a = $a;
        $this->b = $b;
        $this->menu = InvMenu::create(InvMenuTypeIds::TYPE_DOUBLE_CHEST);
        $this->menu->setName(C::colorize("&r&dTrade: &b{$a->getName()} &7| &b{$b->getName()}"));
        $this->setupInventory();
    }

    private function setupInventory(): void {
        $inv = $this->menu->getInventory();
        $divider = VanillaItems::STAINED_GLASS_PANE()->setCustomName(C::colorize("&r&8Divider"));
        foreach ([9,10,11,12,13,14,15,16,17, 27,28,29,30,31,32,33,34,35, 45,46,47,48,49,50,51,52,53] as $slot) {
            $inv->setItem($slot, $divider);
        }
        // Optionally: set colored panes for each side
    }

    public function open(): void {
        $this->menu->send($this->a);
        $this->menu->send($this->b);
        // TODO: Add listeners for item placement, ready/confirm, and trade completion
    }

    public function close(): void {
        $this->menu->getInventory()->clearAll();
    }

    public function getPlayerA(): Player { return $this->a; }
    public function getPlayerB(): Player { return $this->b; }
}