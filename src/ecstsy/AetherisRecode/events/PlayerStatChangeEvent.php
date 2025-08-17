<?php
declare(strict_types=1);

namespace ecstsy\AetherisRecode\events;

use pocketmine\event\Event;
use pocketmine\player\Player;

final class PlayerStatChangeEvent extends Event {
    public const KILLS   = "kills";
    public const DEATHS  = "deaths";
    public const BALANCE = "balance";

    private Player $player;
    private string $statName;
    private int|float $newValue;

    public function __construct(Player $player, string $statName, int|float $newValue) {
        $this->player   = $player;
        $this->statName = $statName;
        $this->newValue = $newValue;
    }

    public function getPlayer(): Player {
        return $this->player;
    }

    public function getStatName(): string {
        return $this->statName;
    }

    public function getNewValue(): int|float {
        return $this->newValue;
    }
}
