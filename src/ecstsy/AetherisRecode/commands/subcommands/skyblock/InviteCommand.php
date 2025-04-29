<?php

namespace ecstsy\AetherisRecode\commands\subcommands\skyblock;

use CortexPE\Commando\args\RawStringArgument;
use CortexPE\Commando\BaseSubCommand;
use ecstsy\AetherisRecode\Loader;
use ecstsy\AetherisRecode\utils\Utils;
use ecstsy\MartianUtilities\utils\PlayerUtils;
use pocketmine\command\CommandSender;
use pocketmine\player\Player;

class InviteCommand extends BaseSubCommand {

    public function prepare(): void {
        $this->setPermission($this->getPermission());

        $this->registerArgument(0, new RawStringArgument("name", false));
    }

    public function onRun(CommandSender $sender, string $aliasUsed, array $args): void
    {
        if (!$sender instanceof Player) {
            return;
        }

        $player = isset($args['name']) ? PlayerUtils::getPlayerByPrefix($args['name']) : null;

        if ($player === null || !$player->isOnline()) {
            $sender->sendToastNotification(Loader::SERVER_TITLE, "§c⚠ §fPlayer §e{$player->getName()} §fis not online.");
            return;
        }

        $sbManager = Loader::getSkyBlockManager();
        $sbManager->sendInvitation($sender, $player);
    }

    public function getPermission(): string {
        return "aetheris.default";
    }
}