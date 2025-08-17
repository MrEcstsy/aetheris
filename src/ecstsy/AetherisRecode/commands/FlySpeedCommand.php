<?php

declare(strict_types=1);

namespace ecstsy\AetherisRecode\commands;

use CortexPE\Commando\args\FloatArgument;
use CortexPE\Commando\BaseCommand;
use ecstsy\MartianUtilities\utils\PlayerUtils;
use pocketmine\command\CommandSender;
use pocketmine\player\Player;
use pocketmine\utils\TextFormat as C;
use pocketmine\world\sound\FizzSound;

final class FlySpeedCommand extends BaseCommand {

    public function prepare(): void {
        $this->setPermission($this->getPermission());

        $this->registerArgument(0, new FloatArgument("speed", false));
    }
    
    public function onRun(CommandSender $sender, string $aliasUsed, array $args): void
    {
        if (!$sender instanceof Player) {
            return;
        }

        $speed = isset($args["speed"]) ? $args["speed"] : null;

        if ($speed === null) {
            $sender->sendMessage(C::colorize("&r&cError: &4Speed not specified."));
            $sound = new FizzSound();
            $sender->getWorld()->addSound($sender->getPosition(), $sound);
            return;
        }

        $sender->setFlightSpeedMultiplier($speed);
        $sender->sendMessage(C::colorize("&r&aYour fly speed has been set to " . $speed . "."));
        PlayerUtils::playSound($sender, "random.orb");
    }

    public function getPermission(): string {
        return "aetheris.flyspeed";
    }
}