<?php

declare(strict_types=1);

namespace ecstsy\AetherisRecode\server\scoreboard;

use ecstsy\AetherisRecode\Loader;
use ecstsy\AetherisRecode\player\PlayerManager;
use IvanCraft623\RankSystem\RankSystem;
use pocketmine\player\Player;
use pocketmine\utils\TextFormat as C;
use Yanoox\ScoreBoardAPI;

final class ScoreboardHelper {

    public static function initScoreboard(Player $player): void {
        ScoreBoardAPI::sendScore($player, C::colorize("     &r&l&dETHEREAL&fHUB     "));
        $session = PlayerManager::getInstance()->getSession($player);
        $rankSession = RankSystem::getInstance()->getSessionManager()->get($player);
        if ($session === null || $rankSession === null) return;

        ScoreBoardAPI::setScoreLine($player, 1, C::colorize("       &r&7" . date("j/m/Y")));
        ScoreBoardAPI::setScoreLine($player, 2, " ");
        ScoreBoardAPI::setScoreLine($player, 3, C::colorize("&r&l&d{$player->getName()}&r"));
        ScoreBoardAPI::setScoreLine($player, 4, C::colorize("&r&d&l┃ &r&fRank&7: &r&d—"));
        ScoreBoardAPI::setScoreLine($player, 5, C::colorize("&r&d&l┃ &r&fBalance&7: &d$" . number_format($session->getBalance())));
        ScoreBoardAPI::setScoreLine($player, 6, C::colorize("&r&d&l┃ &r&fKDR&7: &f—"));
        ScoreBoardAPI::setScoreLine($player, 7, " ");
        ScoreBoardAPI::setScoreLine($player, 8, C::colorize("&r&d&l—"));
        ScoreBoardAPI::setScoreLine($player, 9,C::colorize( "&r&d&l┃ &r&fUse &d/is &fto get started"));

        self::updateIslandLines($player);
        self::updateRankLine($player, 4);
        self::updateKDRLine($player, 6);
    }


    /**
     * Replaces the trailing numeric part of a sidebar line with a new value.
     *
     * @param Player $player
     * @param int    $line      The scoreboard line number
     * @param int|float $newVal The new numeric value to show
     */
    public static function replaceNumberOnLine(Player $player, int $line, int|float $newVal): void {
        $fullLine = ScoreBoardAPI::getLineScore($player, $line);
        if (preg_match('/([\d,\.]+)\s*$/', $fullLine, $m)) {
            $oldVal = $m[1];
            $newValFormatted = number_format((float) $newVal);
            ScoreBoardAPI::editLineScore($player, $line, $oldVal, $newValFormatted);
        } else {
            ScoreBoardAPI::setScoreLine($player, $line, (string) $newVal);
        }
    }

    public static function updateKDRLine(Player $player, int $line = 6): void {
        if (!ScoreBoardAPI::hasScore($player)) return;
        
        $session = PlayerManager::getInstance()->getSession($player);
        if ($session === null) return;

        $kills  = $session->getKills();
        $deaths = $session->getDeaths();
        $kdr    = number_format($session->getKDRRatio(), 2);

        $oldLine = ScoreBoardAPI::getLineScore($player, $line);
        if ($oldLine === null) return;

        $prefixMatch = C::colorize("&r&d&l┃ &r&fKDR&7: ");
        if (!str_starts_with($oldLine, $prefixMatch)) return;

        $oldSuffix = substr($oldLine, strlen($prefixMatch));
        $newSuffix = C::colorize("&f{$kills}&7:&f{$deaths}&7 [&7{$kdr}&7]");

        ScoreBoardAPI::editLineScore($player, $line, $oldSuffix, $newSuffix);
    }

    public static function updateRankLine(Player $player, int $line = 4): void {
        if (!ScoreBoardAPI::hasScore($player)) {
            return;
        }
        $rankSession = RankSystem::getInstance()
            ->getSessionManager()
            ->get($player);
        if ($rankSession === null) {
            return;
        }

        $label = C::colorize("&r&d&l┃ &r&fRank&7: ");

        $oldLine = ScoreBoardAPI::getLineScore($player, $line);
        if ($oldLine === null || !str_starts_with($oldLine, $label)) {
            return;
        }

        $oldSuffix = substr($oldLine, strlen($label));

        $newPrefix = $rankSession->getHighestRank()->getNameTagFormat()['prefix'] ?? "";
        $newSuffix = C::colorize("&r&d{$newPrefix}");

        ScoreBoardAPI::editLineScore($player, $line, $oldSuffix, $newSuffix);
    }

    public static function updateIslandLines(Player $player): void {
        if (!ScoreBoardAPI::hasScore($player)) return;
        $session = PlayerManager::getInstance()->getSession($player);
        if ($session === null) return;

        $island = $session->getIsland();

        $old8 = ScoreBoardAPI::getLineScore($player, 8);
        if ($old8 !== null) {
            $new8 = $island === null
                ? C::colorize("&r&d&lNo Island")
                : C::colorize("&r&d&l" . $island->getName());
            ScoreBoardAPI::editLineScore($player, 8, trim($old8), $new8);
        }

        $old9 = ScoreBoardAPI::getLineScore($player, 9);
        if ($old9 === null) return;

        $label9 = C::colorize("&r&d&l┃ &r&fRole&7: ");
        if ($island === null) {
            $default9 = C::colorize("&r&d&l┃ &r&fUse &d/is &fto get started");
            ScoreBoardAPI::editLineScore($player, 9, $old9, $default9);
            return;
        }

        $role = ucfirst($island->getRole($player->getUniqueId()->toString()) ?: "Leader");
        $newSuffix = C::colorize("&d{$role}");
        if (str_starts_with($old9, $label9)) {
            $oldSuffix = substr($old9, strlen($label9));
            ScoreBoardAPI::editLineScore($player, 9, $oldSuffix, $newSuffix);
        } else {
            $newLine = $label9 . $newSuffix;
            ScoreBoardAPI::editLineScore($player, 9, trim($old9), $newLine);
        }
    }
}