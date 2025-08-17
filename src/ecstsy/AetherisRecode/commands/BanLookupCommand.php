<?php

declare(strict_types=1);

namespace ecstsy\AetherisRecode\commands;

use CortexPE\Commando\BaseCommand;
use CortexPE\Commando\args\RawStringArgument;
use ecstsy\AetherisRecode\commands\arguments\PlayerArgument;
use ecstsy\AetherisRecode\commands\subcommands\staff\BanSearchSubCommand;
use ecstsy\AetherisRecode\Loader;
use pocketmine\command\CommandSender;
use pocketmine\player\Player;
use pocketmine\Server;
use pocketmine\utils\TextFormat as C;

final class BanLookupCommand extends BaseCommand {

    public function prepare(): void {
        $this->setPermission($this->getPermission());
        $this->registerArgument(0, new RawStringArgument("player", true));

        $this->registerSubCommand(new BanSearchSubCommand(Loader::getInstance(), "list", "List banned players"));
    }

    public function onRun(CommandSender $sender, string $aliasUsed, array $args): void {
        $playerName = $args["player"] ?? null;

        if ($playerName === null) {
            $sender->sendMessage(C::colorize("&eUsage: /bl <player> or /bl list"));
            return;
        }

        $banList = Server::getInstance()->getNameBans();
        $info = $banList->getEntry($playerName);

        if ($info !== null) {
            $reason = $info->getReason() ?? "No reason";
            $source = $info->getSource() ?? "Unknown";
            $expires = $info->getExpires() ? date("Y-m-d H:i", $info->getExpires()->getTimestamp()) : "Permanent";
            $sender->sendMessage(C::colorize("&6&lBan Information for &c" . $info->getName() . "&6:"));
            $sender->sendMessage(C::colorize("&eReason: &f$reason"));
            $sender->sendMessage(C::colorize("&eBanned by: &f$source"));
            $sender->sendMessage(C::colorize("&eExpires: &f$expires"));
        } else {
            $sender->sendMessage(C::colorize("&aPlayer &e$playerName &ais not banned."));
        }
    }

    public function getPermission(): string {
        return "aetheris.banlookup";
    }
}