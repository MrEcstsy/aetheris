<?php

declare(strict_types=1);

namespace ecstsy\AetherisRecode\commands\subcommands\staff;

use CortexPE\Commando\args\IntegerArgument;
use CortexPE\Commando\args\RawStringArgument;
use CortexPE\Commando\BaseSubCommand;
use pocketmine\command\CommandSender;
use pocketmine\Server;
use pocketmine\utils\TextFormat as C;

final class BanSearchSubCommand extends BaseSubCommand
{

    public function prepare(): void
    {
        $this->setPermission($this->getPermission());

        $this->registerArgument(0, new IntegerArgument("page", true));
    }

    public function onRun(CommandSender $sender, string $aliasUsed, array $args): void
    {
        $banList = Server::getInstance()->getNameBans();
        $entries = array_values($banList->getEntries());

        $perPage = 10;
        $page = isset($args["page"]) && is_numeric($args["page"]) && (int)$args["page"] > 0 ? (int)$args["page"] : 1;
        $totalPages = (int)ceil(count($entries) / $perPage);

        if ($totalPages === 0) {
            $sender->sendMessage(C::colorize("&aNo players are currently banned."));
            return;
        }

        if ($page > $totalPages) $page = $totalPages;
        $start = ($page - 1) * $perPage;
        $bansOnPage = array_slice($entries, $start, $perPage);

        $sender->sendMessage(C::colorize("&6&l&m--------------------"));
        $sender->sendMessage(C::colorize("&c&lBanned Players &r&7(&f" . count($entries) . "&7) &8[&f{$page}&8/&f{$totalPages}&8]"));
        foreach ($bansOnPage as $entry) {
            $reason = $entry->getReason() ?? "No reason";
            $source = $entry->getSource() ?? "Unknown";
            $expires = $entry->getExpires() ? date("Y-m-d H:i", $entry->getExpires()->getTimestamp()) : "Permanent";
            $sender->sendMessage(C::colorize("&6• &c" . $entry->getName() . " &7- &e$reason"));
            $sender->sendMessage(C::colorize("   &7Banned by: &6$source &8| &7Expires: &b$expires"));
        }

        $sender->sendMessage(C::colorize("&6&l&m--------------------"));
        if ($totalPages > 1) {
            $sender->sendMessage(C::colorize("&7Use &e/bl list <page> &7to view other pages."));
        }
    }

    public function getPermission(): string
    {
        return "aetheris.banlookup";
    }
}
