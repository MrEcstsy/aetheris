<?php

declare(strict_types=1);

namespace ecstsy\AetherisRecode\commands\subcommands\economy;

use CortexPE\Commando\args\IntegerArgument;
use CortexPE\Commando\args\RawStringArgument;
use CortexPE\Commando\BaseSubCommand;
use ecstsy\AetherisRecode\Loader;
use ecstsy\MartianUtilities\utils\GeneralUtils;
use ecstsy\MartianUtilities\utils\PlayerUtils;
use pocketmine\command\CommandSender;
use pocketmine\player\Player;
use pocketmine\utils\TextFormat as C;

final class TakeSubCommand extends BaseSubCommand {

    public function prepare(): void {
        $this->setPermission($this->getPermission());

        $this->registerArgument(0, new RawStringArgument("name", false));
        $this->registerArgument(1, new IntegerArgument("amount", false));
    }

    public function onRun(CommandSender $sender, string $aliasUsed, array $args): void
    {
        if (!$sender instanceof Player) {
            return;
        }

        $player = isset($args["name"]) ? PlayerUtils::getPlayerByPrefix($args["name"]) : null;
        $amount = isset($args["amount"]) ? $args["amount"] : null;

        if ($player === null) {
            $sender->sendMessage(C::colorize("&r&cError: &4Player not found."));
            return;
        }

        if ($amount === null) {
            $sender->sendMessage(C::colorize("&r&cError: &4Amount not specified."));
            return;
        }

        $session = Loader::getPlayerManager()->getSession($player);

        $session->removeBalance($amount);
        $sender->sendMessage(C::colorize("&r&a$" . number_format($amount) . " has been removed from " . $player->getNameTag() . "'s balance."));
        $player->sendMessage(C::colorize("&r&a$" . number_format($amount) . " has been removed from your balance."));
    }

    public function getPermission(): string
    {
        return "aetheris.eco.take";
    }
}