<?php

declare(strict_types=1);

namespace ecstsy\AetherisRecode\utils\ui;

use ecstsy\AetherisRecode\Loader;
use ecstsy\AetherisRecode\player\skills\Skill;
use ecstsy\AetherisRecode\player\skills\SkillType;
use ecstsy\AetherisRecode\utils\Utils;
use ecstsy\MartianUtilities\utils\GeneralUtils;
use ecstsy\MartianUtilities\utils\InventoryUtils;
use ecstsy\MartianUtilities\utils\screens\BaseScreen;
use muqsit\invmenu\InvMenu;
use muqsit\invmenu\transaction\DeterministicInvMenuTransaction;
use pocketmine\block\utils\DyeColor;
use pocketmine\block\VanillaBlocks;
use pocketmine\item\VanillaItems;
use pocketmine\player\Player;
use pocketmine\utils\TextFormat as C;

final class SkillsMainMenuScreen extends BaseScreen {

    private InvMenu $menu;

    public function __construct(Player $player)
    {
        $this->menu = InvMenu::create(InvMenu::TYPE_DOUBLE_CHEST);
        $inventory = $this->menu->getInventory();
        $blackPane = VanillaBlocks::STAINED_GLASS_PANE()->setColor(DyeColor::BLACK())->asItem();
    
        $skillManager = Loader::getSkillManager();
        $skills = $skillManager->getSkillsByPlayerUuid($player->getUniqueId()->toString());
    
        $skill = $skills[SkillType::FARMING] ?? new Skill($player->getUniqueId(), SkillType::FARMING, 0, 0);
    
        $level = GeneralUtils::getRomanNumeral($skill->getLevel() + 1); 
        $requiredExperience = 40 * pow(1.5, $skill->getLevel()); 
        $experience = $skill->getExperience();
        $percentageProgress = round(($experience / $requiredExperience) * 100);
        $progressBarLength = 20;
        $filledBars = round(($percentageProgress / 100) * $progressBarLength);
    
        $progressBar = str_repeat(":", (int)$filledBars) . str_repeat(" ", (int)($progressBarLength - $filledBars));
    
        $rewards = implode("\n", Utils::getRewardsForSkillLevel($skill->getLevel() + 1));
    
        $menuItems = [
            20 => VanillaItems::GOLDEN_HOE()->setCustomName(C::colorize("&r&aFarming"))->setLore([
                C::colorize("&r&7Harvest crops and shear sheep to"),
                C::colorize("&r&7earn Farming XP!"),
                "",
                C::colorize("&r&7Progress to Level " . $level . ": &e" . $percentageProgress . "%"),
                C::colorize("&r&f" . $progressBar . " &6" . $experience . "&6/&e" . $requiredExperience),
                "",
                C::colorize("&r&7Level " . $level . " Rewards:"),
                C::colorize($rewards),
                C::colorize("&r&7Click to view!"),
            ]),
        ];
    
        $this->menu->setName(C::colorize("&r&8Your Skills"));
        InventoryUtils::fillInventory($inventory, $blackPane);
    
        foreach ($menuItems as $slot => $item) {
            $inventory->setItem($slot, $item);
        }
    
        $this->menu->setListener(InvMenu::readonly(function (DeterministicInvMenuTransaction $transaction): void {

        }));
    }

    public static function display(Player $player): void {
        $screen = new self($player);
        $screen->getMenu()->send($player);
    }

    public function getMenu(): InvMenu
    {
        return $this->menu;
    }
}