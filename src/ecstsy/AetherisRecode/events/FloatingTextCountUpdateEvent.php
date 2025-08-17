<?php
declare(strict_types=1);

namespace ecstsy\AetherisRecode\events;

use pocketmine\event\Event;
use pocketmine\event\Cancellable;

class FloatingTextCountUpdateEvent extends Event implements Cancellable {

    /** @var int */
    private $newCount;
    /** @var bool */
    private $cancelled = false;

    public function __construct(int $newCount){
        $this->newCount = $newCount;
    }

    /**
     * The updated player count that should be shown.
     */
    public function getNewCount(): int {
        return $this->newCount;
    }

    public function isCancelled(): bool
    {
        return $this->cancelled;
    }
}
