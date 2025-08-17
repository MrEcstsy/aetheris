<?php

declare(strict_types=1);

namespace ecstsy\AetherisRecode;

use CameraAPI\CameraHandler;
use CortexPE\Commando\PacketHooker;
use cosmicpe\blockdata\world\BlockDataWorldManager;
use ecstsy\AetherisRecode\blocks\tiles\SpawnerTile;
use ecstsy\AetherisRecode\commands\BalanceCommand;
use ecstsy\AetherisRecode\commands\BanCommand;
use ecstsy\AetherisRecode\commands\BanLookupCommand;
use ecstsy\AetherisRecode\commands\CoinFlipCommand;
use ecstsy\AetherisRecode\commands\CollectCommand;
use ecstsy\AetherisRecode\commands\EcoCommand;
use ecstsy\AetherisRecode\commands\EnchantmentsCommand;
use ecstsy\AetherisRecode\commands\EtherealGuardCommand;
use ecstsy\AetherisRecode\commands\ExpCommand;
use ecstsy\AetherisRecode\commands\FeedCommand;
use ecstsy\AetherisRecode\commands\FlyCommand;
use ecstsy\AetherisRecode\commands\FlySpeedCommand;
use ecstsy\AetherisRecode\commands\FreezeCommand;
use ecstsy\AetherisRecode\commands\GamemodeCommand;
use ecstsy\AetherisRecode\commands\GiveItemCommand;
use ecstsy\AetherisRecode\commands\HealCommand;
use ecstsy\AetherisRecode\commands\InfractionsCommand;
use ecstsy\AetherisRecode\commands\JackpotCommand;
use ecstsy\AetherisRecode\commands\KitCommand;
use ecstsy\AetherisRecode\commands\MainMenuCommand;
use ecstsy\AetherisRecode\commands\SeeBragCommand;
use ecstsy\AetherisRecode\commands\SeeItemCommand;
use ecstsy\AetherisRecode\commands\SettingsCommand;
use ecstsy\AetherisRecode\commands\SkillsCommand;
use ecstsy\AetherisRecode\commands\SkyBlockCommand;
use ecstsy\AetherisRecode\commands\SpawnCommand;
use ecstsy\AetherisRecode\commands\StaffChatCommand;
use ecstsy\AetherisRecode\commands\TradeCommand;
use ecstsy\AetherisRecode\commands\UnfreezeCommand;
use ecstsy\AetherisRecode\commands\WarnCommand;
use ecstsy\AetherisRecode\enchantments\CustomEnchantments;
use ecstsy\AetherisRecode\listeners\AetherisListener;
use ecstsy\AetherisRecode\listeners\AntiCheatListener;
use ecstsy\AetherisRecode\listeners\CrateListener;
use ecstsy\AetherisRecode\listeners\EnchantmentListener;
use ecstsy\AetherisRecode\listeners\ItemListener;
use ecstsy\AetherisRecode\listeners\RegionListener;
use ecstsy\AetherisRecode\listeners\SkillsListener;
use ecstsy\AetherisRecode\listeners\SkyblockListener;
use ecstsy\AetherisRecode\player\PlayerManager;
use ecstsy\AetherisRecode\server\crates\CrateManager;
use ecstsy\AetherisRecode\server\FloatingTextsInstance;
use ecstsy\AetherisRecode\server\items\AetherisItem;
use ecstsy\AetherisRecode\server\items\stardrops\StarDrop;
use ecstsy\AetherisRecode\server\JackpotInstance;
use ecstsy\AetherisRecode\server\PunishmentInstance;
use ecstsy\AetherisRecode\server\regions\RegionManager;
use ecstsy\AetherisRecode\skyblock\SkyBlockManager;
use ecstsy\AetherisRecode\tasks\ItemEntityNameTagUpdateTask;
use ecstsy\AetherisRecode\utils\QueryStmts;
use ecstsy\AetherisRecode\utils\SpawnerItem;
use ecstsy\AetherisRecode\utils\Utils;
use JackMD\ConfigUpdater\ConfigUpdater;
use libCustomPack\libCustomPack;
use muqsit\invmenu\InvMenuHandler;
use pocketmine\block\tile\TileFactory;
use pocketmine\plugin\PluginBase;
use pocketmine\resourcepacks\ZippedResourcePack;
use pocketmine\Server;
use pocketmine\utils\SingletonTrait;
use poggit\libasynql\DataConnector;
use poggit\libasynql\libasynql;

use function PHPSTORM_META\map;

final class Loader extends PluginBase
{

    use SingletonTrait;

    public const SERVER_PREFIX = "&r&l&dETHEREAL&fHUB &r&d► ";
    public const SERVER_TITLE = "&r&3✦ Ethereal Hub &3✦";
    public const ANTICHEAT_PREFIX = "&r&l&dETHEREAL&fGUARD &r&8► ";

    public const PERMISSION_PREFIX = "aetheris.";

    public const NO_PERMISSION = "&r&cYou do not have permission to use this command!";

    public const TYPE_DYNAMIC_PREFIX = "muqsit:customsizedinvmenu_";

    public static DataConnector $connector;

    public static PlayerManager $playerManager;

    public static SkyBlockManager $skyblockManager;

    private BlockDataWorldManager $blockDataWorldManager;

    private static ?ZippedResourcePack $resourcePack;

    private static JackpotInstance $jackpotInstance;

    private static PunishmentInstance $punishmentInstance;

    protected function onLoad(): void
    {
        self::setInstance($this);
    }

    public function onEnable(): void
    {
        ConfigUpdater::checkUpdate($this, $this->getConfig(), "version", 1);

        $regionManager = new RegionManager();
        $listeners = [
            new AetherisListener(),
            new SkyblockListener(),
            new ItemListener(),
            new SkillsListener(),
            new RegionListener($regionManager),
            new CrateListener(),
            new EnchantmentListener(),
            new AntiCheatListener(),
        ];

        foreach ($listeners as $listener) {
            $this->getServer()->getPluginManager()->registerEvents($listener, $this);
        }

        self::$connector = libasynql::create($this, ["type" => "sqlite", "sqlite" => ["file" => "sqlite.sql"], "worker-limit" => 2], ["sqlite" => "sqlite.sql"]);
        self::$connector->executeGeneric(QueryStmts::PLAYERS_INIT);
        self::$connector->executeGeneric(QueryStmts::ISLANDS_INIT);
        self::$connector->executeGeneric(QueryStmts::COINFLIP_INIT);
        self::$connector->executeGeneric(QueryStmts::JACKPOT_INIT);
        self::$connector->executeGeneric(QueryStmts::JACKPOT_STATS_INIT);
        self::$connector->executeGeneric(QueryStmts::PUNISHMENTS_INIT);
        self::$connector->executeGeneric(QueryStmts::ACTIVE_PUNISHMENTS_INIT);
        self::$connector->executeGeneric(QueryStmts::ANTICHEAT_LOGS_INIT);
        self::$connector->waitAll();

        self::$playerManager = new PlayerManager($this);
        self::$skyblockManager = new SkyBlockManager($this);
        self::$jackpotInstance = new JackpotInstance();
        self::$punishmentInstance = new PunishmentInstance();

        $unregisteredCommands = ["gamemode", "ban", "xp"];
        foreach ($unregisteredCommands as $command) {
            $this->getServer()->getCommandMap()->unregister(Server::getInstance()->getCommandMap()->getCommand($command));
        }

        $this->getServer()->getCommandMap()->registerAll('Aetheris', [
            new SkyBlockCommand($this, "island", "Manage your SkyBlock island", ["is", "skyblock"]),
            new EcoCommand($this, "economy", "Economy management commands", ['eco']),
            new BalanceCommand($this, "balance", "Check your balance", ['bal']),
            new MainMenuCommand($this, "menu", "Open the main menu"),
            new KitCommand($this, "kit", "Access the kit menu"),
            new SkillsCommand($this, "skills", "View your skills"),
            new CollectCommand($this, "collect", "Open your collection menu", ["collection"]),
            new SettingsCommand($this, "settings", "Adjust your settings"),
            new CoinFlipCommand($this, "coinflip", "Play coinflip", ["cf"]),
            new StaffChatCommand($this, "staffchat", "Toggle the staff chat", ["sc"]),
            new SeeItemCommand($this, "seeitem", "See the last [item] from a player", ["citem"]),
            new SeeBragCommand($this, "seebrag", "See a player's last [brag]", ["cbrag"]),
            new FlySpeedCommand($this, "flyspeed", "Set your fly speed", ["flyspeed"]),
            new GiveItemCommand($this, "giveitem", "Get a custom aetheris item", ["gi"]),
            new SpawnCommand($this, "spawn", "Teleport to spawn"),
            new EnchantmentsCommand($this, "aetherisenchantments", "Enchantments commands", ["ae"]),
            new GamemodeCommand($this, "gamemode", "Change your gamemode", ["gm", "gmc", "gms", "gma", "gmsp"]),
            new FlyCommand($this, "fly", "Toggle flight"),
            new JackpotCommand($this, "jackpot", "View the jackpot", ["jp"]),
            new FreezeCommand($this, "freeze", "Freeze a player", ["f"]),
            new UnfreezeCommand($this, "unfreeze", "Unfreeze a player", ["uf"]),
            new WarnCommand($this, "warn", "Warn a player", ["w"]),
            new InfractionsCommand($this, "infractions", "View player infractions", ["i"]),
            new BanCommand($this, "ban", "Ban a player"),
            new BanLookupCommand($this, "banlookup", "Lookup banned players", ["bl"]),
            new ExpCommand($this, "exp", "View your exp", ["xp"]),
            new EtherealGuardCommand($this, "etherealguard", "Anti-cheat commands", ["eg"]),
            new TradeCommand($this, "trade", "Trade with a player", ["t"]),
            new FeedCommand($this, "feed", "Restore your hunger"),
            new HealCommand($this, "heal", "Restore your health"),
        ]);

        if (!InvMenuHandler::isRegistered()) {
            InvMenuHandler::register($this);
        }

        if (!CameraHandler::isRegistered()) {
            CameraHandler::register($this);
        }

        if (!PacketHooker::isRegistered()) {
            PacketHooker::register($this);
        }

        //libCustomPack::registerResourcePack(self::$resourcePack = libCustomPack::generatePackFromResources($this));
        Utils::initCustomSizedInvMenu();
        $this->getLogger()->info("Resource pack loaded");

        SpawnerItem::getAll();
        SpawnerItem::initHack();
        TileFactory::getInstance()->register(SpawnerTile::class, ['MobSpawner', 'minecraft:mob_spawner']);
        Utils::initDispenserMenu();
        Utils::registerAetherisEntities();
        FloatingTextsInstance::register();
        AetherisItem::init();
        Utils::initRegions($regionManager);

        Utils::initCratePositions();
        Utils::initHandlers();
        CrateManager::init();
        StarDrop::init();
        $this->getScheduler()->scheduleRepeatingTask(new ItemEntityNameTagUpdateTask($this), 20); 

        CustomEnchantments::getAll();
        Utils::initEnchantHandlers();

        CrateManager::scheduleCrateLetterAnimation();
    }

    public function onDisable(): void
    {
        if (isset(self::$connector)) {
            self::$connector->close();
        }

        //libCustomPack::unregisterResourcePack(self::$resourcePack);

        $this->getLogger()->info("resource pack unloaded");
        //unlink(Path::join($this->getDataFolder(), self::$resourcePack->getPackName() . ".mcpack"));

        FloatingTextsInstance::removeAll();
    }

    public static function getDatabase(): DataConnector
    {
        return self::$connector;
    }

    public static function getPlayerManager(): PlayerManager
    {
        return self::$playerManager;
    }

    public static function getSkyBlockManager(): SkyBlockManager
    {
        return self::$skyblockManager;
    }

    public static function getBlockDataWorldManager(): BlockDataWorldManager
    {
        return self::$instance->blockDataWorldManager;
    }

    public static function getJackpotInstance(): JackpotInstance
    {
        return self::$jackpotInstance;
    }

    public static function getPunishmentInstance(): PunishmentInstance
    {
        return self::$punishmentInstance;
    }
}
