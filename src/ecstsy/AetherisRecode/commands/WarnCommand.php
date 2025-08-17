<?php

declare(strict_types=1);

namespace ecstsy\AetherisRecode\commands;

use CortexPE\Commando\args\TextArgument;
use CortexPE\Commando\BaseCommand;
use ecstsy\AetherisRecode\commands\arguments\PlayerArgument;
use ecstsy\AetherisRecode\commands\subcommands\staff\ClearSubCommand;
use ecstsy\AetherisRecode\Loader;
use pocketmine\command\CommandSender;
use pocketmine\player\Player;
use pocketmine\utils\TextFormat as C;

final class WarnCommand extends BaseCommand {

    public function prepare(): void {
        $this->setPermission($this->getPermission());

        $this->registerArgument(0, new PlayerArgument("player", false));
        $this->registerArgument(1, new TextArgument("reason", false));
        $this->registerSubCommand(new ClearSubCommand(Loader::getInstance(), "clear", "Clear all warnings"));
    }

    public function onRun(CommandSender $sender, string $aliasUsed, array $args): void
    {
        $target = $args['player'] ?? null;
        $reason = $args['reason'] ?? null;

        if (!$target instanceof Player) {
            $sender->sendMessage(C::colorize("&r&cPlayer not found or not online."));
            return;
        }

        if ($reason === null) {
            $sender->sendMessage(C::colorize("&r&cInvalid reason reached! set proper message"));
            return;
        }

        $session = Loader::getPlayerManager()->getSession($target);

        $staffUuid = $sender instanceof Player ? $sender->getUniqueId()->toString() : "CONSOLE";
        $session->addStrike($staffUuid, $reason, "warn", null, function(int $currentStrikes) use ($sender, $target, $reason) {
            $target->sendTitle(
                C::colorize("&c&lWARNING"),
                C::colorize("&f" . $reason),
                20, 60, 20
            );
            $target->sendMessage(C::colorize("&cYou have been warned by staff: &f" . $reason));
            $sender->sendMessage(C::colorize("&aWarned &e{$target->getName()} &afor: &f$reason &7(Strikes: $currentStrikes)"));
        });  
    }

    public function getPermission(): string {
        return "aetheris.warn";
    }
}