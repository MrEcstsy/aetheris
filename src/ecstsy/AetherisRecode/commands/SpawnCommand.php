<?php

declare(strict_types=1);

namespace ecstsy\AetherisRecode\commands;

use CortexPE\Commando\BaseCommand;
use ecstsy\AetherisRecode\commands\arguments\PlayerArgument;
use pocketmine\command\CommandSender;
use pocketmine\player\Player;
use pocketmine\Server;
use pocketmine\utils\TextFormat as C;

final class SpawnCommand extends BaseCommand {

    public function prepare(): void {
        $this->setPermission($this->getPermission());          
        $this->registerArgument(0, new PlayerArgument("player", true));
    }

    public function onRun(CommandSender $sender, string $aliasUsed, array $args): void {
        if (!$sender instanceof Player) {
            $sender->sendMessage(C::colorize("&cError: This command can only be used in-game."));
            return;
        }

        $world = Server::getInstance()->getWorldManager()->getDefaultWorld();
        if ($world === null) {
            $sender->sendMessage(C::colorize("&cError: Default world is not set."));
            return;
        }

        $spawn = $world->getSpawnLocation();

        $target = $args["player"] ?? $sender;

        if ($target instanceof Player && $target !== $sender) {
            if (!$sender->hasPermission("aetheris.admin")) {
                $sender->sendMessage(C::colorize("&cYou lack permission to teleport others."));
                return;
            }
        }

        $target->teleport($spawn);
        $target->sendMessage(C::colorize("&r&2Success: &r&aYou have been teleported to spawn."));

        if ($target !== $sender) {
            $sender->sendMessage(C::colorize("&r&2Success: &r&a{$target->getName()} has been teleported to spawn."));
        }
    }

    public function getPermission(): string {
        return "aetheris.default";
    }
}
