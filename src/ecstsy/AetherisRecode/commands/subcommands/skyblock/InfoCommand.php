<?php

declare(strict_types=1);

namespace ecstsy\AetherisRecode\commands\subcommands\skyblock;

use CortexPE\Commando\args\RawStringArgument;
use CortexPE\Commando\BaseSubCommand;
use ecstsy\AetherisRecode\Loader;
use ecstsy\AetherisRecode\skyblock\SkyBlockManager;
use ecstsy\AetherisRecode\utils\Utils;
use ecstsy\MartianUtilities\utils\PlayerUtils;
use pocketmine\command\CommandSender;
use pocketmine\player\Player;
use pocketmine\Server;
use pocketmine\utils\TextFormat as C;

final class InfoCommand extends BaseSubCommand {

    public function prepare(): void {
        $this->setPermission($this->getPermission());

        $this->registerArgument(0, new RawStringArgument("target", true));
    }

    public function onRun(CommandSender $sender, string $aliasUsed, array $args): void
    {
        if (!$sender instanceof Player) {
            return;
        }

        $target = $args['target'] ?? null;

        if ($target === null) {
            $session = Loader::getPlayerManager()->getSession($sender);
            $skyblock = $session?->getIsland();

            if ($skyblock === null) {
                $sender->sendMessage(C::colorize("&r&4Error: &cYou are not in an island!"));
                return;
            }

            Utils::sendIslandInfo($sender, $skyblock);
            return;
        }

        $skyblock = Loader::getSkyBlockManager()->getSkyBlock(strtolower($target));

        if ($skyblock === null) {
            $player = PlayerUtils::getPlayerByPrefix($target);
            if ($player !== null) {
                $session = Loader::getPlayerManager()->getSession($player);
            } else {
                $session = Loader::getPlayerManager()->getSessionByName($target);
            }

            $skyblock = $session?->getIsland();
            if ($skyblock === null) {
                $sender->sendMessage(C::colorize("&r&4Error: &cNo island found for '&7" . $target . "&c'"));
                return;
            }
        }

        Utils::sendIslandInfo($sender, $skyblock);
    }

    public function getPermission(): string {
        return "aetheris.default";
    }
}