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

final class FeedCommand extends BaseCommand {

    public function prepare(): void {
        $this->setPermission($this->getPermission());

        $this->registerArgument(0, new PlayerArgument("player", true));
    }

    public function onRun(CommandSender $sender, string $aliasUsed, array $args): void
    {
        if (!$sender instanceof Player) return;

        $target = $args["player"] ?? $sender;
        if (!$target instanceof Player) {
            $sender->sendMessage(C::colorize("&c&l✗ &r&7Player not found or not online."));
            return;
        }

        $session = Loader::getInstance()->getPlayerManager()->getSession($sender);
        $cooldown = Loader::getInstance()->getConfig()->getNested("settings.cooldowns.feed", 60);

        if ($session->getCooldown("feed") > 0) {
            $remaining = $session->getCooldown("feed");
            $sender->sendMessage(C::colorize("&d&l┃ &r&7You must wait &d{$remaining}s &7before using &d/feed&r&7 again."));
            return;
        }

        $target->getHungerManager()->setFood($target->getHungerManager()->getMaxFood());
        $target->getHungerManager()->setSaturation(20.0);

        $target->getWorld()->addParticle($target->getPosition(), new HeartParticle());
        $target->getWorld()->addSound($target->getPosition(), new XpCollectSound());

        if ($target === $sender) {
            $sender->sendMessage(C::colorize("&d&l┃ &r&aYou have been &dfed&r&a!"));
        } else {
            $target->sendMessage(C::colorize("&d&l┃ &r&aYou have been &dfed &aby &d{$sender->getName()}&a!"));
            $sender->sendMessage(C::colorize("&d&l┃ &r&aYou have fed &d{$target->getName()}&a!"));
        }

        $session->addCooldown("feed", $cooldown);
    }

    public function getPermission(): string
    {
        return "aetheris.feed";
    }
}