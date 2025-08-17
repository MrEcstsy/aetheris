<?php

declare(strict_types=1);

namespace ecstsy\AetherisRecode\utils\ui\skills;

use ecstsy\AetherisRecode\Loader;
use ecstsy\AetherisRecode\player\AetherisPlayer;
use ecstsy\AetherisRecode\player\skills\SkillAbilities;
use ecstsy\AetherisRecode\player\skills\SkillType;
use ecstsy\MartianUtilities\utils\GeneralUtils;
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

final class SkillsLevelScreen extends BaseScreen
{

    private static InvMenu $menu;
    private string $skillKey;
    private int $page = 1;
    private int $perPage = 24;
    private int $totalPages;

    /** @var int[] */
    private array $progressionSlots = [9,18,27,36,37,38,29,20,11,12,13,22,31,40,41,42,33,24,15,16,17,26,35,44];

    private Item $prevPageItem;
    private Item $nextPageItem;

    public function __construct(Player $player, string $skillKey)
    {
        $this->skillKey = $skillKey;
        
        self::$menu = InvMenu::create(InvMenuTypeIds::TYPE_DOUBLE_CHEST);

        self::$menu->setName(C::colorize("&r&8Skill Progression - " . ucfirst($skillKey)));

        $this->prevPageItem = VanillaItems::ARROW()->setCustomName(C::colorize("&r&c◀ Previous"));
        $this->nextPageItem = VanillaItems::ARROW()->setCustomName(C::colorize("&r&aNext ▶"));

        $blueprint = SkillAbilities::getAbilities($skillKey)[0] ?? [];
        $maxTier   = $blueprint ? count($blueprint["levels"]) : 0;
        $this->totalPages = (int)ceil($maxTier / $this->perPage);

        $this->drawPage($player);

        self::$menu->setListener(InvMenu::readonly(function (DeterministicInvMenuTransaction $tx): void {
            $clicked = $tx->getItemClicked();
            $player = $tx->getPlayer();

            if ($clicked->equalsExact($this->prevPageItem) && $this->page > 1) {
                $this->page--;
                $this->drawPage($player);
            }

            if ($clicked->equalsExact($this->nextPageItem) && $this->page < $this->totalPages) {
                $this->page++;
                $this->drawPage($player);
            }
        }));
    }

    private function drawPage(Player $player): void {
        $inv = self::$menu->getInventory();
        $session = Loader::getInstance()->getPlayerManager()->getSession($player);
        if ($session === null) return;

        $this->placeSkillIcon(0, $player, $session);

        $blueprint = SkillAbilities::getAbilities($this->skillKey)[0] ?? [];
        $levels = $blueprint["levels"]  ?? [];
        $effects = $blueprint["effects"] ?? [];
        $desc = $blueprint["description"] ?? "";
        $name = $blueprint["name"]    ?? "";

        $allTiers = array_keys($levels);
        $start = ($this->page - 1) * $this->perPage;
        $slice = array_slice($allTiers, $start, $this->perPage);

        $playerLevel = $session->getSkillLevel($this->skillKey);
        $xp = $session->getSkillXp($this->skillKey);
        $nextXp = 100 + ($playerLevel * 75);
        $pct = $nextXp > 0 ? min(100, round(($xp / $nextXp) * 100)) : 0;

        foreach ($slice as $i => $tier) {
            $slot = $this->progressionSlots[$i];
            if ($tier <= $playerLevel) {
                $color = DyeColor::LIME();
                $statusLine = C::colorize("&aUNLOCKED");
            } elseif ($tier === $playerLevel + 1) {
                $color = DyeColor::PINK();
                $statusLine = C::colorize("&eIN PROGRESS");
            } else {
                $color = DyeColor::RED();
                $statusLine = C::colorize("&cLOCKED");
            }
            $paneItem = VanillaBlocks::STAINED_GLASS_PANE()
                        ->setColor($color)
                        ->asItem()
                        ->setCustomName(C::colorize("&r&dLevel " . GeneralUtils::getRomanNumeral($tier)));

            $lore = [
                C::colorize("&r&fLevel {$tier}"),
                C::colorize("&r&fRewards:")
            ];

            if ($tier === 2) {
                $lore[] = C::colorize("&r&d   +1❤ Health");
                $lore[] = C::colorize("&r&4   +1➤ Strength");
            }

            $lore[] = "";
            if ($tier === 1) {
                $lore[] = C::colorize("&r&d{$name} - &lABILITY UNLOCK");
                $lore[] = C::colorize("&r&d ┃ &f{$desc}");
            } elseif ($tier === $playerLevel + 1) {
                $lore[] = C::colorize("&r&d{$name} Unlock");
                $lore[] = C::colorize("&r&d ┃ &f{$desc}");
                $lore[] = "";
                $lore[] = C::colorize("&r&fProgress: {$pct}%");
                $lore[] = C::colorize("&r&f{$xp}/{$nextXp} XP");
            } elseif ($tier <= $playerLevel) {
                $lore[] = C::colorize("&r&d{$name} " . GeneralUtils::getRomanNumeral($tier));
                $lore[] = C::colorize("&r&d ┃ &f{$desc}");
            } else {
                $lore[] = C::colorize("&r&7{$name}");
                $lore[] = C::colorize("&r&7 ┃ &f{$desc}");
            }

            $lore[] = "";
            $lore[] = $statusLine;
            $paneItem->setLore($lore);

            $inv->setItem($slot, $paneItem);
        }

        if ($this->page > 1) {
            $inv->setItem(52, $this->prevPageItem);
        }
        if ($this->page < $this->totalPages) {
            $inv->setItem(53, $this->nextPageItem);
        }
    }

    private function placeSkillIcon(int $slot, Player $player, AetherisPlayer $session): void {
        $level  = $session->getSkillLevel($this->skillKey);
        $xp     = $session->getSkillXp($this->skillKey);
        $nextXp = 100 + ($level * 75);
        $pct    = $nextXp > 0 ? min(100, round(($xp / $nextXp) * 100)) : 0;

        $icons = [
            SkillType::FARMING    => VanillaItems::DIAMOND_HOE(),
            SkillType::MINING     => VanillaItems::IRON_PICKAXE(),
            SkillType::FORAGING   => VanillaItems::STONE_AXE(),
            SkillType::FISHING    => VanillaItems::FISHING_ROD(),
            SkillType::EXCAVATION => VanillaItems::GOLDEN_SHOVEL(),
            SkillType::ARCHERY    => VanillaItems::BOW(),
            SkillType::DEFENSE    => VanillaItems::CHAINMAIL_CHESTPLATE(),
            SkillType::FIGHTING   => VanillaItems::DIAMOND_SWORD(),
            SkillType::ENDURANCE  => VanillaItems::GOLDEN_APPLE(),
            SkillType::AGILITY    => VanillaItems::FEATHER(),
            SkillType::ALCHEMY    => VanillaItems::POTION(),
            SkillType::ENCHANTING => VanillaBlocks::ENCHANTING_TABLE()->asItem(),
            SkillType::SORCERY    => VanillaItems::BLAZE_ROD(),
            SkillType::HEALING    => VanillaItems::SPLASH_POTION(),
            SkillType::FORGING    => VanillaBlocks::ANVIL()->asItem(),
        ];

        $item = ($icons[$this->skillKey] ?? VanillaItems::BOOK())
            ->setCustomName(C::colorize("&r&d&l" . ucfirst($this->skillKey) . " &r&f" . GeneralUtils::getRomanNumeral($level)));

        $descMap = [
            SkillType::FARMING    => "Harvest Crops to earn Farming XP",
            SkillType::FORAGING   => "Cut trees to earn Foraging XP",
            SkillType::MINING     => "Mine stone and ores to earn Mining XP",
            SkillType::FISHING    => "Catch fish to earn Fishing XP",
            SkillType::EXCAVATION => "Dig with a shovel to earn Excavation XP",
            SkillType::ARCHERY    => "Shoot mobs and players with a bow to earn Archery XP",
            SkillType::DEFENSE    => "Take damage from entities to earn Defense XP",
            SkillType::FIGHTING   => "Fight mobs with melee weapons to earn Fighting XP",
            SkillType::ENDURANCE  => "Walk and run to earn Endurance XP",
            SkillType::AGILITY    => "Jump and take fall damage to earn Agility XP",
            SkillType::ALCHEMY    => "Brew potions to earn Alchemy XP",
            SkillType::ENCHANTING => "Enchant items and books to earn Enchanting XP",
            SkillType::SORCERY    => "Use mana abilities to earn Sorcery XP",
            SkillType::HEALING    => "Drink and splash potions to earn Healing XP",
            SkillType::FORGING    => "Combine and apply books in an anvil to earn Forging XP",
        ];

        $lore = [
            C::colorize("&r&d┃ &r&f" . ($descMap[$this->skillKey] ?? "")),
            "",
            C::colorize("&r&d┃ &r&fLevel: &d" . GeneralUtils::getRomanNumeral($level)),
            C::colorize("&r&d┃ &r&fProgress to Level " . ($level + 1) . ":&d " . $pct . "%"),
            C::colorize("&r&d┃ &r&f{$xp}/{$nextXp} XP"),
            "",
            C::colorize("&r&d┃ &r&fAbility Levels:")
        ];
        foreach ($this->getFormattedAbilities($this->skillKey, $session) as $line) {
            $lore[] = $line;
        }
        $lore[] = "";
        $lore[] = C::colorize("&r&dUse ◀ ▶ to change pages");

        $item->setLore($lore);
        self::$menu->getInventory()->setItem($slot, $item);
    }

    public static function display(Player $player, string $skillKey): void {
        $screen = new self($player, $skillKey);
        $screen->getMenu()->send($player);
    }

    public function getMenu(): InvMenu
    {
        return self::$menu;
    }

    private function getFormattedAbilities(string $skill, AetherisPlayer $player): array {
        $lines = [];
        $abilities = SkillAbilities::getAbilities($skill);

        foreach ($abilities as $ability) {
            $name     = $ability["name"];
            $desc     = $ability["description"];
            $tiers    = $ability["levels"];
            $effects  = $ability["effects"];
            $current  = $player->getAbilityLevel($skill, $name);
            $maxTier  = max(array_keys($tiers));

            if ($current > 0) {
                $val = $effects[$current] ?? "";
                $lines[] = C::colorize("&r&d• {$name} &f{$val}");
            } else {
                $lines[] = C::colorize("&r&7• {$name} (locked)");
            }
            $lines[] = C::colorize("&r&8» {$desc}");
        }

        return $lines;
    }
}