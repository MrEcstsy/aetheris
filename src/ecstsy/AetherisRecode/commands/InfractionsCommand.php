<?php
namespace ecstsy\AetherisRecode\commands;

use CortexPE\Commando\BaseCommand;
use ecstsy\AetherisRecode\commands\arguments\PlayerArgument;
use ecstsy\AetherisRecode\Loader;
use ecstsy\AetherisRecode\utils\QueryStmts;
use pocketmine\command\CommandSender;
use pocketmine\player\Player;
use pocketmine\utils\TextFormat as C;

final class InfractionsCommand extends BaseCommand {

    public function prepare(): void {
        $this->setPermission("aetheris.infractions");
        $this->registerArgument(0, new PlayerArgument("player", false));
    }

    public function onRun(CommandSender $sender, string $aliasUsed, array $args): void {
        $target = $args['player'] ?? null;
        if (!$target instanceof Player) {
            $sender->sendMessage(C::colorize("&cPlayer not found or not online."));
            return;
        }

        $session = Loader::getPlayerManager()->getSession($target);
        $session->getActiveInfractions(function(array $infractions) use ($sender, $target) {
            if (empty($infractions)) {
                $sender->sendMessage(C::colorize("&a{$target->getName()} has no infractions."));
                return;
            }
            $sender->sendMessage(C::colorize("&eInfractions for &b{$target->getName()}&e:"));
            foreach ($infractions as $row) {
                $type = ucfirst($row["type"]);
                $reason = $row["reason"];
                $date = date("Y-m-d H:i", $row["timestamp"]);
                $sender->sendMessage(C::colorize("&7[$date] &c$type &7- &f$reason"));
            }
        });
    }

    public function getPermission(): string {
        return "aetheris.infractions";
    }
}