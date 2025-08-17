<?php

declare(strict_types=1);

namespace ecstsy\AetherisRecode\skyblock;

use ecstsy\AetherisRecode\Loader;
use ecstsy\AetherisRecode\server\scoreboard\ScoreboardHelper;
use ecstsy\AetherisRecode\utils\QueryStmts;
use ecstsy\AetherisRecode\utils\Utils;
use pocketmine\math\Vector3;
use pocketmine\player\Player;
use pocketmine\Server;
use Ramsey\Uuid\Uuid;
use Ramsey\Uuid\UuidInterface;
use Yanoox\ScoreBoardAPI;

final class SkyBlock
{

    public function __construct(
        private string $island_id,
        private string $name,
        private string $description,
        private int $value,
        private string $leader,
        private ?string $leaderName,
        private array $members,
        private string $world,
        private array $rolePermissions,
        private array $settings,
        private Vector3 $spawn,
        private int $bank,
        private int $maxMembers
    ) {
    }

    /**
     * @var array<string, string> Stores member UUIDs mapped to their names.
     */
    private array $memberNames = [];

    public function getIslandId(): string
    {
        return $this->island_id;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function setName(string $name): void
    {
        $this->name = $name;
        $this->updateDb();
    }

    public function getLeader(): string
    {
        return $this->leader;
    }

    public function setLeader(string $leader): void
    {
        $this->leader = $leader;
        $this->updateDb();

        $player = Server::getInstance()->getPlayerByUUID(Uuid::fromString($leader));
        ScoreBoardAPI::editLineScore($player, 8, "", $this->members[$leader]['role']);
    }

    public function getMembers(): array
    {
        return $this->members;
    }

    public function setMembers(array $members): void
    {
        $this->members = array_map(function ($member) {
            if (is_string($member)) {
                return ['name' => $member, 'role' => 'recruit', 'join_date' => time()];
            }
            return $member;
        }, $members);
        $this->updateDb();
    }

    public function addMember(Player $player, string $role = 'recruit'): void
    {
        $uuid = $player->getUniqueId()->toString();
        $name = $player->getName();
        if (!isset($this->members[$uuid])) {
            $this->members[$uuid] = [
                'name' => $name,
                'role' => $role,
                'join_date' => time(),
                'uuid' => $uuid
            ];
            $this->updateDb();
        }
    }

    public function removeMember(Player $player): void
    {
        $uuid = $player->getUniqueId()->toString();
        if (isset($this->members[$uuid])) {
            $info = $this->members[$uuid];
            unset($this->members[$uuid]);
            $this->updateDb();

            $name = $info['name'];
            $targetPlayer = Server::getInstance()->getPlayerExact($name);
            if ($targetPlayer !== null) {
                $targetSession = Loader::getPlayerManager()->getSession($targetPlayer);
                if ($targetSession !== null) {
                    $targetSession->setSkyblock(null);
                }
            } else {
                $offlineSession = Loader::getPlayerManager()->getSessionByUuid(Uuid::fromString($uuid));
                $offlineSession->setSkyblock(null);
            }
        }
    }

    /**
     * Update (or add) a member's stored name.
     */
    public function updateMemberName(UuidInterface $uuid, string $name): void
    {
        if (isset($this->members[$uuid->toString()])) {
            $this->members[$uuid->toString()]['name'] = $name;
            $this->updateDb();
        }
    }

    public function getMemberNames(): array
    {
        return array_column($this->members, 'name');
    }

    public function getMember(string $uuid): ?array
    {
        return $this->members[$uuid] ?? null;
    }

    public function getRole(string $uuid): ?string
    {
        return $this->members[$uuid]['role'] ?? null;
    }

    public function updateRole(string $uuid, string $newRole): void
    {
        if (isset($this->members[$uuid])) {
            $this->members[$uuid]['role'] = $newRole;
            $this->updateDb();
            
            $player = Server::getInstance()->getPlayerByUUID(Uuid::fromString($uuid));
            ScoreboardHelper::updateIslandLines($player);
        }
    }

    public function getFormattedJoinDate(string $uuid): string
    {
        if (isset($this->members[$uuid]['join_date'])) {
            return date("l, F jS Y", $this->members[$uuid]['join_date']);
        }
        return "Unknown";
    }

    public function getWorld(): string
    {
        return $this->world;
    }

    public function setWorld(string $world): void
    {
        $this->world = $world;
        $this->updateDb();
    }

    public function getSettings(): array
    {
        return $this->settings;
    }

    public function updateSettings(array $settings): void
    {
        $this->settings = $settings;
        $this->updateDb();
    }

    public function getSpawn(): Vector3
    {
        return $this->spawn;
    }

    public function setSpawn(Vector3 $spawn): void
    {
        $this->spawn = $spawn;
        $this->updateDb();
    }

    public function getBank(): int
    {
        return $this->bank;
    }

    public function setBank(int $bank): void
    {
        $this->bank = $bank;
        $this->updateDb();
    }

    public function removeBank(int $amount): void
    {
        $this->bank -= $amount;
        $this->updateDb();
    }

    public function addBank(int $amount): void
    {
        $this->bank += $amount;
        $this->updateDb();
    }

    public function getMaxMembers(): int
    {
        return $this->maxMembers;
    }

    public function setMaxMembers(int $amount): void
    {
        $this->maxMembers = $amount;
        $this->updateDb();
    }

    public function addMaxMembers(int $amount): void
    {
        $this->maxMembers += $amount;
        $this->updateDb();
    }

    public function removeMaxMembers(int $amount): void
    {
        $this->maxMembers -= $amount;
        $this->updateDb();
    }

    public function isMember(UuidInterface $uuid): bool
    {
        $key = $uuid->toString();

        if ($this->leader === $key) {
            return true;
        }

        return isset($this->members[$key]);
    }

    public function getDescription(): string
    {
        return $this->description;
    }

    public function getValue(): int
    {
        return $this->value;
    }

    public function setValue(int $value): void
    {
        $this->value = $value;
        $this->updateDb();
    }

    public function getLeaderName(): ?string
    {
        return $this->leaderName;
    }

    public function setLeaderName(?string $leaderName): void
    {
        $this->leaderName = $leaderName;
        $this->updateDb();
    }

    public function getRolePermissions(string $role): array 
    {
        $r = Utils::normalizeRole($role) ?? 'recruit';
        return $this->rolePermissions[$r] ?? [];
    }

    public function canRole(string $role, string $permission): bool 
    {
        $perms = $this->getRolePermissions($role);
        return isset($perms[$permission]) && $perms[$permission];
    }

    public function setRolePermission(string $role, string $permission, bool $value): void 
    {
        $r = Utils::normalizeRole($role) ?? 'recruit';
        $this->rolePermissions[$r][$permission] = $value;
        $this->updateDb(); 
    }

    public function getSetting(string $setting): mixed
    {
        return $this->settings[$setting] ?? null;
    }

    public function setSetting(string $setting, mixed $value): void
    {
        $this->settings[$setting] = $value;
        $this->updateDb();
    }

    public function updateDb(): void
    {
        $spawn = [
            'x' => $this->spawn->getX(),
            'y' => $this->spawn->getY(),
            'z' => $this->spawn->getZ()
        ];
        Loader::getDatabase()->executeChange(QueryStmts::ISLANDS_UPDATE, [
            'island_id' => $this->island_id,
            'name' => $this->name,
            'description' => $this->description,
            'value' => $this->value,
            'leader_uuid' => $this->leader,
            'leader_name' => $this->leaderName,
            'members' => json_encode($this->members),
            'world' => $this->world,
            'role_permissions' => json_encode($this->rolePermissions),
            'settings' => json_encode($this->settings),
            'spawn' => json_encode($spawn),
            'bank_balance' => $this->bank,
            'max_members' => $this->maxMembers
        ]);
    }
}
