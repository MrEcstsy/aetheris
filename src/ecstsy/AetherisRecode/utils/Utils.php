<?php

declare(strict_types=1);

namespace ecstsy\AetherisRecode\utils;

use CameraAPI\Instructions\ClearCameraInstruction;
use CameraAPI\Instructions\FadeCameraInstruction;
use CameraAPI\Instructions\ShakeCameraInstruction;
use ecstsy\AetherisRecode\listeners\AetherisListener;
use ecstsy\AetherisRecode\listeners\SkillsListener;
use ecstsy\AetherisRecode\Loader;
use ecstsy\AetherisRecode\player\skills\SkillType;
use ecstsy\AetherisRecode\skyblock\SkyBlock;
use ecstsy\AetherisRecode\utils\inventory\CustomSizedInvMenuType;
use ecstsy\MartianUtilities\utils\GeneralUtils;
use ecstsy\MartianUtilities\utils\PlayerUtils;
use pocketmine\block\utils\DyeColor;
use pocketmine\block\VanillaBlocks;
use pocketmine\item\enchantment\EnchantmentInstance;
use pocketmine\item\enchantment\StringToEnchantmentParser;
use pocketmine\item\Item;
use pocketmine\item\VanillaItems;
use pocketmine\math\Vector3;
use pocketmine\nbt\tag\CompoundTag;
use pocketmine\network\mcpe\cache\StaticPacketCache;
use pocketmine\network\mcpe\protocol\CameraShakePacket;
use pocketmine\player\Player;
use pocketmine\scheduler\ClosureTask;
use pocketmine\Server;
use pocketmine\world\Position;
use pocketmine\utils\TextFormat as C;
use Ramsey\Uuid\Uuid;

use function Ramsey\Uuid\v1;

final class Utils
{

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
        $bars = 5;
        $filledBars = max(1, min($bars, 6 - ceil($ping / 100)));
        $emptyBars = $bars - $filledBars;

        if ($ping === null) return "■";

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

    public static function createKitToken(string $kit, int $amount = 1): Item
    {
        $item = VanillaItems::AIR();

        switch ($kit) {
            case 'initiate':
                $item = VanillaItems::NETHER_STAR()->setCount($amount);

                $item->setCustomName(C::colorize("&r&l&6Essence of Initiate"));
                $item->setLore([
                    C::colorize("&r&7&oThe first steps into the skies begin here."),
                    C::colorize("&r&fUnlock this kit and prepare for adventure."),
                    C::colorize("&r&d&lRight-Click &7to claim your rewards.")
                ]);

                $root = $item->getNamedTag();
                $kitTag = new CompoundTag();

                $kitTag->setString("aetherisItem", $kit . "_kit");
                $root->setTag("Aetheris", $kitTag);
                break;
            case 'explorer':
                $item = VanillaItems::NETHER_STAR()->setCount($amount);

                $item->setCustomName(C::colorize("&r&l&6Essence of Explorer"));
                $item->setLore([
                    C::colorize("&r&7&oForge your path among the clouds."),
                    C::colorize("&r&fUnlock this kit and continue your journey."),
                    C::colorize("&r&d&lRight-Click &7to claim your rewards.")
                ]);

                $root = $item->getNamedTag();
                $kitTag = new CompoundTag();

                $kitTag->setString("aetherisItem", $kit . "_kit");
                $root->setTag('Aetheris', $kitTag);
                break;
            case 'champion':
                $item = VanillaItems::NETHER_STAR()->setCount($amount);

                $item->setCustomName(C::colorize("&r&l&6Essence of Champion"));
                $item->setLore([
                    C::colorize("&r&7&oA token for those who rise above the rest."),
                    C::colorize("&r&fUnlock this kit and equip yourself for glory."),
                    C::colorize("&r&d&lRight-Click &7to claim your rewards.")
                ]);

                $root = $item->getNamedTag();
                $kitTag = new CompoundTag();

                $kitTag->setString("aetherisItem", $kit . "_kit");

                $root->setTag('Aetheris', $kitTag);
                break;
            case 'warden':
                $item = VanillaItems::NETHER_STAR()->setCount($amount);

                $item->setCustomName(C::colorize("&r&l&6Essence of Warden"));
                $item->setLore([
                    C::colorize("&r&7&oFor those who guard the skies with unmatched valor."),
                    C::colorize("&r&fUnlock this kit and defend your realm."),
                    C::colorize("&r&d&lRight-Click &7to claim your rewards.")
                ]);

                $root = $item->getNamedTag();
                $kitTag = new CompoundTag();

                $kitTag->setString("aetherisItem", $kit . "_kit");
                $root->setTag('Aetheris', $kitTag);
                break;
            case 'aetherian':
                $item = VanillaItems::NETHER_STAR()->setCount($amount);

                $item->setCustomName(C::colorize("&r&l&6Essence of Aetherian"));
                $item->setLore([
                    C::colorize("&r&7&oBestowed upon the masters of the skies."),
                    C::colorize("&r&fUnlock this kit and claim your legacy."),
                    C::colorize("&r&d&lRight-Click &7to claim your rewards.")
                ]);

                $root = $item->getNamedTag();
                $kitTag = new CompoundTag();

                $kitTag->setString("aetherisItem", $kit . "_kit");
                $root->setTag('Aetheris', $kitTag);
                break;
        }
        return $item;
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
                    self::createBankNote(null, 2500),
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
                    self::createBankNote(null, 3500),
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
                    self::createBankNote(null, 10000),
                    VanillaItems::ENCHANTED_GOLDEN_APPLE()->setCount(16),
                    // sum else
                ];
                break;
        }

        return $items;
    }

    public static function createBankNote(?Player $player, int $amount = 1): Item
    {
        $item = VanillaItems::PAPER();
        $signer = $player === null ? 'Ethereal Hub' : $player->getName();

        $item->setCustomName(C::colorize("&r&l&bBank Note &r&7(Right Click)"));
        $item->setLore([
            C::colorize("&r&8▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬"),
            C::colorize("&r&fValue: &a$" . number_format($amount)),
            C::colorize("&r&fSigner: &b" . $signer),
            C::colorize("&r&8▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬"),
            C::colorize("&r&7Redeem this note to receive money."),
        ]);

        $root = $item->getNamedTag();
        $noteTag = new CompoundTag();

        $noteTag->setString("aetherisItem", "banknote");
        $noteTag->setInt("worth", $amount);

        $root->setTag('Aetheris', $noteTag);
        return $item;
    }

    public static function createExperienceBottle(?Player $player, int $amount = 1): Item
    {
        $item = VanillaItems::EXPERIENCE_BOTTLE();
        $signer = $player === null ? 'Ethereal Hub' : $player->getName();

        $item->setCustomName(C::colorize("&r&l&aExperience Bottle &r&7(Right Click)"));
        $item->setLore([
            C::colorize("&r&8▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬"),
            C::colorize("&r&fExperience: &a" . number_format($amount) . " EXP"),
            C::colorize("&r&fSigner: &b" . $signer),
            C::colorize("&r&8▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬"),
            C::colorize("&r&7Redeem this bottle to gain EXP."),
        ]);

        $root = $item->getNamedTag();
        $noteTag = new CompoundTag();

        $noteTag->setString("aetherisItem", "xpnote");
        $noteTag->setInt("worth", $amount);

        $root->setTag('Aetheris', $noteTag);
        return $item;
    }

    public static function createDebugStick(): Item
    {
        $item = VanillaItems::STICK();

        $item->setCustomName(C::colorize("&r&l&d* Debug Stick &r&7(Right Click)"));
        $item->setLore([
            C::colorize("&r&7Right click to toggle debug.")
        ]);

        $root = $item->getNamedTag();
        $debugTag = new CompoundTag();

        $debugTag->setString("aetherisItem", "debugstick");

        $root->setTag('Aetheris', $debugTag);
        return $item;
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

    public static function hasPlayerKillSkillCooldown(Player $player, Player $target): bool
    {
        $cooldownTime = 10;

        $pairKey = self::getKillPairKey($player, $target);

        if (isset(SkillsListener::$lastKills[$pairKey])) {
            $lastKillTime = SkillsListener::$lastKills[$pairKey];
            $currentTime = time();

            if ($currentTime - $lastKillTime < $cooldownTime) {
                return true;
            }
        }

        return false;
    }

    /**
     * Update the last kill time for a player-target pair
     * @param Player $player
     * @param Player $target
     */
    public static function updateLastKillTime(Player $player, Player $target): void
    {
        $pairKey = self::getKillPairKey($player, $target);
        SkillsListener::$lastKills[$pairKey] = time();
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

    public static function initializeSkillsForPlayer(Player $player): void
    {
        $skillManager = Loader::getSkillManager();
        $skills = $skillManager->getSkillsByPlayerUuid($player->getUniqueId()->toString());

        if (!isset($skills[SkillType::FARMING])) {
            $skillManager->updateSkill($player->getUniqueId()->toString(), SkillType::FARMING, 1, 0);
        }
        if (!isset($skills[SkillType::MINING])) {
            $skillManager->updateSkill($player->getUniqueId()->toString(), SkillType::MINING, 1, 0);
        }
        if (!isset($skills[SkillType::COMBAT])) {
            $skillManager->updateSkill($player->getUniqueId()->toString(), SkillType::COMBAT, 1, 0);
        }
        if (!isset($skills[SkillType::FORAGING])) {
            $skillManager->updateSkill($player->getUniqueId()->toString(), SkillType::FORAGING, 1, 0);
        }
        if (!isset($skills[SkillType::ENCHANTING])) {
            $skillManager->updateSkill($player->getUniqueId()->toString(), SkillType::ENCHANTING, 1, 0);
        }
        if (!isset($skills[SkillType::ALCHEMY])) {
            $skillManager->updateSkill($player->getUniqueId()->toString(), SkillType::ALCHEMY, 1, 0);
        }
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

                foreach (AetherisListener::$combatPlayers as $player) {
                    if (AetherisListener::$combatPlayers[$player] <= $now) {
                        AetherisListener::$combatPlayers->detach($player);
                        $player->sendMessage($exitMessage);
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
}
