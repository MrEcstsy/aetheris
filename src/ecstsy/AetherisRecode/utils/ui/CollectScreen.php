<?php

declare(strict_types=1);

namespace ecstsy\AetherisRecode\utils\ui;

use ecstsy\AetherisRecode\Loader;
use ecstsy\MartianUtilities\utils\ItemUtils;
use ecstsy\MartianUtilities\utils\screens\BaseScreen;
use muqsit\invmenu\InvMenu;
use muqsit\invmenu\transaction\DeterministicInvMenuTransaction;
use muqsit\invmenu\type\InvMenuTypeIds;
use pocketmine\block\utils\DyeColor;
use pocketmine\block\VanillaBlocks;
use pocketmine\item\VanillaItems;
use pocketmine\player\Player;
use pocketmine\utils\TextFormat as C;

final class CollectScreen extends BaseScreen
{
    private InvMenu $menu;

    private const BACK_BUTTON_SLOT = 48;
    private const NEXT_BUTTON_SLOT = 50;
    private const ITEMS_PER_PAGE = 45;

    public function __construct(Player $player, int $currentPage = 1)
    {
        $this->menu = InvMenu::create(InvMenuTypeIds::TYPE_DOUBLE_CHEST);
        $this->menu->setName(C::colorize("&r&8Collection"));

        $inventory = $this->menu->getInventory();
        $this->fillBottomRowWithGlass($inventory);

        $session = Loader::getPlayerManager()->getSession($player);
        $items = $session->getItemsFromCollection();
        $totalItems = count($items);

        $this->populateItems($inventory, $items, $currentPage, $totalItems);
        $this->addNavigationButtons($inventory, $currentPage, $totalItems);

        $this->menu->setListener(InvMenu::readonly(function (DeterministicInvMenuTransaction $transaction) use ($player, &$currentPage, $totalItems, $inventory, $session, $items) {
            $slot = $transaction->getAction()->getSlot();

            if ($slot === self::BACK_BUTTON_SLOT && $currentPage > 1) {
                $currentPage--;
                $this->updateMenu($inventory, $player, $currentPage, $totalItems);
            }

            if ($slot === self::NEXT_BUTTON_SLOT && ($currentPage - 1) * self::ITEMS_PER_PAGE + self::ITEMS_PER_PAGE < $totalItems) {
                $currentPage++;
                $this->updateMenu($inventory, $player, $currentPage, $totalItems);
            }

            if ($slot >= 0 && $slot < self::ITEMS_PER_PAGE) {
                $this->handleItemClick($player, $inventory, $session, $items, $currentPage, $slot);
            }
        }));
    }

    public static function display(Player $player): void 
    {
        $screen = new self($player);
        $screen->menu->send($player);
    }

    private function fillBottomRowWithGlass($inventory): void
    {
        for ($i = 45; $i <= 53; $i++) {
            $color = ($i % 2 === 0) ? DyeColor::BLACK() : DyeColor::GRAY();
            $glass = VanillaBlocks::STAINED_GLASS_PANE()->setColor($color)->asItem()->setCustomName(" ");
            $inventory->setItem($i, $glass);
        }
    }

    private function populateItems($inventory, array $items, int $currentPage, int $totalItems): void
    {
        $startIndex = ($currentPage - 1) * self::ITEMS_PER_PAGE;
        $endIndex = min($startIndex + self::ITEMS_PER_PAGE, $totalItems);

        for ($i = $startIndex; $i < $endIndex; $i++) {
            $item = ItemUtils::decodeItem($items[$i]);
            if (!$item->equals(VanillaBlocks::STAINED_GLASS_PANE()->asItem()) && !$item->equals(VanillaItems::ARROW())) {
                $item->setLore(array_merge($item->getLore(), [C::colorize("&r&l&6Click to claim")]));
            }
            $inventory->setItem($i - $startIndex, $item);
        }
    }

    private function addNavigationButtons($inventory, int $currentPage, int $totalItems): void
    {
        $inventory->setItem(
            self::BACK_BUTTON_SLOT,
            $currentPage > 1
                ? VanillaItems::ARROW()->setCustomName(C::colorize("&r&7Back"))
                : VanillaBlocks::STAINED_GLASS_PANE()->setColor(DyeColor::BLACK())->asItem()->setCustomName(" ")
        );

        $inventory->setItem(
            self::NEXT_BUTTON_SLOT,
            ($currentPage * self::ITEMS_PER_PAGE < $totalItems)
                ? VanillaItems::ARROW()->setCustomName(C::colorize("&r&7Next"))
                : VanillaBlocks::STAINED_GLASS_PANE()->setColor(DyeColor::BLACK())->asItem()->setCustomName(" ")
        );
    }

    private function handleItemClick(Player $player, $inventory, $session, array $items, int $currentPage, int $slot): void
    {
        $itemIndex = ($currentPage - 1) * self::ITEMS_PER_PAGE + $slot;
        if (isset($items[$itemIndex])) {
            $clickedItem = ItemUtils::decodeItem($items[$itemIndex]);
            if ($player->getInventory()->canAddItem($clickedItem)) {
                $player->getInventory()->addItem($clickedItem);
                $session->removeItemFromCollection($clickedItem);
                $inventory->removeItem($clickedItem);
                $player->removeCurrentWindow();
            } else {
                $player->sendToastNotification(
                    C::colorize(Loader::SERVER_TITLE),
                    C::colorize("&r&7Clear space in your inventory to collect this!")
                );
            }
        }
    }

    private function updateMenu($inventory, Player $player, int $currentPage, int $totalItems): void
    {
        for ($i = 0; $i < self::ITEMS_PER_PAGE; $i++) {
            $inventory->setItem($i, VanillaItems::AIR());
        }

        $session = Loader::getPlayerManager()->getSession($player);
        $items = $session->getItemsFromCollection();

        $this->populateItems($inventory, $items, $currentPage, $totalItems);
        $this->addNavigationButtons($inventory, $currentPage, $totalItems);
    }
}
