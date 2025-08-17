<?php

declare(strict_types=1);

namespace ecstsy\AetherisRecode\server\regions;

use pocketmine\math\Vector3;

final class Region {
    private string $name;
    private Vector3 $min;
    private Vector3 $max;
    private RegionPermissions $permissions;

    public function __construct(
        string $name,
        Vector3 $pos1,
        Vector3 $pos2,
        RegionPermissions $permissions
    ){
        $this->name = $name;
        $this->min = new Vector3(
            min($pos1->x, $pos2->x),
            min($pos1->y, $pos2->y),
            min($pos1->z, $pos2->z),
        );
        $this->max = new Vector3(
            max($pos1->x, $pos2->x),
            max($pos1->y, $pos2->y),
            max($pos1->z, $pos2->z),
        );
        $this->permissions = $permissions;
    }

    public function getName(): string {
        return $this->name;
    }

    /**
     * Is this world-position inside the cuboid?
     */
    public function contains(Vector3 $pos): bool {
        return
            $pos->x >= $this->min->x && $pos->x <= $this->max->x &&
            $pos->y >= $this->min->y && $pos->y <= $this->max->y &&
            $pos->z >= $this->min->z && $pos->z <= $this->max->z;
    }

    public function permissions(): RegionPermissions {
        return $this->permissions;
    }

    public function isWithinBounds(Vector3 $position): bool {
        return $position->x >= $this->min->x && $position->x <= $this->max->x &&
               $position->y >= $this->min->y && $position->y <= $this->max->y &&
               $position->z >= $this->min->z && $position->z <= $this->max->z;
    }
}