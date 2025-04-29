<?php

declare(strict_types=1);

namespace ecstsy\AetherisRecode\commands;

use CortexPE\Commando\BaseCommand;
use ecstsy\AetherisRecode\commands\subcommands\kits\KitGiveSubCommand;
use ecstsy\AetherisRecode\Loader;
use ecstsy\AetherisRecode\utils\inventory\CustomSizedInvMenu;
use ecstsy\AetherisRecode\utils\Screens;
use ecstsy\AetherisRecode\utils\ui\KitScreen;
use pocketmine\command\CommandSender;
use pocketmine\player\Player;

final class KitCommand extends BaseCommand {

    public function prepare(): void {
        $this->setPermission($this->getPermission());

        $this->registerSubCommand(new KitGiveSubCommand(Loader::getInstance(), "give", "Give kit to player"));
    }

    public function onRun(CommandSender $sender, string $aliasUsed, array $args): void
    {
        if (!$sender instanceof Player) {
            return;
        }

        $session = Loader::getPlayerManager()->getSession($sender);

        if ($session->getSetting("chest_inventories") === true) {
            KitScreen::display($sender);
        } else {
            
        }
    }

    public function getPermission(): string
    {
        return "aetheris.default";
    }
}