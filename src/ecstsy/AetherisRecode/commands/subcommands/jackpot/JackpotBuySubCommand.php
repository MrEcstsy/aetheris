<?php

declare(strict_types=1);

namespace ecstsy\AetherisRecode\commands\subcommands\jackpot;

use CortexPE\Commando\args\IntegerArgument;
use CortexPE\Commando\BaseSubCommand;
use ecstsy\AetherisRecode\Loader;
use pocketmine\command\CommandSender;
use pocketmine\player\Player;

final class JackpotBuySubCommand extends BaseSubCommand {

    public function prepare(): void {
        $this->setPermission($this->getPermission());

        $this->registerArgument(0, new IntegerArgument("amount", false));
    }

    public function onRun(CommandSender $sender, string $aliasUsed, array $args): void
    {
        if (!$sender instanceof Player) {
            return;
        }

        $amount = $args["amount"] ?? null;
        if ($amount === null || $amount <= 0) {
            $sender->sendMessage("§cPlease specify a valid ticket amount.");
            return;
        }

        $jackpot = Loader::getJackpotInstance();
        $success = $jackpot->purchaseTickets($sender, (int)$amount);

        if ($success) {
            $sender->sendMessage("§aYou have purchased §l" . number_format($amount) . "§r§a jackpot ticket(s)!");
        }
    }

    public function getPermission(): string {
        return "aetheris.default";
    }
}