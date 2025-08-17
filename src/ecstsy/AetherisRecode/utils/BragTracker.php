<?php
declare(strict_types=1);

namespace ecstsy\AetherisRecode\utils;

use pocketmine\player\Player;
use pocketmine\item\Item;
use pocketmine\item\VanillaItems;

final class BragTracker {

    /**
     * @var array<string, array{
     *     hotbar:    Item[],
     *     inventory: Item[],
     *     armor:     Item[],
     *     offhand:   Item|null,
     *     level:     int
     * }>
     */
    private static array $snapshots = [];

    /**
     * Captures a snapshot of everything we need.
     */
    public static function setLastInventoryBrag(Player $player): void {
        $key = strtolower($player->getName());
        $inventory = $player->getInventory()->getContents(false);

        $hotbar = array_map(
            fn($slot) => $inventory[$slot] ?? VanillaItems::AIR(),
            range(0, 8)
        );

        $mainInventory = array_slice($inventory, 9);

        $armor = $player->getArmorInventory()->getContents();
        $offhand = $player->getOffHandInventory()->getItem(0);
        $offhand = $offhand->isNull() ? null : $offhand;

        self::$snapshots[$key] = [
            'hotbar'    => $hotbar,
            'inventory' => $mainInventory,
            'armor'     => $armor,
            'offhand'   => $offhand,
            'level'     => $player->getXpManager()->getXpLevel(),
        ];
    }

    public static function hasLastInventoryBrag(string $name): bool {
        return isset(self::$snapshots[strtolower($name)]);
    }

    /**
     * @return array{
     *     hotbar:    Item[],
     *     inventory: Item[],
     *     armor:     Item[],
     *     offhand:   Item|null,
     *     level:     int
     * }|null
     */
    public static function getLastInventoryBrag(string $name): ?array {
        return self::$snapshots[strtolower($name)] ?? null;
    }

    public static function clear(string $name): void {
        unset(self::$snapshots[strtolower($name)]);
    }
}
