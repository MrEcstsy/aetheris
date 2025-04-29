<?php

declare(strict_types=1);

namespace ecstsy\AetherisRecode\commands;

use CortexPE\Commando\args\RawStringArgument;
use CortexPE\Commando\BaseCommand;
use ecstsy\AetherisRecode\commands\subcommands\skyblock\AcceptCommand;
use ecstsy\AetherisRecode\commands\subcommands\skyblock\ChatCommand;
use ecstsy\AetherisRecode\commands\subcommands\skyblock\DenyCommand;
use ecstsy\AetherisRecode\commands\subcommands\skyblock\DisbandCommand;
use ecstsy\AetherisRecode\commands\subcommands\skyblock\InfoCommand;
use ecstsy\AetherisRecode\commands\subcommands\skyblock\InviteCommand;
use ecstsy\AetherisRecode\Loader;
use ecstsy\AetherisRecode\utils\Screens;
use ecstsy\AetherisRecode\utils\ui\Skyblock\IslandControlScreen;
use ecstsy\AetherisRecode\utils\ui\skyblock\IslandCreationScreen;
use pocketmine\command\CommandSender;
use pocketmine\player\Player;

final class SkyBlockCommand extends BaseCommand
{

    public function prepare(): void
    {
        $this->setPermission($this->getPermission());

        $this->registerSubCommand(new DisbandCommand(Loader::getInstance(), "disband", "Disband your island", ['delete']));
        $this->registerSubCommand(new InviteCommand(Loader::getInstance(), "invite", "Invite a player to your island"));
        $this->registerSubCommand(new AcceptCommand(Loader::getInstance(), "accept", "Accept an island invitation", ["join"]));
        $this->registerSubCommand(new DenyCommand(Loader::getInstance(), "deny", "Deny an island invitation"), ['reject', 'decline']);
        $this->registerSubCommand(new ChatCommand(Loader::getInstance(), "chat", "Change your chat mode", ['c']));
        $this->registerSubCommand(new InfoCommand(Loader::getInstance(), "info", "View island information", ['i', 'islandinfo', 'whois', 'who']));
    }

    public function onRun(CommandSender $sender, string $aliasUsed, array $args): void
    {
        if (!$sender instanceof Player) {
            return;
        }

        $session = Loader::getPlayerManager()->getSession($sender);

        if ($session->getSkyblock() === null) {
            IslandCreationScreen::display($sender);
            return;
        }

        if ($session->getSetting("chest_inventories")) {
            IslandControlScreen::display($sender);
            return;
        } else {
            IslandControlScreen::displayForm($sender);
            return;
        }
    }

    public function getPermission(): string
    {
        return "aetheris.default";
    }
}
