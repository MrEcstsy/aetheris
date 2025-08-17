<?php

declare(strict_types=1);

namespace ecstsy\AetherisRecode\commands\subcommands\enchants;

use CortexPE\Commando\args\IntegerArgument;
use CortexPE\Commando\args\RawStringArgument;
use CortexPE\Commando\BaseSubCommand;
use ecstsy\AetherisRecode\enchantments\CustomEnchantment;
use ecstsy\AetherisRecode\enchantments\CustomEnchantmentManager;
use ecstsy\AetherisRecode\enchantments\CustomEnchantments;
use pocketmine\command\CommandSender;
use pocketmine\item\VanillaItems;
use pocketmine\player\Player;
use pocketmine\utils\TextFormat as C;

final class EnchantSubCommand extends BaseSubCommand {

    public function prepare(): void {
        $this->setPermission($this->getPermission());

        $this->setPermissionMessage(C::colorize("&r&cYou don't have permission to do that!"));
        $this->registerArgument(0, new RawStringArgument("enchantment", true));
        $this->registerArgument(1, new IntegerArgument("level", true));
    }

    public function onRun(CommandSender $sender, string $aliasUsed, array $args): void
    {
        if (!$sender instanceof Player) {
            $sender->sendMessage(C::colorize("&r&7In-game only!"));
            return;
        }

        if (!isset($args["enchantment"]) || !isset($args["level"])) {
            $sender->sendMessage(C::colorize(str_replace("{usage}", $this->getUsage(), "&cUsage: {usage}")));
            return;
        }

        $enchantName = $args["enchantment"];
        $level = (int)$args["level"];
        $item = $sender->getInventory()->getItemInHand();
    
        $enchant = CustomEnchantments::getEnchantmentByName($enchantName) ?? null;
        if ($enchant === null || !($enchant instanceof CustomEnchantment)) {
            $sender->sendMessage(C::colorize(str_replace("{enchant}", $enchantName, "&c{enchant} is not a valid enchant!")));
            return;
        }
    
        if ($item->getTypeId() === VanillaItems::AIR()->getTypeId()) {
            $sender->sendMessage(C::colorize("&cYou must be holding an item!"));
            return;
        }
    
        if ($level < 1 || $level > $enchant->getMaxLevel()) {
            $maxLevel = $enchant->getMaxLevel();
            $levelsArray = range(1, $maxLevel);
            $levels = implode(", ", $levelsArray);
            $sender->sendMessage(C::colorize(str_replace("{levels}", $levels, "&cInvalid enchant level! Try using: {levels}")));
            return;
        }

        $existingEnchantment = $item->getNamedTag()->getCompoundTag("MartianCES")?->getInt($enchant->getName(), -1);
        if ($existingEnchantment !== -1) {
            if ($existingEnchantment === $level) {
                CustomEnchantmentManager::removeEnchantment($item, $enchant);
                $sender->sendMessage(C::colorize(str_replace("{enchant}", $enchant->getName(), "&cRemoved the {enchant} enchant from the item.")));
            } else {
                CustomEnchantmentManager::applyEnchantment($item, $enchant, $level);
                $message = $level > $existingEnchantment
                    ? str_replace(["{enchant}", "{previous-level}", "{level}"], [$enchant->getName(), $existingEnchantment, $level], "&2Upgraded {enchant} from level {previous-level} to {level}.")
                    : str_replace(["{enchant}", "{previous-level}", "{level}"], [$enchant->getName(), $existingEnchantment, $level], "&2Downgraded {enchant} from level {previous-level} to {level}.");
                $sender->sendMessage(C::colorize($message));
            }
        } else {
            CustomEnchantmentManager::applyEnchantment($item, $enchant, $level);
            $sender->sendMessage(C::colorize(str_replace("{enchant}", $enchant->getName(), "&aAdded the {enchant} enchant!")));
        }
    
        $sender->getInventory()->setItemInHand($item);
    }

    public function getUsage(): string {
        return "/me enchant <enchantment> <level>";
    }

    public function getPermission(): ?string
    {
        return "aetheris.enchant";
    }
}