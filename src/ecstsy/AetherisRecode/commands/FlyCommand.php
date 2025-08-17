<?php

declare(strict_types=1);

namespace ecstsy\AetherisRecode\commands;

use CortexPE\Commando\BaseCommand;
use ecstsy\AetherisRecode\utils\Utils;
use pocketmine\command\CommandSender;
use pocketmine\player\Player;

final class FlyCommand extends BaseCommand {
    public function prepare(): void {
        $this->setPermission($this->getPermission());
    }

    public function onRun(CommandSender $sender, string $aliasUsed, array $args): void
    {
        if (!$sender instanceof Player) {
            return;
        }

        Utils::toggleFlight($sender);
    }

    public function getPermission(): string {
        return "aetheris.fly";
    }
}