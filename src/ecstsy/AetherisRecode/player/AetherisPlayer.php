<?php

declare(strict_types=1);

namespace ecstsy\AetherisRecode\player;

use ecstsy\AetherisRecode\Loader;
use ecstsy\AetherisRecode\skyblock\SkyBlock;
use ecstsy\AetherisRecode\skyblock\SkyBlockManager;
use ecstsy\AetherisRecode\utils\ChatTypes;
use ecstsy\AetherisRecode\utils\QueryStmts;
use ecstsy\MartianUtilities\utils\GeneralUtils;
use ecstsy\MartianUtilities\utils\ItemUtils;
use pocketmine\block\VanillaBlocks;
use pocketmine\item\Item;
use pocketmine\player\Player;
use pocketmine\Server;
use pocketmine\utils\TextFormat as C;
use Ramsey\Uuid\UuidInterface;

final class AetherisPlayer {

    private bool $isConnected = false;
    private bool $adminMode = false;
    private string $chat = ChatTypes::ALL;

    public function __construct(
        private UuidInterface $uuid,
        private string        $username,
        private int           $balance,
        private string        $cooldowns,
        private int           $kills,
        private int           $deaths,
        private int           $bounty,
        private string        $settings,
        private ?string        $island,
        private string        $collection
    )
    {
        
    }

    public function isConnected(): bool {
        return $this->isConnected;
    }

    public function setConnected(bool $connceted): void {
        $this->isConnected = $connceted;
    }

    /**
     * @return UuidInterface the player's UUID
     */
    public function getUuid(): UuidInterface {
        return $this->uuid;
    }

    /**
     * Gets the Player instance associated with this AetherisPlayer, or null if none is found.
     * @return Player|null the associated Player instance, or null if offline
     */
    public function getPlayer(): ?Player {
        return Server::getInstance()->getPlayerByUUID($this->uuid);
    }

    /**
     * Gets the username of the player associated with this AetherisPlayer.
     * @return string the username
     */
    public function getUsername(): string {
        return $this->username;
    }

    /**
     * Sets the username of the player associated with this AetherisPlayer.
     * 
     * @param string $username The new username to set for the player.
     */

    public function setUsername(string $username): void {
        $this->username = $username;
        $this->updateDb();
    }

    /**
     * Gets the current balance of the player associated with this AetherisPlayer.
     * @return int the current balance
     */
    public function getBalance(): int {
        return $this->balance;
    }

    /**
     * Adds a specified amount of money to the player's balance, but does not allow the balance to exceed the configured maximum amount.
     * If the player's balance already exceeds the maximum amount, no action is taken and a message is sent to the player.
     * @param int $amount The amount of money to add to the player's balance.
     */
    public function addBalance(int $amount): void {
        $config = GeneralUtils::getConfiguration(Loader::getInstance(), "config.yml");
        $maxAmount = $config->getNested("settings.economy.max-money");
        $player = $this->getPlayer();

        $remainingAmount = $maxAmount - $this->balance;
        $amountToAdd = min($amount, $remainingAmount);

        if ($amountToAdd <= 0) {
            $player->sendMessage(C::colorize("&r&l&4(!) &r&cYou have reached the maximum amount of money!"));
            return;
        }

        $this->balance += $amountToAdd;
        $this->updateDb();
    }

    /**
     * Removes a specified amount of money from the player's balance.
     * @param int $amount The amount of money to remove from the player's balance.
     */
    public function removeBalance(int $amount): void {
        $this->balance -= $amount;
        $this->updateDb();
    }

    /**
     * Sets the player's balance to the specified amount and updates the database.
     * @param int $amount The amount to set the player's balance to.
     */
    public function setBalance(int $amount): void {
        $this->balance = $amount;
        $this->updateDb();
    }

    /**
     * Adds a cooldown with the given name and duration to the player. This will overwrite any existing cooldown with the same name.
     * @param string $cooldownName The name of the cooldown to add.
     * @param int $duration The duration of the cooldown in seconds.
     */
    public function addCooldown(string $cooldownName, int $duration): void
    {
        $cooldowns = json_decode($this->cooldowns, true) ?? [];

        $cooldowns[$this->getUuid()->toString()][$cooldownName] = time() + $duration;

        $this->cooldowns = json_encode($cooldowns);

        $this->updateDb();
    }

    /**
     * Gets the remaining cooldown time for the specified cooldown name, or null if the cooldown doesn't exist.
     * @param string $cooldownName The name of the cooldown to get the remaining time for.
     * @return int|null The remaining cooldown time in seconds, or null if the cooldown doesn't exist.
     */
    public function getCooldown(string $cooldownName): ?int
    {
        $cooldowns = json_decode($this->cooldowns, true);

        if ($cooldowns !== null && isset($cooldowns[$this->getUuid()->toString()][$cooldownName])) {
            $cooldownExpireTime = $cooldowns[$this->getUuid()->toString()][$cooldownName];
            $remainingCooldown = $cooldownExpireTime - time();
            return max(0, $remainingCooldown);
        }

        return null;
    }

    /**
     * Gets the number of player kills this player has.
     * @return int The number of kills.
     */

    public function getKills(): int {
        return $this->kills;
    }

    /**
     * Adds the specified number of kills to this player's kill count.
     * @param int $amount The number of kills to add.
     */
    public function addKills(int $amount): void {
        $this->kills += $amount;
        $this->updateDb();
    }

    /**
     * Sets the number of player kills this player has to the given amount.
     * @param int $amount The number of kills to set.
     */
    public function setKills(int $amount): void {
        $this->kills = $amount;
        $this->updateDb();
    }

    /**
     * Removes the specified number of kills from this player's kill count.
     * @param int $amount The number of kills to remove.
     */
    public function removeKills(int $amount): void {
        $this->kills -= $amount;
        $this->updateDb();
    }
    
    /**
     * Gets the number of deaths this player has.
     * @return int The number of deaths.
     */

    public function getDeaths(): int {
        return $this->deaths;
    }
    
    /**
     * Adds the specified number of deaths to this player's death count.
     * @param int $amount The number of deaths to add.
     */
    public function addDeaths(int $amount): void {
        $this->deaths += $amount;
        $this->updateDb();
    }

    /**
     * Sets the number of deaths this player has to the given amount.
     * @param int $amount The number of deaths to set.
     */
    public function setDeaths(int $amount): void {
        $this->deaths = $amount;
        $this->updateDb();
    }

    /**
     * Removes the specified number of deaths from this player's death count.
     * @param int $amount The number of deaths to remove.
     */
    public function removeDeaths(int $amount): void {
        $this->deaths -= $amount;
        $this->updateDb();
    }
    
    /**
     * Sets the island UUID for the player.
     * @param string|null $uuid The island UUID to associate with the player, or null to disassociate.
     */
    public function setSkyblock(?string $islandId): void {
        $this->island = $islandId ?? null; 
        $this->updateDb();  
    }

    public function getSkyblock(): ?string {
        return $this->island;
    }

    public function getIsland(): ?SkyBlock {
        if ($this->island === null) {
            return null;
        }
        return SkyBlockManager::getInstance()->getSkyBlockById($this->island);
    }

    /**
     * Get all settings and their values for the player
     *
     * @return array Associative array of settings and their values
     */
    public function getAllSettings(): array
    {
        return json_decode($this->settings, true) ?? [];
    }

    /**
     * Get a specific setting value by key
     *
     * @param string $key
     * @return mixed|null The value of the setting if found, or null if the key doesn't exist
     */
    public function getSetting(string $key): mixed
    {
        $decodedSettings = json_decode($this->settings, true);
        return $decodedSettings[$key] ?? null;
    }

    /**
     * Set a specific setting value by key
     *
     * @param string $key
     * @param mixed $value
     * @return void
     */
    public function setSetting(string $key, mixed $value): void
    {
        $decodedSettings = json_decode($this->settings, true);
        $decodedSettings[$key] = $value;
        $this->settings = json_encode($decodedSettings);
        $this->updateDb();
    }

    /**
     * Toggle a setting
     *
     * @param string $key
     * @return void
     */
    public function toggleSetting(string $key): void
    {
        $settings = json_decode($this->settings, true);
        $settings[$key] = !($settings[$key] ?? false);
        $this->settings = json_encode($settings);
        $this->updateDb();
    }

    public function getKDRRatio(): float {
        return $this->deaths > 0 ? round($this->kills / $this->deaths, 2) : $this->kills;
    }

    public static function createCollectionItem(string $category, array $storedItems): Item {
        $encodedItems = array_map(fn($item) => is_string($item) ? $item : ItemUtils::encodeItem($item), $storedItems);

        $displayItem = match ($category) {
            "buycraft" => VanillaBlocks::CHEST()->asItem()->setCustomName(C::colorize("&r&l&b* Buycraft Purchase *")),
            "crate" => VanillaBlocks::CHEST()->asItem()->setCustomName(C::colorize("&r&l&d* Crate Reward *")),
            "overflow" => VanillaBlocks::CHEST()->asItem()->setCustomName(C::colorize("&r&l&c* Full Inventory | Stored *")),
            default => VanillaBlocks::CHEST()->asItem()->setCustomName(C::colorize("&r&l&f* Stored Item *"))
        };
    
        $lore = [
            C::colorize("&r&7Category: &f" . ucfirst($category)),
            C::colorize("&r&8Contains:")
        ];
    
        foreach ($encodedItems as $storedItemData) {
            $item = ItemUtils::decodeItem($storedItemData);
            $lore[] = C::colorize("&r&7- &f" . $item->getName() . " x" . $item->getCount());
        }
    
        $lore[] = "";
        $lore[] = C::colorize("&r&l&6Click to claim");
    
        $displayItem->setLore($lore);
    
        return $displayItem;
    }
    
    public function addItemToCollection(Item $item): void
    {
        $collection = $this->getItemsFromCollection();
        
        $itemData = ItemUtils::encodeItem($item);

        if ($itemData === false) {
            $this->getPlayer()->sendMessage(C::colorize("&r&cFailed to serialize item!"));
            return;
        }
        
        if (in_array($itemData, $collection, true)) {
            $this->getPlayer()->sendMessage(C::colorize("&r&cItem is already in the collection!"));
            return;
        }

        $collection[] = $itemData;
        $this->collection = json_encode($collection);
        $this->updateDb();

        $this->getPlayer()->sendMessage(C::colorize("&r&aItem has been added to your collection."));
    }

    public function removeItemFromCollection(Item $item): void
    {
        $collection = $this->getItemsFromCollection();
        $itemData = ItemUtils::encodeItem($item);
        
        if (($key = array_search($itemData, $collection, true)) !== false) {
            unset($collection[$key]);
            $this->collection = json_encode(array_values($collection));
            $this->updateDb();

            $this->getPlayer()->sendMessage(C::colorize("&r&aItem has been removed from your collection."));
        } else {
            $this->getPlayer()->sendMessage(C::colorize("&r&cItem not found in the collection!"));
        }
    }

    public function getItemsFromCollection(): array
    {
        $collection = json_decode($this->collection, true);
        return is_array($collection) ? $collection : [];
    }

    public function isInAdminMode(): bool
    {
        return $this->adminMode;
    }

    public function setInAdminMode(bool $value): void
    {
        $this->adminMode = $value;
    }

    public function getCurrentChat(): string
    {
        return $this->chat;
    }

    public function setCurrentChat(string $chat): void
    {
        $this->chat = $chat;
    }
    
    /**
     * Updates the player's row in the database with the current values
     */
    public function updateDb(): void {
        Loader::getDatabase()->executeChange(QueryStmts::PLAYERS_UPDATE, [
            'uuid' => $this->uuid->toString(),
            'username' => $this->username,
            'balance' => $this->balance,
            'cooldowns' => $this->cooldowns,
            'bounty' => $this->bounty,
            'kills' => $this->kills,
            'deaths' => $this->deaths,
            'bounty' => $this->bounty,
            'settings' => $this->settings,
            'island' => $this->island,
            'collection' => $this->collection
        ]);
    }
}