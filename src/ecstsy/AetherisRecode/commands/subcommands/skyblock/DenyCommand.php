<?php

namespace ecstsy\AetherisRecode\commands\subcommands\skyblock;

use CortexPE\Commando\args\RawStringArgument;
use CortexPE\Commando\BaseSubCommand;
use pocketmine\command\CommandSender;
use pocketmine\player\Player;
use ecstsy\AetherisRecode\Loader;

class DenyCommand extends BaseSubCommand {

    public function prepare(): void {
        $this->setPermission($this->getPermission());
        $this->registerArgument(0, new RawStringArgument("islandName", false));
    }

    public function onRun(CommandSender $sender, string $aliasUsed, array $args): void {
        if (!$sender instanceof Player) {
            $sender->sendMessage("This command can only be run in-game.");
            return;
        }

        $islandName = $args["islandName"];

        $skyblockManager = Loader::getSkyBlockManager();
        $skyblockManager->denyInvitation($sender, $islandName);
    }

    public function getPermission(): string {
        return "aetheris.default";
    }
}
