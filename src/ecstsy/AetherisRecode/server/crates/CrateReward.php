<?php
declare(strict_types=1);

namespace ecstsy\AetherisRecode\server\crates;

use pocketmine\item\Item;

final class CrateReward {
    private \Closure $factory;

    private function __construct(\Closure $factory) {
        $this->factory = $factory;
    }

    /**
     * A single fixed item.
     */
    public static function of(Item $item): self {
        return new self(fn() => clone $item);
    }

    /**
     * An item with a random count between $min and $max (inclusive).
     */
    public static function ranged(Item $item, int $min, int $max): self {
        return new self(function() use ($item, $min, $max) {
            $copy = clone $item;
            $copy->setCount(mt_rand($min, $max));
            return $copy;
        });
    }

    /**
     * Roll this reward, producing an Item.
     */
    public function roll(): Item {
        return ($this->factory)();
    }
}
