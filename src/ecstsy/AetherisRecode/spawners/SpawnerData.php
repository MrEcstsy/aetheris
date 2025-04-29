<?php

declare(strict_types=1);

namespace ecstsy\AetherisRecode\spawners;

use cosmicpe\blockdata\BlockData;
use pocketmine\nbt\tag\CompoundTag;

final class SpawnerData extends BlockData {
    /** @var string */
    private string $entityType;
    /** @var int */
    private int $delay;
    /** @var int */
    private int $count;
    /** @var bool */
    private bool $stackMobs;


    public function __construct(string $entityType, int $delay = 40, int $count = 1, bool $stack = false)
    {
        $this->entityType = $entityType;
        $this->delay = $delay;
        $this->count = $count;
        $this->stackMobs = $stack;
    }

    public static function nbtDeserialize(CompoundTag $nbt): BlockData
    {
        return new self(
            $nbt->getString("entityType"),
            $nbt->getInt("delsy"),
            $nbt->getInt("count"),
            $nbt->getByte("stack") !== 0
        );
    }

    public function nbtSerialize(): CompoundTag
    {
        return CompoundTag::create()
            ->setString("entityType", $this->entityType)
            ->setInt("delay", $this->delay)
            ->setInt("count", $this->count)
            ->setByte("stack", $this->stackMobs ? 1 : 0
        );
    }

    public function getEntityType(): string
    {
        return $this->entityType;
    }

    public function getDelay(): int
    {
        return $this->delay;
    }

    public function setDelay(int $delay): void
    {
        $this->delay = $delay;
    }

    public function getCount(): int
    {
        return $this->count;
    }
}