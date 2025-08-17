<?php
declare(strict_types=1);

namespace ecstsy\AetherisRecode\utils\ui\crates;

use ecstsy\AetherisRecode\Loader;
use muqsit\invmenu\InvMenu;
use muqsit\invmenu\transaction\DeterministicInvMenuTransaction;
use muqsit\invmenu\type\InvMenuTypeIds;
use pocketmine\block\VanillaBlocks;
use pocketmine\block\utils\DyeColor;
use pocketmine\utils\TextFormat as C;
use pocketmine\player\Player;
use ecstsy\AetherisRecode\tasks\CrateRollTask;
use ecstsy\MartianUtilities\utils\screens\BaseScreen;
use pocketmine\plugin\Plugin;

final class CrateRollScreen extends BaseScreen {

    private const BORDER_TOP    = [0,1,2,3,4,5,6,7,8];
    private const BORDER_BOTTOM = [18,19,20,21,22,23,24,25,26];
    private const BLACK_SLOTS   = [4, 22];
    private InvMenu $menu;
    private string  $crateKey;
    private Plugin  $plugin;
    private Player  $player;

    public function __construct(Plugin $plugin, string $crateKey, Player $player) {
        $this->plugin   = $plugin;
        $this->player   = $player;
        $this->crateKey = $crateKey;

        $this->menu = InvMenu::create(InvMenuTypeIds::TYPE_CHEST);
        $inv = $this->menu->getInventory();
        $this->menu->setName(C::colorize("&r&6Opening &e" . ucfirst($crateKey) . " Crate"));
        
        $black = VanillaBlocks::STAINED_GLASS_PANE()
            ->setColor(DyeColor::BLACK())
            ->asItem()
            ->setCustomName(" ");

        foreach (self::BLACK_SLOTS as $slot) {
            $inv->setItem($slot, $black);
        }

        $this->menu->setListener(InvMenu::readonly());

        $task = new CrateRollTask($this->menu, $crateKey, $this->player);
        Loader::getInstance()->getScheduler()->scheduleRepeatingTask($task, CrateRollTask::INTERVAL_FAST);
    }

    public static function display(Player $player, string $crateKey): void 
    {
        $screen = new self(Loader::getInstance(), $crateKey, $player);
        $screen->menu->send($player);
    }

    public function getMenu(): ?InvMenu
    {
        return $this->menu;
    }
}
