<?php
declare(strict_types=1);

namespace ecstsy\AetherisRecode\utils;

use pocketmine\item\Item;
use pocketmine\player\Player;

class ChatItemTracker {

    /** @var array<string, Item> */
    private static array $lastItem = [];

    public static function setLastItem(Player $player, Item $item): void {
        self::$lastItem[strtolower($player->getName())] = $item;
    }

    public static function getLastItem(Player $player): ?Item {
        return self::$lastItem[strtolower($player->getName())] ?? null;
    }
}
