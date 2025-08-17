<?php

declare(strict_types=1);

namespace ecstsy\AetherisRecode\commands\subcommands\kits;

use CortexPE\Commando\args\RawStringArgument;
use CortexPE\Commando\BaseSubCommand;
use ecstsy\AetherisRecode\server\items\AetherisItemFactory;
use ecstsy\AetherisRecode\utils\Utils;
use ecstsy\MartianUtilities\utils\PlayerUtils;
use pocketmine\command\CommandSender;
use pocketmine\player\Player;
use pocketmine\utils\TextFormat as C;

final class KitGiveSubCommand extends BaseSubCommand {
    public function prepare(): void {
        $this->setPermission($this->getPermission());

        $this->registerArgument(0, new RawStringArgument('kit', true));
        $this->registerArgument(0, new RawStringArgument("name", true));
    }

    public function onRun(CommandSender $sender, string $aliasUsed, array $args): void
    {
        $kitName = isset($args['kit']) ? $args['kit'] : null;
        $player = isset($args['name']) ? PlayerUtils::getPlayerByPrefix($args['name']) : $sender;

        if ($kitName === null) {
            $sender->sendMessage(C::colorize("&r&cPlease specify a kit name."));
            return;
        }

        if ($player === $sender) {
            if ($player instanceof Player) {
                $player->getInventory()->addItem(AetherisItemFactory::kitToken($kitName));
                $player->sendMessage(C::colorize("&r&3You have been given the " . $kitName . " kit."));
            }
        }

        if ($player !== $sender) {
            if ($player instanceof Player) {
                $player->getInventory()->addItem(AetherisItemFactory::kitToken($kitName));
                $sender->sendMessage(C::colorize("&r&3You have given " . $player->getName() . " the " . $kitName . " kit."));
                $player->sendMessage(C::colorize("&r&3You have been given the " . $kitName . " kit."));
            }
        }
    }

    public function getPermission(): ?string
    {
        return "aetheris.kits.give";
    }
}