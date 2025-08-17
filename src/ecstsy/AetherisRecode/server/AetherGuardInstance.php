<?php

declare(strict_types=1);

namespace ecstsy\AetherisRecode\server;

use ecstsy\AetherisRecode\Loader;
use ecstsy\AetherisRecode\utils\QueryStmts;
use pocketmine\player\Player;
use pocketmine\utils\SingletonTrait;

final class AetherGuardInstance {
    use SingletonTrait;

    public function __construct()
    {
        self::setInstance($this);
    }

    public static function logAnticheatBan(Player $player, string $reason, int $violations): void {
        Loader::getDatabase()->executeInsert(
            QueryStmts::ANTICHEAT_LOGS_INSERT,
            [
                "uuid" => $player->getUniqueId()->toString(),
                "username" => $player->getName(),
                "reason" => $reason,
                "violations" => $violations,
                "timestamp" => time()
            ]
        );
    }

    public function getAllAnticheatLogs(callable $callback): void {
        Loader::getDatabase()->executeSelect(
            QueryStmts::ANTICHEAT_LOGS_SELECT_ALL,
            [],
            $callback
        );
    }

    public function getAnticheatLogsByUuid(string $uuid, callable $callback): void {
        Loader::getDatabase()->executeSelect(
            QueryStmts::ANTICHEAT_LOGS_SELECT_BY_UUID,
            ["uuid" => $uuid],
            $callback
        );
    }
}