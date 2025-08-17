<?php

declare(strict_types=1);

namespace ecstsy\AetherisRecode\listeners;

use ecstsy\AetherisRecode\utils\ui\CratePreviewScreen;
use pocketmine\block\Beacon;
use pocketmine\event\Listener;
use pocketmine\event\player\PlayerInteractEvent;
use pocketmine\player\Player;
use pocketmine\world\Position;
use pocketmine\utils\TextFormat as C;

final class CrateListener implements Listener {
    
    /** @var array<string, Position> */
    public static array $cratePositions = [];

    /** @var array<string, callable(Player, string)> */
    public static array $onLeftClick  = [];
    /** @var array<string, callable(Player, string)> */
    public static array $onRightClick = [];

    public function onInteract(PlayerInteractEvent $ev): void {
        $p      = $ev->getPlayer();
        $block  = $ev->getBlock();
        if (!$block instanceof Beacon) return;
        $type = $this->getCrateTypeByPosition($block->getPosition());
        if ($type === null) return;

        $ev->cancel();
        if ($ev->getAction() === PlayerInteractEvent::LEFT_CLICK_BLOCK) {
            (self::$onLeftClick[$type] ?? function() {})($p, $type);
        } else {
            (self::$onRightClick[$type] ?? function() {})($p, $type);
        }
    }

    private function getCrateTypeByPosition(Position $pos): ?string {
        foreach (self::$cratePositions as $type => $cratePos) {
            if ($pos->equals($cratePos)) {
                return $type;
            }
        }
        return null;
    }
}