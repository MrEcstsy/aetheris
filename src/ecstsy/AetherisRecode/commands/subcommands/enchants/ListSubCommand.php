<?php

declare(strict_types=1);

namespace ecstsy\AetherisRecode\commands\subcommands\enchants;

use CortexPE\Commando\args\IntegerArgument;
use CortexPE\Commando\BaseSubCommand;
use ecstsy\AetherisRecode\enchantments\CustomEnchantments;
use ecstsy\AetherisRecode\enchantments\Groups;
use pocketmine\command\CommandSender;
use pocketmine\player\Player;
use pocketmine\utils\TextFormat as C;

final class ListSubCommand extends BaseSubCommand {

    private const ENCHANTMENTS_PER_PAGE = 15;

    public function prepare(): void {
        $this->setPermission($this->getPermission());
        $this->registerArgument(0, new IntegerArgument("page", true));
    }

    public function onRun(CommandSender $sender, string $aliasUsed, array $args): void {
        if (!$sender instanceof Player) {
            $sender->sendMessage(C::colorize("&r&7In-game only!"));
            return;
        }

        $page = isset($args["page"]) ? max(1, (int)$args["page"]) : 1;

        $allEnchantments = CustomEnchantments::getAll(); 
        $groupedEnchantments = [];

        foreach ($allEnchantments as $enchantment) {
            $groupId = $enchantment->getRarity();
            $groupedEnchantments[$groupId][] = $enchantment;
        }

        ksort($groupedEnchantments);

        $sortedList = [];
        foreach ($groupedEnchantments as $groupId => $enchantments) {
            foreach ($enchantments as $enchant) {
                $sortedList[] = $enchant;
            }
        }

        $totalEnchantments = count($sortedList);
        $totalPages = max(1, (int)ceil($totalEnchantments / self::ENCHANTMENTS_PER_PAGE));

        if ($page > $totalPages) {
            $page = $totalPages;
        }

        $start = ($page - 1) * self::ENCHANTMENTS_PER_PAGE;
        $end = min($start + self::ENCHANTMENTS_PER_PAGE, $totalEnchantments);

        $headerFooter = C::YELLOW . "[<] " . C::DARK_GRAY . "+-----< " . C::GOLD . "Custom Enchantments List " . C::GRAY . "(Page: " . $page . "/" . $totalPages . ") " . C::DARK_GRAY . ">-----+" . C::YELLOW . " [>]";
        $sender->sendMessage($headerFooter);

        for ($i = $start; $i < $end; $i++) {
            $enchant = $sortedList[$i];
            $groupColor = Groups::translateGroupToColor($enchant->getRarity());
            $sender->sendMessage(C::colorize("&r&7" . ($i + 1) . ". " . $groupColor . $enchant->getName()));
        }

        $sender->sendMessage($headerFooter);
    }

    public function getPermission(): ?string {
        return "aetheris.list";
    }
}
