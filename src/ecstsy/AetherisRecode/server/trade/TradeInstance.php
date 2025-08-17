<?php

declare(strict_types=1);

namespace ecstsy\AetherisRecode\server\trade;

use pocketmine\player\Player;
use pocketmine\Server;
use pocketmine\utils\TextFormat as C;

final class TradeInstance {
    /** @var array<string, string> */
    private static array $pending = [];
    /** @var array<string, TradeSession> */
    private static array $sessions = [];

    public static function requestTrade(Player $from, Player $to): void {
        if (self::inTrade($from) || self::inTrade($to)) {
            $from->sendMessage(C::colorize("&cOne of you is already trading."));
            return;
        }
        if ($from->getPosition()->distance($to->getPosition()) > 8) {
            $from->sendMessage(C::colorize("&cYou must be within 8 blocks to trade."));
            return;
        }
        self::$pending[$to->getName()] = $from->getName();
        $to->sendMessage(C::colorize("&e{$from->getName()} wants to trade. Type &a/trade {$from->getName()} &eto accept."));
        $from->sendMessage(C::colorize("&aTrade request sent to {$to->getName()}."));
    }

    public static function acceptTrade(Player $to, Player $from): void {
        if (!isset(self::$pending[$to->getName()]) || self::$pending[$to->getName()] !== $from->getName()) {
            $to->sendMessage(C::colorize("&cNo trade request from {$from->getName()}."));
            return;
        }
        unset(self::$pending[$to->getName()]);
        $session = new TradeSession($from, $to);
        self::$sessions[$from->getName()] = $session;
        self::$sessions[$to->getName()] = $session;
        $session->open();
    }

    public static function inTrade(Player $player): bool {
        return isset(self::$sessions[$player->getName()]);
    }

    public static function endTrade(Player $player): void {
        if (isset(self::$sessions[$player->getName()])) {
            $session = self::$sessions[$player->getName()];
            $session->close();
            unset(self::$sessions[$session->getPlayerA()->getName()]);
            unset(self::$sessions[$session->getPlayerB()->getName()]);
        }
    }
}