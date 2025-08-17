<?php

declare(strict_types=1);

namespace ecstsy\AetherisRecode\utils\ui\crates;

use ecstsy\AetherisRecode\server\crates\CrateManager;
use ecstsy\AetherisRecode\server\crates\CrateReward;
use ecstsy\MartianUtilities\utils\InventoryUtils;
use ecstsy\MartianUtilities\utils\PlayerUtils;
use ecstsy\MartianUtilities\utils\screens\BaseScreen;
use muqsit\invmenu\InvMenu;
use muqsit\invmenu\transaction\DeterministicInvMenuTransaction;
use muqsit\invmenu\type\InvMenuTypeIds;
use pocketmine\block\utils\DyeColor;
use pocketmine\block\VanillaBlocks;
use pocketmine\item\Item;
use pocketmine\item\VanillaItems;
use pocketmine\player\Player;
use pocketmine\utils\TextFormat as C;

final class CratePreviewScreen extends BaseScreen {

    private const BORDER_SLOTS = [
        0, 1, 2, 3, 4, 5, 6, 7, 8,
        45, 46, 47, 48, 49, 50, 51, 52, 53,
        9, 18, 27, 36,
        17, 26, 35, 44
    ];

    private Item $prevPage;
    private Item $nextPage;
    private Item $borderPane;
    private InvMenu $menu;
    private int $page = 1;
    private int $totalPages;
    /** @var Item[] */
    private array $crateItems;

    public function __construct(string $crateName) 
    {

        $crate = CrateManager::get($crateName);
        assert($crate !== null, "Unknown crate '$crate'");
        /** @var CrateReward[] $rewards */
        $raw = $crate->getRewardsWithChances();
        $this->crateItems = array_map(function(array $e): array {
            /** @var CrateReward $r */
            $r = $e["reward"];
            $itm = $r->roll();              
            $lore = $itm->getLore();
            $lore[] = C::colorize("&r&d► &fChance &d{$e['chance']}%");
            $itm->setLore($lore);
            return ["item" => $itm, "chance" => $e["chance"]];
        }, $raw);


        $count    = count($this->crateItems);
        $perPage  = 28;
        $this->totalPages = (int)ceil($count / $perPage);

        $this->borderPane = VanillaBlocks::STAINED_GLASS_PANE()->setColor(DyeColor::BLACK())->asItem()->setCustomName(" ");
        $this->prevPage = VanillaItems::DYE()->setColor(DyeColor::RED())->setCustomName(C::colorize("&r&cPrevious Page"));
        $this->nextPage = VanillaItems::DYE()->setColor(DyeColor::LIME())->setCustomName(C::colorize("&r&cNext Page"));

        $this->menu = InvMenu::create(InvMenuTypeIds::TYPE_DOUBLE_CHEST);
        $this->menu->setName($this->makeTitle($crateName));
        InventoryUtils::fillBorders($this->menu->getInventory(), $this->borderPane);
        $this->drawPage();

        $this->menu->setListener(InvMenu::readonly(function(DeterministicInvMenuTransaction $transaction) use ($perPage, $count): void {
            $player = $transaction->getPlayer();
            $clicked = $transaction->getItemClicked();
            if($clicked->equalsExact($this->prevPage) && $this->page > 1) {
                $this->page--;
                PlayerUtils::playSound($player, "item.book.page_turn");
                $this->drawPage();
            } elseif($clicked->equalsExact($this->nextPage) && $this->page < $this->totalPages) {
                $this->page++;
                PlayerUtils::playSound($player, "item.book.page_turn");
                $this->drawPage();
            }
        }));
    }

    private function makeTitle(string $key): string {
        return match(strtolower($key)) {
            "vote"   => C::colorize("&r&a┌╴ Vote &2Crate ╶┐ &r&8Preview"),
            "void"  => C::colorize("&r&b┌╴ Void &3Crate ╶┐ &r&8Preview"),
            "stardust" => C::colorize("&r&d┌╴ Stardust &5Crate ╶┐ &r&8Preview"),
            "meteorite" => C::colorize("&r&e┌╴ Meteorite &6Crate ╶┐ &r&8Preview"),
            default  => C::colorize("&r&8┌╴ Crate ╶┐ &r&8Preview"),
        };
    }

    private function drawPage(): void {
        $inv = $this->menu->getInventory();
        $inv->clearAll();
        InventoryUtils::fillBorders($inv, $this->borderPane);

        
        $start = ($this->page - 1) * 28;
        $slice = array_slice($this->crateItems, $start, 28);

        $slot = 9; 
        foreach($slice as $entry) {
            while(in_array($slot, self::BORDER_SLOTS, true)) {
                $slot++;
            }
            $inv->setItem($slot, $entry["item"]);
            $slot++;
        }

        if($this->page > 1) {
            $inv->setItem(47, $this->prevPage);
        }
        if($this->page < $this->totalPages) {
            $inv->setItem(51, $this->nextPage);
        }
    }

    /**
     * @param Player $player
     * @param string $crateKey  one of your crate identifiers
     */
    public static function display(Player $player, string $crateKey): void {
        $screen = new self($crateKey);
        $screen->getMenu()->send($player);
    }

    public function getMenu(): InvMenu {
        return $this->menu;
    }

}