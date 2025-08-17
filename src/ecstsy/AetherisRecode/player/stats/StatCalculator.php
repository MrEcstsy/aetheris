<?php

declare(strict_types=1);

namespace ecstsy\AetherisRecode\player\stats;

use ecstsy\AetherisRecode\Loader;
use ecstsy\AetherisRecode\player\skills\SkillType;
use pocketmine\player\Player;

final class StatCalculator
{
    /** @var array */
    private array $config;

    public function __construct(array $config)
    {
        $this->config = $config;
    }

    public function getMaxHealth(Player $player): float
    {
        $uuid = $player->getUniqueId()->toString();
        $skill = Loader::getSkillManager()->getSkill($uuid, SkillType::ENDURANCE);
        $level = $skill ? $skill->getLevel() : 1;
        $baseHearts = $this->config['health']['hearts'][$level] ?? 20;
        return $baseHearts * 0.5; // 1 heart = 2 health in PMMP
    }

    public function getStrengthMultiplier(Player $player): float
    {
        $uuid = $player->getUniqueId()->toString();
        $skill = Loader::getSkillManager()->getSkill($uuid, SkillType::FIGHTING);
        $level = $skill ? $skill->getLevel() : 1;
        $modifier = $this->config['strength']['modifier'] ?? 0.5;
        return 1.0 + ($level * $modifier);
    }

    public function getLuckModifier(Player $player): float
    {
        $uuid = $player->getUniqueId()->toString();
        $skill = Loader::getSkillManager()->getSkill($uuid, SkillType::LUCK);
        $level = $skill ? $skill->getLevel() : 1;
        $modifier = $this->config['luck']['modifier'] ?? 0.1;
        return $level * $modifier;
    }

    public function getRegenAmount(Player $player): float
    {
        $uuid = $player->getUniqueId()->toString();
        $skill = Loader::getSkillManager()->getSkill($uuid, SkillType::REGENERATION);
        $level = $skill ? $skill->getLevel() : 1;
        $base = $this->config['regeneration']['base_regen'] ?? 1;
        $mod = $this->config['regeneration']['mana_regen_mod'] ?? 0.25;
        return $base + ($level * $mod);
    }

    public function getAnvilCostModifier(Player $player): float
    {
        $uuid = $player->getUniqueId()->toString();
        $skill = Loader::getSkillManager()->getSkill($uuid, SkillType::WISDOM);
        $level = $skill ? $skill->getLevel() : 1;
        $mod = $this->config['wisdom']['anvil_cost_mod'] ?? 0.25;
        return max(0.1, 1.0 - ($level * $mod));
    }

    public function getXpModifier(Player $player): float
    {
        $uuid = $player->getUniqueId()->toString();
        $skill = Loader::getSkillManager()->getSkill($uuid, SkillType::WISDOM);
        $level = $skill ? $skill->getLevel() : 1;
        $mod = $this->config['wisdom']['xp_mod'] ?? 0.01;
        return 1.0 + ($level * $mod);
    }
}