<?php

declare(strict_types=1);

namespace ecstsy\AetherisRecode\commands;

use CortexPE\Commando\BaseCommand;
use ecstsy\AetherisRecode\commands\arguments\PlayerArgument;
use ecstsy\AetherisRecode\server\AetherGuardInstance;
use pocketmine\command\CommandSender;
use pocketmine\player\Player;
use pocketmine\utils\TextFormat as C;

final class EtherealGuardCommand extends BaseCommand {

    public function prepare(): void {
        $this->setPermission($this->getPermission());

        $this->registerArgument(0, new PlayerArgument("player", false));
    }

    public function onRun(CommandSender $sender, string $aliasUsed, array $args): void
    {
        if (!$sender instanceof Player) {
            return;
        }

        $instance = AetherGuardInstance::getInstance();

        if (isset($args["player"]) && $args["player"] !== null) {
            $target = $args["player"];
            $targetName = $target instanceof Player ? $target->getName() : (string)$args["player"];
            $uuid = $target instanceof Player ? $target->getUniqueId()->toString() : $targetName;

            $instance->getAnticheatLogsByUuid($uuid, function(array $rows) use ($sender, $targetName) {
                if (empty($rows)) {
                    $sender->sendMessage(C::colorize("&cNo anti-cheat logs found for &e$targetName&c."));
                    return;
                }
                $sender->sendMessage(C::colorize("&d[AntiCheat Logs for &b$targetName&d]"));
                foreach ($rows as $row) {
                    $sender->sendMessage(C::colorize(
                        "&7- &f{$row['username']} &8| &bViolations: &f{$row['violations']} &8| &e{$row['reason']} &8| &7" . date("Y-m-d H:i:s", $row['timestamp'])
                    ));
                }
            });
            return;
        }

        $instance->getAllAnticheatLogs(function(array $rows) use ($sender) {
            if (empty($rows)) {
                $sender->sendMessage(C::colorize("&cNo anti-cheat logs found."));
                return;
            }
            $sender->sendMessage(C::colorize("&d[Recent AntiCheat Logs]"));
            foreach (array_slice($rows, 0, 10) as $row) {
                $sender->sendMessage(C::colorize(
                    "&7- &f{$row['username']} &8| &bViolations: &f{$row['violations']} &8| &e{$row['reason']} &8| &7" . date("Y-m-d H:i:s", $row['timestamp'])
                ));
            }
        });
    }

    public function getPermission(): string {
        return "aetheris.admin";
    }
}