<?php
declare(strict_types=1);

namespace ecstsy\AetherisRecode\tasks;

use ecstsy\AetherisRecode\Loader;
use ecstsy\AetherisRecode\server\crates\CrateManager;
use ecstsy\AetherisRecode\server\crates\CrateReward;
use ecstsy\AetherisRecode\utils\ui\crates\CrateRollScreen;
use ecstsy\MartianUtilities\utils\PlayerUtils;
use muqsit\invmenu\InvMenu;
use pocketmine\scheduler\Task;
use pocketmine\block\VanillaBlocks;
use pocketmine\block\utils\DyeColor;
use pocketmine\inventory\Inventory;
use pocketmine\item\Item;
use pocketmine\player\Player;

final class CrateRollTask extends Task {

    private const TOTAL_TICKS   = self::FAST_TICKS + self::SLOW_TICKS; // 100
    private const FAST_TICKS    = 60;    //  3 s @ 1‐tick
    private const SLOW_TICKS    = 40;    //  4 s @ 2‐ticks
    public  const INTERVAL_FAST =   1;   //  0.05 s per frame
    private const INTERVAL_SLOW =   2;   //  0.10 s per frame

    private InvMenu $menu;
    private string  $crateKey;
    private Player  $player;

    /** @var Item[] sliding window of 9 items */
    private array $items = [];
    /** @var DyeColor[] sliding window of 9 pane‐colors */
    private array $paneColors = [];

    private int $ticksElapsed = 0;
    private int $interval = self::INTERVAL_FAST;

    public function __construct(InvMenu $menu, string $crateKey, Player $player) {
        $this->menu     = $menu;
        $this->crateKey = $crateKey;
        $this->player   = $player;

        $rewards = CrateManager::get($crateKey)->getAllRewards();
        for ($i = 0; $i < 9; $i++) {
            /** @var CrateReward $r */
            $r = $rewards[array_rand($rewards)];
            $this->items[] = $r->roll();
        }

        $colors = DyeColor::cases();
        for ($i = 0; $i < 9; $i++) {
            $this->paneColors[] = $colors[array_rand($colors)];
        }
    }

    public function onRun(): void {
        $this->ticksElapsed++;
        $inv = $this->menu->getInventory();
        $viewers = $inv->getViewers();

        if ($this->ticksElapsed <= self::FAST_TICKS) {
            $shouldUpdate = true; 
        } else {
            $slowElapsed = $this->ticksElapsed - self::FAST_TICKS;          
            $p           = $slowElapsed / self::SLOW_TICKS;               
            $maxSkip     = 8;                                             
            $skipInterval = 1 + (int)floor($p * ($maxSkip - 1));          
    
            $shouldUpdate = ($slowElapsed % $skipInterval) === 0;
        }
    
        if ($shouldUpdate) {
            array_unshift($this->items, $this->nextRandomItem());
            array_pop($this->items);
            for ($i = 0; $i < 9; ++$i) {
                $inv->setItem(9 + $i, $this->items[$i]);
            }
    
            array_push($this->paneColors, $this->randomColor());
            array_shift($this->paneColors);
            for ($i = 0; $i < 9; ++$i) {
                if ($i === 4) continue;
                $pane = VanillaBlocks::STAINED_GLASS_PANE()
                    ->setColor($this->paneColors[$i])
                    ->asItem()
                    ->setCustomName(" ");
                $inv->setItem($i,       $pane);
                $inv->setItem($i + 18,  clone $pane);
            }
    
            $black = VanillaBlocks::STAINED_GLASS_PANE()
                ->setColor(DyeColor::BLACK())
                ->asItem()
                ->setCustomName(" ");
            $inv->setItem(4,  $black);
            $inv->setItem(22, $black);
    
            foreach ($viewers as $v) {
                if ($v instanceof Player) {
                    PlayerUtils::playSound($v, "random.click");
                }
            }
        }
    
        if ($this->ticksElapsed >= self::TOTAL_TICKS) {
            foreach ($viewers as $v) {
                if ($v instanceof Player) {
                    PlayerUtils::playSound($v, "note.chime");
                }
            }
            $this->finish($inv);
        }
    }

    private function nextRandomItem(): Item {
        $all = CrateManager::get($this->crateKey)->getAllRewards();
        /** @var CrateReward $r */
        $r = $all[array_rand($all)];
        return $r->roll();
    }

    private function randomColor(): DyeColor {
        $cases = DyeColor::cases();
        return $cases[array_rand($cases)];
    }

    private function finish(Inventory $inv): void {
        if ($this->getHandler() !== null) {
            $this->getHandler()->cancel();
        }

        $prize = $inv->getItem(13); 

        foreach ($inv->getViewers() as $viewer) {
            if (!$viewer instanceof Player) continue;
            $viewer->getInventory()->addItem($prize);
            $viewer->removeCurrentWindow();
            PlayerUtils::playSound($viewer, "random.levelup");
            $viewer->sendMessage("§aYou won: §f" . $prize->getName());
        }
    }
}
