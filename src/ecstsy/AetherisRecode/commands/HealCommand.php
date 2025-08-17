<?php

declare(strict_types=1);

namespace ecstsy\AetherisRecode\commands;

use CortexPE\Commando\BaseCommand;
use ecstsy\AetherisRecode\commands\arguments\PlayerArgument;
use ecstsy\AetherisRecode\Loader;
use pocketmine\command\CommandSender;
use pocketmine\player\Player;
use pocketmine\world\particle\HeartParticle;
use pocketmine\world\sound\XpCollectSound;
use pocketmine\utils\TextFormat as C;

final class HealCommand extends BaseCommand {

    public function prepare(): void {
        $this->setPermission($this->getPermission());
        $this->registerArgument(0, new PlayerArgument("player", true));
    }

    public function onRun(CommandSender $sender, string $aliasUsed, array $args): void
    {
        if (!$sender instanceof Player) return;

        $target = $sender;
        if (isset($args["player"])) {
            if ($sender->hasPermission("aetheris.command.heal.others")) {
                $target = $args["player"];
                if (!$target instanceof Player) {
                    $sender->sendMessage(C::colorize("&c&l✗ &r&7Player not found or not online."));
                    return;
                }
            } else {
                $sender->sendMessage(C::colorize("&c&l✗ &r&7You do not have permission to heal others."));
                return;
            }
        }

        $session = Loader::getInstance()->getPlayerManager()->getSession($sender);
        $cooldown = Loader::getInstance()->getConfig()->getNested("settings.cooldowns.heal", 60);

        if ($session->getCooldown("heal") > 0) {
            $remaining = $session->getCooldown("heal");
            $sender->sendMessage(C::colorize("&d&l┃ &r&7You must wait &d{$remaining}s &7before using &d/heal&r&7 again."));
            return;
        }

        $target->setHealth($target->getMaxHealth());
        $target->getWorld()->addParticle($target->getPosition(), new HeartParticle());
        $target->getWorld()->addSound($target->getPosition(), new XpCollectSound());

        if ($target === $sender) {
            $sender->sendMessage(C::colorize("&d&l┃ &r&aYou have been &dhealed&r&a!"));
        } else {
            $target->sendMessage(C::colorize("&d&l┃ &r&aYou have been &dhealed &aby &d{$sender->getName()}&a!"));
            $sender->sendMessage(C::colorize("&d&l┃ &r&aYou have healed &d{$target->getName()}&a!"));
        }

        $session->addCooldown("heal", $cooldown);
    }

    public function getPermission(): string { return "aetheris.heal"; }
}