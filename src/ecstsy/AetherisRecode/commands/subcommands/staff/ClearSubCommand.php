<?php

declare(strict_types=1);

namespace ecstsy\AetherisRecode\commands\subcommands\staff;

use CortexPE\Commando\BaseSubCommand;
use ecstsy\AetherisRecode\commands\arguments\PlayerArgument;
use ecstsy\AetherisRecode\Loader;
use pocketmine\command\CommandSender;
use pocketmine\player\Player;
use pocketmine\utils\TextFormat as C;

class ClearSubCommand extends BaseSubCommand {

    public function prepare(): void {
        $this->setPermission($this->getPermission());
        $this->registerArgument(0, new PlayerArgument("player", false));
    }

    public function onRun(CommandSender $sender, string $aliasUsed, array $args): void {
        $target = $args["player"] ?? null;

        if (!$target instanceof Player) {
            $sender->sendMessage(C::colorize("&cPlayer not found or not online."));
            return;
        }

        $session = Loader::getPlayerManager()->getSession($target);
        $staffUuid = $sender instanceof Player ? $sender->getUniqueId()->toString() : "CONSOLE";
        $session->clearStrikes($staffUuid, "Infractions cleared by staff");

        $sender->sendMessage(C::colorize("&aAll infractions for &e{$target->getName()} &ahave been cleared."));
        $target->sendMessage(C::colorize("&aAll your infractions have been cleared by staff."));
    }

    public function getPermission(): string {
        return "aetheris.warn.clear";
    }
}