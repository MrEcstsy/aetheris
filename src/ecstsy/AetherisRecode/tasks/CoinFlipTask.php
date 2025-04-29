<?php

declare(strict_types=1);

namespace ecstsy\AetherisRecode\tasks;

use pocketmine\player\Player;
use pocketmine\scheduler\Task;
use xenialdan\apibossbar\BossBar;
use pocketmine\utils\TextFormat as C;
use ecstsy\AetherisRecode\Loader;
use ecstsy\AetherisRecode\server\CoinFlipInstance;
use pocketmine\Server;
use pocketmine\world\sound\ClickSound;
use pocketmine\world\sound\XpLevelUpSound;
use xenialdan\apibossbar\BarColor;

final class CoinFlipTask extends Task
{
    private const ANIMATION_FRAMES = [
        "&e>&a>>>> &7Winner: {winner} &a<<<<&e<",
        "&a>&e>&a>>> &7Winner: {winner} &a<<<&e<&a<",
        "&a>>&e>&a>> &7Winner: {winner} &a<<&e<&a<<",
        "&a>>>&e>&a> &7Winner: {winner} &a<&e<&a<<<",
        "&a>>>>&e> &7Winner: {winner} &e<&a<<<<"
    ];

    private Player $p1;
    private Player $p2;
    private string $color1;
    private string $color2;
    private float $progress = 0.0;
    private int $amount;
    private BossBar $bar;
    private int $ticksElapsed = 0;
    private int $animationStep = 0;
    private bool $winnerAnnounced = false;
    private bool $animationStarted = false;
    private ?Player $winner = null;

    public function __construct(Player $p1, Player $p2, string $color1, string $color2, int $amount)
    {
        $this->p1 = $p1;
        $this->p2 = $p2;
        $this->color1 = self::getColorCode($color1);
        $this->color2 = self::getColorCode($color2);
        $this->amount = $amount;

        $this->bar = new BossBar();
        $this->bar->setColor(BarColor::PURPLE);
        $this->bar->setPercentage(0.0);
        $this->bar->addPlayers([$p1, $p2]);
    }

    public function onRun(): void
    {
        $this->ticksElapsed++;

        if ($this->progress < 1.0) {
            $this->updateProgress();
        } elseif (!$this->animationStarted) {
            $this->startAnimation();
        } elseif (!$this->winnerAnnounced) {
            $this->updateAnimation();
        }
    }

    private function updateProgress(): void
    {
        $this->progress = min($this->progress + 0.01, 1.0);
        $this->bar->setPercentage($this->progress);

        if ($this->ticksElapsed % 3 === 0) {
            $this->p1->getWorld()->addSound($this->p1->getPosition(), new ClickSound());
            $this->p2->getWorld()->addSound($this->p2->getPosition(), new ClickSound());
        }

        $colors = [$this->color1, $this->color2];
        $currentColor = $colors[intval($this->ticksElapsed / 3) % count($colors)];
        $square = "⬛";
        $this->bar->setTitle(C::colorize(
            "&6&l   " . $this->p1->getName() . " &7[{$currentColor}{$square}&7] &6" . $this->p2->getName()
        ));
    }

    private function startAnimation(): void
    {
        $this->animationStarted = true;
        $this->winner = rand(0, 1) ? $this->p1 : $this->p2;
        $this->bar->setTitle(C::colorize("&6&l   " . $this->p1->getName() . " &7[{$this->color1}⬛&7] &6" . $this->p2->getName()));
    }

    private function updateAnimation(): void
    {
        if ($this->ticksElapsed % 10 === 0) {
            $this->animationStep = min($this->animationStep + 1, count(self::ANIMATION_FRAMES) - 1);
            $frame = str_replace("{winner}", $this->winner->getName(), self::ANIMATION_FRAMES[$this->animationStep]);
            $this->bar->setTitle(C::colorize($frame));
        }

        if ($this->ticksElapsed >= 150) {
            $this->p1->getWorld()->addSound($this->p1->getPosition(), new XpLevelUpSound(1000));
            $this->p2->getWorld()->addSound($this->p2->getPosition(), new XpLevelUpSound(1000));
            $this->announceWinner();
        }
    }

    private function announceWinner(): void
    {
        $this->winnerAnnounced = true;
        $loser = ($this->winner === $this->p1) ? $this->p2 : $this->p1;

        CoinFlipInstance::removeCoinFlip($this->p1->getUniqueId()->toString());
        CoinFlipInstance::removeCoinFlip($this->p2->getUniqueId()->toString());

        $winnerSession = Loader::getPlayerManager()->getSession($this->winner);
        $winnerSession->addBalance($this->amount * 2);

        $winnerColor = ($this->winner === $this->p1) ? $this->color1 : $this->color2;
        $colorName = ucfirst(strtolower(array_search($winnerColor, CoinFlipInstance::COLORS) ?? "Unknown"));

        $this->winner->sendMessage(C::colorize("&r&l&a(!) &r&a" . $this->winner->getName() . " has won the Coin Flip with " . $winnerColor . $colorName));
        $loser->sendMessage(C::colorize("&r&l&a(!) &r&a" . $this->winner->getName() . " has won the Coin Flip with " . $winnerColor . $colorName));

        foreach (Server::getInstance()->getOnlinePlayers() as $player) {
            $player->sendMessage(C::colorize("&r&6&lCF &r&8| &7" . $this->winner->getName() . " &ahas won against &c" . $loser->getName() . " &7for &a$" . number_format($this->amount * 2)));
        }

        $this->winner->getWorld()->addSound($this->winner->getPosition(), new XpLevelUpSound(1000));
        $loser->getWorld()->addSound($loser->getPosition(), new XpLevelUpSound(1000));

        $this->bar->setPercentage(1.0);

        Loader::getInstance()->getScheduler()->scheduleDelayedTask(new class($this->bar, $this->p1, $this->p2) extends Task {
            private BossBar $bar;
            private Player $p1;
            private Player $p2;

            public function __construct(BossBar $bar, Player $p1, Player $p2)
            {
                $this->bar = $bar;
                $this->p1 = $p1;
                $this->p2 = $p2;
            }

            public function onRun(): void
            {
                $this->bar->hideFrom([$this->p1, $this->p2]);
            }
        }, 40);

        $this->getHandler()->cancel();
    }

    private static function getColorCode(string $color): string
    {
        return CoinFlipInstance::COLORS[$color] ?? "§f";
    }
}
