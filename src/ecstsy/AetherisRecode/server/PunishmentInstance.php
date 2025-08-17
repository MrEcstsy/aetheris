<?php
namespace ecstsy\AetherisRecode\server;

use ecstsy\AetherisRecode\Loader;
use ecstsy\AetherisRecode\utils\QueryStmts;

final class PunishmentInstance {

    public function addPunishment(string $uuid, string $staffUuid, string $type, string $reason, int $duration = null, int $strikesAfter = 0): void {
        Loader::getDatabase()->executeInsert(QueryStmts::PUNISHMENTS_INSERT, [
            "uuid" => $uuid,
            "staff_uuid" => $staffUuid,
            "type" => $type,
            "reason" => $reason,
            "timestamp" => time(),
            "duration" => $duration,
            "strikes_after" => $strikesAfter
        ]);
    }

    public function setActivePunishment(string $uuid, string $type, int $expiresAt): void {
        Loader::getDatabase()->executeInsert(QueryStmts::ACTIVE_PUNISHMENTS_INSERT, [
            "uuid" => $uuid,
            "type" => $type,
            "expires_at" => $expiresAt
        ]);
    }

    public function clearActivePunishment(string $uuid): void {
        Loader::getDatabase()->executeChange(QueryStmts::ACTIVE_PUNISHMENTS_DELETE, [
            "uuid" => $uuid
        ]);
    }
}