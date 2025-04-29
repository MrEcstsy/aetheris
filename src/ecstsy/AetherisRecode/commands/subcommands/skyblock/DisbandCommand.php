<?php

namespace ecstsy\AetherisRecode\commands\subcommands\skyblock;

use CortexPE\Commando\BaseSubCommand;
use ecstsy\AetherisRecode\Loader;
use pocketmine\command\CommandSender;
use pocketmine\player\Player;
use pocketmine\utils\TextFormat as C;

class DisbandCommand extends BaseSubCommand {

    public function prepare(): void {
        $this->setPermission($this->getPermission());
    }

    public function onRun(CommandSender $sender, string $aliasUsed, array $args): void
    {
        if (!$sender instanceof Player) {
            return;
        }

        $session = Loader::getPlayerManager()->getSession($sender);

        if ($session->getSkyblock() === null) {
            $sender->sendMessage(C::colorize("&r&c&l(!) &r&cYou are not apart of an island."));
            return;
        }

        Loader::getSkyBlockManager()->deleteSkyBlock($session->getSkyblock());  
        $session->setSkyblock(null);
        $sender->sendMessage(C::colorize("&r&c&l(!) &r&cYou have disbanded your island."));      
    }

    public function getPermission(): ?string
    {
        return "aetheris.default";
    }
}