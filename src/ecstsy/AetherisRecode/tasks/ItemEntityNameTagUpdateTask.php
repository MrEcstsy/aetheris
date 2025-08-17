<?php

declare(strict_types=1);

namespace ecstsy\AetherisRecode\tasks;

use ecstsy\AetherisRecode\utils\Utils;
use pocketmine\entity\object\ItemEntity;
use pocketmine\scheduler\Task;
use pocketmine\Server;

final class ItemEntityNameTagUpdateTask extends Task {

    public function onRun(): void {
        foreach (Server::getInstance()->getWorldManager()->getWorlds() as $world) {
            foreach ($world->getEntities() as $entity) {
                if ($entity instanceof ItemEntity) {
                    Utils::setItemEntityNameTag($entity);
                }
            }
        }
    }
}