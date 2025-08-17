<?php

declare(strict_types=1);

namespace ecstsy\AetherisRecode\utils;

use CameraAPI\Instructions\ClearCameraInstruction;
use CameraAPI\Instructions\FadeCameraInstruction;
use ecstsy\AetherisRecode\enchantments\CustomEnchantmentManager;
use ecstsy\AetherisRecode\enchantments\enchants\chestplate\BlazedEnchant;
use ecstsy\AetherisRecode\enchantments\enchants\hoe\AutoPlanterEnchant;
use ecstsy\AetherisRecode\enchantments\enchants\leggings\JellyLegsEnchant;
use ecstsy\AetherisRecode\enchantments\enchants\pickaxe\AutoSmeltEnchant;
use ecstsy\AetherisRecode\enchantments\manager\EnchantmentEventRegistry;
use ecstsy\AetherisRecode\entity\other\FloatingTextEntity;
use ecstsy\AetherisRecode\listeners\AetherisListener;
use ecstsy\AetherisRecode\listeners\CrateListener;
use ecstsy\AetherisRecode\listeners\SkillsListener;
use ecstsy\AetherisRecode\Loader;
use ecstsy\AetherisRecode\player\skills\SkillType;
use ecstsy\AetherisRecode\server\crates\CrateManager;
use ecstsy\AetherisRecode\server\FloatingTextsInstance;
use ecstsy\AetherisRecode\server\items\AetherisItemFactory;
use ecstsy\AetherisRecode\server\regions\Region;
use ecstsy\AetherisRecode\server\regions\RegionManager;
use ecstsy\AetherisRecode\server\regions\RegionPermissions;
use ecstsy\AetherisRecode\skyblock\SkyBlock;
use ecstsy\AetherisRecode\tasks\AutoPlanterTask;
use ecstsy\AetherisRecode\utils\inventory\CustomSizedInvMenuType;
use ecstsy\AetherisRecode\utils\ui\crates\CratePreviewScreen;
use ecstsy\AetherisRecode\utils\ui\crates\CrateRollScreen;
use ecstsy\MartianUtilities\utils\GeneralUtils;
use ecstsy\MartianUtilities\utils\PlayerUtils;
use muqsit\invmenu\InvMenuHandler;
use muqsit\invmenu\type\util\InvMenuTypeBuilders;
use pocketmine\block\Beetroot;
use pocketmine\block\Block;
use pocketmine\block\Carrot;
use pocketmine\block\Crops;
use pocketmine\block\NetherWartPlant;
use pocketmine\block\Potato;
use pocketmine\block\utils\DyeColor;
use pocketmine\block\VanillaBlocks;
use pocketmine\block\Wheat;
use pocketmine\data\bedrock\EnchantmentIdMap;
use pocketmine\entity\effect\EffectInstance;
use pocketmine\entity\effect\VanillaEffects;
use pocketmine\entity\Entity;
use pocketmine\entity\EntityDataHelper;
use pocketmine\entity\EntityFactory;
use pocketmine\entity\Location;
use pocketmine\entity\object\ItemEntity;
use pocketmine\inventory\PlayerInventory;
use pocketmine\item\enchantment\EnchantmentInstance;
use pocketmine\item\enchantment\StringToEnchantmentParser;
use pocketmine\item\Item;
use pocketmine\item\StringToItemParser;
use pocketmine\item\VanillaItems;
use pocketmine\math\Vector3;
use pocketmine\nbt\tag\CompoundTag;
use pocketmine\network\mcpe\cache\StaticPacketCache;
use pocketmine\network\mcpe\protocol\GameRulesChangedPacket;
use pocketmine\network\mcpe\protocol\types\BoolGameRule;
use pocketmine\network\mcpe\protocol\types\FloatGameRule;
use pocketmine\network\mcpe\protocol\types\IntGameRule;
use pocketmine\network\mcpe\protocol\types\inventory\WindowTypes;
use pocketmine\player\Player;
use pocketmine\scheduler\ClosureTask;
use pocketmine\scheduler\Task;
use pocketmine\Server;
use pocketmine\world\Position;
use pocketmine\utils\TextFormat as C;
use pocketmine\world\World;
use Ramsey\Uuid\Uuid;

use function PHPSTORM_META\map;

final class Utils
{
    public const FAKE_ENCH_ID = -1;

    public const TYPE_DISPENSER = 'aetheris:dispenser';

    public static function updateGlowEffect(Item $item): void {
        $root = $item->getNamedTag();
        $martianCES = $root->getCompoundTag("AetherisCES");
        $vanillaEnchants = $item->getEnchantments(); 
        
        if (($martianCES !== null && $martianCES->count() > 0) && count($vanillaEnchants) === 0) {
            self::applyDisplayEnchant($item); 
        } elseif (count($vanillaEnchants) > 0 || ($martianCES !== null && $martianCES->count() > 0)) {
            self::applyDisplayEnchant($item);
        } else {
            self::removeDisplayEnchant($item);
        }
    }

    public static function applyDisplayEnchant(Item $item): void {
        $item->addEnchantment(new EnchantmentInstance(EnchantmentIdMap::getInstance()->fromId(self::FAKE_ENCH_ID)));
    }

    public static function removeDisplayEnchant(Item $item): void {
        $item->removeEnchantment(EnchantmentIdMap::getInstance()->fromId(self::FAKE_ENCH_ID));
    }

    public static function getSkyblockRoleSymbol(?string $role): string {
        if ($role === null) {
            return "";
        }

        switch ($role) {
            case "leader":
                return "***"; 
            case "officer":
                return "**"; 
            case "member":
                return ""; 
            case "recruit":
                return "-"; 
            default:
                return ""; 
        }
    } 

    private static array $roleHierarchy = [
        'visitor' => -1,
        'recruit' => 0,
        'member' => 1,
        'moderator' => 2,
        'co-leader' => 3,
        'leader' => 4
    ];

    /**
     * Determines the color of the ping based on its value.
     *
     * @param int $ping The ping value.
     * @return string The color code for the ping.
     */
    public static function getPingColor(?int $ping): string
    {
        if ($ping === null) return "&a";

        if ($ping <= 50) {
            return "&a";
        } elseif ($ping <= 150) {
            return "&e";
        } else {
            return "&c";
        }
    }

    /**
     * Generates a network bar representation based on the ping value.
     *
     * @param int $ping The ping value.
     * @return string The network bar string.
     */
    public static function getNetworkBar(?int $ping): string
    {
        if ($ping === null) {
            return "■";
        }

        $bars = 5;
        $filledBars = (int) max(1, min($bars, 6 - ceil($ping / 100)));
        $emptyBars = $bars - $filledBars;

        return str_repeat("&a■", $filledBars) . str_repeat("&7■", $emptyBars);
    }

    /**
     * Helper function to create a stained glass pane item with a specific color.
     *
     * @param DyeColor $color The color of the glass pane.
     * @return Item The stained glass pane item.
     */
    public static function createGlassPane(DyeColor $color): Item
    {
        $pane = VanillaBlocks::STAINED_GLASS_PANE()->setColor($color)->asItem();
        $pane->setCustomName(C::colorize("&r&7"));
        return $pane;
    }

    public static function timedTeleport(Player $player, Position $targetPosition, string $preTeleportMessage, string $postTeleportMessage, string $preTeleportSound = "beacon.activate", string $postTeleportSound = "mob.endermen.portal", int $delayTicks = 40): void
    {
        PlayerUtils::playSound($player, $preTeleportSound);

        $player->sendMessage(C::colorize("&r&l&a(!) &r&2" . $preTeleportMessage));
        $player->sendActionBarMessage(C::colorize("&r&l&6Sneak to skip animation"));

        $center = $player->getPosition();
        $world = $player->getWorld();

        $fadeOut = new FadeCameraInstruction();
        $fadeOut->setTime(1, 3, 0);
        $fadeOut->setColor(0, 0, 0);
        $fadeOut->send($player);

        $taskHandler = Loader::getInstance()->getScheduler()->scheduleRepeatingTask(new ClosureTask(function () use ($player, $center, $world) {
            GeneralUtils::addParticleToPosition($center, "minecraft:mob_portal");
        }), 4);

        Loader::getInstance()->getScheduler()->scheduleDelayedTask(new ClosureTask(function () use ($player, $targetPosition, $postTeleportMessage, $postTeleportSound, $taskHandler) {
            $taskHandler->cancel();

            $player->teleport($targetPosition);
            PlayerUtils::playSound($player, $postTeleportSound);

            $fadeIn = new FadeCameraInstruction();
            $fadeIn->setTime(0, 2, 1);
            $fadeIn->setColor(0, 0, 0);
            $fadeIn->send($player);

            $player->sendMessage(C::colorize("&r&l&a(!) &r&2" . $postTeleportMessage));
        }), $delayTicks);

        Loader::getInstance()->getScheduler()->scheduleDelayedTask(new ClosureTask(function () use ($player) {
            $clearCamera = new ClearCameraInstruction();
            $clearCamera->setClear(true);
            $clearCamera->setRemoveTarget(true);
            $clearCamera->send($player);
        }), $delayTicks + 10);
    }

    public static function getValidRoles(): array
    {
        return [
            'visitor' => ['visitor', 'v'],
            'recruit' => ['recruit', 'r'],
            'member' => ['member', 'm'],
            'moderator' => ['moderator', 'mod'],
            'co-leader' => ['co-leader', 'co'],
            'leader' => ['leader', 'l']
        ];
    }

    public static function isValidRole(string $role): bool
    {
        $roles = self::getValidRoles();
        foreach ($roles as $aliases) {
            if (in_array(strtolower($role), $aliases, true)) {
                return true;
            }
        }
        return false;
    }

    public static function canPromote(string $viewerRole, string $targetRole): bool
    {
        $viewerRole = self::normalizeRole($viewerRole);
        $targetRole = self::normalizeRole($targetRole);

        if ($viewerRole === null || $targetRole === null) {
            return false;
        }

        return self::$roleHierarchy[$viewerRole] > self::$roleHierarchy[$targetRole];
    }

    public static function canDemote(string $viewerRole, string $targetRole): bool
    {
        $viewerRole = self::normalizeRole($viewerRole);
        $targetRole = self::normalizeRole($targetRole);

        if ($viewerRole === null || $targetRole === null) {
            return false;
        }

        return self::$roleHierarchy[$viewerRole] > self::$roleHierarchy[$targetRole];
    }


    public static function getNextRole(string $currentRole): ?string
    {
        $currentRole = self::normalizeRole($currentRole);
        if ($currentRole === null) {
            return $currentRole;
        }

        $hierarchy = array_flip(self::$roleHierarchy);
        $currentLevel = self::$roleHierarchy[$currentRole] ?? null;

        if ($currentLevel === null || $currentLevel >= max(self::$roleHierarchy)) {
            return $currentRole;
        }

        return $hierarchy[$currentLevel + 1];
    }


    public static function getPreviousRole(string $currentRole): string
    {
        $hierarchy = array_flip(self::$roleHierarchy);

        $currentLevel = self::$roleHierarchy[$currentRole] ?? null;

        if ($currentLevel === null || $currentLevel <= min(self::$roleHierarchy)) {
            return $currentRole;
        }

        return $hierarchy[$currentLevel - 1];
    }

    public static function normalizeRole(string $role): ?string
    {
        $roles = self::getValidRoles();
        foreach ($roles as $canonicalRole => $aliases) {
            if (in_array(strtolower($role), $aliases, true)) {
                return $canonicalRole;
            }
        }
        return null;
    }

    public static function getKitRankKitItems(string $kit): array
    {
        $enchantments = [
            'initiate' => [
                'protection' => 1,
                'feather_falling' => 1,
                'sharpness' => 1,
                'efficiency' => 1,
            ],
            'explorer' => [
                'protection' => 2,
                'feather_falling' => 2,
                //'depth_strider' => 1,
                'sharpness' => 2,
                'unbreaking' => 1,
                'efficiency' => 2,
                'fortune' => 1,
            ],
            'champion' => [
                'protection' => 3,
                'thorns' => 1,
                'feather_falling' => 3,
                //'depth_strider' => 2,
                'sharpness' => 3,
                'fire_aspect' => 1,
                'efficiency' => 3,
                'fortune' => 2,
                'power' => 2,
                'flame' => 1,
            ],
            'warden' => [
                'protection' => 4,
                'thorns' => 2,
                'feather_falling' => 4,
                //'depth_strider' => 3,
                //'soul_speed' => 1,
                'sharpness' => 4,
                'fire_aspect' => 2,
                //'looting' => 2,
                'unbreaking' => 3,
                'efficiency' => 4,
                'fortune' => 3,
                'power' => 4,
                'flame' => 1,
                'infinity' => 1,
                'punch' => 1,
            ],
            'aetherian' => [
                'protection' => 5,
                'unbreaking' => 3,
                'feather_falling' => 5,
                //'depth_strider' => 3,
                //'soul_speed' => 3,
                'frost_walker' => 2,
                'sharpness' => 5,
                'fire_aspect' => 2,
                //'looting' => 3,
                'efficiency' => 5,
                'fortune' => 4,
                'power' => 5,
                'flame' => 1,
                'infinity' => 1,
                'punch' => 2,
            ]
        ];

        $createItem = function ($item, string $name, array $enchants) {
            $item->setCustomName(C::colorize($name));
            foreach ($enchants as $enchant => $level) {
                $instance = new EnchantmentInstance(
                    StringToEnchantmentParser::getInstance()->parse($enchant),
                    $level
                );
                $item->addEnchantment($instance);
            }
            return $item;
        };

        $items = [];
        switch ($kit) {
            case 'initiate':
                $enchants = $enchantments['initiate'];
                $items = [
                    $createItem(VanillaItems::IRON_HELMET(), "&r&9Initiate's Helm", ['protection' => $enchants['protection']]),
                    $createItem(VanillaItems::IRON_CHESTPLATE(), "&r&9Initiate's Chestguard", ['protection' => $enchants['protection']]),
                    $createItem(VanillaItems::IRON_LEGGINGS(), "&r&9Initiate's Leggings", ['protection' => $enchants['protection']]),
                    $createItem(VanillaItems::IRON_BOOTS(), "&r&9Initiate's Striders", [
                        'protection' => $enchants['protection'],
                        'feather_falling' => $enchants['feather_falling'],
                    ]),
                    $createItem(VanillaItems::IRON_SWORD(), "&r&9Initiate's Blade", ['sharpness' => $enchants['sharpness']]),
                    $createItem(VanillaItems::IRON_PICKAXE(), "&r&9Initiate's Pickaxe", ['efficiency' => $enchants['efficiency']]),
                    VanillaItems::BREAD()->setCount(16),
                    VanillaItems::GOLDEN_APPLE()->setCount(2),
                ];
                break;

            case 'explorer':
                $enchants = $enchantments['explorer'];
                $items = [
                    $createItem(VanillaItems::DIAMOND_HELMET(), "&r&cExplorer's Vigilance", ['protection' => $enchants['protection']]),
                    $createItem(VanillaItems::DIAMOND_CHESTPLATE(), "&r&cExplorer's Resolve", ['protection' => $enchants['protection']]),
                    $createItem(VanillaItems::DIAMOND_LEGGINGS(), "&r&cExplorer's Might", ['protection' => $enchants['protection']]),
                    $createItem(VanillaItems::DIAMOND_BOOTS(), "&r&cExplorer's Steps", [
                        'protection' => $enchants['protection'],
                        'feather_falling' => $enchants['feather_falling'],
                    ]),
                    $createItem(VanillaItems::DIAMOND_SWORD(), "&r&cExplorer's Blade", [
                        'sharpness' => $enchants['sharpness'],
                        'unbreaking' => $enchants['unbreaking'],
                    ]),
                    $createItem(VanillaItems::DIAMOND_PICKAXE(), "&r&cExplorer's Pickaxe", [
                        'efficiency' => $enchants['efficiency'],
                        'fortune' => $enchants['fortune'],
                    ]),
                    VanillaItems::STEAK()->setCount(32),
                    VanillaItems::GOLDEN_APPLE()->setCount(5),
                ];
                break;

            case 'champion':
                $enchants = $enchantments['champion'];
                $items = [
                    $createItem(VanillaItems::DIAMOND_HELMET(), "&r&6Champion Crown", [
                        'protection' => $enchants['protection'],
                        'thorns' => $enchants['thorns'],
                    ]),
                    $createItem(VanillaItems::DIAMOND_CHESTPLATE(), "&r&6Champion Heart", [
                        'protection' => $enchants['protection'],
                        'thorns' => $enchants['thorns'],
                    ]),
                    $createItem(VanillaItems::DIAMOND_LEGGINGS(), "&r&6Champion Wrath", [
                        'protection' => $enchants['protection'],
                        'thorns' => $enchants['thorns'],
                    ]),
                    $createItem(VanillaItems::DIAMOND_BOOTS(), "&r&6Champion Striders", [
                        'protection' => $enchants['protection'],
                        'thorns' => $enchants['thorns'],
                        'feather_falling' => $enchants['feather_falling'],
                    ]),
                    $createItem(VanillaItems::DIAMOND_SWORD(), "&r&6Champion Blade", [
                        'sharpness' => $enchants['sharpness'],
                        'fire_aspect' => $enchants['fire_aspect'],
                    ]),
                    $createItem(VanillaItems::DIAMOND_PICKAXE(), "&r&6Champion Pickaxe", [
                        'efficiency' => $enchants['efficiency'],
                        'fortune' => $enchants['fortune'],
                    ]),
                    $createItem(VanillaItems::BOW(), '&r&6Champion Bow', [
                        'power' => $enchants['power'],
                        'flame' => $enchants['flame'],
                    ]),
                    VanillaItems::COOKED_PORKCHOP()->setCount(48),
                    AetherisItemFactory::bankNote(null, 2500),
                    VanillaItems::GOLDEN_APPLE()->setCount(8),
                    VanillaItems::ENCHANTED_GOLDEN_APPLE()->setCount(2),
                ];
                break;
            case 'warden':
                $enchants = $enchantments['warden'];

                $items = [
                    $createItem(VanillaItems::DIAMOND_HELMET(), "&r&aWarden's Aegis", [
                        'protection' => $enchants['protection'],
                        'thorns' => $enchants['thorns'],
                    ]),
                    $createItem(VanillaItems::DIAMOND_CHESTPLATE(), "&r&aWarden's Bastion", [
                        'protection' => $enchants['protection'],
                        'thorns' => $enchants['thorns'],
                    ]),
                    $createItem(VanillaItems::DIAMOND_LEGGINGS(), "&r&aWarden's Defiance", [
                        'protection' => $enchants['protection'],
                        'thorns' => $enchants['thorns'],
                    ]),
                    $createItem(VanillaItems::DIAMOND_BOOTS(), "&r&aWarden's Striders", [
                        'protection' => $enchants['protection'],
                        'thorns' => $enchants['thorns'],
                        'feather_falling' => $enchants['feather_falling'],
                    ]),
                    $createItem(VanillaItems::NETHERITE_SWORD(), "&r&aWarden's Blade", [
                        'sharpness' => $enchants['sharpness'],
                        'fire_aspect' => $enchants['fire_aspect'],
                        'unbreaking' => $enchants['unbreaking'],
                    ]),
                    $createItem(VanillaItems::NETHERITE_PICKAXE(), "&r&aWarden's Pickaxe", [
                        'efficiency' => $enchants['efficiency'],
                        'fortune' => $enchants['fortune'],
                        'unbreaking' => $enchants['unbreaking'],
                    ]),
                    $createItem(VanillaItems::BOW(), "&r&aWarden's Bow", [
                        'power' => $enchants['power'],
                        'flame' => $enchants['flame'],
                        'infinity' => $enchants['infinity'],
                        'punch' => $enchants['punch'],
                    ]),
                    VanillaItems::COOKED_PORKCHOP()->setCount(48),
                    AetherisItemFactory::bankNote(null, 3500),
                    VanillaItems::GOLDEN_APPLE()->setCount(8),
                    VanillaItems::ENCHANTED_GOLDEN_APPLE()->setCount(4),
                ];
                break;
            case 'aetherian':
                $enchants = $enchantments['aetherian'];
                $items = [
                    $createItem(VanillaItems::NETHERITE_HELMET(), "&r&dCrown of Zenith", [
                        'protection' => $enchants['protection'],
                        'unbreaking' => $enchants['unbreaking'],
                    ]),
                    $createItem(VanillaItems::NETHERITE_CHESTPLATE(), "&r&dZenith's Mantle", [
                        'protection' => $enchants['protection'],
                        'unbreaking' => $enchants['unbreaking'],
                    ]),
                    $createItem(VanillaItems::NETHERITE_LEGGINGS(), "&r&dAetherian Guard", [
                        'protection' => $enchants['protection'],
                        'unbreaking' => $enchants['unbreaking'],
                    ]),
                    $createItem(VanillaItems::NETHERITE_BOOTS(), "&r&dSteps of Eternity", [
                        'protection' => $enchants['protection'],
                        'unbreaking' => $enchants['unbreaking'],
                        'feather_falling' => $enchants['feather_falling'],
                    ]),
                    $createItem(VanillaItems::NETHERITE_SWORD(), "&r&dZenith Blade", [
                        'sharpness' => $enchants['sharpness'],
                        'fire_aspect' => $enchants['fire_aspect'],
                        'unbreaking' => $enchants['unbreaking'],
                        //'looting' => $enchants['looting'],
                    ]),
                    $createItem(VanillaItems::NETHERITE_PICKAXE(), "&r&dZenith Pickaxe", [
                        'efficiency' => $enchants['efficiency'],
                        'fortune' => $enchants['fortune'],
                        'unbreaking' => $enchants['unbreaking'],
                    ]),
                    $createItem(VanillaItems::BOW(), "&r&dZenith Bow", [
                        'power' => $enchants['power'],
                        'flame' => $enchants['flame'],
                        'infinity' => $enchants['infinity'],
                        'punch' => $enchants['punch'],
                    ]),
                    VanillaItems::STEAK()->setCount(64),
                    AetherisItemFactory::bankNote(null, 10000),
                    VanillaItems::ENCHANTED_GOLDEN_APPLE()->setCount(16),
                    // sum else
                ];
                break;
        }

        return $items;
    }

    public static function getRewardsForSkillLevel(int $level): array
    {
        $rewards = [
            1 => [" &r&eFarmhand Level I", "    &r&a4% &fchance to get double", "    &r&fcrops.", "    &r&8+&a2 HP &c♥ Health", "    &r&8+&650&7 Coins"],
            2 => [],
            3 => [],
            4 => [],
            5 => [],
            6 => [],
            7 => [],
            8 => [],
            9 => [],
            10 => [],
            11 => [],
            12 => [],
            13 => [],
            14 => [],
            15 => [],
            16 => [],
            17 => [],
            18 => [],
            19 => [],
            20 => [],
            21 => [],
            22 => [],
            23 => [],
            24 => [],
            25 => []
        ];

        return $rewards[$level] ?? ["No rewards available."];
    }

    /**
     * Generate a unique key for the player-target pair (for storage in the cooldown list)
     * @param Player $player
     * @param Player $target
     * @return string
     */
    public static function getKillPairKey(Player $player, Player $target): string
    {
        return min($player->getName(), $target->getName()) . '-' . max($player->getName(), $target->getName());
    }

    public static function initCustomSizedInvMenu(): void
    {
        $packet = StaticPacketCache::getInstance()->getAvailableActorIdentifiers();
		$tag = $packet->identifiers->getRoot();
		assert($tag instanceof CompoundTag);
		$id_list = $tag->getListTag("idlist");
		assert($id_list !== null);
		$id_list->push(CompoundTag::create()
			->setString("bid", "")
			->setByte("hasspawnegg", 0)
			->setString("id", CustomSizedInvMenuType::ACTOR_NETWORK_ID)
			->setByte("summonable", 0)
		);
    }

    public static function combatTask(): void {
        Loader::getInstance()->getScheduler()->scheduleRepeatingTask(new ClosureTask(
            function (): void {
                $now = microtime(true);
                $exitMessage = C::colorize("&r&l&a(!) &r&2You are no longer in combat.");

                foreach (array_keys(AetherisListener::$combatPlayers) as $playerName) {
                    if (AetherisListener::$combatPlayers[$playerName] <= $now) {
                        unset(AetherisListener::$combatPlayers[$playerName]);

                        $player = Server::getInstance()->getPlayerExact($playerName);
                        if ($player !== null && $player->isOnline()) {
                            $player->sendMessage($exitMessage);
                        }
                    }
                }
            }
        ), 20);
    }

    public static function sendIslandInfo(Player $player, SkyBlock $island): void {
        $leaderName = $island->getLeaderName() ?? "Unknown";
        $description = $island->getDescription();
        $value = number_format($island->getValue());
        $bank = number_format($island->getBank());

        $storedMemberNames = $island->getMemberNames();
        $memberNamesDisplay = [];
        $onlineMembers = 0;
    
        foreach ($island->getMembers() as $uuidStr => $memberData) {
            $storedName = $memberData['name'] ?? substr($uuidStr, 0, 8);
    
            $memberPlayer = Server::getInstance()->getPlayerExact($storedName);
            $role         = $island->getRole($uuidStr);
            $roleSymbol   = self::getSkyblockRoleSymbol($role);
    
            if ($memberPlayer !== null) {
                $realName = $memberPlayer->getName();
                $island->updateMemberName(Uuid::fromString($uuidStr), $realName);
    
                $memberNamesDisplay[] = "&a{$roleSymbol}{$realName}";
                $onlineMembers++;
            } else {
                $memberNamesDisplay[] = "&c{$roleSymbol}{$storedName}";
            
            }
        }
    
        $memberCount = count($memberNamesDisplay);
        $memberList = implode(", ", $memberNamesDisplay);
    
        $messages = [
            "&r&l&6" . str_repeat("-", 10) . " &7[ &e" . $island->getName() . " &7] &r&l&6" . str_repeat("-", 10),
            "  &r&7* &6Leader: &f" . $leaderName,
            "  &r&7* &6Description: &f" . $description,
            "  &r&7* &6Members &7[&f$onlineMembers&7/&f$memberCount&7]&6:",
            "&r&f" . $memberList,
            "  &r&7* &6Value: &f" . $value,
            "  &r&7* &6Bank: &f$" . $bank,
        ];
    
        foreach ($messages as $message) {
            $player->sendMessage(C::colorize($message));
        }
    }

    /**
     * Converts an entity ID to its corresponding mob name
     * @param int $id
     * @return string
     */
    public static function convertMobIdToName(int $id): string
    {
        return match ($id) {
            10 => "chicken",
            11 => "cow",
            12 => "pig",
            17 => "squid",
            20 => "iron_golem",
            32 => "zombie",
            36 => "zombie_pigman",
            34 => "skeleton",
            37 => "slime",
            16 => "mooshroom",
            43 => "blaze",
            default => "unknown",
        };
    }

    public static function initDispenserMenu(): void {
        InvMenuHandler::getTypeRegistry()->register(self::TYPE_DISPENSER, InvMenuTypeBuilders::BLOCK_ACTOR_FIXED()
                ->setBlock(StringToItemParser::getInstance()->parse("dispenser")->getBlock())
                ->setBlockActorId("Dispenser")
                ->setSize(9)
                ->setNetworkWindowType(WindowTypes::DISPENSER)
                ->build());
    }   

    public static function initEntities(): void {
        EntityFactory::getInstance()->register(FloatingTextEntity::class, function(World $world, CompoundTag $nbt): Entity {
            return new FloatingTextEntity(EntityDataHelper::parseLocation($nbt, $world), $nbt);
        }, [FloatingTextEntity::getNetworkTypeId()]);
    }

    public static function initRegions(RegionManager $regionManager): void {
        $regions = [
            new Region("Spawn", new Vector3(470, 82, -397), new Vector3(167, 256, -115), new RegionPermissions(false, false, true, false, false, false, false)),
        ];

        foreach ($regions as $region) {
            $regionManager->addRegion($region);
        }
    }

    public static function initHandlers(): void {
        CrateListener::$onLeftClick  = [
            "vote"   => fn($p, $k) => CratePreviewScreen::display($p, $k),
            "void"  => fn($p, $k) => CratePreviewScreen::display($p, $k),
            "stardust" => fn($p, $k) => CratePreviewScreen::display($p, $k),
            "meteorite" => fn($p, $k) => CratePreviewScreen::display($p, $k),
        ];
        CrateListener::$onRightClick = [
            "vote"   => fn($p, $k) => self::processCrateRoll($p, $k),
            "void"  => fn($p, $k) => self::processCrateRoll($p, $k),
            "stardust" => fn($p, $k) => self::processCrateRoll($p, $k),
            "meteorite" => fn($p, $k) => self::processCrateRoll($p, $k),
        ];
    }

    public static function initCratePositions(): void {
        CrateListener::$cratePositions = [
            "vote" => new Position(325, 217, -257, Server::getInstance()->getWorldManager()->getDefaultWorld()),
            "void" => new Position(329, 217, -256, Server::getInstance()->getWorldManager()->getDefaultWorld()),
            "stardust" => new Position(331, 217, -263, Server::getInstance()->getWorldManager()->getDefaultWorld()),
            "meteorite" => new Position(332, 217, -259, Server::getInstance()->getWorldManager()->getDefaultWorld()),
        ];

        $crateNamePositions = [
            "vote" => [
                'position' => new Location(325.468, 218, -256.521, Server::getInstance()->getWorldManager()->getDefaultWorld(), 0, 0),
                'text' => [ 
                    '&r&d► &aVote &fCrate &r&d◄',
                    ' ',
                    '&r&d► &fRight-Click to open the crate.',
                    '&r&d► &fLeft-Click to preview the crate.',
                ],
            ],
            "void" => [
                'position' => new Location(329.516, 218, -255.424, Server::getInstance()->getWorldManager()->getDefaultWorld(), 0, 0),
                'text' => [
                    '&r&d► &bVoid &fCrate &r&d◄',
                    ' ',
                    '&r&d► &fRight-Click to open the crate.',
                    '&r&d► &fLeft-Click to preview the crate.',
                ],
            ],
            'stardust' => [
                'position' => new Location(331.487, 218, -262.492, Server::getInstance()->getWorldManager()->getDefaultWorld(), 0, 0),
                'text' => [
                    '&r&d► &dStardust &fCrate &r&d◄',
                    ' ',
                    '&r&d► &fRight-Click to open the crate.',
                    '&r&d► &fLeft-Click to preview the crate.',
                ],
            ],
            'meteorite' => [
                'position' => new Location(332.506, 218, -258.482, Server::getInstance()->getWorldManager()->getDefaultWorld(), 0, 0),
                'text' => [
                    '&r&d► &cMeteorite &fCrate &r&d◄',
                    ' ',
                    '&r&d► &fRight-Click to open the crate.',
                    '&r&d► &fLeft-Click to preview the crate.',
                ],
            ]
        ];

        foreach ($crateNamePositions as $key => $data) {
            $entity = new FloatingTextEntity($data['position']);
            $entity->setText(C::colorize(implode("\n", $data['text'])));
            $entity->spawnToAll();
            FloatingTextsInstance::$particles[$key] = $entity; 
        }
    }

    public static function userHasKeyFor(Player $p, string $crateType): bool {
        $nbt = $p
            ->getInventory()
            ->getItemInHand()
            ->getNamedTag()
            ->getCompoundTag("Aetheris"); 
    
        if ($nbt === null) {
            return false;
        }
    
        $key = $nbt->getString("crate_key", "");
        return $key !== "" && strtolower($key) === strtolower($crateType);
    }

    public static function processCrateRoll(Player $p, string $crateType): void {
        if (!self::userHasKeyFor($p, $crateType)) {
            $p->sendMessage(C::colorize("&4Error: &cYou must have a " . $crateType . " key in your hand to open this crate."));
            PlayerUtils::playSound($p, "mob.villager.no");
            $direction = $p->getDirectionVector()->multiply(-0.9)->add(0, 0.8, 0); 
            $p->setMotion($direction);
            return;
        }

        $item = $p->getInventory()->getItemInHand();
        $item->pop();
        $p->getInventory()->setItemInHand($item);
        CrateRollScreen::display($p, $crateType);
    }

    public static function toggleFlight(Player $player, bool $force = false): void
    {
        if ($force) {
            if (!$player->getAllowFlight()) {
                $player->setAllowFlight(true);
                $player->sendMessage(C::colorize(Loader::SERVER_PREFIX . "&fSet fly mode &aenabled &ffor " . $player->getNameTag()));
            }
        } else {
            if (!$player->getAllowFlight()) {
                $player->setAllowFlight(true);
                $player->sendMessage(C::colorize(Loader::SERVER_PREFIX . "&fSet fly mode &aenabled &ffor " . $player->getNameTag()));
            } else {
                $player->setAllowFlight(false);
                $player->setFlying(false);
                $player->resetFallDistance();
                $player->sendMessage(C::colorize(Loader::SERVER_PREFIX . "&fSet fly mode &cdisabled &ffor " . $player->getNameTag()));
            }
        }
    
        if ($force || $player->getAllowFlight() && !$force) {
            $player->setFlying(true);
            $player->resetFallDistance();
        }
    }

    public static function onInventorySlotChange(PlayerInventory $inventory, int $slot, Item $oldItem): void {
        $player = $inventory->getHolder();
        if (!$player instanceof Player) return;

        $heldSlot = $player->getInventory()->getHeldItemIndex();
        $newItem = $inventory->getItem($slot);

        if ($slot === $heldSlot) {
            if (!$oldItem->isNull()) {
                if (CustomEnchantmentManager::hasEnchantment($oldItem, "haste")) {
                    $current = $player->getEffects()->get(VanillaEffects::HASTE());
                    if ($current !== null && $current->getAmplifier() < 3) {
                        $player->getEffects()->remove(VanillaEffects::HASTE());
                    }
                }
            }

            if (!$newItem->isNull()) {
                if (CustomEnchantmentManager::hasEnchantment($newItem, "haste")) {
                    $hasteLevel = CustomEnchantmentManager::getLevel($newItem, "haste");
                    $current = $player->getEffects()->get(VanillaEffects::HASTE());
                    if ($current === null || $current->getAmplifier() + 1 < $hasteLevel) {
                        $player->getEffects()->add(new EffectInstance(
                            VanillaEffects::HASTE(),
                            2147483647, 
                            $hasteLevel - 1,
                            false
                        ));
                    }
                }
            }
        }
    }

    public static function banPlayer(Player $target, string $reason, string $staffName, bool $silent = false): void {
        $targetName = $target->getName();
        Server::getInstance()->getNameBans()->addBan($targetName, $reason, null, $staffName);
        $target->kick(C::colorize("&cYou have been banned!\n&fReason: &7$reason"));
        self::announceBan($targetName, $staffName, $reason, $silent);
    }

    public static function announceBan(string $bannedName, string $staffName, string $reason, bool $silent = false): void {
        if ($silent) {
            $msg = C::colorize("&r&8&l[&5SILENT BAN&8] &7| &d{$bannedName} &7was &5silently banned &7by &d{$staffName}");
        } else {
            $msg = C::colorize("&r&4&l[&cBAN&4] &7| &c{$bannedName} &7was banned by &c{$staffName} &8| &fReason: &6{$reason}");
        }

        foreach(Server::getInstance()->getOnlinePlayers() as $player){
            if(!$silent || $player->hasPermission("aetheris.staff.silentban")){
                $player->sendMessage($msg);
            }
        }
    }

    public static function setItemEntityNameTag(ItemEntity $entity, ?int $count = null): void {
        $item = $entity->getItem();
        if ($count === null) {
            $count = $item->getCount();
        }
        $name = $item->hasCustomName() ? $item->getCustomName() : $item->getName();
        $format = "&r&f{$name} x{$count}";
        $entity->setNameTag(C::colorize($format));
        $entity->setNameTagAlwaysVisible();
    }

    public static function initEnchantHandlers(): void {
        $blockBreakHandlers = [
            'autosmelt' => [AutoSmeltEnchant::class, 'handle'],
            'autoplanter' => [AutoPlanterEnchant::class, 'handle']
        ];

        foreach ($blockBreakHandlers as $name => $handler) {
            EnchantmentEventRegistry::registerHandler('block_break', $name, $handler);
        }

        /** ! LINE ! */
        $entityDamageHandlers = [
            'jellylegs' => [JellyLegsEnchant::class, 'handle'],
        ];

        foreach ($entityDamageHandlers as $name => $handler) {
            EnchantmentEventRegistry::registerHandler('entity_damage', $name, $handler);
        }

        /** ! LINE ! */
        $entityDamageByEntityHandlers = [
            'blazed' => [BlazedEnchant::class, 'handle'],
        ];

        foreach ($entityDamageByEntityHandlers as $name => $handler) {
            EnchantmentEventRegistry::registerHandler('entity_damage_by_entity', $name, $handler);
        }

        $autoPlantTask = new AutoPlanterTask();
        Loader::getInstance()->getScheduler()->scheduleRepeatingTask($autoPlantTask, 5  );
        AutoPlanterEnchant::setTask($autoPlantTask);
    }
    /**
     * Dynamically modifies a game rule for a player.
     *
     * @param Player $player
     * @param string $rule The name of the game rule.
     * @param mixed $value The value to set for the game rule.
     * @param bool $isEditable Whether the rule is editable (default: false).
     */
    public static function modifyGameRule(Player $player, string $rule, mixed $value, bool $isEditable = false): void
    {
        if (is_bool($value)) {
            $gameRule = new BoolGameRule($value, $isEditable);
        } elseif (is_int($value)) {
            $gameRule = new IntGameRule($value, $isEditable);
        } elseif (is_float($value)) {
            $gameRule = new FloatGameRule($value, $isEditable);
        } else {
            return;
        }

        $packet = GameRulesChangedPacket::create([
            $rule => $gameRule
        ]);

        $player->getNetworkSession()->sendDataPacket($packet);
    }

    public static function registerAetherisEntities(): void {
        EntityFactory::getInstance()->register(FloatingTextEntity::class, function(World $world, CompoundTag $nbt): Entity {
            return new FloatingTextEntity(EntityDataHelper::parseLocation($nbt, $world), $nbt);
        }, [FloatingTextEntity::getNetworkTypeId()]);
    }

    public static function isFullyGrownCrop(Block $block): bool {
        if ($block instanceof Wheat) {
            return $block->getAge() >= $block::MAX_AGE;
        }

        if ($block instanceof Carrot) {
            return $block->getAge() >= $block::MAX_AGE;
        }
        
        if ($block instanceof Potato) {
            return $block->getAge() >= $block::MAX_AGE;
        }

        if ($block instanceof Beetroot) {
            return $block->getAge() >= $block::MAX_AGE;
        }

        if ($block instanceof NetherWartPlant) {
            return $block->getAge() >= $block::MAX_AGE;
        }

        return false;
    }
}
