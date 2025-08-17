<?php

declare(strict_types=1);

namespace ecstsy\AetherisRecode\listeners;

use ecstsy\AetherisRecode\blocks\CustomSpawner;
use ecstsy\AetherisRecode\blocks\tiles\SpawnerTile;
use ecstsy\AetherisRecode\entity\other\FloatingTextEntity;
use ecstsy\AetherisRecode\events\FloatingTextCountUpdateEvent;
use ecstsy\AetherisRecode\events\PlayerStatChangeEvent;
use ecstsy\AetherisRecode\Loader;
use ecstsy\AetherisRecode\player\PlayerManager;
use ecstsy\AetherisRecode\server\AnvilManager;
use ecstsy\AetherisRecode\server\FloatingTextsInstance;
use ecstsy\AetherisRecode\server\items\AetherisItemFactory;
use ecstsy\AetherisRecode\server\scoreboard\Scoreboard;
use ecstsy\AetherisRecode\server\scoreboard\ScoreboardHelper;
use ecstsy\AetherisRecode\skyblock\SkyBlockManager;
use ecstsy\AetherisRecode\utils\BragTracker;
use ecstsy\AetherisRecode\utils\ChatItemTracker;
use ecstsy\AetherisRecode\utils\ChatTypes;
use ecstsy\AetherisRecode\utils\inventory\anvils\AnvilRegistry;
use ecstsy\AetherisRecode\utils\inventory\anvils\EnchantCombineService;
use ecstsy\AetherisRecode\utils\inventory\anvils\RepairService;
use ecstsy\AetherisRecode\utils\SpawnerItem;
use ecstsy\AetherisRecode\utils\Utils;
use ecstsy\MartianUtilities\utils\GeneralUtils;
use InvalidArgumentException;
use pocketmine\block\Anvil;
use pocketmine\block\inventory\AnvilInventory;
use pocketmine\block\MonsterSpawner;
use pocketmine\entity\Location;
use pocketmine\entity\object\ItemEntity;
use pocketmine\event\block\BlockBreakEvent;
use pocketmine\event\block\BlockPlaceEvent;
use pocketmine\event\entity\EntityDamageByEntityEvent;
use pocketmine\event\entity\EntityDamageEvent;
use pocketmine\event\entity\EntityDeathEvent;
use pocketmine\event\entity\ItemMergeEvent;
use pocketmine\event\entity\ItemSpawnEvent;
use pocketmine\event\inventory\InventoryCloseEvent;
use pocketmine\event\Listener;
use pocketmine\event\player\PlayerChatEvent;
use pocketmine\event\player\PlayerDeathEvent;
use pocketmine\event\player\PlayerInteractEvent;
use pocketmine\event\player\PlayerJoinEvent;
use pocketmine\event\player\PlayerLoginEvent;
use pocketmine\event\player\PlayerMoveEvent;
use pocketmine\event\player\PlayerQuitEvent;
use pocketmine\event\server\CommandEvent;
use pocketmine\event\server\DataPacketReceiveEvent;
use pocketmine\event\world\ChunkLoadEvent;
use pocketmine\item\enchantment\VanillaEnchantments;
use pocketmine\item\ItemBlock;
use pocketmine\item\Pickaxe;
use pocketmine\item\StringToItemParser;
use pocketmine\network\mcpe\protocol\ItemStackRequestPacket;
use pocketmine\network\mcpe\protocol\types\inventory\ContainerUIIds;
use pocketmine\network\mcpe\protocol\types\inventory\stackrequest\PlaceStackRequestAction;
use pocketmine\player\Player;
use pocketmine\Server;
use pocketmine\utils\TextFormat as C;
use pocketmine\world\sound\XpCollectSound;
use SplObjectStorage;
use Yanoox\ScoreBoardAPI;

final class AetherisListener implements Listener {

    /** @var Player[] */
    public static array $combatPlayers = [];
    private float $combatTime;
    private array $bannedCommandsMap;
    private array $bannedCommandsList;
    private bool $banAllCommands;
    private bool $killOnLog;
    private AnvilManager $anvilManager;
    private AnvilRegistry $registry;
    public static array $lastMessages = [];
    public static array $lastMessageTime = [];

    /** @var Scoreboard[] */
    public static array $boards = [];

    public function __construct() {
        $config = GeneralUtils::getConfiguration(Loader::getInstance(), "config.yml");
        
        $this->combatTime = (float)$config->getNested("settings.combat.time", 30);
        $this->banAllCommands = (bool)$config->getNested("settings.combat.ban-all-commands", false);
        $this->killOnLog = (bool)$config->getNested("settings.combat.kill-on-log", false);
        
        $commands = $config->getNested("settings.combat.banned-commands", []);
        $this->bannedCommandsList = $commands;
        $this->bannedCommandsMap = array_flip($commands);
        Utils::combatTask();
        
        $registry       = new AnvilRegistry();
        $repairService  = new RepairService();
        $enchantService = new EnchantCombineService();

        $this->anvilManager = new AnvilManager($registry, $repairService, $enchantService);

        $this->registry = $registry;
    }

    public function onLoad(PlayerLoginEvent $event): void {
        $player = $event->getPlayer();

        if (PlayerManager::getInstance()->getSession($player) === null) {
            PlayerManager::getInstance()->createSession($player);
        }
    }

    public function onJoin(PlayerJoinEvent $event): void {
        $player = $event->getPlayer();
        $ping = $player->getNetworkSession()->getPing();
        $pingColor = Utils::getPingColor($ping);
        $networkBar = Utils::getNetworkBar($ping);

        PlayerManager::getInstance()->getSession($player)->setConnected(true);

        $joinMessages = [
            "   &r&f&lAetheris &dETHEREAL HUB",
            " ",
            "   &r&f      Welcome &d" . $player->getName(),
            "   &r&f     Online players &d" . count(Server::getInstance()->getOnlinePlayers()),
            " ",
            "   &r&f    " . $player->getNameTag() . " &r" . $pingColor . $ping . "ms " . $networkBar,
            "   &r&d&l WEBSITE &r&5➤ &fetherealhub.net",
            "  &r&d&l VOTE &r&5➤ &fvote.etherealhub.net",
            " &r&d&l STORE &r&5➤ &fstore.etherealhub.net",
            " ",
            "       &r&daetheris.etherealhub.net"
        ];

        foreach ($joinMessages as $message) {
            $player->sendMessage(C::colorize($message));
        }

        $event->setJoinMessage(C::colorize("&r&8[&2✓&8] &7" . $player->getName()));

        $count = count(Server::getInstance()->getOnlinePlayers());
        $countEvent = new FloatingTextCountUpdateEvent($count);
        $countEvent->call();

        $player->getInventory()->addItem(AetherisItemFactory::bankNote($player, 10000));

        ScoreboardHelper::initScoreboard($player);

        if ($player->getWorld()->getFolderName() === "world") {
            Utils::modifyGameRule($player, "showCoordinates", true, true);
            Utils::modifyGameRule($player, "locatorBar", false, true);
        }
    }

    public function onLeave(PlayerQuitEvent $event) {
        $player = $event->getPlayer();
        $session = PlayerManager::getInstance()->getSession($player);
        
        $session->setConnected(false);

        if ($session->getSkyblock() !== null) {
            Loader::getSkyBlockManager()->unloadSkyblock($session->getSkyblock());
            return;
        }

        $event->setQuitMessage(C::colorize("&r&8[&c✗&8] &7" . $player->getName()));

        $count = count(Server::getInstance()->getOnlinePlayers()) - 1;
        $countEvent = new FloatingTextCountUpdateEvent($count);
        $countEvent->call();

        $this->registry->remove($player);

        ScoreBoardAPI::removeScore($player);
    }

    /**
     * @priority MONITOR
     */
    public function onFloatingTextCountUpdate(FloatingTextCountUpdateEvent $ev): void {
        if ($ev->isCancelled()) return;

        if (isset(FloatingTextsInstance::$particles['spawn'])) {
            FloatingTextsInstance::$particles['spawn']->flagForDespawn();
            unset(FloatingTextsInstance::$particles['spawn']);
        }

        $def   = FloatingTextsInstance::$definitions['spawn'];
        $srv   = Server::getInstance();
        $world = $srv->getWorldManager()->getWorldByName($def['pos']['world']);
        if ($world === null) return;

        $text = implode("\n", array_map(
            fn(string $line) => str_replace("{count}", (string)$ev->getNewCount(), $line),
            $def['text']
        ));

        $nbt = FloatingTextsInstance::makeDefaultNBT($def['pos']);
        $loc = new Location(
            $def['pos']['x'], $def['pos']['y'], $def['pos']['z'],
            $world, 0.0, 0.0
        );
        $ent = new FloatingTextEntity($loc, $nbt);
        $ent->setText(C::colorize($text));
        $ent->spawnToAll();

        FloatingTextsInstance::$particles['spawn'] = $ent;
    }

    /**
     * @priority HIGHEST
     */
    public function onDamage(EntityDamageByEntityEvent $event): void {
        if ($event->isCancelled()) return;

        $player = $event->getEntity();
        $damager = $event->getDamager();
        
        if (!($player instanceof Player) || !($damager instanceof Player)) return;

        $message = C::colorize("&r&l&c(!) &r&cYou are now in combat!");

        foreach ([$player, $damager] as $combatant) {
            $combatantName = $combatant->getName();
            if (!isset(self::$combatPlayers[strtolower($combatantName)])) {
                $combatant->sendMessage($message);
            }
            self::$combatPlayers[strtolower($combatantName)] = microtime(true) + $this->combatTime;
        }
    }

    public function onCommandPreprocess(CommandEvent $event): void {
        $sender = $event->getSender();
        if (!$sender instanceof Player) return;
        $senderName = $sender->getName();

        if (isset(self::$combatPlayers[strtolower($senderName)])) {
            $command = strtolower(explode(' ', $event->getCommand(), 2)[0]);
            
            if ($this->banAllCommands || isset($this->bannedCommandsMap[$command])) {
                $sender->sendMessage(C::colorize(
                    "&r&l&c(!) &r&cYou cannot use this command while in combat!"
                ));
                $event->cancel();
            }
        }
    }

    /**
     * @priority HIGH
     * @ignoreCancelled
     */
    public function onChat(PlayerChatEvent $event): void {
        $sender = $event->getPlayer();
        $session = Loader::getPlayerManager()->getSession($sender);
        if ($session === null) {
            return;
        }
        
        $sender = $event->getPlayer();
        $session = Loader::getPlayerManager()->getSession($sender);
        if ($session === null) {
            return;
        }
        
        $message = $event->getMessage();
        $server = Loader::getInstance()->getServer();
        $formatted = "";
        
        if (strpos($message, "#") === 0 && $sender->hasPermission("aetheris.staff-chat")) {
            $message = ltrim(substr($message, 1));
            $formatted = "§l§6[STAFF] §r§6{$sender->getName()} » §6{$message}";
            $event->cancel();
            foreach ($server->getOnlinePlayers() as $player) {
                if ($player->hasPermission("aetheris.staff-chat")) {
                    $player->sendMessage(C::colorize($formatted));
                }
            }
            return;
        }
        
        $currentChat = $session->getCurrentChat();
        
        if ($currentChat === ChatTypes::STAFF) {
            $formatted = "§l§6[STAFF] §r§6{$sender->getName()} » §6{$message}";
            $event->cancel();
            foreach ($server->getOnlinePlayers() as $player) {
                if ($player->hasPermission("aetheris.staff-chat")) {
                    $player->sendMessage(C::colorize($formatted));
                }
            }
            return;
        }

        if ($currentChat === ChatTypes::ISLAND) {
            $formatted = "&r&l&a{$sender->getName()} &r&2» &r&a{$message}";
            $event->cancel();
            $skyblock = SkyBlockManager::getInstance()->getSkyBlockByUuid($session->getSkyblock());

            foreach ($server->getOnlinePlayers() as $player) {
                if ($skyblock !== null && $skyblock->isMember($player->getUniqueId())) {
                    $player->sendMessage(C::colorize($formatted));
                }
            }
        }
    }

    public function onSpawnerPlace(BlockPlaceEvent $event) {
        if ($event->isCancelled()) return;

        $item = $event->getItem();

        if (!$item instanceof ItemBlock) return;

        $block = $item->getBlock();

        if (!$block instanceof MonsterSpawner || $block instanceof CustomSpawner) {
            return;
        }

        $transaction = $event->getTransaction();

        foreach ($transaction->getBlocks() as [$x, $y, $z, $transBlock]) {
            $transaction->addBlock($transBlock->getPosition()->asVector3(), SpawnerItem::MONSTER_SPAWNER()->setLegacyEntityId(SpawnerItem::getSpawnerEntityId($item)));
        }
    }

    public function onSpawnerBreak(BlockBreakEvent $event): void {
        if ($event->isCancelled()) return;

        $item = $event->getItem();
        $tile = ($position = $event->getBlock()->getPosition())->getWorld()->getTile($position);

        if (!$tile instanceof SpawnerTile || !$item instanceof Pickaxe || !$item->hasEnchantment(VanillaEnchantments::SILK_TOUCH())) return;

        $event->setDrops([StringToItemParser::getInstance()->parse(Utils::convertMobIdToName($tile->getLegacyEntityId()) . "_spawner") ?? SpawnerItem::MONSTER_SPAWNER()->asItem()]);
    }

    public function onChunkLoad(ChunkLoadEvent $event): void {
        $chunk = $event->getChunk();
        $world = $event->getWorld();

        foreach ($chunk->getTiles() as $tile) {
            if ($tile instanceof SpawnerTile) {
                $world->scheduleDelayedBlockUpdate($tile->getPosition(), 1);
            }
        }
    }

    public function chatModeration(PlayerChatEvent $event): void {
        $message = $event->getMessage();
        $player = $event->getPlayer();
        $name = $player->getName();

        $slurs = [
            '/\bnigg[ae]r?\b/i'
        ];

        foreach ($slurs as $slur) {
            if (preg_match($slur, $message)) {
                $event->cancel();
                $player->sendMessage(C::colorize("&r&l&c(!) &r&cYour message contained prohibited language!"));
                return;
            }
        }

        if (!$player->hasPermission("aetheris.chat.advertise.bypass")) {
            if (preg_match('/\b([a-z0-9-]+\.)+(com|net|org|gg|co|xyz|io|me|us|uk|ru|ca|de|fr|au|nl|info|biz|site|store)\b/i', $message)) {
                $event->cancel();
                $player->sendMessage(C::colorize("&r&l&c(!) &r&cAdvertising is not allowed!"));
                return;
            }
        }

        $now = microtime(true);
        $lastMsg = self::$lastMessages[$name] ?? "";
        $lastTime = self::$lastMessageTime[$name] ?? 0;

        if ($now - $lastTime < 0.5) {
            $event->cancel();
            $player->sendMessage(C::colorize("&r&l&c(!) &r&cYou are sending messages too quickly!"));
            return;
        }

        if (trim(strtolower($message)) === trim(strtolower($lastMsg))) {
            $event->cancel();
            $player->sendMessage(C::colorize("&r&l&c(!) &r&cYou are sending the same message too quickly!"));
            return;
        }

        self::$lastMessages[$name] = $message;
        self::$lastMessageTime[$name] = $now;
    }

    public function onPlayerEmbed(PlayerChatEvent $event): void {
        $message = $event->getMessage();
        $player = $event->getPlayer();

        if (str_contains($message, '[brag]')) {
            $message = str_replace(
                '[brag]',
                C::colorize("&r&f[&d{$player->getName()}&f's Inventory]"),
                $message
            );
            BragTracker::setLastInventoryBrag($player);
        }

        if (str_contains($message, '[item]')) {
            $item = $player->getInventory()->getItemInHand();
            $replacement = $item->isNull() || $item->getCount() === 0
                ? C::colorize("&r&f[Air]")
                : C::colorize("&r&f[" . ($item->getCount() > 1 ? "&d" : "&f") . "{$item->getName()}" . ($item->getCount() > 1 ? " x{$item->getCount()}" : "") . "&f]");
            $message = str_replace('[item]', $replacement, $message);
            ChatItemTracker::setLastItem($player, $item);
        }

        $event->setMessage($message);
    }

    public function onSpawnWorldVoidBypass(PlayerMoveEvent $event): void {
        $player = $event->getPlayer();
        $world = $player->getWorld();

        if ($world->getFolderName() === "world") {
            $y = $player->getPosition()->getY();
            if ($y < 83 || $y > 256) {
                $player->teleport($world->getSpawnLocation());
            }
        }
    }

    /** @priority MONITOR */
    public function onItemSpawn(ItemSpawnEvent $event): void {
        $entity = $event->getEntity();
        if ($entity instanceof ItemEntity) {
            Utils::setItemEntityNameTag($entity);
        }
    }

    /** @priority MONITOR */
    public function onItemMerge(ItemMergeEvent $event): void {
        $entity = $event->getEntity();
        $target = $event->getTarget();
        if ($entity instanceof ItemEntity && $target instanceof ItemEntity) {
            $count = $entity->getItem()->getCount() + $target->getItem()->getCount();
            Utils::setItemEntityNameTag($target, $count);
        }
    }

    public function onVoidDamage(EntityDamageEvent $event): void {
        $entity = $event->getEntity();

        if ($entity instanceof Player && $event->getCause() === EntityDamageEvent::CAUSE_VOID) {
            $event->cancel();
            $world = $entity->getWorld();
            $spawn = $world->getSpawnLocation();

            if ($world->getFolderName() === "world") { // Change "world" to your default world folder name if different
                $spawn = $spawn->withComponents($spawn->getX(), 83, $spawn->getZ());
            }

            $entity->teleport($spawn);
            $entity->sendMessage("§bYou fell into the void and were sent to spawn!");
        }
    }
    
    /**
     * @param PlayerInteractEvent $event
     * @return void
     */
    public function onInteract(PlayerInteractEvent $event) : void
    {
        if ($event->getAction() !== PlayerInteractEvent::RIGHT_CLICK_BLOCK) return;
        $block = $event->getBlock();
        if (!$block instanceof Anvil) return;
        $player = $event->getPlayer();
        $this->registry->register($player, $block);
    }

    /**
     * @param InventoryCloseEvent $event
     * @return void
     */
    public function onInventoryClose(InventoryCloseEvent $event) : void
    {
        $player = $event->getPlayer();
        $inv = $event->getInventory();

        if($inv instanceof AnvilInventory)
            $this->registry->remove($player);
    }

    /**
     * @param DataPacketReceiveEvent $event
     * @return void
     */
    public function onReceive(DataPacketReceiveEvent $event) : void
    {
        $player = $event->getOrigin()->getPlayer();

        if(!is_null($player))
        {
            $inv = $player->getCurrentWindow();

            //Anvil window
            if($inv instanceof AnvilInventory)
            {
                $pk = $event->getPacket();
                if($pk instanceof ItemStackRequestPacket)
                {
                    foreach($pk->getRequests() as $request)
                    {
                        foreach ($request->getActions() as $action)
                        {
                            if ($action instanceof PlaceStackRequestAction)
                            {
                                if ($action->getSource()->getContainerName()->getContainerId() === ContainerUIIds::CREATED_OUTPUT) //Picking up the object (Result)
                                {
                                    try
                                    {
                                        if (!$this->anvilManager->processResult($player, $inv, $request->getFilterStrings()))
                                            $event->cancel();
                                    }
                                    catch (InvalidArgumentException)
                                    {
                                        throw new InvalidArgumentException("Invalid argument");
                                    }
                                }
                            }
                        }
                    }
                }
            }
        }
    }

    public function onPlayerDeath(PlayerDeathEvent $event): void {
        $player = $event->getPlayer();
        $this->registry->remove($player);
    }


    public function onUpdateKD(PlayerDeathEvent $event): void {
        $player = $event->getPlayer();
        $session = PlayerManager::getInstance()->getSession($player);
        if ($session !== null) {
            $session->addDeaths(1);
        }

        $lastDamage = $player->getLastDamageCause();
        if ($lastDamage instanceof EntityDamageByEntityEvent) {
            $damager = $lastDamage->getDamager();
            if ($damager instanceof Player) {
                $killerSession = PlayerManager::getInstance()->getSession($damager);
                if ($killerSession !== null) {
                    $killerSession->addKills(1);
                }
            }
        }
    }

    public function onEntityDeath(EntityDeathEvent $event): void {
        $entity = $event->getEntity();
        $last = $entity->getLastDamageCause();

        if (!$last instanceof EntityDamageByEntityEvent) return;

        $damager = $last->getDamager();

        if (!$damager instanceof Player) return;

        $pos = $damager->getPosition();
        $damager->getWorld()->addSound($pos->asVector3(), new XpCollectSound());
    }

    public function onBlockBreak(BlockBreakEvent $event): void {
        $player = $event->getPlayer();
        $block = $event->getBlock();

        if (!Utils::isFullyGrownCrop($block)) return;

        $pos = $player->getPosition();
        $world = $pos->getWorld();

        $world->addSound($pos->asVector3(), new XpCollectSound());
    }
}