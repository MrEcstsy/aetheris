<?php

declare(strict_types=1);

namespace ecstsy\AetherisRecode\commands;

use CortexPE\Commando\args\IntegerArgument;
use CortexPE\Commando\BaseCommand;
use ecstsy\AetherisRecode\Loader;
use ecstsy\AetherisRecode\server\CoinFlipInstance;
use ecstsy\AetherisRecode\utils\Screens;
use pocketmine\command\CommandSender;
use pocketmine\player\Player;
use pocketmine\utils\TextFormat as C;

final class CoinFlipCommand extends BaseCommand {

    public function prepare(): void {
        $this->setPermission($this->getPermission());

        $this->registerArgument(0, new IntegerArgument('amount', true));
    }

    public function onRun(CommandSender $sender, string $aliasUsed, array $args): void
    {
        if (!$sender instanceof Player) {
            return;
        }

        $session = Loader::getPlayerManager()->getSession($sender);
        $amount = isset($args['amount']) ? $args['amount'] : null;

        if ($amount === null) {
            $sender->sendForm(CoinFlipInstance::getCoinFlipList($sender));
            return;
        }

        if ($amount < 1000) {
            $sender->sendMessage(C::colorize("&r&cError: &4Amount must be at least 1,000."));
            return;
        }

        if (CoinFlipInstance::hasSubmittedCoinFlip($sender)) {
            $sender->sendMessage(C::colorize("&r&cError: &4You have already submitted a coin flip."));
            return;
        }

        $sender->sendForm(CoinFlipInstance::getCoinFlipColorOption($sender, $amount));
    }

    public function getPermission(): string {
        return "aetheris.default";
    }
}