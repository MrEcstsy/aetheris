<?php

declare(strict_types=1);

namespace ecstsy\AetherisRecode\tasks;

use ecstsy\AetherisRecode\Loader;
use ecstsy\AetherisRecode\spawners\SpawnerData;
use pocketmine\entity\EntityFactory;
use pocketmine\scheduler\Task;
use pocketmine\world\Position;

final class SpawnerSpawnTask extends Task {

    private Loader $loader;
    private Position $position;

    public function __construct(Loader $loader, Position $position) {
        $this->loader = $loader;
        $this->position = $position;
    }

    public function onRun(): void
    {
        $bdw = $this->loader->getBlockDataWorldManager()->get($this->position->getWorld());
        $data = $bdw->getBlockDataAt($this->position->getFloorX(), $this->position->getFloorY(), $this->position->getFloorZ());

        if (!($data instanceof SpawnerData)) {
            return;
        }

        for($i = 0; $i < $data->getCount(); $i++) {
            EntityFactory::getInstance();
        }
    }
}