<?php

declare(strict_types=1);

namespace ecstsy\AetherisRecode\commands\subcommands\enchants;

use CortexPE\Commando\args\IntegerArgument;
use CortexPE\Commando\args\RawStringArgument;
use CortexPE\Commando\BaseSubCommand;
use ecstsy\AetherisRecode\enchantments\CustomEnchantments;
use ecstsy\AetherisRecode\server\items\AetherisItemFactory;
use ecstsy\MartianUtilities\utils\PlayerUtils;
use pocketmine\command\CommandSender;
use pocketmine\utils\TextFormat as C;
use pocketmine\player\Player;

final class GiveBookSubCommand extends BaseSubCommand {

    public function prepare(): void {
        $this->setPermission($this->getPermission());

        $this->setPermissionMessage(C::colorize("&r&cYou don't have permission to do that!"));
        $this->registerArgument(0, new RawStringArgument("name", false));
        $this->registerArgument(1, new RawStringArgument("enchantment", false));
        $this->registerArgument(2, new IntegerArgument("level", false));
        $this->registerArgument(3, new IntegerArgument("amount", true));
        $this->registerArgument(4, new IntegerArgument("success", true));
        $this->registerArgument(5, new IntegerArgument("destroy", true));
    }

    public function onRun(CommandSender $sender, string $aliasUsed, array $args): void
    {
        if (!$sender instanceof Player) {
            $sender->sendMessage(C::colorize("&r&7In-game only!"));
            return;
        }

        $player = isset($args["name"]) ? PlayerUtils::getPlayerByPrefix($args["name"]) : null;
        if ($player === null) {
            $sender->sendMessage(C::colorize("&r&cThat player is not online!"));
            return;
        }

        $enchant = $args["enchantment"] ?? null;
        if ($enchant === null) {
            $sender->sendMessage(C::colorize("&c{enchant} is not a valid enchant!"));
            return;
        }

        $level = $args["level"] ?? 1;
        $enchantment = CustomEnchantments::getEnchantmentByName($enchant);
        if ($enchantment === null) {
            $sender->sendMessage(C::colorize("&cInvalid enchantment!"));
            return;
        }

        $amount = $args["amount"] ?? 1;
        $success = $args["success"] ?? null;
        $destroy = $args["destroy"] ?? null;

        $book = AetherisItemFactory::enchantmentBook($enchantment, $level, $success, $destroy)->setCount($amount);

        if ($player->getInventory()->canAddItem($book)) {
            $player->getInventory()->addItem($book);
            $sender->sendMessage(C::colorize(str_replace(
                ["{enchant}", "{level}", "{player}", "{amount}"],
                [$enchant, $level, $player->getName(), $amount],
                "&aGave &2{player} {amount}x {enchant} {level} &abooks!"
            )));
            PlayerUtils::playSound($sender, "random.orb");
        } else {
            $sender->getWorld()->dropItem($sender->getPosition()->asVector3(), $book);
        }
    }

    public function getUsage(): string {
        return "/ae givebook <player> <enchant> <level> [amount] [success] [destroy]";
    }

    public function getPermission(): ?string
    {
        return "aetheris.give-book";
    }
}
