<?php

declare(strict_types=1);

namespace ecstsy\AetherisRecode\player\skills;

use ecstsy\AetherisRecode\Loader;
use ecstsy\AetherisRecode\utils\QueryStmts;
use pocketmine\player\Player;
use Ramsey\Uuid\Uuid;

class SkillManager
{

    /** @var array<string, Skill[]> */
    private array $playerSkills = [];

    public function __construct(private Loader $plugin)
    {
        $this->loadAllSkills();
    }

    /**
     * Load all skills from the database into memory.
     */
    private function loadAllSkills(): void
    {
        $this->plugin->getDatabase()->executeSelect(QueryStmts::SKILLS_SELECT, [], function (array $rows): void {
            foreach ($rows as $row) {
                $uuid = $row['uuid'];
                $skillName = $row['skill_name'];
                $level = (int)$row['level'];
                $experience = (int)$row['experience'];
                $this->playerSkills[$uuid][$skillName] = new Skill(Uuid::fromString($row['uuid']), $skillName, $level, $experience);
            }
        });
    }

    /**
     * Get a player's skills by UUID.
     */
    public function getSkillsByPlayerUuid(string $uuid): array
    {
        if (!isset($this->playerSkills[$uuid])) {
            $this->playerSkills[$uuid] = [];

            $this->plugin->getDatabase()->executeSelect(QueryStmts::SKILLS_SELECT, ['uuid' => $uuid], function (array $rows) use ($uuid): void {
                foreach ($rows as $row) {
                    $uuid = $row['uuid'];
                    $skillName = $row['skill_name'];
                    $level = (int)$row['level'];
                    $experience = (int)$row['experience'];
                    $this->playerSkills[$uuid][$skillName] = new Skill(Uuid::fromString($uuid), $skillName, $level, $experience);
                }
            });
        }

        return $this->playerSkills[$uuid];
    }

    /**
     * Get a specific skill for a player.
     */
    public function getSkill(string $uuid, string $skillName): ?Skill
    {
        return $this->playerSkills[$uuid][$skillName] ?? null;
    }

    /**
     * Add or update a skill for a player.
     */
    public function updateSkill(string $uuid, string $skillName, int $level, int $experience): void
    {
        $skill = $this->getSkill($uuid, $skillName);

        if ($skill === null) {
            $skill = new Skill(Uuid::fromString($uuid), $skillName, $level, $experience);
            $this->playerSkills[$uuid][$skillName] = $skill;

            $this->plugin->getDatabase()->executeInsert(QueryStmts::SKILLS_CREATE, [
                'uuid' => $uuid,
                'skill_name' => $skillName,
                'level' => $level,
                'experience' => $experience,
            ]);
        } else {
            $skill->setLevel($level);
            $skill->setExperience($experience);

            $this->plugin->getDatabase()->executeChange(QueryStmts::SKILLS_UPDATE, [
                'uuid' => $uuid,
                'skill_name' => $skillName,
                'level' => $level,
                'experience' => $experience,
            ]);
        }
    }

    /**
     * Delete a skill for a player.
     */
    public function deleteSkill(string $uuid, string $skillName): void
    {
        unset($this->playerSkills[$uuid][$skillName]);

        $this->plugin->getDatabase()->executeChange(QueryStmts::SKILLS_DELETE, [
            'uuid' => $uuid,
            'skill_name' => $skillName,
        ]);
    }
}
