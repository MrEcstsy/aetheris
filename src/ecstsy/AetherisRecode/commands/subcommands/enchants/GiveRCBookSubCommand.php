<?php

declare(strict_types=1);

namespace ecstsy\AetherisRecode\commands\subcommands\enchants;

use CortexPE\Commando\args\IntegerArgument;
use CortexPE\Commando\args\RawStringArgument;
use CortexPE\Commando\BaseSubCommand;
use ecstsy\AetherisRecode\enchantments\Groups;
use ecstsy\AetherisRecode\server\items\AetherisItemFactory; 
use ecstsy\MartianUtilities\utils\PlayerUtils;
use pocketmine\command\CommandSender;
use pocketmine\player\Player;
use pocketmine\utils\TextFormat as C;

class GiveRCBookSubCommand extends BaseSubCommand {

    public function prepare(): void {
        $this->setPermission($this->getPermission());

        $this->registerArgument(0, new RawStringArgument("group", false));
        $this->registerArgument(1, new RawStringArgument("player", false));
        $this->registerArgument(2, new IntegerArgument("amount", false));
    }

    public function onRun(CommandSender $sender, string $aliasUsed, array $args): void {
        if (!$sender instanceof Player) {
            $sender->sendMessage(C::colorize("&r&7In-game only."));
            return;
        }

        $groupKey = isset($args["group"]) ? strtoupper($args["group"]) : null;
        $targetName = $args["player"] ?? null;
        $amount = $args["amount"] ?? 1;

        $groupData = $groupKey !== null ? Groups::getGroupData($groupKey) : null;
        if ($groupData === null) {
            $available = implode(", ", Groups::getGroupData($groupKey));
            $sender->sendMessage(C::colorize("&r&cUnknown group “{$groupKey}”. Available: &f{$available}"));
            return;
        }

        $player = PlayerUtils::getPlayerByPrefix($targetName);
        if (!$player instanceof Player) {
            $sender->sendMessage(C::colorize("&r&cPlayer “{$targetName}” is not online!"));
            return;
        }

        if ($amount < 1 || $amount > 64) {
            $sender->sendMessage(C::colorize("&cInvalid amount. Must be between 1 and 64."));
            return;
        }

        $book = AetherisItemFactory::randomEnchantBook(strtolower($groupKey))->setCount($amount);
        if (!$player->getInventory()->canAddItem($book)) {
            $sender->sendMessage(C::colorize("&r&c{$player->getName()}'s inventory is full."));
            return;
        }
        $player->getInventory()->addItem($book);

        $sender->sendMessage(C::colorize(str_replace(
            ["{player}", "{amount}", "{group}"],
            [$player->getName(), $amount, $groupData['group_name']],
            "&aGave &2{player} &f{amount}x &e{group}&a random enchantment books!"
        )));
        PlayerUtils::playSound($sender, "random.orb");
    }

    public function getUsage(): string {
        return "/ae givercbook <group> <player> <amount>";
    }

    public function getPermission(): string {
        return "aetheris.give-rcbook";
    }
}
