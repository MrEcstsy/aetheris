<?php

declare(strict_types=1);

namespace ecstsy\AetherisRecode\tasks;

use ecstsy\AetherisRecode\Loader;
use pocketmine\player\Player;
use pocketmine\scheduler\Task;
use pocketmine\Server;
use pocketmine\utils\TextFormat as C;
use pocketmine\world\sound\AnvilFallSound;
use pocketmine\world\sound\ExplodeSound;

final class PouchRevealTask extends Task {

    private Player $player;
    private int $amount;
    private string $type; // "money" or "xp"
    private int $tier; // 1, 2, 3
    private int $revealed = 0;

    public function __construct(Player $player, int $amount, string $type, int $tier) {
        $this->player = $player;
        $this->amount = $amount;
        $this->type = $type;
        $this->tier = $tier;
    }

    public function onRun(): void {
        if (!$this->player->isOnline()) {
            $this->getHandler()?->cancel();
            return;
        }

        $amountStr = (string)$this->amount;
        $len = mb_strlen($amountStr);

        if ($this->revealed >= $len) {
            $this->getHandler()?->cancel();

            if ($this->type === "money") {
                Loader::getInstance()->getPlayerManager()->getSession($this->player)->addBalance($this->amount);
            } else {
                $this->player->getXpManager()->addXp($this->amount);
            }

            $this->player->getWorld()->addSound($this->player->getPosition(), new ExplodeSound());

            $color = C::LIGHT_PURPLE; 
            $typeName = $this->type === "money" ? "Money" : "XP";
            $tierName = match($this->tier) {
                1 => "Tier I",
                2 => "Tier II",
                3 => "Tier III",
                default => "Unknown Tier"
            };
            $rewardStr = $this->type === "money"
                ? "$" . number_format($this->amount)
                : number_format($this->amount) . " XP";
            $msg = "{$color}(!) {$color}{$this->player->getName()} &7just won &l{$rewardStr} &r&7from a {$color}&l{$tierName} {$typeName} Pouch&r&7!";
            foreach (Server::getInstance()->getOnlinePlayers() as $p) {
                $p->sendMessage(C::colorize($msg));
            }
            return;
        }

        $revealColor = C::LIGHT_PURPLE;
        $obfuscateColor = C::DARK_PURPLE;

        $obfuscatedLen = $len - $this->revealed - 1;
        $obfuscatedPart = $this->obfuscate(mb_substr($amountStr, 0, $obfuscatedLen), $obfuscateColor);
        $revealedDigit = mb_substr($amountStr, $obfuscatedLen, 1);
        $trailingDigits = mb_substr($amountStr, $obfuscatedLen + 1); 

        $title = $this->type === "money"
            ? "&r{$revealColor}&r&f$&r" . "{$obfuscatedPart}&r{$revealColor}{$revealedDigit}{$trailingDigits}&r"
            : "&r{$obfuscatedPart}&r{$revealColor}{$revealedDigit}{$trailingDigits}{$revealColor} &r&fXP";
        $subtitle = C::RESET . C::GRAY . "Opening pouch...";

        $this->player->sendTitle(C::colorize($title), C::colorize($subtitle), 0, 40, 0);

        $this->player->getWorld()->addSound($this->player->getPosition(), new AnvilFallSound());

        $this->revealed++;
    }

    private function obfuscate(string $str, string $color): string {
        $out = "";
        foreach (mb_str_split($str) as $char) {
            $out .= $color . "§k" . $char;
        }
        return $out;
    }
}