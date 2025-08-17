<?php

declare(strict_types=1);

namespace ecstsy\AetherisRecode\utils\ui\skills;

use ecstsy\AetherisRecode\Loader;
use ecstsy\AetherisRecode\player\AetherisPlayer;
use ecstsy\AetherisRecode\player\skills\SkillAbilities;
use ecstsy\AetherisRecode\player\skills\SkillType;
use ecstsy\AetherisRecode\utils\inventory\CustomSizedInvMenu;
use ecstsy\MartianUtilities\utils\GeneralUtils;
use ecstsy\MartianUtilities\utils\InventoryUtils;
use ecstsy\MartianUtilities\utils\screens\BaseScreen;
use muqsit\invmenu\InvMenu;
use muqsit\invmenu\transaction\DeterministicInvMenuTransaction;
use pocketmine\block\BlockTypeIds;
use pocketmine\block\utils\DyeColor;
use pocketmine\block\VanillaBlocks;
use pocketmine\item\ItemTypeIds;
use pocketmine\item\PotionType;
use pocketmine\item\VanillaItems;
use pocketmine\player\Player;
use pocketmine\utils\TextFormat as C;

final class SkillsMainMenuScreen extends BaseScreen {

    private static InvMenu $menu;

    public function __construct(Player $player)
    {
        self::$menu = CustomSizedInvMenu::create(45);
        $inventory = self::$menu->getInventory();
        $blackPane = VanillaBlocks::STAINED_GLASS_PANE()->setColor(DyeColor::BLACK())->asItem();

        InventoryUtils::fillInventory($inventory, $blackPane);
        self::$menu->setName(C::colorize("&r&8Your Skills"));

        $aetherisPlayer = Loader::getInstance()->getPlayerManager()->getSession($player);

        if ($aetherisPlayer === null) return;

        $inventory->setItem(0, VanillaItems::DIAMOND_AXE()->setCustomName(C::colorize("&r&dYour Skills - " . $player->getName()))->setLore([
            C::colorize("&r&d┃ &r&fUpgrade Skills by doing various tasks"),
            C::colorize("&r&fto unlock valuable stat boosts, abilities, and more!"),
            "",
            C::colorize("&r&d┃ Hover over a Skill for more information!"),
            C::colorize("&r&d┃ Click on a Skill to view level progression!")
        ]));

        $skillDescriptions = [
            SkillType::FARMING => "Harvest Crops to earn Farming XP",
            SkillType::FORAGING => "Cut trees to earn Foraging XP", 
            SkillType::MINING => "Mine stone and ores to earn Mining XP",
            SkillType::FISHING => "Catch fish to earn Fishing XP",
            SkillType::EXCAVATION => "Dig with a shovel to earn Excavation XP",
            SkillType::ARCHERY => "Shoot mobs and players with a bow to earn Archery XP",
            SkillType::DEFENSE => "Take damage from entities to earn Defense XP",
            SkillType::FIGHTING => "Fight mobs with melee weapons to earn Fighting XP",
            SkillType::ENDURANCE => "Walk and run to earn Endurance XP",
            SkillType::AGILITY => "Jump and take fall damage to earn Agility XP",
            SkillType::ALCHEMY => "Brew potions to earn Alchemy XP",
            SkillType::ENCHANTING => "Enchant items and books to earn Enchanting XP",
            SkillType::SORCERY => "Use mana abilities to earn Sorcery XP",
            SkillType::HEALING => "Drink and splash potions to earn Healing XP",
            SkillType::FORGING => "Combine and apply books in an anvil to earn Forging XP",
        ];

        $skills = SkillType::getAllSkillNames();
        $skillIcons = [
            SkillType::FARMING => VanillaItems::DIAMOND_HOE(),
            SkillType::MINING => VanillaItems::IRON_PICKAXE(),
            SkillType::FORAGING => VanillaItems::STONE_AXE(),
            SkillType::FISHING => VanillaItems::FISHING_ROD(),
            SkillType::EXCAVATION => VanillaItems::GOLDEN_SHOVEL(),
            SkillType::ARCHERY => VanillaItems::BOW(),
            SkillType::DEFENSE => VanillaItems::CHAINMAIL_CHESTPLATE(),
            SkillType::FIGHTING => VanillaItems::DIAMOND_SWORD(),
            SkillType::ENDURANCE => VanillaItems::GOLDEN_APPLE(),
            SkillType::AGILITY => VanillaItems::FEATHER(),
            SkillType::ALCHEMY => VanillaItems::POTION()->setType(PotionType::HEALING()),
            SkillType::ENCHANTING => VanillaBlocks::ENCHANTING_TABLE()->asItem(),
            SkillType::SORCERY => VanillaItems::BLAZE_ROD(),
            SkillType::HEALING => VanillaItems::SPLASH_POTION()->setType(PotionType::HEALING()),
            SkillType::FORGING => VanillaBlocks::ANVIL()->asItem(),
        ];

        $slots = array_merge(range(11, 15), range(20, 24), range(29, 33));

        foreach ($skills as $i => $skillName) {
            $slot = $slots[$i] ?? null;
            if ($slot === null) break;

            $icon  = ($skillIcons[$skillName] ?? VanillaItems::BOOK())->setCustomName(
                C::colorize("&r&d&l" . ucfirst($skillName) . " &r&f" . GeneralUtils::getRomanNumeral($aetherisPlayer->getSkillLevel($skillName)))
            );

            $lore = [
                C::colorize("&r&d┃ &r&f" . ($skillDescriptions[$skillName] ?? "")),
                ""
            ];

            $lvl = $aetherisPlayer->getSkillLevel($skillName);
            $xp = number_format($aetherisPlayer->getSkillXp($skillName), 2);
            $xpToN = 100 + ($lvl * 75);
            $pct = $xpToN > 0 ? min(100, round(($xp / $xpToN) * 100)) : 0;

            $lore[] = C::colorize("&r&d┃ &r&fAbility Levels:");
            foreach ($this->getFormattedAbilities($skillName, $aetherisPlayer) as $line) {
                $lore[] = $line;
            }
            $lore[] = "";
            $lore[] = C::colorize("&r&d┃ &r&fLevel: &d" . GeneralUtils::getRomanNumeral($lvl));
            $lore[] = "";
            $lore[] = C::colorize("&r&d┃ &r&fProgress to Level " . ($lvl + 1) . ":&d " . $pct . "%");
            $lore[] = C::colorize("&r&d┃ &r&f{$xp}/{$xpToN} XP");
            $lore[] = "";
            $lore[] = C::colorize("&r&dClick to view Level Progression!");

            $inventory->setItem($slot, $icon->setLore($lore));
        }

        self::$menu->setListener(InvMenu::readonly(function (DeterministicInvMenuTransaction $transaction): void {
            $itemClicked = $transaction->getItemClicked();

            if ($itemClicked->getTypeId() === ItemTypeIds::DIAMOND_HOE) {
                SkillsLevelScreen::display($transaction->getPlayer(), SkillType::FARMING);
            }

            if ($itemClicked->getTypeId() === ItemTypeIds::IRON_PICKAXE) {
                SkillsLevelScreen::display($transaction->getPlayer(), SkillType::MINING);
            }

            if ($itemClicked->getTypeId() === ItemTypeIds::STONE_AXE) {
                SkillsLevelScreen::display($transaction->getPlayer(), SkillType::FORAGING);
            }

            if ($itemClicked->getTypeId() === ItemTypeIds::FISHING_ROD) {
                SkillsLevelScreen::display($transaction->getPlayer(), SkillType::FISHING);
            }

            if ($itemClicked->getTypeId() === ItemTypeIds::GOLDEN_SHOVEL) {
                SkillsLevelScreen::display($transaction->getPlayer(), SkillType::EXCAVATION);
            }

            if ($itemClicked->getTypeId() === ItemTypeIds::BOW) {
                SkillsLevelScreen::display($transaction->getPlayer(), SkillType::ARCHERY);
            }

            if ($itemClicked->getTypeId() === ItemTypeIds::CHAINMAIL_CHESTPLATE) {
                SkillsLevelScreen::display($transaction->getPlayer(), SkillType::DEFENSE);
            }

            if ($itemClicked->getTypeId() === ItemTypeIds::DIAMOND_SWORD) {
                SkillsLevelScreen::display($transaction->getPlayer(), SkillType::FIGHTING);
            }

            if ($itemClicked->getTypeId() === ItemTypeIds::GOLDEN_APPLE) {
                SkillsLevelScreen::display($transaction->getPlayer(), SkillType::ENDURANCE);
            }

            if ($itemClicked->getTypeId() === ItemTypeIds::FEATHER) {
                SkillsLevelScreen::display($transaction->getPlayer(), SkillType::AGILITY);
            }
        
            if ($itemClicked->getTypeId() === ItemTypeIds::POTION) {
                SkillsLevelScreen::display($transaction->getPlayer(), SkillType::ALCHEMY);
            }

            if ($itemClicked->getTypeId() === BlockTypeIds::ENCHANTING_TABLE) {
                SkillsLevelScreen::display($transaction->getPlayer(), SkillType::ENCHANTING);
            }

            if ($itemClicked->getTypeId() === ItemTypeIds::BLAZE_ROD) {
                SkillsLevelScreen::display($transaction->getPlayer(), SkillType::SORCERY);
            }

            if ($itemClicked->getTypeId() === ItemTypeIds::SPLASH_POTION) {
                SkillsLevelScreen::display($transaction->getPlayer(), SkillType::HEALING);
            }

            if ($itemClicked->getTypeId() === BlockTypeIds::ANVIL) {
                SkillsLevelScreen::display($transaction->getPlayer(), SkillType::FORGING);
            }
        }));
    }

    public static function display(Player $player): void {
        $screen = new self($player);
        $screen->getMenu()->send($player);
    }

    public function getMenu(): InvMenu {
        return self::$menu;
    }

    public function getFormattedAbilities(string $skill, AetherisPlayer $player): array {
        $lines = [];
        $abilities = SkillAbilities::getAbilities($skill);

        foreach ($abilities as $ability) {
            $name        = $ability["name"] ?? "Unknown";
            $tierReqs    = $ability["levels"] ?? [];
            $effects     = $ability["effects"]  ?? [];
            $currentTier = 0;

            foreach ($tierReqs as $tier => $reqLevel) {
                if ($player->getSkillLevel($skill) >= $reqLevel) {
                    $currentTier = $tier;
                }
            }

            if ($currentTier === 0) {
                $lines[] = C::colorize("&r&7  {$name}");
            } else {
                $full = $effects[$currentTier];
                $clean = preg_replace(
                    ["/^\+/", "/ chance to drop double crops/i"],
                    ["", "% 2x Drops"],
                    $full
                );
                $lines[] = C::colorize("&r&d  {$name} (&f{$clean}&d)");
            }
        }

        return $lines;
    }
}