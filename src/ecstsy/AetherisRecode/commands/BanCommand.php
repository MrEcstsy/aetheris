<?php

declare(strict_types=1);

namespace ecstsy\AetherisRecode\commands;

use CortexPE\Commando\args\RawStringArgument;
use CortexPE\Commando\args\TextArgument;
use CortexPE\Commando\BaseCommand;
use ecstsy\AetherisRecode\commands\arguments\PlayerArgument;
use ecstsy\AetherisRecode\Loader;
use ecstsy\AetherisRecode\utils\Utils;
use pocketmine\command\CommandSender;
use pocketmine\player\Player;
use pocketmine\Server;
use pocketmine\utils\TextFormat as C;

final class BanCommand extends BaseCommand {

    public function prepare(): void {
        $this->setPermission($this->getPermission());

        $this->registerArgument(0, new PlayerArgument("player", false));
        $this->registerArgument(1, new TextArgument("reason", true));
    }

    public function onRun(CommandSender $sender, string $aliasUsed, array $args): void {
        $target = $args["player"] ?? null;
        $reason = $args["reason"] ?? "No reason provided";

        if (!$target instanceof Player) {
            $sender->sendMessage(C::colorize("&cPlayer not found or not online."));
            return;
        }

        $silent = false;
        if (preg_match('/\s+-s\s*$/i', $reason)) {
            $silent = true;
            $reason = trim(preg_replace('/\s+-s\s*$/i', '', $reason));
            if ($reason === "") $reason = "No reason provided";
        }


        $staffName = $sender instanceof Player ? $sender->getName() : "CONSOLE";

        Utils::banPlayer($target, $reason, $staffName, $silent);

        $sender->sendMessage(C::colorize("&aBanned &e" . $target->getName() . " &afor: &f$reason" . ($silent ? " &7(Silently)" : "")));
    }

    public function getPermission(): string {
        return "pocketmine.command.ban";
    }
}