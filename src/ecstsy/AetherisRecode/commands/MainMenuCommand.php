<?php

declare(strict_types=1);

namespace ecstsy\AetherisRecode\commands;

use CortexPE\Commando\BaseCommand;
use ecstsy\AetherisRecode\Loader;
use ecstsy\AetherisRecode\utils\ui\MainMenuScreen;
use pocketmine\command\CommandSender;
use pocketmine\player\Player;

final class MainMenuCommand extends BaseCommand {

    public function prepare(): void {
        $this->setPermission($this->getPermission());
    }

    public function onRun(CommandSender $sender, string $aliasUsed, array $args): void
    {
        if (!$sender instanceof Player) {
            return;
        }

        $session = Loader::getPlayerManager()->getSession($sender);

        if ($session->getSetting("chest_inventories") === true) {
            MainMenuScreen::display($sender);
        } else {
            
        }
    }

    public function getPermission(): string {
        return "aetheris.default";
    }
}