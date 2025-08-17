<?php

declare(strict_types=1);

namespace ecstsy\AetherisRecode\utils\inventory\anvils;

use pocketmine\block\Anvil;
use pocketmine\player\Player;

final class AnvilRegistry
{
    /** @var Anvil[] */
    private array $byPlayer = [];

    public function register(Player $player, Anvil $anvil): void {
        $this->byPlayer[$player->getName()] = $anvil;
    }

    public function remove(Player $player): void {
        unset($this->byPlayer[$player->getName()]);
    }

    public function getAnvilFor(Player $player): ?Anvil {
        return $this->byPlayer[$player->getName()] ?? null;
    }
}