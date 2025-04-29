<?php

declare(strict_types=1);

namespace ecstsy\AetherisRecode;

use CameraAPI\CameraHandler;
use cosmicpe\blockdata\BlockDataFactory;
use cosmicpe\blockdata\world\BlockDataWorldManager;
use ecstsy\AetherisRecode\commands\BalanceCommand;
use ecstsy\AetherisRecode\commands\CoinFlipCommand;
use ecstsy\AetherisRecode\commands\CollectCommand;
use ecstsy\AetherisRecode\commands\EcoCommand;
use ecstsy\AetherisRecode\commands\KitCommand;
use ecstsy\AetherisRecode\commands\MainMenuCommand;
use ecstsy\AetherisRecode\commands\SettingsCommand;
use ecstsy\AetherisRecode\commands\SkillsCommand;
use ecstsy\AetherisRecode\commands\SkyBlockCommand;
use ecstsy\AetherisRecode\commands\StaffChatCommand;
use ecstsy\AetherisRecode\listeners\AetherisListener;
use ecstsy\AetherisRecode\listeners\ItemListener;
use ecstsy\AetherisRecode\listeners\SkillsListener;
use ecstsy\AetherisRecode\listeners\SkyblockListener;
use ecstsy\AetherisRecode\player\PlayerManager;
use ecstsy\AetherisRecode\player\skills\SkillManager;
use ecstsy\AetherisRecode\skyblock\SkyBlockManager;
use ecstsy\AetherisRecode\spawners\SpawnerData;
use ecstsy\AetherisRecode\utils\QueryStmts;
use ecstsy\AetherisRecode\utils\Utils;
use JackMD\ConfigUpdater\ConfigUpdater;
use libCustomPack\libCustomPack;
use muqsit\invmenu\InvMenuHandler;
use pocketmine\plugin\PluginBase;
use pocketmine\resourcepacks\ZippedResourcePack;
use pocketmine\utils\SingletonTrait;
use poggit\libasynql\DataConnector;
use poggit\libasynql\libasynql;
use Symfony\Component\Filesystem\Path;

final class Loader extends PluginBase
{

    use SingletonTrait;

    public const SERVER_TITLE = "&r&3✦ Ethereal Hub &3✦";

    public const PERMISSION_PREFIX = "aetheris.";

    public const NO_PERMISSION = "&r&cYou do not have permission to use this command!";

    public const TYPE_DYNAMIC_PREFIX = "muqsit:customsizedinvmenu_";

    public static DataConnector $connector;

    public static PlayerManager $playerManager;

    public static SkyBlockManager $skyblockManager;

    public static SkillManager $skillManager;

    private BlockDataWorldManager $blockDataWorldManager;

    private static ?ZippedResourcePack $resourcePack;

    protected function onLoad(): void
    {
        self::setInstance($this);
    }

    public function onEnable(): void
    {
        ConfigUpdater::checkUpdate($this, $this->getConfig(), "version", 1);

        $listeners = [
            new AetherisListener(),
            new SkyblockListener(),
            new ItemListener(),
            new SkillsListener()
        ];

        foreach ($listeners as $listener) {
            $this->getServer()->getPluginManager()->registerEvents($listener, $this);
        }

        self::$connector = libasynql::create($this, ["type" => "sqlite", "sqlite" => ["file" => "sqlite.sql"], "worker-limit" => 2], ["sqlite" => "sqlite.sql"]);
        self::$connector->executeGeneric(QueryStmts::PLAYERS_INIT);
        self::$connector->executeGeneric(QueryStmts::ISLANDS_INIT);
        self::$connector->executeGeneric(QueryStmts::SKILLS_INIT);
        self::$connector->executeGeneric(QueryStmts::COINFLIP_INIT);
        self::$connector->waitAll();

        self::$playerManager = new PlayerManager($this);
        self::$skyblockManager = new SkyBlockManager($this);
        self::$skillManager = new SkillManager($this);


        $this->getServer()->getCommandMap()->registerAll('Aetheris', [
            new SkyBlockCommand($this, "island", "View skyblock information", ["is", "skyblock"]),
            new EcoCommand($this, "economy", "View the economy commands", ['eco']),
            new BalanceCommand($this, "balance", "View your balance", ['bal']),
            new MainMenuCommand($this, "menu", "Open the server's main memu"),
            new KitCommand($this, "kit", "Open the kit menu"),
            new SkillsCommand($this, "skills", "Open the skills menu"),
            new CollectCommand($this, "collect", "Open the collect menu", ["collection"]),
            new SettingsCommand($this, "settings", "Open the settings menu"),
            new CoinFlipCommand($this, "coinflip", "Open cf menu", ["cf"]),
            new StaffChatCommand($this, "staffchat", "Toggle the staff chat", ["sc"])
        ]);

        if (!InvMenuHandler::isRegistered()) {
            InvMenuHandler::register($this);
        }

        if (!CameraHandler::isRegistered()) {
            CameraHandler::register($this);
        }

        libCustomPack::registerResourcePack(self::$resourcePack = libCustomPack::generatePackFromResources($this));
        Utils::initCustomSizedInvMenu();
        $this->getLogger()->info("Resource pack loaded");

        BlockDataFactory::register("AetherisRecode:Spawner", SpawnerData::class);

        $this->blockDataWorldManager = BlockDataWorldManager::create($this);

        
    }

    public function onDisable(): void
    {
        if (isset(self::$connector)) {
            self::$connector->close();
        }

        libCustomPack::unregisterResourcePack(self::$resourcePack);

        $this->getLogger()->info("resource pack unloaded");
        unlink(Path::join($this->getDataFolder(), self::$resourcePack->getPackName() . ".mcpack"));
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

    public static function getSkillManager(): SkillManager
    {
        return self::$skillManager;
    }

    public static function getBlockDataWorldManager(): BlockDataWorldManager
    {
        return self::$instance->blockDataWorldManager;
    }
}
