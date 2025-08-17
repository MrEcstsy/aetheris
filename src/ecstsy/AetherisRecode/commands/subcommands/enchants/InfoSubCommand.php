<?php

declare(strict_types=1);

namespace ecstsy\AetherisRecode\commands\subcommands\enchants;

use CortexPE\Commando\args\RawStringArgument;
use CortexPE\Commando\BaseSubCommand;
use ecstsy\AetherisRecode\enchantments\CustomEnchantment;
use ecstsy\AetherisRecode\enchantments\CustomEnchantments;
use ecstsy\AetherisRecode\enchantments\Groups;
use pocketmine\command\CommandSender;
use pocketmine\item\enchantment\ItemFlags;
use pocketmine\player\Player;
use pocketmine\utils\TextFormat as C;

final class InfoSubCommand extends BaseSubCommand {

    public function prepare(): void {
        $this->setPermission($this->getPermission());
        $this->registerArgument(0, new RawStringArgument("enchantment", true));
    }

    public function onRun(CommandSender $sender, string $aliasUsed, array $args): void {
        if (!$sender instanceof Player) {
            $sender->sendMessage(C::colorize("&r&7In-game only."));
            return;
        }

        $input = $args["enchantment"] ?? null;
        if ($input === null) {
            $sender->sendMessage(C::colorize("&cYou must specify an enchantment name."));
            return;
        }

        $enchantment = CustomEnchantments::getEnchantmentByName($input);
        if (!$enchantment instanceof CustomEnchantment) {
            $sender->sendMessage(C::colorize(str_replace("{enchant}", $input, "&c{enchant} is not a valid enchant!")));
            return;
        }

        // Get data
        $name = ucfirst($enchantment->getName());
        $description = $enchantment->getDescription();
        $maxLevel = $enchantment->getMaxLevel();
        $groupId = $enchantment->getRarity();
        $groupName = Groups::getGroupName($groupId);
        $groupColor = Groups::translateGroupToColor($groupId);
        $appliesTo = CustomEnchantments::$definitions[$name]['flags'] ?? 'Unknown';

        $appliesToString = $this->translateFlagsToText($enchantment->getFlags());

        $sender->sendMessage(C::YELLOW . "» " . C::GOLD . $groupColor . $name . " &7(" . $groupName . ")");
        $sender->sendMessage(C::GRAY . "Description: " . C::WHITE . $description);
        $sender->sendMessage(C::GRAY . "Max Level: " . C::WHITE . $maxLevel);
        $sender->sendMessage(C::GRAY . "Applies To: " . C::WHITE . $appliesToString);
    }

    public function getPermission(): string {
        return "aetheris.info";
    }

    private function translateFlagsToText(int $flags): string {
        $map = [
            ItemFlags::ALL => "All Items",
            ItemFlags::ARMOR => "Armor",
            ItemFlags::SWORD => "Sword",
            ItemFlags::AXE => "Axe",
            ItemFlags::PICKAXE => "Pickaxe",
            ItemFlags::SHOVEL => "Shovel",
            ItemFlags::HOE => "Hoe",
            ItemFlags::BOW => "Bow",
            ItemFlags::TRIDENT => "Trident",
            ItemFlags::FEET => "Boots",
            ItemFlags::LEGS => "Leggings",
            ItemFlags::TORSO => "Chestplate",
            ItemFlags::HEAD => "Helmet",
        ];

        $applies = [];
        foreach ($map as $bit => $name) {
            if (($flags & $bit) === $bit) {
                $applies[] = $name;
            }
        }

        return implode(", ", $applies) ?: "None";
    }
}
