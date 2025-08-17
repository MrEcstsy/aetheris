<?php

declare(strict_types=1);

namespace ecstsy\AetherisRecode\commands;

use CortexPE\Commando\BaseCommand;
use ecstsy\AetherisRecode\commands\subcommands\jackpot\JackpotBuySubCommand;
use ecstsy\AetherisRecode\Loader;
use ecstsy\MartianUtilities\utils\GeneralUtils;
use pocketmine\command\CommandSender;
use pocketmine\player\Player;
use pocketmine\utils\TextFormat as C;

final class JackpotCommand extends BaseCommand {

    public function prepare(): void {
        $this->setPermission($this->getPermission());

        $this->registerSubCommand(new JackpotBuySubCommand(Loader::getInstance(), "buy", "Buy jackpot tickets"));
    }

    public function onRun(CommandSender $sender, string $aliasUsed, array $args): void
    {
        if (!$sender instanceof Player) {
            return;
        }

        $jackpot = Loader::getJackpotInstance();

        $pot = $jackpot->getPot();
        $tax = $jackpot->getTaxPercent();
        $tickets = $jackpot->getTickets();
        $totalTickets = array_sum($tickets);
        $yourTickets = $tickets[$sender->getUniqueId()->toString()] ?? 0;
        $winPercentage = $totalTickets > 0 ? round(($yourTickets / $totalTickets) * 100, 2) : 0;
        $time = $jackpot->getTimeLeft();
        $taxPercent = (int)($tax * 100);

        $sender->sendMessage(C::colorize("&r&d&lAetheris &fJackpot&r\n" .
            "  &dJackpot Value: &f$" . number_format($pot) . " &7($taxPercent% tax)\n" .
            "  &dTickets Sold: &f$totalTickets\n" .
            "  &dYour Tickets: &f$yourTickets &7(" . ($totalTickets > 0 ? "$winPercentage%" : "0%") . " chance)\n" .
            "&d(!) &fNext Winner in &d" . GeneralUtils::translateTime($time)
        ));
    }

    public function getPermission(): string {
        return "aetheris.default";
    }
}