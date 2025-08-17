<?php

declare(strict_types=1);

namespace ecstsy\AetherisRecode\commands;

use CortexPE\Commando\BaseCommand;
use ecstsy\AetherisRecode\commands\arguments\PlayerArgument;
use ecstsy\AetherisRecode\Loader;
use pocketmine\command\CommandSender;
use pocketmine\player\Player;

final class UnfreezeCommand extends BaseCommand {

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

        if (!$session->isFrozen()) {
            $sender->sendMessage("§e{$target->getName()} is not frozen.");
            return;
        }

        $session->setFrozen(false);
        $target->setNoClientPredictions(false);
        $sender->sendMessage("§b{$target->getName()} has been §aUNFROZEN§b.");
        $target->sendMessage("§aYou have been unfrozen.");
    }

    public function getPermission(): string {
        return "aetheris.freeze";
    }
}