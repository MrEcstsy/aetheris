<?php

declare(strict_types=1);

namespace ecstsy\AetherisRecode\utils\ui;

use muqsit\invmenu\InvMenu;
use pocketmine\block\utils\DyeColor;
use pocketmine\block\utils\MobHeadType;
use pocketmine\block\VanillaBlocks;
use pocketmine\inventory\Inventory;
use pocketmine\item\Item;
use pocketmine\item\VanillaItems;
use pocketmine\player\Player;
use pocketmine\utils\TextFormat as C;

final class BragScreen
{
    private InvMenu $menu;

    public function __construct(Player $target)
    {
        $this->menu = InvMenu::create(InvMenu::TYPE_DOUBLE_CHEST);
        $this->menu->setName(C::colorize("&r&8{$target->getName()}'s Inventory"));
        $inv = $this->menu->getInventory();

        $pane = VanillaBlocks::STAINED_GLASS_PANE()->setColor(DyeColor::GRAY())->asItem();
        $this->fillRange($inv, 0, 8, $pane);

        $inv->setItem(0, $this->createPlayerSkull($target));
        $inv->setItem(1, $this->createXpBottle($target));
        $this->setArmorItems($inv, $target);
        $inv->setItem(8, $target->getOffhandInventory()->getItem(0));

        $this->fillRange($inv, 9, 17, $pane);
        $this->setInventoryItems($inv, $target);
        $this->setHotbarItems($inv, $target);

        $this->menu->setListener(InvMenu::readonly());
    }

    private function createPlayerSkull(Player $target): Item
    {
        return VanillaBlocks::MOB_HEAD()
            ->setMobHeadType(MobHeadType::PLAYER())
            ->asItem()
            ->setCustomName(C::colorize("&r&d{$target->getName()}"));
    }

    private function createXpBottle(Player $target): Item
    {
        return VanillaItems::EXPERIENCE_BOTTLE()
            ->setCustomName(C::colorize("&r&e{$target->getXpManager()->getXpLevel()} Enchantment Levels"));
    }

    private function setArmorItems(Inventory $inv, Player $target): void
    {
        $armorInv = $target->getArmorInventory();
        $inv->setItem(3, $armorInv->getHelmet());
        $inv->setItem(4, $armorInv->getChestplate());
        $inv->setItem(5, $armorInv->getLeggings());
        $inv->setItem(6, $armorInv->getBoots());
    }

    private function setInventoryItems(Inventory $inv, Player $target): void
    {
        foreach ($target->getInventory()->getContents(false) as $slot => $item) {
            if ($slot > 8) {
                $inv->setItem(18 + ($slot - 9), $item);
            }
        }
    }

    private function setHotbarItems(Inventory $inv, Player $target): void
    {
        for ($hot = 0; $hot < 9; ++$hot) {
            $inv->setItem(45 + $hot, $target->getInventory()->getHotbarSlotItem($hot));
        }
    }

    private function fillRange(Inventory $inv, int $start, int $end, Item $item): void
    {
        for ($i = $start; $i <= $end; ++$i) {
            $inv->setItem($i, $item);
        }
    }

    public static function display(Player $player): void
    {
        (new self($player))->getMenu()->send($player);
    }

    public function getMenu(): InvMenu
    {
        return $this->menu;
    }
}
