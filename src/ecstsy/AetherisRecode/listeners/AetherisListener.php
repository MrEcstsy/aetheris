<?php

declare(strict_types=1);

namespace ecstsy\AetherisRecode\listeners;

use ecstsy\AetherisRecode\Loader;
use ecstsy\AetherisRecode\player\PlayerManager;
use ecstsy\AetherisRecode\skyblock\SkyBlockManager;
use ecstsy\AetherisRecode\utils\ChatTypes;
use ecstsy\AetherisRecode\utils\inventory\CustomSizedInvMenu;
use ecstsy\AetherisRecode\utils\Utils;
use ecstsy\MartianUtilities\utils\GeneralUtils;
use pocketmine\event\entity\EntityDamageByEntityEvent;
use pocketmine\event\Listener;
use pocketmine\event\player\PlayerChatEvent;
use pocketmine\event\player\PlayerJoinEvent;
use pocketmine\event\player\PlayerLoginEvent;
use pocketmine\event\player\PlayerQuitEvent;
use pocketmine\event\server\CommandEvent;
use pocketmine\event\world\ChunkLoadEvent;
use pocketmine\player\Player;
use pocketmine\Server;
use pocketmine\utils\TextFormat as C;
use SplObjectStorage;

final class AetherisListener implements Listener {

    public static SplObjectStorage $combatPlayers;
    private float $combatTime;
    private array $bannedCommandsMap;
    private array $bannedCommandsList;
    private bool $banAllCommands;
    private bool $killOnLog;

    public function __construct() {
        $config = GeneralUtils::getConfiguration(Loader::getInstance(), "config.yml");
        self::$combatPlayers = new SplObjectStorage();
        
        $this->combatTime = (float)$config->getNested("settings.combat.time", 30);
        $this->banAllCommands = (bool)$config->getNested("settings.combat.ban-all-commands", false);
        $this->killOnLog = (bool)$config->getNested("settings.combat.kill-on-log", false);
        
        $commands = $config->getNested("settings.combat.banned-commands", []);
        $this->bannedCommandsList = $commands;
        $this->bannedCommandsMap = array_flip($commands);
        Utils::combatTask();
        
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
        $skillManager = Loader::getSkillManager();
    
        $skills = $skillManager->getSkillsByPlayerUuid($player->getUniqueId()->toString());
    
        if (empty($skills)) {
            Utils::initializeSkillsForPlayer($player);
        }

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
            if (!self::$combatPlayers->contains($combatant)) {
                $combatant->sendMessage($message);
            }
            self::$combatPlayers[$combatant] = microtime(true) + $this->combatTime;
        }
    }

    public function onCommandPreprocess(CommandEvent $event): void {
        $sender = $event->getSender();
        if (!$sender instanceof Player) return;

        if (self::$combatPlayers->contains($sender)) {
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

    public function onChunkLoad(ChunkLoadEvent $event): void {
        $chunk = $event->getChunk();
        
    }
}