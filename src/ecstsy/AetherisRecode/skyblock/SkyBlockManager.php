<?php

declare(strict_types=1);

namespace ecstsy\AetherisRecode\skyblock;

use ecstsy\AetherisRecode\Loader;
use ecstsy\AetherisRecode\player\AetherisPlayer;
use ecstsy\AetherisRecode\server\scoreboard\ScoreboardHelper;
use ecstsy\AetherisRecode\utils\IslandPermissions;
use ecstsy\AetherisRecode\utils\IslandSettings;
use ecstsy\AetherisRecode\utils\QueryStmts;
use ecstsy\MartianUtilities\utils\GeneralUtils;
use ecstsy\MartianUtilities\utils\PlayerUtils;
use pocketmine\math\Vector3;
use pocketmine\player\Player;
use pocketmine\scheduler\ClosureTask;
use pocketmine\Server;
use pocketmine\utils\Filesystem;
use pocketmine\utils\SingletonTrait;
use pocketmine\world\World;
use pocketmine\utils\TextFormat as C;
use Ramsey\Uuid\Uuid;
use Yanoox\ScoreBoardAPI;

final class SkyBlockManager
{

    use SingletonTrait;

    /** @var SkyBlock[] */
    private array $skyblocks = [];

    /** @var string[] */
    private array $worlds = [];

    private array $invitations = [];

    public function __construct(private Loader $plugin)
    {
        $this->loadAllSkyblocks();

        self::setInstance($this);
    }

    public function loadAllSkyblocks(): void
    {
        Loader::getDatabase()->executeSelect(QueryStmts::ISLANDS_SELECT, [], function (array $rows): void {
            $worldManager = $this->plugin->getServer()->getWorldManager();

            foreach ($rows as $row) {
                $spawn = (array)json_decode($row['spawn'], true);
                $settings = (array)json_decode($row['settings'], true);

                $this->skyblocks[$row['island_id']] = new SkyBlock(
                    $row['island_id'],
                    $row['name'],
                    $row['description'],
                    $row['value'],
                    $row['leader_uuid'],
                    $row['leader_name'],
                    json_decode($row['members'], true),
                    $row['world'],
                    json_decode($row['role_permissions'], true),
                    $settings,
                    new Vector3($spawn['x'], $spawn['y'], $spawn['z']),
                    $row['bank_balance'],
                    $row['max_members']
                );

                $worldName = $row['world'];

                if (!$worldManager->isWorldLoaded($worldName)) {
                    if ($worldManager->loadWorld($worldName)) {
                    } else {
                        $this->plugin->getLogger()->warning("Failed to load SkyBlock world: {$worldName}");
                    }
                } else {
                    $this->plugin->getLogger()->info("SkyBlock world already loaded: {$worldName}");
                }
            }

            $loadedWorlds = array_map(function (SkyBlock $skyblock) {
                return $skyblock->getWorld();
            }, $this->skyblocks);
        });
    }

    public function unloadSkyblock(string $uuid): void
    {
        $skyblock = $this->getSkyBlockByUuid($uuid);

        if (!$skyblock instanceof SkyBlock) {
            return;
        }

        foreach ($skyblock->getMemberNames() as $member) {
            if ($this->plugin->getPlayerManager()->getSessionByName($member) instanceof AetherisPlayer) {
                return;
            }
        }

        $world = $this->plugin->getServer()->getWorldManager()->getWorldByName($skyblock->getWorld());

        if (!$world instanceof World) {
            return;
        }

        $this->plugin->getServer()->getWorldManager()->unloadWorld($world);
        unset($this->skyblocks[$uuid]);
    }

    public function createSkyBlock(Player $player, string $islandIdent, string $name, string $generator): void
    {
        $session = $this->plugin->getPlayerManager()->getSession($player);
        $config = GeneralUtils::getConfiguration(Loader::getInstance(), "config.yml");

        if (!$session instanceof AetherisPlayer) {
            return;
        }

        $islandId = strtolower($islandIdent);
        $worldsPath = $this->plugin->getServer()->getDataPath() . "worlds/";
        $generatorPath = $worldsPath . $generator;

        if (!is_dir($generatorPath)) {
            $player->sendMessage(C::RED . "The generator world '{$generator}' does not exist in the worlds folder.");
            return;
        }

        $newWorldName = "sb-" . uniqid();
        $newWorldPath = $worldsPath . $newWorldName;

        try {
            Filesystem::recursiveCopy($generatorPath, $newWorldPath);
        } catch (\RuntimeException $e) {
            $player->sendMessage(C::RED . "Failed to copy generator world '{$generator}': " . $e->getMessage());
            return;
        }

        if (!$this->plugin->getServer()->getWorldManager()->loadWorld($newWorldName)) {
            $player->sendMessage(C::RED . "Failed to load the new SkyBlock world '{$newWorldName}'.");
            return;
        }

        $newWorld = $this->plugin->getServer()->getWorldManager()->getWorldByName($newWorldName);
        if ($newWorld === null) {
            $player->sendMessage(C::RED . "Failed to initialize the new SkyBlock world.");
            return;
        }

        $spawn = $newWorld->getSpawnLocation();
        $playerUuid = $player->getUniqueId()->toString();
        $members = [
            $playerUuid => [
                'name' => $player->getName(),
                'role' => 'leader',
                'join_date' => time(),
                'uuid' => $playerUuid
            ],
        ];

        $defaultRolePermissions = [
            'visitor'   => [
                IslandPermissions::KILL_MOBS => false,
                IslandPermissions::OPEN_CONTAINERS => false,
                IslandPermissions::OPEN_DOORS  => false,
                IslandPermissions::PICKUP         => false,
                IslandPermissions::PLACE          => false,
                IslandPermissions::BREAK          => false,
                IslandPermissions::USE_BUCKETS => false,
                IslandPermissions::EDIT_PERMISSIONS => false,
                IslandPermissions::DEMOTE_USERS => false,
                IslandPermissions::EDIT_DESCRIPTION => false,
                IslandPermissions::INVITE_USERS => false,
                IslandPermissions::TRUST_USERS => false,
                IslandPermissions::KICK_USERS => false,
                IslandPermissions::PROMOTE_USERS => false,
                IslandPermissions::USE_REDSTONE => false,
                IslandPermissions::RENAME_ISLAND => false,
                IslandPermissions::ISLAND_HOME => false,
                IslandPermissions::BREAK_SPAWNERS => false,
                IslandPermissions::EDIT_SETTINGS => false,
                IslandPermissions::MANAGE_WARPS => false,
                IslandPermissions::EDIT_SIGNS => false,
            ],
            'recruit'   => [
                IslandPermissions::KILL_MOBS            => true,
                IslandPermissions::OPEN_CONTAINERS => false,
                IslandPermissions::OPEN_DOORS  => true,
                IslandPermissions::PICKUP         => true,
                IslandPermissions::PLACE          => false,
                IslandPermissions::BREAK          => false,
                IslandPermissions::USE_BUCKETS => false,
                IslandPermissions::EDIT_PERMISSIONS => false,
                IslandPermissions::DEMOTE_USERS => false,
                IslandPermissions::EDIT_DESCRIPTION => false,
                IslandPermissions::INVITE_USERS => false,
                IslandPermissions::TRUST_USERS => false,
                IslandPermissions::KICK_USERS => false,
                IslandPermissions::PROMOTE_USERS => false,
                IslandPermissions::USE_REDSTONE => true,
                IslandPermissions::RENAME_ISLAND => false,
                IslandPermissions::ISLAND_HOME => false,
                IslandPermissions::BREAK_SPAWNERS => false,
                IslandPermissions::EDIT_SETTINGS => false,
                IslandPermissions::MANAGE_WARPS => false,
                IslandPermissions::EDIT_SIGNS => false,
            ],
            'member'    => [
                IslandPermissions::KILL_MOBS => true,
                IslandPermissions::OPEN_CONTAINERS => true,
                IslandPermissions::OPEN_DOORS  => true,
                IslandPermissions::PICKUP         => true,
                IslandPermissions::PLACE          => true,
                IslandPermissions::BREAK          => true,
                IslandPermissions::USE_BUCKETS => true,
                IslandPermissions::EDIT_PERMISSIONS => false,
                IslandPermissions::DEMOTE_USERS => false,
                IslandPermissions::EDIT_DESCRIPTION => false,
                IslandPermissions::INVITE_USERS => false,
                IslandPermissions::TRUST_USERS => false,
                IslandPermissions::KICK_USERS => false,
                IslandPermissions::PROMOTE_USERS => false,
                IslandPermissions::USE_REDSTONE => true,
                IslandPermissions::RENAME_ISLAND => false,
                IslandPermissions::ISLAND_HOME => false,
                IslandPermissions::BREAK_SPAWNERS => true,
                IslandPermissions::EDIT_SETTINGS => false,
                IslandPermissions::MANAGE_WARPS => false,
                IslandPermissions::EDIT_SIGNS => false,
            ],
            'moderator' => [
                IslandPermissions::KILL_MOBS            => true,
                IslandPermissions::OPEN_CONTAINERS => true,
                IslandPermissions::OPEN_DOORS  => true,
                IslandPermissions::PICKUP         => true,
                IslandPermissions::PLACE          => true,
                IslandPermissions::BREAK          => true,
                IslandPermissions::USE_BUCKETS => true,
                IslandPermissions::EDIT_PERMISSIONS => false,
                IslandPermissions::DEMOTE_USERS => false,
                IslandPermissions::EDIT_DESCRIPTION => false,
                IslandPermissions::INVITE_USERS => true,
                IslandPermissions::TRUST_USERS => true,
                IslandPermissions::KICK_USERS => true,
                IslandPermissions::PROMOTE_USERS => false,
                IslandPermissions::USE_REDSTONE => true,
                IslandPermissions::RENAME_ISLAND => false,
                IslandPermissions::ISLAND_HOME => true,
                IslandPermissions::BREAK_SPAWNERS => true,
                IslandPermissions::EDIT_SETTINGS => false,
                IslandPermissions::MANAGE_WARPS => true,
                IslandPermissions::EDIT_SIGNS => true,
            ],
            'co-leader' => [
                IslandPermissions::KILL_MOBS            => true,
                IslandPermissions::OPEN_CONTAINERS => true,
                IslandPermissions::OPEN_DOORS  => true,
                IslandPermissions::PICKUP         => true,
                IslandPermissions::PLACE          => true,
                IslandPermissions::BREAK          => true,
                IslandPermissions::USE_BUCKETS => true,
                IslandPermissions::EDIT_PERMISSIONS => true,
                IslandPermissions::DEMOTE_USERS => true,
                IslandPermissions::EDIT_DESCRIPTION => true,
                IslandPermissions::INVITE_USERS => true,
                IslandPermissions::TRUST_USERS => true,
                IslandPermissions::KICK_USERS => true,
                IslandPermissions::PROMOTE_USERS => true,
                IslandPermissions::USE_REDSTONE => true,
                IslandPermissions::RENAME_ISLAND => true,
                IslandPermissions::ISLAND_HOME => true,
                IslandPermissions::BREAK_SPAWNERS => true,
                IslandPermissions::EDIT_SETTINGS => true,
                IslandPermissions::MANAGE_WARPS => true,
                IslandPermissions::EDIT_SIGNS => true,
            ],
            'leader'    => [
                IslandPermissions::KILL_MOBS            => true,
                IslandPermissions::OPEN_CONTAINERS => true,
                IslandPermissions::OPEN_DOORS  => true,
                IslandPermissions::PICKUP         => true,
                IslandPermissions::PLACE          => true,
                IslandPermissions::BREAK          => true,
                IslandPermissions::USE_BUCKETS => true,
                IslandPermissions::EDIT_PERMISSIONS => true,
                IslandPermissions::DEMOTE_USERS => true,
                IslandPermissions::EDIT_DESCRIPTION => true,
                IslandPermissions::INVITE_USERS => true,
                IslandPermissions::TRUST_USERS => true,
                IslandPermissions::KICK_USERS => true,
                IslandPermissions::PROMOTE_USERS => true,
                IslandPermissions::USE_REDSTONE => true,
                IslandPermissions::RENAME_ISLAND => true,
                IslandPermissions::ISLAND_HOME => true,
                IslandPermissions::BREAK_SPAWNERS => true,
                IslandPermissions::EDIT_SETTINGS => true,
                IslandPermissions::MANAGE_WARPS => true,
                IslandPermissions::EDIT_SIGNS => true,
            ],
        ];

        $args = [
            'island_id' => $islandId,
            'name' => $name,
            'description' => "Default island description :(",
            'value' => 0,
            'leader_uuid' => $playerUuid,
            'leader_name' => $player->getName(),
            'members' => json_encode($members),
            'world' => $newWorldName,
            'role_permissions' => json_encode($defaultRolePermissions),
            'settings' => json_encode(IslandSettings::getDefaults()),
            'spawn' => json_encode([
                'x' => $spawn->getX(),
                'y' => $spawn->getY(),
                'z' => $spawn->getZ()
            ]),
            'bank_balance' => 0,
            'max_members' => $config->getNested("settings.skyblock.max-members")
        ];

        $skyblock = new SkyBlock(
            $islandId,
            $args['name'],
            $args['description'],
            $args['value'],
            $args['leader_uuid'],
            $args['leader_name'],
            $members,
            $args['world'],
            json_decode($args['role_permissions'], true),
            json_decode($args['settings'], true),
            new Vector3($spawn->getX(), $spawn->getY(), $spawn->getZ()),
            $args['bank_balance'],
            $args['max_members']
        );

        $this->skyblocks[$islandId] = $skyblock;
        $this->worlds[] = $newWorldName;

        $this->plugin->getDatabase()->executeInsert(QueryStmts::ISLANDS_CREATE, $args);

        $session->setSkyblock($islandId);
        $skyblock->updateDb();
        ScoreboardHelper::updateIslandLines($player);
    }

    /**
     * Get a skyblock by its ID.
     */
    public function getSkyBlockById(string $skyblockId): ?SkyBlock
    {
        return $this->skyblocks[$skyblockId] ?? null;
    }

    public function getSkyBlockByUuid(string $uuid): ?SkyBlock
    {
        return $this->skyblocks[$uuid] ?? null;
    }

    public function getSkyBlock(string $name): ?SkyBlock
    {
        foreach ($this->skyblocks as $SkyBlock) {
            if ($SkyBlock->getName() == $name) return $SkyBlock;
        }
        return null;
    }

    public function getSkyBlockByWorld(World $world): ?SkyBlock
    {
        foreach ($this->skyblocks as $SkyBlock) {
            if ($SkyBlock->getWorld() == $world->getFolderName()) return $SkyBlock;
        }
        return null;
    }

    public function isSkyBlockWorld(string $world): bool
    {
        if (in_array($world, $this->worlds, true)) return true;
        return false;
    }


    public function deleteSkyBlock(string $islandId): void
    {
        $skyblock = $this->getSkyBlock($islandId);
        if (!$skyblock) {
            $this->plugin->getLogger()->warning("Skyblock with the name {$islandId} not found.");
            return;
        }

        unset($this->skyblocks[$islandId]);

        foreach ($this->plugin->getPlayerManager()->getSessions() as $session) {
            if ($session->getSkyblock() === $islandId) {
                $session->setSkyblock(null);
                ScoreboardHelper::updateIslandLines($session->getPlayer());
            }
        }

        $this->plugin->getDataBase()->executeGeneric(
            QueryStmts::ISLANDS_DELETE,
            [
                'island_id' => $islandId
            ]
        );

        $worldName = $skyblock->getWorld();

        $worldManager = $this->plugin->getServer()->getWorldManager();
        $skyblockWorld = $worldManager->getWorldByName($worldName);

        if ($skyblockWorld !== null) {
            foreach ($skyblockWorld->getPlayers() as $player) {
                $player->teleport($this->plugin->getServer()->getWorldManager()->getDefaultWorld()->getSpawnLocation());
                $player->sendMessage(C::colorize("&cYour island was disbanded. You have been sent to spawn."));
            }
        }

        $worldPath = $this->plugin->getServer()->getDataPath() . "worlds/" . $worldName;
        if (is_dir($worldPath)) {
            try {
                $worldManager->unloadWorld($skyblockWorld);
                Filesystem::recursiveUnlink($worldPath);
                $this->plugin->getLogger()->info("SkyBlock world deleted: {$worldName}");
            } catch (\RuntimeException $e) {
                $this->plugin->getLogger()->warning("Failed to delete SkyBlock world folder: {$worldPath}. Error: " . $e->getMessage());
            }
        } else {
            $this->plugin->getLogger()->warning("SkyBlock world folder not found: {$worldPath}");
        }
    }

    public function sendInvitation(Player $inviter, Player $invitee): void
    {
        $session = Loader::getPlayerManager()->getSession($inviter);
        $sbSession = Loader::getSkyBlockManager()->getSkyBlockByUuid($session->getSkyblock());

        if ($sbSession === null) {
            $inviter->sendToastNotification(C::colorize(Loader::SERVER_TITLE), C::colorize("&r&f➤ &fYou do not have an island!"));
            return;
        }

        if (count($sbSession->getMembers()) >= $sbSession->getMaxMembers()) {
            $inviter->sendToastNotification(C::colorize(Loader::SERVER_TITLE), C::colorize("&r&f➤ &fYour island has reached maximum capacity!"));
            return;
        }

        $inviteTimeout = GeneralUtils::getConfiguration(Loader::getInstance(), "config.yml")->getNested("settings.skyblock.invite-timeout");

        $this->invitations[$invitee->getName()] = [
            'inviter' => $inviter->getName(),
            'island' => $sbSession->getName(),
            'expires' => time() + $inviteTimeout
        ];

        $invitee->sendToastNotification(
            C::colorize(Loader::SERVER_TITLE),
            C::colorize("&r&2✉ &fInvitation to join &b{$sbSession->getName()} &ffrom &a{$inviter->getName()}&f!")
        );

        PlayerUtils::playSound($invitee, "random.orb");
        GeneralUtils::addParticleToPosition($invitee->getPosition(), "minecraft:heart");

        $inviter->sendToastNotification(
            C::colorize(Loader::SERVER_TITLE),
            C::colorize("&r&2✔ &fInvitation sent to &a{$invitee->getName()}&f.")
        );

        Loader::getInstance()->getScheduler()->scheduleDelayedTask(new ClosureTask(function () use ($invitee): void {
            if (isset($this->invitations[$invitee->getName()])) {
                $invitation = $this->invitations[$invitee->getName()];
                unset($this->invitations[$invitee->getName()]);

                $inviter = Server::getInstance()->getPlayerExact($invitation['inviter']);

                if ($inviter !== null) {
                    $inviter->sendToastNotification(C::colorize(Loader::SERVER_TITLE), C::colorize("&r&6⚠ &fYour invitation to &a{$invitee->getName()} &fhas expired."));
                    return;
                }

                if ($invitee->isOnline()) {
                    $invitee->sendToastNotification(C::colorize(Loader::SERVER_TITLE), C::colorize("&r&6⚠ &fYour invitation to &b{$invitation['island']} &fhas expired."));
                }
            }
        }), $inviteTimeout * 20);
    }

    public function acceptInvitation(Player $player, string $islandName): void
    {
        $invitation = $this->invitations[$player->getName()] ?? null;

        if ($invitation === null || $invitation['island'] !== $islandName) {
            $player->sendToastNotification(C::colorize(Loader::SERVER_TITLE), C::colorize("&r&6⚠ &fYou don't have an invitation to join &b{$islandName}&f."));
            return;
        }

        $sbSession = Loader::getSkyBlockManager()->getSkyBlock($islandName);

        if ($sbSession === null) {
            $player->sendToastNotification(C::colorize(Loader::SERVER_TITLE), C::colorize("&r&6⚠ &fThe island &b{$islandName} &fdoens't exist."));
            return;
        }

        $members = $sbSession->getMembers();

        if (count($members) >= $sbSession->getMaxMembers()) {
            $player->sendToastNotification(C::colorize(Loader::SERVER_TITLE), C::colorize("&r&6⚠ &fThe island &b{$islandName} &fhas reached its member limit."));
            return;
        }

        $sbSession->addMember($player);

        unset($this->invitations[$player->getName()]);

        $player->sendToastNotification(C::colorize(Loader::SERVER_TITLE), C::colorize("&r&2✔ &fYou have joined the island &b{$islandName}&f!"));

        PlayerUtils::playSound($player, "random.levelup");
        GeneralUtils::addParticleToPosition($player->getPosition(), "minecraft:happy_villager");

        $inviter = Server::getInstance()->getPlayerExact($invitation['inviter']);

        if ($inviter !== null) {
            $inviter->sendToastNotification(C::colorize(Loader::SERVER_TITLE), C::colorize("&r&2✔ &f{$player->getName()} has accepted your invitation!"));
        }
    }

    public function denyInvitation(Player $player, string $islandName): void
    {
        $invitation = $this->invitations[$player->getName()] ?? null;

        if ($invitation === null || $invitation['island'] !== $islandName) {
            $player->sendToastNotification(C::colorize(Loader::SERVER_TITLE), C::colorize("&r&6⚠ &fYou don't have an invitation to deny."));
            return;
        }

        unset($this->invitations[$player->getName()]);
        $player->sendToastNotification(C::colorize(Loader::SERVER_TITLE), C::colorize("&r&2✔ &fYou have denied the invitation to join &b{$islandName}&f."));

        $inviter = Server::getInstance()->getPlayerExact($invitation['inviter']);
        if ($inviter !== null) {
            $inviter->sendToastNotification(C::colorize(Loader::SERVER_TITLE), C::colorize("&r&6⚠ &f{$player->getName()} has denied your invitation."));
        }
    }

    public function listInvitations(Player $player): void
    {
        $invitation = $this->invitations[$player->getName()] ?? null;

        if ($invitation === null) {
            $player->sendToastNotification(C::colorize(Loader::SERVER_TITLE), C::colorize("&r&6⚠ &fYou have no pending invitations."));
            return;
        }

        $expiresIn = $invitation['expires'] - time();
        $player->sendToastNotification(
            C::colorize(Loader::SERVER_TITLE),
            C::colorize("&r&2✉ &fInvitation to join &b{$invitation['island']} &ffrom &a{$invitation['inviter']}&f.\n&fExpires in: &e{$expiresIn} seconds")
        );
    }
}
