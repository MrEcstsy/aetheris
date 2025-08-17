<?php

declare(strict_types=1);

namespace ecstsy\AetherisRecode\commands;

use CortexPE\Commando\args\RawStringArgument;
use CortexPE\Commando\BaseCommand;
use ecstsy\AetherisRecode\Loader;
use ecstsy\MartianUtilities\utils\PlayerUtils;
use pocketmine\command\CommandSender;
use pocketmine\player\Player;
use pocketmine\utils\TextFormat as C;

final class BalanceCommand extends BaseCommand {

    public function prepare(): void {
        $this->setPermission($this->getPermission());

        $this->registerArgument(0, new RawStringArgument('name', true));
    }

    public function onRun(CommandSender $sender, string $aliasUsed, array $args): void
    {
        if (!$sender instanceof Player) {
            return;
        }
        $name = isset($args['name']) ? $args['name'] : null;
        if ($name === null) {
            $session = Loader::getPlayerManager()->getSession($sender);
            $sender->sendMessage(C::colorize("&r&3◆ &fBalance&8: &b$" . number_format($session->getBalance()) . " &r&3◆"));
            return;
        }

        $player = PlayerUtils::getPlayerByPrefix($name);

        if ($player === null) {
            $sender->sendMessage(C::colorize("&r&cError: &4Player not found."));
            return;
        }

        $session = Loader::getPlayerManager()->getSession($player);
        $sender->sendMessage(C::colorize("&r&3◆ &f" . $player->getNameTag() . "&r&f's Balance&8: &b$" . number_format($session->getBalance()) . " &r&3◆"));
    }


    public function getPermission(): string {
        return 'aetheris.default';
    }
}