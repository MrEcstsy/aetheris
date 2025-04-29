<?php

declare(strict_types=1);

namespace ecstsy\AetherisRecode\utils\ui\skyblock;

use ecstsy\AetherisRecode\utils\Utils;
use ecstsy\MartianUtilities\utils\PlayerUtils;
use ecstsy\MartianUtilities\utils\screens\BaseScreen;
use muqsit\invmenu\InvMenu;
use muqsit\invmenu\transaction\DeterministicInvMenuTransaction;
use pocketmine\block\utils\DyeColor;
use pocketmine\block\VanillaBlocks;
use pocketmine\player\Player;
use pocketmine\utils\TextFormat as C;

final class IslandCreationScreen extends BaseScreen
{
    /** @var InvMenu|null */
    private ?InvMenu    $menu = null;

    public function __construct()
    {
        $this->menu = InvMenu::create(InvMenu::TYPE_CHEST);
        $this->menu->setName(C::colorize("&r&8Island Creation"));

        $inventory = $this->menu->getInventory();

        $colors = [
            DyeColor::BLACK(),
            DyeColor::GRAY(),
        ];

        $excludedSlots = [10, 13, 16];
        $allSlots = array_merge(
            range(0, 8),
            range(9, 17),
            range(18, 26)
        );

        foreach ($allSlots as $index => $slot) {
            if (in_array($slot, $excludedSlots, true)) {
                continue;
            }

            $color = $colors[$index % 2];
            $inventory->setItem($slot, Utils::createGlassPane($color));
        }

        $islandGenerators = [
            [
                'name' => "&r&fBasic Island",
                'lore' => ["&r&7A simple, flat island with", "&r&7limited resources."],
                'icon' => VanillaBlocks::STONE()->asItem(),
                'slot' => 10
            ],
            [
                'name' => "&r&2Forest Island",
                'lore' => ["&r&7A forest-themed island with", "&r&7lots of trees and resources."],
                'icon' => VanillaBlocks::OAK_SAPLING()->asItem(),
                'slot' => 13
            ],
            [
                'name' => "&r&6Desert Island",
                'lore' => ["&r&7A dry, sandy island with", "&r&7limited resources but great for", "&r&7a unique challenge."],
                'icon' => VanillaBlocks::SAND()->asItem(),
                'slot' => 16
            ]
        ];

        foreach ($islandGenerators as $generator) {
            $generatorItem = $generator['icon'];
            $generatorItem->setCustomName(C::colorize($generator['name']));
            $generatorItem->setLore(array_map(function ($lore) {
                return C::colorize($lore);
            }, $generator['lore']));
            $inventory->setItem($generator['slot'], $generatorItem);
        }

        $this->menu->setListener(InvMenu::readonly(function (DeterministicInvMenuTransaction $transaction): void {
            $player = $transaction->getPlayer();
            $slot = $transaction->getAction()->getSlot();

            $generators = [
                10 => "basic_island",
                13 => "forest_island",
                16 => "desert_island"
            ];

            PlayerUtils::playSound($player, "mob.enderdragon.flap");

            if (isset($generators[$slot])) {
                $player->removeCurrentWindow();

                $transaction->then(function () use ($player, $generators, $slot): void {
                    IslandCreationConfirmScreen::displayForm($player, $generators[$slot]);
                });
            }
        }));
    }

    public static function display(Player $player): void
    {
        $screen = new self();
        $screen->menu->send($player);
    }
}
