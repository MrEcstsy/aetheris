<?php

declare(strict_types=1);

namespace ecstsy\AetherisRecode\server;

use pocketmine\player\Player;
use pocketmine\Server;
use pocketmine\scheduler\ClosureTask;
use ecstsy\AetherisRecode\Loader;
use ecstsy\AetherisRecode\utils\QueryStmts;
use ecstsy\AetherisRecode\utils\Utils;
use ecstsy\MartianUtilities\utils\GeneralUtils;
use Ramsey\Uuid\Uuid;
use pocketmine\utils\TextFormat as C;

final class JackpotInstance {

    private int $pot = 0;
    private int $ticketPrice;
    private float $taxPercent;
    private int $interval; // seconds
    private int $lastDrawTime = 0;
    private int $timeLeft;

    /** @var array<string, int> playerName => ticketCount */
    private array $tickets = [];

    public function __construct() {
        $config = GeneralUtils::getConfiguration(Loader::getInstance(), "config.yml")->getAll();
        $this->ticketPrice = $config["jackpot"]["ticket-price"] ?? 10000;
        $this->taxPercent = $config["tax"]["jackpot"] ?? 0.10;
        $this->interval = $config["jackpot"]["interval"] ?? 7200; 
        $this->timeLeft = $this->interval;

        $this->loadPot();
        $this->scheduleDrawTask();
    }

    public function purchaseTickets(Player $player, int $amount): bool {
        $cost = $amount * $this->ticketPrice;
        $session = Loader::getInstance()->getPlayerManager()->getSession($player);
        if ($session->getBalance() < $cost) {
            $player->sendMessage("§cYou do not have enough money to buy $amount ticket(s).");
            return false;
        }
        $session->removeBalance($cost);
        $this->pot += $cost;
        $this->tickets[$player->getUniqueId()->toString()] = ($this->tickets[$player->getUniqueId()->toString()] ?? 0) + $amount;
        $player->sendMessage("§aYou bought $amount jackpot ticket(s)!");
        $this->savePot();
        return true;
    }

    public function drawWinner(): void {
        if (empty($this->tickets)) {
            foreach (Server::getInstance()->getOnlinePlayers() as $player) {
                $player->sendMessage(C::colorize("§d§l⎯⎯⎯⎯⎯⎯⎯⎯⎯⎯⎯⎯⎯⎯⎯\n§dNo tickets were sold this round.\n§d§l⎯⎯⎯⎯⎯⎯⎯⎯⎯⎯⎯⎯⎯⎯⎯"));
            }
            $this->resetJackpot();
            return;
        }
        $winnerUuid = $this->selectWinner();
        $winnerSession = Loader::getInstance()->getPlayerManager()->getSessionByUuid(Uuid::fromString($winnerUuid));
        $winnerName = $winnerSession?->getPlayer()->getName() ?? "Unknown";
        $tax = (int)($this->pot * $this->taxPercent);
        $prize = $this->pot - $tax;

        if ($winnerSession !== null) {
            $winnerSession->addBalance($prize);
            $this->updateStats($winnerUuid, $winnerName, $prize);
        }

        foreach (Server::getInstance()->getOnlinePlayers() as $player) {
            $player->sendMessage(C::colorize(            
            "§d§l⎯⎯⎯⎯⎯⎯⎯⎯⎯⎯⎯⎯⎯⎯⎯\n" .
            "§b🎲 Jackpot Winner: §f$winnerName\n" .
            "§b💰 Prize: §d$" . number_format($prize) . " &r§7(Tax: §c$" . number_format($tax) . "§7)\n" .
            "§d§l⎯⎯⎯⎯⎯⎯⎯⎯⎯⎯⎯⎯⎯⎯⎯"
            ));
        }

        $this->resetJackpot();
    }

    private function selectWinner(): ?string {
        $entries = [];
        foreach ($this->tickets as $uuid => $count) {
            for ($i = 0; $i < $count; $i++) {
                $entries[] = $uuid;
            }
        }
        if (empty($entries)) return null;
        return $entries[array_rand($entries)];
    }

    public function startJackpotRoundIfTimeElapsed(): void {
        $now = time();
        if ($now - $this->lastDrawTime >= $this->interval) {
            $this->drawWinner();
            $this->lastDrawTime = $now;
            $this->savePot();
        }
    }

    private function resetJackpot(): void {
        $this->pot = 0;
        $this->tickets = [];
        $this->savePot();
    }

    public function getPot(): int {
        return $this->pot;
    }

    public function getTicketPrice(): int {
        return $this->ticketPrice;
    }

    public function getTickets(): array {
        return $this->tickets;
    }

    private function scheduleDrawTask(): void {
        Loader::getInstance()->getScheduler()->scheduleRepeatingTask(new ClosureTask(function () {
            $this->timeLeft--;

            switch ($this->timeLeft) {
                case 30:
                    foreach (Server::getInstance()->getOnlinePlayers() as $player) {
                        $player->sendMessage(C::colorize("§dJackpot rolls in §b30§d seconds!"));
                    }
                    break;
                case 10:
                    foreach (Server::getInstance()->getOnlinePlayers() as $player) {
                        $player->sendMessage(C::colorize("§dJackpot rolls in §b10§d seconds!"));
                    }
                    break;
                case 3:
                case 2:
                case 1:
                    foreach (Server::getInstance()->getOnlinePlayers() as $player) {
                        $player->sendMessage(C::colorize("§dJackpot rolls in §b{$this->timeLeft}§d second" . ($this->timeLeft === 1 ? "" : "s") . "!"));
                    }
                    break;
            }

            if ($this->timeLeft <= 0) {
                $this->drawWinner();
                $this->timeLeft = $this->interval;
            }
        }), 20); 
    }

    public function getTaxPercent(): float {
        return $this->taxPercent;
    }

    public function getTimeLeft(): int {
        return $this->timeLeft;
    }


    private function savePot(): void {
        Loader::getInstance()->getDatabase()->executeChange(QueryStmts::JACKPOT_UPDATE, [
            "pot" => $this->pot,
            "last_draw" => $this->lastDrawTime
        ]);
    }

    private function loadPot(): void {
        Loader::getInstance()->getDatabase()->executeSelect(QueryStmts::JACKPOT_SELECT, [], function(array $rows): void {
            if (!empty($rows)) {
                $row = $rows[0];
                $this->pot = (int)$row["pot"];
                $this->lastDrawTime = (int)$row["last_draw"];
            } else {
                Loader::getInstance()->getDatabase()->executeInsert(QueryStmts::JACKPOT_INSERT, [
                    "pot" => 0,
                    "last_draw" => time()
                ]);
            }
        });
    }

    private function updateStats(string $uuid, string $username, int $prize): void {
        $db = Loader::getInstance()->getDatabase();
        $db->executeSelect(QueryStmts::JACKPOT_STATS_SELECT, ["uuid" => $uuid], function(array $rows) use ($db, $uuid, $username, $prize) {
            if (!empty($rows)) {
                $row = $rows[0];
                $db->executeChange(QueryStmts::JACKPOT_STATS_UPDATE, [
                    "uuid" => $uuid,
                    "wins" => $row["wins"] + 1,
                    "winnings" => $row["winnings"] + $prize
                ]);
            } else {
                $db->executeInsert(QueryStmts::JACKPOT_STATS_INSERT, [
                    "uuid" => $uuid,
                    "username" => $username,
                    "wins" => 1,
                    "winnings" => $prize
                ]);
            }
        });
    }


}