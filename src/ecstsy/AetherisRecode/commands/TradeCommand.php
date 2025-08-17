<?php

namespace ecstsy\AetherisRecode\commands;

use CortexPE\Commando\BaseCommand;
use ecstsy\AetherisRecode\commands\arguments\PlayerArgument;
use ecstsy\AetherisRecode\server\trade\TradeInstance;
use pocketmine\command\CommandSender;
use pocketmine\player\Player;
use pocketmine\utils\TextFormat as C;

class TradeCommand extends BaseCommand {
    public function prepare(): void {
        $this->setPermission($this->getPermission());
        $this->registerArgument(0, new PlayerArgument("target", true));
    }

    public function onRun(CommandSender $sender, string $aliasUsed, array $args): void {
        if (!$sender instanceof Player) return;
        $target = $args["target"] ?? null;
        if (!$target || $target === $sender) {
            $sender->sendMessage(C::colorize("&cInvalid player."));
            return;
        }
        TradeInstance::requestTrade($sender, $target);
    }

    public function getPermission(): string {
        return "aetheris.default";
    }
}