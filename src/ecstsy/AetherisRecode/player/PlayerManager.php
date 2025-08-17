<?php

declare(strict_types=1);

namespace ecstsy\AetherisRecode\player;

use ecstsy\AetherisRecode\Loader;
use ecstsy\AetherisRecode\player\skills\SkillType;
use ecstsy\AetherisRecode\utils\QueryStmts;
use ecstsy\MartianUtilities\utils\GeneralUtils;
use pocketmine\player\Player;
use pocketmine\utils\SingletonTrait;
use Ramsey\Uuid\Uuid;
use Ramsey\Uuid\UuidInterface;

final class PlayerManager {
    use SingletonTrait;

    /** @var AetherisPlayer[] */
    private array $sessions;

    public function __construct(public Loader $plugin)
    {
        self::setInstance($this);
        $this->loadSessions();        
    }

    /**
     * Loads all player sessions from the database
     *
     * @internal
     */
    private function loadSessions(): void {
        Loader::getDatabase()->executeSelect(QueryStmts::PLAYERS_SELECT, [], function(array $rows): void {
            foreach ($rows as $row) {
                $this->sessions[$row['uuid']] = new AetherisPlayer(
                    Uuid::fromString($row['uuid']),
                    $row['username'],
                    $row['balance'],
                    $row['cooldowns'],
                    $row['kills'],
                    $row['deaths'],
                    $row['bounty'],
                    $row['settings'],
                    $row['island'],
                    $row['collection'],
                    $row['skills']
                );
            }
        });
    }

    /**
     * Creates a new player session or updates an existing one, if the player
     * has never joined the server before.
     *
     * @param Player $player the player to create a session for
     * @return AetherisPlayer the newly created session
     */
    public function createSession(Player $player): AetherisPlayer {
        $config = GeneralUtils::getConfiguration(Loader::getInstance(), "config.yml");

        $skills = [];
        foreach (SkillType::getAllSkillNames() as $skill) {
            $skills[$skill] = ["level" => 1, "xp" => 0.0];
        }

        $args = [
            'uuid' => $player->getUniqueId()->toString(),
            'username' => $player->getName(),
            'balance' => $config->getNested("settings.economy.starting-money"),
            "cooldowns" => "{}",
            "kills" => 0,
            "deaths" => 0,
            "bounty" => 0,
            "settings" => json_encode([
                'chest_inventories' => true, 'broadcasts' => true, 'loot_announcer' => true, 'quick_claim' => true
            ]),
            'island' => null,
            'collection' => '',
            'skills' => json_encode($skills),
        ];

        Loader::getDatabase()->executeInsert(QueryStmts::PLAYERS_CREATE, $args);

        $this->sessions[$player->getUniqueId()->toString()] = new AetherisPlayer(
            $player->getUniqueId(),
            $args['username'],
            $args['balance'],
            $args['cooldowns'],
            $args['kills'],
            $args['deaths'],
            $args['bounty'],
            $args['settings'],
            $args['island'],
            $args['collection'],
            $args['skills']
        );

        return $this->sessions[$player->getUniqueId()->toString()];
    }

    /**
     * Retrieves the AetherisPlayer session associated with the given Player.
     *
     * @param Player $player The player whose session is being retrieved.
     * @return AetherisPlayer|null The associated AetherisPlayer session, or null if it doesn't exist.
     */
    public function getSession(Player $player): ?AetherisPlayer {
        return $this->getSessionByUuid($player->getUniqueId());
    }

    /**
     * Retrieves the AetherisPlayer session associated with the given username.
     *
     * This function performs a case-insensitive search for the provided username
     * among the active player sessions and returns the matching session if found.
     *
     * @param string $name The username to search for.
     * @return AetherisPlayer|null The associated AetherisPlayer session, or null if no match is found.
     */
    public function getSessionByName(string $name) : ?AetherisPlayer
    {
        foreach ($this->sessions as $session) {
            if (strtolower($session->getUsername()) === strtolower($name)) {
                return $session;
            }
        }
        return null;
    }

    public function getSessionByUuid(UuidInterface $uuid) : ?AetherisPlayer
    {
        return $this->sessions[$uuid->toString()] ?? null;
    }

    public function destroySession(AetherisPlayer $session) : void
    {
        Loader::getDatabase()->executeChange(QueryStmts::PLAYERS_DELETE, ["uuid", $session->getUuid()->toString()]);

        # Remove session from the array
        unset($this->sessions[$session->getUuid()->toString()]);
    }

    public function getSessions() : array
    {
        return $this->sessions;
    }
}