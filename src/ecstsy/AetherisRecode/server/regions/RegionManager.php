<?php

declare(strict_types=1);

namespace ecstsy\AetherisRecode\server\regions;

use pocketmine\math\Vector3;

final class RegionManager {
    /** @var Region[] */
    private array $regions = [];

    public function addRegion(Region $region): void {
        $this->regions[$region->getName()] = $region;
    }

    /**
     * Returns *all* regions containing the given position.
     *
     * @return Region[]
     */
    public function getRegionsAt(Vector3 $pos): array {
        $found = [];
        foreach ($this->regions as $region) {
            if ($region->contains($pos)) {
                $found[] = $region;
            }
        }
        return $found;
    }

    /**
     * Returns the *first* region containing the position, or null.
     */
    public function getFirstRegionAt(Vector3 $pos): ?Region {
        foreach ($this->regions as $region) {
            if ($region->contains($pos)) {
                return $region;
            }
        }
        return null;
    }

    public function getRegionByName(string $name): ?Region {
        return $this->regions[$name] ?? null;
    }

    public function getRegionAt(Vector3 $position): ?Region {
        foreach ($this->regions as $region) {
            if ($region->contains($position)) {
                return $region;
            }
        }
        return null;
    }
}