<?php

declare(strict_types=1);

namespace ecstsy\AetherisRecode\commands;

use CortexPE\Commando\BaseCommand;
use ecstsy\AetherisRecode\commands\arguments\PlayerArgument;
use ecstsy\AetherisRecode\commands\subcommands\exp\AddExpSubCommand;
use ecstsy\AetherisRecode\Loader;
use pocketmine\utils\TextFormat as C;
use ecstsy\MartianUtilities\utils\PlayerUtils;
use pocketmine\command\CommandSender;
use pocketmine\player\Player;

final class ExpCommand extends BaseCommand {

    public function prepare(): void {
        $this->setPermission($this->getPermission());

        $this->registerArgument(0, new PlayerArgument("player", true));
        $this->registerSubCommand(new AddExpSubCommand(Loader::getInstance(), "add", "Add exp to a player", ["give"]));
    }

    public function onRun(CommandSender $sender, string $aliasUsed, array $args): void
    {
        if (!$sender instanceof Player) {
            return;
        }

        /** @var Player|null $target */
        $target = $args["player"] ?? $sender;
        $xpManager = $target->getXpManager();
        $currentLevel = $xpManager->getXpLevel();
        $currentTotalXp = $xpManager->getCurrentTotalXp();

        $xpForCurrentLevel = PlayerUtils::getExpToLevelUp($currentLevel);
        $xpForNextLevel = PlayerUtils::getExpToLevelUp($currentLevel + 1);

        $xpProgressInLevel = $currentTotalXp - $xpForCurrentLevel;

        $xpNeeded = max(0, ($xpForNextLevel - $xpForCurrentLevel) - $xpProgressInLevel);

        $sender->sendMessage(C::colorize(
            Loader::SERVER_PREFIX .
            $target->getNameTag() . " &fhas &d" . number_format($currentTotalXp) .
            " &fexp (level &d" . $currentLevel .
            "&f) and needs &d" . $xpNeeded . " &fmore exp to level up."
        ));
    }

    public function getPermission(): string {
        return "aetheris.default";
    }
}