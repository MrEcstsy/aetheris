<?php

declare(strict_types=1);

namespace ecstsy\AetherisRecode\server\regions;

final class RegionPermissions {
    public bool $pvp;
    public bool $canBreak;
    public bool $fly;
    public bool $fallDamage;
    public bool $build;
    public bool $interact;
    public bool $explosions;

    public function __construct(
        bool $pvp,
        bool $canBreak,
        bool $fly,
        bool $fallDamage,
        bool $build,
        bool $interact,
        bool $explosions
    ){
        $this->pvp          = $pvp;
        $this->canBreak     = $canBreak;
        $this->fly          = $fly;
        $this->fallDamage   = $fallDamage;
        $this->build        = $build;
        $this->interact     = $interact;
        $this->explosions   = $explosions;
    }
}