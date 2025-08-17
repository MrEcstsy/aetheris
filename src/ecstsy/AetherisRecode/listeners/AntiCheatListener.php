<?php

declare(strict_types=1);

namespace ecstsy\AetherisRecode\listeners;

use ecstsy\AetherisRecode\Loader;
use ecstsy\AetherisRecode\server\AetherGuardInstance;
use ecstsy\AetherisRecode\utils\Utils;
use pocketmine\block\BlockTypeIds;
use pocketmine\entity\effect\VanillaEffects;
use pocketmine\event\Listener;
use pocketmine\event\player\PlayerMoveEvent;
use pocketmine\utils\TextFormat as C;

final class AntiCheatListener implements Listener {

    private array $lastPositions = [];
    private array $moveViolations = [];

    private array $noClipViolations = [];
    private array $airTicks = [];
    private array $recentJumpTicks = [];

    public function onSpeedHackDetect(PlayerMoveEvent $event): void {
        $player = $event->getPlayer();
        $name = strtolower($player->getName());

        $currentPos = $player->getPosition();
        $lastPos = $this->lastPositions[$name] ?? $currentPos;

        $dx = $currentPos->getX() - $lastPos->getX();
        $dz = $currentPos->getZ() - $lastPos->getZ();
        $dy = $currentPos->getY() - $lastPos->getY();

        $horizontalDistance = sqrt($dx * $dx + $dz * $dz);
        $verticalDistance = abs($dy);

        $maxDistance = 1.5;
        $speedEffect = $player->getEffects()->get(VanillaEffects::SPEED());
        if ($speedEffect !== null) {
            $maxDistance += 0.15 * $speedEffect->getEffectLevel();
        }
        $maxDistance = min($maxDistance, 1.8);

        if (!$player->isOnGround()) {
            $maxDistance = 1.05 + ($speedEffect !== null ? 0.10 * $speedEffect->getEffectLevel() : 0);
            $maxDistance = min($maxDistance, 1.4); // Hard cap for air movement
        }

        $maxVertical = 1.25; // Vanilla jump/fall per tick, adjust if needed

        $kickThreshold = 40;

        if (
            ($horizontalDistance <= $maxDistance && $verticalDistance <= $maxVertical) ||
            $player->hasPermission("aetheris.anticheat.bypass")
        ) {
            $this->moveViolations[$name] = max(0, ($this->moveViolations[$name] ?? 0) - 1);
            $this->lastPositions[$name] = $currentPos;
            return;
        }

        $excess = $horizontalDistance - $maxDistance;
        $violationAdd = 1;
        if ($excess > 0.5) $violationAdd = 3;
        elseif ($excess > 0.25) $violationAdd = 2;
        $violations = &$this->moveViolations[$name];
        $violations = ($violations ?? 0) + $violationAdd;

        if ($violations % 5 === 0) {
            $adminMsg = Loader::ANTICHEAT_PREFIX .
                "&c{$player->getName()} &7moved &c" . round($horizontalDistance, 2) . " &7blocks in one tick " .
                "&8[&eMax: &f$maxDistance&8, &bSpeed: &f" . ($speedEffect ? $speedEffect->getEffectLevel() : 0) .
                "&8, &aOnGround: &f" . ($player->isOnGround() ? "Yes" : "No") . "&8]";
            foreach ($player->getServer()->getOnlinePlayers() as $p) {
                if ($p->hasPermission("aetheris.admin")) {
                    $p->sendMessage(C::colorize($adminMsg));
                }
            }
        }

        if ($violations >= $kickThreshold) {
            AetherGuardInstance::logAnticheatBan($player, "Repeated unnatural movement detected by ETHEREALGUARD", $violations);
            //$player->kick(C::colorize("&cYou were kicked for repeated unnatural movement (ETHEREALGUARD)"));
            $this->moveViolations[$name] = 0;
            return;
        }

        $event->cancel();
        if (isset($this->lastPositions[$name])) {
            $player->teleport($this->lastPositions[$name]);
        }
        $player->sendMessage(C::colorize(Loader::ANTICHEAT_PREFIX . "&fSuspicious movement detected. &7(Speed)"));
        $this->lastPositions[$name] = $currentPos;
    }

    public function onNoClipDetect(PlayerMoveEvent $event): void {
        $player = $event->getPlayer();
        $name = strtolower($player->getName());

        if ($player->hasPermission("aetheris.anticheat.bypass") || $player->getAllowFlight()) {
            return;
        }

        $pos = $player->getPosition();
        $world = $player->getWorld();

        $halfWidth = 0.3;
        $minY = floor($pos->getY());
        $maxY = floor($pos->getY() + $player->getEyeHeight());

        $corners = [
            [$pos->getX() - $halfWidth, $pos->getZ() - $halfWidth],
            [$pos->getX() - $halfWidth, $pos->getZ() + $halfWidth],
            [$pos->getX() + $halfWidth, $pos->getZ() - $halfWidth],
            [$pos->getX() + $halfWidth, $pos->getZ() + $halfWidth],
        ];

        $isInsideSolid = false;
        for ($y = $minY; $y <= $maxY; $y++) {
            foreach ($corners as [$cx, $cz]) {
                $block = $world->getBlockAt((int)floor($cx), (int)$y, (int)floor($cz));
                if ($block->isFullCube()) {
                    $isInsideSolid = true;
                    break 2;
                }
            }
        }

        if ($isInsideSolid) {
            $event->cancel();
            if (isset($this->lastPositions[$name])) {
                $player->teleport($this->lastPositions[$name]);
            }
            $player->sendMessage(C::colorize(Loader::ANTICHEAT_PREFIX . "&fSuspicious movement detected. &7(No-Clip)"));
            return;
        }

        $this->lastPositions[$name] = $pos;
    }
}