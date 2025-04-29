<?php

declare(strict_types=1);

namespace ecstsy\AetherisRecode\player\skills;

use ecstsy\AetherisRecode\Loader;
use ecstsy\AetherisRecode\utils\QueryStmts;
use Ramsey\Uuid\UuidInterface;

final class Skill
{

    public function __construct(
        private UuidInterface $uuid,
        private string $name,
        private int $level,
        private int $experience
    ) {}

    public function getName(): string
    {
        return $this->name;
    }

    public function getUuid(): UuidInterface
    {
        return $this->uuid;
    }

    public function getLevel(): int
    {
        return $this->level;
    }

    public function setLevel(int $level = 0): void
    {
        $this->level = $level;

        $this->updateDb();
    }

    public function addLevel(int $amount = 1): void
    {
        $this->level += $amount;

        $this->updateDb();
    }

    public function removeLevel(int $amount = 1): void
    {
        $this->level -= $amount;

        $this->updateDb();
    }

    public function getExperience(): int
    {
        return $this->experience;
    }

    public function setExperience(int $experience = 0): void
    {
        $this->experience = $experience;

        $this->updateDb();
    }

    public function addExperience(int $amount = 1): void
    {
        $this->experience += $amount;

        $this->updateDb();
    }

    public function removeExperience(int $amount = 1): void
    {
        $this->experience -= $amount;

        $this->updateDb();
    }

    public function updateDb(): void
    {
        Loader::getDatabase()->executeChange(QueryStmts::SKILLS_UPDATE, [
            'uuid' => $this->uuid->toString(),
            'skill_name' => $this->name,
            'level' => $this->level,
            'experience' => $this->experience
        ]);
    }
}
