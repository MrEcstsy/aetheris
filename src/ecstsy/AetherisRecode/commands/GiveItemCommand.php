<?php

declare(strict_types=1);

namespace ecstsy\AetherisRecode\commands;

use CortexPE\Commando\args\IntegerArgument;
use CortexPE\Commando\args\RawStringArgument;
use CortexPE\Commando\BaseCommand;
use ecstsy\AetherisRecode\server\items\AetherisItemFactory;
use pocketmine\command\CommandSender;
use pocketmine\player\Player;
use pocketmine\utils\TextFormat as C;

final class GiveItemCommand extends BaseCommand {

    public function prepare(): void {
        $this->setPermission($this->getPermission());

        $this->registerArgument(0, new RawStringArgument('aetheris-item', false));
        $this->registerArgument(1, new IntegerArgument("amount", true));
    }

    public function onRun(CommandSender $sender, string $aliasUsed, array $args): void
    {
        if (!$sender instanceof Player) {
            $sender->sendMessage(C::RED . "This command can only be used in‐game.");
            return;
        }

        $key = $args["aetheris-item"];
        $amount = isset($args["amount"]) ? (int)$args["amount"] : 1;

        try {
            if (in_array($key, ["bank_note", "xpnote"], true)) {
                if ($amount === null) {
                    $sender->sendMessage(C::RED . "You must specify an amount for {$key}.");
                    return;
                }
                $item = $key === "bank_note"
                    ? AetherisItemFactory::bankNote($sender, $amount)
                    : AetherisItemFactory::xpNote($sender, $amount);
            } else {
                $templateKey = $key;
                $item = AetherisItemFactory::create($templateKey);
            }
        } catch (\InvalidArgumentException $e) {
            $sender->sendMessage(C::RED . "Unknown Aetheris item key: " . $key);
            return;
        }

        $sender->getInventory()->addItem($item->setCount($amount));
        $sender->sendMessage(C::GREEN . "Gave you: " . C::WHITE . $item->getName());
    }

    public function getPermission(): string {
        return "aetheris.give-item";
    }
}