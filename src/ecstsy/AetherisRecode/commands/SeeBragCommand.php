<?php
declare(strict_types=1);

namespace ecstsy\AetherisRecode\commands;

use ecstsy\AetherisRecode\utils\BragTracker;
use ecstsy\MartianUtilities\utils\PlayerUtils;
use CortexPE\Commando\args\RawStringArgument;
use CortexPE\Commando\BaseCommand;
use ecstsy\AetherisRecode\utils\ui\BragScreen;
use pocketmine\command\CommandSender;
use pocketmine\player\Player;
use pocketmine\utils\TextFormat as C;

final class SeeBragCommand extends BaseCommand {

    protected function prepare(): void {
        $this->setPermission("aetheris.default");
        $this->registerArgument(0, new RawStringArgument("name", false));
    }

    public function onRun(CommandSender $sender, string $aliasUsed, array $args): void {
        if (!$sender instanceof Player) {
            $sender->sendMessage(C::colorize("&r&cThis command can only be used in-game."));
            return;
        }

        $targetName = $args["name"] ?? null;
        if ($targetName === null) {
            $sender->sendMessage(C::colorize("&r&cPlease specify a player."));
            return;
        }

        $target = PlayerUtils::getPlayerByPrefix($targetName);
        if ($target === null) {
            $sender->sendMessage(C::colorize("&r&cThat player could not be found."));
            return;
        }

        $targetName = $target->getName();
        if (!BragTracker::hasLastInventoryBrag($targetName)) {
            $sender->sendMessage(C::colorize("&r&cThat player has not bragged recently."));
            return;
        }

        BragScreen::display($target);
    }
}
