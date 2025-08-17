<?php

declare(strict_types=1);

namespace ecstsy\AetherisRecode\server\items\stardrops;

use diamondgold\DummyItemsBlocks\block\Scaffolding;
use ecstsy\AetherisRecode\entity\other\FloatingTextEntity;
use ecstsy\AetherisRecode\listeners\ItemListener;
use ecstsy\AetherisRecode\Loader;
use ecstsy\AetherisRecode\server\items\AetherisItemFactory;
use pocketmine\block\BaseSign;
use pocketmine\block\Carpet;
use pocketmine\block\Chest;
use pocketmine\block\Door;
use pocketmine\block\EnderChest;
use pocketmine\block\Fence;
use pocketmine\block\FenceGate;
use pocketmine\block\GlassPane;
use pocketmine\block\Ladder;
use pocketmine\block\Leaves;
use pocketmine\block\MobHead;
use pocketmine\block\ShulkerBox;
use pocketmine\block\Slab;
use pocketmine\block\Stair;
use pocketmine\block\Trapdoor;
use pocketmine\block\utils\SupportType;
use pocketmine\block\VanillaBlocks;
use pocketmine\block\Wall;
use pocketmine\block\WallSign;
use pocketmine\color\Color;
use pocketmine\entity\Location;
use pocketmine\item\Item;
use pocketmine\math\Facing;
use pocketmine\math\Vector3;
use pocketmine\nbt\tag\CompoundTag;
use pocketmine\network\mcpe\protocol\AddItemActorPacket;
use pocketmine\network\mcpe\protocol\BlockActorDataPacket;
use pocketmine\network\mcpe\protocol\BlockEventPacket;
use pocketmine\network\mcpe\protocol\MoveActorAbsolutePacket;
use pocketmine\network\mcpe\protocol\RemoveActorPacket;
use pocketmine\network\mcpe\protocol\types\BlockPosition;
use pocketmine\network\mcpe\protocol\types\CacheableNbt;
use pocketmine\network\mcpe\protocol\types\inventory\ItemStackWrapper;
use pocketmine\utils\TextFormat as C;
use pocketmine\player\Player;
use pocketmine\scheduler\ClosureTask;
use pocketmine\world\particle\BlockBreakParticle;
use pocketmine\world\particle\DustParticle;
use pocketmine\world\sound\AnvilFallSound;
use pocketmine\world\sound\AnvilUseSound;
use pocketmine\world\sound\BlockBreakSound;
use pocketmine\world\sound\ChestOpenSound;
use pocketmine\world\sound\XpCollectSound;

final class StarDrop
{
    public string $rarity;
    /** @var array<int, array{item: Item, weight: int}> */
    public array $rewardPool;
    /** @var array<string, StarDrop> */
    public static array $drops = [];
    private static ?Color $black = null;

    private const RARITY_COLORS = [
        'simple'    => '&f', 
        'unique'    => '&a', 
        'elite'     => '&b', 
        'exotic'    => '&e', 
        'legendary' => '&6', 
        'divine'    => '&5',
    ];

    public function __construct(string $rarity, array $rewardPool)
    {
        $this->rarity = $rarity;
        $this->rewardPool = $rewardPool;
    }

   /**
     * Rolls a random reward from the pool based on weight.
     */
    public function rollReward(): Item
    {
        $totalWeight = array_sum(array_column($this->rewardPool, 'weight'));
        $rand = mt_rand(1, $totalWeight);
        $cumulative = 0;
        foreach ($this->rewardPool as $reward) {
            $cumulative += $reward['weight'];
            if ($rand <= $cumulative) {
                return $reward['item'];
            }
        }
        return $this->rewardPool[0]['item'];
    }

    /**
     * Finds a valid position in front of the player to place a chest.
     */
    public static function findValidChestPosition(Player $player): ?Vector3 {
        $world   = $player->getWorld();
        $feetPos = $player->getPosition();
        $loc     = $player->getLocation();
        $yawRad  = deg2rad($loc->getYaw());
        $dir     = new Vector3(-sin($yawRad), 0, cos($yawRad));
        
        $maxDistance = 3;
        $startY      = (int) floor($feetPos->getY());

        for ($i = 1; $i <= $maxDistance; $i++) {
            $step = $feetPos->addVector($dir->multiply($i));
            $x = (int) floor($step->getX());
            $z = (int) floor($step->getZ());

            $groundY = null;
            for ($y = $startY; $y >= 0; $y--) {
                $block = $world->getBlockAt($x, $y, $z);
                if (
                    $block->isSolid() &&
                    $block->isFullCube() &&
                    !(
                        $block instanceof Slab ||
                        $block instanceof Stair ||
                        $block instanceof Carpet ||
                        $block instanceof MobHead ||
                        $block instanceof Fence ||
                        $block instanceof FenceGate ||
                        $block instanceof Wall ||
                        $block instanceof ShulkerBox ||
                        $block instanceof Chest ||
                        $block instanceof EnderChest ||
                        $block instanceof Ladder ||
                        $block instanceof BaseSign ||
                        $block instanceof Trapdoor ||
                        $block instanceof Scaffolding ||
                        $block instanceof GlassPane ||
                        $block instanceof Leaves ||
                        $block instanceof Door
                    )
                ) {
                    $groundY = $y;
                    break;
                }
            }

            if ($groundY === null) {
                continue; 
            }

            $above1 = $world->getBlockAt($x, $groundY + 1, $z);
            $above2 = $world->getBlockAt($x, $groundY + 2, $z);

            if (
                $above1->getTypeId() === VanillaBlocks::AIR()->getTypeId() &&
                $above2->getTypeId() === VanillaBlocks::AIR()->getTypeId()
            ) {
                return new Vector3($x, $groundY + 1, $z);
            }
        }

        return null; 
    }

    public static function playChestOpenAnimation(Player $player, Vector3 $pos): void
    {
        $pk = BlockEventPacket::create(
            new BlockPosition($pos->getFloorX(), $pos->getFloorY(), $pos->getFloorZ()),
            1,
            1
        );
        $player->getNetworkSession()->sendDataPacket($pk);
    }

    public static function showFakeRewardItem(Player $player, Vector3 $pos, Item $item): array
    {
        $eid = mt_rand(100000, 999999);
        $spawnPos = new Vector3($pos->getX() + 0.5, $pos->getY() + 1.05, $pos->getZ() + 0.5);
        $velocity = new Vector3(0, 0.6, 0);

        $pk = AddItemActorPacket::create(
            $eid,
            $eid,
            ItemStackWrapper::legacy($player->getNetworkSession()->getTypeConverter()->coreItemStackToNet($item)),
            $spawnPos,
            $velocity,
            [],
            false
        );
        $player->getNetworkSession()->sendDataPacket($pk);

        $itemName = $item->getName();
        $textPos = $spawnPos->add(0, 0.5, 0);
        $floatingText = new FloatingTextEntity(new Location($textPos->getX(), $textPos->getY(), $textPos->getZ(), $player->getWorld(), 0, 0), null);
        $floatingText->setText($itemName);
        $floatingText->spawnToAll();


        self::animateRewardItemFall($player, $eid, $spawnPos, $floatingText);

        return [$eid, $floatingText->getId()];
    }

    private static function getPlayerFacingDirection(Player $player): int
    {
        $yaw = fmod($player->getLocation()->getYaw(), 360);
        if ($yaw < 0) $yaw += 360;
        if ($yaw >= 45 && $yaw < 135) {
            return Facing::WEST;
        } elseif ($yaw >= 135 && $yaw < 225) {
            return Facing::NORTH;
        } elseif ($yaw >= 225 && $yaw < 315) {
            return Facing::EAST;
        } else {
            return Facing::SOUTH;
        }
    }

    private static function getFacingVector(int $facing): Vector3
    {
        switch ($facing) {
            case Facing::NORTH:
                return new Vector3(0, 0, -1);
            case Facing::SOUTH:
                return new Vector3(0, 0, 1);
            case Facing::WEST:
                return new Vector3(-1, 0, 0);
            case Facing::EAST:
                return new Vector3(1, 0, 0);
            default:
                return new Vector3(0, 0, 1);
        }
    }

    public static function animateRewardItemFall(Player $player, int $eid, Vector3 $startPos, ?FloatingTextEntity $floatingText = null): void
    {
        $ticks = 12;
        $arcHeight = 0.4;
        $endY = $startPos->getY() - 0.35;

        Loader::getInstance()->getScheduler()->scheduleRepeatingTask(new ClosureTask(
            (function () use ($player, $eid, $startPos, $ticks, $arcHeight, $endY, $floatingText) {
                static $tick = 0;
                $tick++;

                if (!$player->isOnline()) {
                    if ($floatingText !== null && !$floatingText->isClosed() && !$floatingText->isFlaggedForDespawn()) {
                        $floatingText->flagForDespawn();
                    }
                    return true;
                }
                $progress = min($tick / $ticks, 1);
                $y = max($endY, $startPos->getY() + (1 - $progress) * $arcHeight - $arcHeight * $progress);

                $pk = MoveActorAbsolutePacket::create(
                    $eid,
                    new Vector3($startPos->getX(), $y, $startPos->getZ()),
                    0.0,
                    0.0,
                    0,
                    0
                );
                $player->getNetworkSession()->sendDataPacket($pk);

                if ($floatingText !== null) {
                    $floatingText->teleport(new Vector3($startPos->getX(), $y + 0.5, $startPos->getZ()));
                }

                if ($progress >= 1) {
                    if ($floatingText !== null && !$floatingText->isClosed() && !$floatingText->isFlaggedForDespawn()) {
                        $floatingText->flagForDespawn();
                    }
                    return true;
                }
                return false;
            })
        ), 1);
    }

    /**
     * Spawns a StarDrop chest and floating text for the player.
     */
    public static function spawnStarDropChest(Player $player, string $rarity): ?Vector3
    {
        $chestPos = self::findValidChestPosition($player);
        if ($chestPos === null) {
            $player->sendMessage(C::colorize("&cNo suitable spot found in front of you! Make sure there is space at head height."));
            return null;
        }

        $name = strtolower($player->getName());
        if (isset(ItemListener::$starDropSessions[$name])) {
            $player->sendMessage(C::colorize("&cYou already have an active StarDrop!"));
            return null;
        }

        $facing = self::getPlayerFacingDirection($player);
        $oppositeFacing = Facing::opposite($facing);
        $chestBlock = VanillaBlocks::CHEST()->setFacing($oppositeFacing);
        $player->getWorld()->setBlock($chestPos, $chestBlock);

        ItemListener::$starDropSessions[$name] = [
            'pos' => $chestPos,
            'rarity' => $rarity,
            'taps' => 0,
            'tapText' => null
        ];

        self::spawnChestEffects($player, $chestPos, $rarity);

        return $chestPos;
    }

    /**
     * Spawns floating text and particles above the chest.
     */
    private static function spawnChestEffects(Player $player, Vector3 $chestPos, string $rarity): void
    {
        $vec = $chestPos->add(0.5, 0, 0.5);
        $player->getWorld()->addParticle($vec, new BlockBreakParticle(VanillaBlocks::CHEST()));

        $rarityName = "&l★ &r" . (self::RARITY_COLORS[$rarity] ?? "&f") . ucfirst($rarity) . " Star Drop";
        $text = C::colorize($rarityName);
        $textPos = $chestPos->add(0.5, 1, 0.5);

        $floatingText = new FloatingTextEntity(
            new Location($textPos->getX(), $textPos->getY(), $textPos->getZ(), $player->getWorld(), 0, 0),
            null
        );
        $floatingText->setText($text);
        $floatingText->spawnToAll();

        $name = strtolower($player->getName());
        ItemListener::$starDropSessions[$name]['floatingText'] = $floatingText;

        if (self::$black === null) {
            self::$black = Color::fromRGB(0, 0, 0);
        }
        $black = self::$black;

        for ($i = 0; $i < 32; $i++) {
            $offset = new Vector3(
                mt_rand(-10, 10) / 20,
                mt_rand(-10, 10) / 20,
                mt_rand(-10, 10) / 20
            );
            $player->getWorld()->addParticle(
                $vec->add($offset->x, $offset->y, $offset->z),
                new DustParticle($black)
            );
        }

        $player->getWorld()->addSound($player->getPosition()->asVector3(), new XpCollectSound());
    }

    public static function removeStarDropChest(Player $player, Vector3 $pos): void
    {
        $player->getWorld()->setBlock($pos, VanillaBlocks::AIR());
    }

    public static function isStarDropBlock($block, $pos): bool {
        $bPos = $block->getPosition();
        return $bPos->getFloorX() === $pos->getFloorX()
            && $bPos->getFloorY() === $pos->getFloorY()
            && $bPos->getFloorZ() === $pos->getFloorZ();
    }

    public static function handleStarDropTap($player, &$session): void {
        if (isset($session['tapText']) && $session['tapText'] instanceof FloatingTextEntity && !$session['tapText']->isClosed()) {
            $session['tapText']->flagForDespawn();
        }

        if (isset($session['floatingText']) && $session['floatingText'] instanceof FloatingTextEntity && !$session['floatingText']->isClosed()) {
            $session['floatingText']->flagForDespawn();
            $session['floatingText'] = null;
        }

        $remaining = 3 - $session['taps'];
        $text = "&e&l✦ &r&6Tap the &eStarDrop &6Chest! &7(&b{$remaining}&7 left)";
        $textPos = $session['pos']->add(0.5, 1, 0.5);
        $floatingText = new FloatingTextEntity(
            new Location($textPos->getX(), $textPos->getY(), $textPos->getZ(), $player->getWorld(), 0, 0),
            null
        );
        $floatingText->setText($text);
        $floatingText->spawnToAll();

        $session['tapText'] = $floatingText;

        $chestBlock = VanillaBlocks::CHEST();
        $player->getWorld()->addParticle(
            $session['pos']->add(0.5, 0, 0.5),
            new BlockBreakParticle($chestBlock)
        );

        switch ($session['taps']) {
            case 1:
                $player->getWorld()->addSound($session['pos']->add(0.5, 1, 0.5), new AnvilUseSound());
                break;
            case 2:
                $player->getWorld()->addSound($session['pos']->add(0.5, 1, 0.5), new AnvilFallSound());
                break;
        }
    }

    public static function handleStarDropClaim($player, &$session, string $name): void {
        if (isset($session['tapText']) && $session['tapText'] instanceof FloatingTextEntity && !$session['tapText']->isClosed()) {
            $session['tapText']->flagForDespawn();
            $session['tapText'] = null;
        }

        $session['claimed'] = true;
        $rarity = $session['rarity'];
        $starDrop = StarDrop::$drops[$rarity] ?? null;
        if ($starDrop === null || empty($starDrop->rewardPool)) {
            $player->sendMessage(C::colorize("&cThere are no rewards available for this StarDrop!"));
            unset(ItemListener::$starDropSessions[$name]);
            StarDrop::removeStarDropChest($player, $session['pos']);
            return;
        }

        $reward = $starDrop->rollReward();
        StarDrop::playChestOpenAnimation($player, $session['pos']);
        $eid = StarDrop::showFakeRewardItem($player, $session['pos'], $reward);
        $player->getWorld()->addSound($session['pos'], new ChestOpenSound());

        Loader::getInstance()->getScheduler()->scheduleDelayedTask(new ClosureTask(function() use ($player, $session, $eid, $name, $reward) {
            if(!$player->isOnline()) return;

            StarDrop::removeStarDropChest($player, $session['pos']);
            $player->getNetworkSession()->sendDataPacket(RemoveActorPacket::create($eid[0]));
            if ($player->getInventory()->canAddItem($reward)) {
                $player->getInventory()->addItem($reward);
            } else {
                $player->dropItem($reward);
            }
            unset(ItemListener::$starDropSessions[$name]);

            $vec = $session['pos']->add(0.5, 1, 0.5);
            if (self::$black === null) {
                self::$black = Color::fromRGB(0, 0, 0);
            }
            $black = self::$black;

            for ($i = 0; $i < 16; $i++) {
                $offset = new Vector3(
                    mt_rand(-7, 7) / 20,
                    mt_rand(-7, 7) / 20,
                    mt_rand(-7, 7) / 20
                );
                $player->getWorld()->addParticle(
                    $vec->add($offset->x, $offset->y, $offset->z),
                    new DustParticle($black)
                );
            }

            $player->getWorld()->addSound($player->getPosition()->asVector3(), new BlockBreakSound(VanillaBlocks::GLASS()));
            $player->sendTip(C::colorize("&aYou received your reward!"));
        }), 40);
    }

    /**
     * Call this on plugin enable to initialize all StarDrop types.
     */
    public static function init(): void
    {
        self::$drops['simple'] = new StarDrop('simple', [
            ['item' => AetherisItemFactory::bankNote(null, rand(500, 1000)), 'weight' => 40],
            ['item' => AetherisItemFactory::xpNote(null, rand(200, 500)), 'weight' => 30],
            ['item' => AetherisItemFactory::currencyPouch("money_pouch_1"), 'weight' => 15],
        ]);
    }
}
