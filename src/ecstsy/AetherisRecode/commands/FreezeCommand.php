<?php

declare(strict_types=1);

namespace ecstsy\AetherisRecode\commands;

use CortexPE\Commando\BaseCommand;
use ecstsy\AetherisRecode\commands\arguments\PlayerArgument;
use ecstsy\AetherisRecode\Loader;
use pocketmine\command\CommandSender;
use pocketmine\player\Player;

final class FreezeCommand extends BaseCommand {

    public function prepare(): void {
        $this->setPermission($this->getPermission());

        $this->registerArgument(0, new PlayerArgument("player", false));
    }

    public function onRun(CommandSender $sender, string $aliasUsed, array $args): void
    {
        if (!$sender instanceof Player) {
            return;
        }

        /** @var Player|null $target */
        $target = $args["player"] ?? null;
        if (!$target instanceof Player) {
            $sender->sendMessage("§cPlayer not found or not online.");
            return;
        }

        $session = Loader::getInstance()->getPlayerManager()->getSession($target);
        if ($session === null) {
            $sender->sendMessage("§cCould not find session for player.");
            return;
        }

        $session->setFrozen(!$session->isFrozen());
        if ($session->isFrozen()) {
            $sender->sendMessage("§b{$target->getName()} has been §cFROZEN§b.");
            $target->sendMessage("§cYou have been frozen by a staff member.");
            $target->setNoClientPredictions(true);
        } else {
            $sender->sendMessage("§b{$target->getName()} has been §aUNFROZEN§b.");
            $target->sendMessage("§aYou have been unfrozen.");
            $target->setNoClientPredictions(false);
        }
    }

    public function getPermission(): string {
        return "aetheris.freeze";
    }
}