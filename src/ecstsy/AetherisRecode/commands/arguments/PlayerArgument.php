<?php
declare(strict_types=1);

namespace ecstsy\AetherisRecode\commands\arguments;

use CortexPE\Commando\args\BaseArgument;
use ecstsy\MartianUtilities\utils\PlayerUtils;
use pocketmine\player\Player;
use pocketmine\command\CommandSender;
use pocketmine\network\mcpe\protocol\AvailableCommandsPacket;

class PlayerArgument extends BaseArgument {

    public function getNetworkType(): int {
        return AvailableCommandsPacket::ARG_TYPE_TARGET;
    }

    public function getTypeName(): string {
        return "player";
    }

    public function canParse(string $testString, CommandSender $sender): bool {
        return PlayerUtils::getPlayerByPrefix($testString) instanceof Player;
    }

    /**
     * @param string        $argument
     * @param CommandSender $sender
     * @return Player
     */
    public function parse(string $argument, CommandSender $sender): Player {
        $player = PlayerUtils::getPlayerByPrefix($argument);
        if (!($player instanceof Player)) {
            throw new \InvalidArgumentException("Player “{$argument}” not found");
        }
        return $player;
    }
}
