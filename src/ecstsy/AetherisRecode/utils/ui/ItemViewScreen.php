<?php

declare(strict_types=1);

namespace ecstsy\AetherisRecode\utils\ui;

use ecstsy\AetherisRecode\utils\Utils;
use ecstsy\MartianUtilities\utils\InventoryUtils;
use ecstsy\MartianUtilities\utils\screens\BaseScreen;
use muqsit\invmenu\InvMenu;
use pocketmine\block\utils\DyeColor;
use pocketmine\block\VanillaBlocks;
use pocketmine\item\Item;
use pocketmine\utils\TextFormat as C;
use pocketmine\player\Player;
use Vecnavium\FormsUI\SimpleForm;

final class ItemViewScreen extends BaseScreen {

    private InvMenu $menu;
    private SimpleForm $form;

    public function __construct(Player $player, Item $item)
    {
        $this->menu = InvMenu::create(Utils::TYPE_DISPENSER);
        $this->menu->setName(C::colorize("&r&8" . $player->getName() . "'s Item"));

        $inventory = $this->menu->getInventory();
        $pane = VanillaBlocks::STAINED_GLASS_PANE()->setColor(DyeColor::BLACK())->asItem();
        InventoryUtils::fillInventory($inventory, $pane, [4]);

        $inventory->setItem(4, $item);

        $this->menu->setListener(InvMenu::readonly());

        $this->form = new SimpleForm(function(Player $player, $data): void {
            
        });

        $this->form->setTitle("§r§8" . $player->getName() . "'s Item");

        $lines = [];

        $name =  $item->getName();
        $count = $item->getCount() > 1 ? " x{$item->getCount()}" : "";
        $lines[] = C::BOLD . C::AQUA . $name . C::RESET . $count;

        foreach ($item->getEnchantments() as $enchant) {
            $lines[] = C::GRAY . "- " . $enchant->getType()->getName() . " " . $enchant->getLevel();
        }

        $lore = $item->getLore();
        if (!empty($lore)) {
            $lines[] = C::DARK_GRAY . "Lore:";
            foreach ($lore as $l) {
                $lines[] = C::WHITE . $l;
            }
        }

        $this->form->setContent(C::colorize(implode("\n", $lines)));
        $this->form->addButton(C::colorize("&r&l&0Close"));
    }

    public static function display(Player $player, Item $item): void {
        $screen = new self($player, $item);
        $screen->getMenu()->send($player);
    }

    public static function displayForm(Player $player, Item $item): void {
        $screen = new self($player, $item);
        $player->sendForm($screen->getForm());
    }

    public function getMenu(): InvMenu {
        return $this->menu;
    }

    public function getForm(): SimpleForm {
        return $this->form;
    }
}