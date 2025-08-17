<?php
declare(strict_types=1);

namespace ecstsy\AetherisRecode\server\crates;

use ecstsy\AetherisRecode\Loader;
use ecstsy\AetherisRecode\server\FloatingTextsInstance;
use ecstsy\AetherisRecode\server\items\AetherisItemFactory;
use pocketmine\utils\TextFormat as C;
use pocketmine\block\VanillaBlocks;
use pocketmine\item\Item;
use pocketmine\item\VanillaItems;
use pocketmine\scheduler\Task;

final class CrateManager {
    /** @var array<string, Crate> */
    private static array $crates = [];

    const METEORITE_NAME = "Meteorite Crate";
    const STARDUST_NAME  = "Stardust Crate";
    public static int $meteorCursor = 0;
    public static int $stardustCursor = 0;
    private const ANIMATION_INTERVAL_TICKS = 2;

    public static function init(): void {
        self::$crates = [
            'vote' => new Crate([
                ["chance" => 20, "rewards" => [
                    CrateReward::of(AetherisItemFactory::bankNote(null, 5000)),
                ]],
                ["chance" => 15, "rewards" => [
                    CrateReward::of(AetherisItemFactory::bankNote(null, 10000)),
                ]],
                ["chance" => 10, "rewards" => [
                    CrateReward::of(AetherisItemFactory::bankNote(null, 20000)),
                ]],
                ["chance" => 20, "rewards" => [
                    CrateReward::of(AetherisItemFactory::xpNote(null, 1395)),
                ]],
                ["chance" => 15, "rewards" => [
                    CrateReward::of(AetherisItemFactory::xpNote(null, 4020)),
                ]],
                ["chance" => 10, "rewards" => [
                    CrateReward::of(AetherisItemFactory::xpNote(null, 8670)),
                ]],
                ["chance" => 10, "rewards" => [
                    CrateReward::of(AetherisItemFactory::crateKey('vote')),
                ]],
                ["chance" => 25, "rewards" => [
                    CrateReward::of(VanillaBlocks::GOLD()->asItem()->setCount(64)),
                ]],
                ["chance" => 30, "rewards" => [
                    CrateReward::of(VanillaBlocks::REDSTONE()->asItem()->setCount(32)),
                ]],
                ["chance" => 15, "rewards" => [
                    CrateReward::of(VanillaBlocks::DIAMOND()->asItem()->setCount(32)),
                ]],
                ["chance" => 10, "rewards" => [
                    CrateReward::of(VanillaBlocks::EMERALD()->asItem()->setCount(32)),
                ]],
                ["chance" => 5, "rewards" => [
                    CrateReward::of(VanillaBlocks::NETHERITE()->asItem()->setCount(8)),
                ]],
                ["chance" => 35, "rewards" => [
                    CrateReward::of(VanillaBlocks::GRASS()->asItem()->setCount(64))
                ]],
                ["chance" => 25, "rewards" => [
                    CrateReward::of(VanillaBlocks::GLOWSTONE()->asItem()->setCount(64))
                ]],
                ["chance" => 10, "rewards" => [
                    CrateReward::of(VanillaBlocks::HOPPER()->asItem()->setCount(32)),
                ]],
                ["chance" => 30, "rewards" => [
                    CrateReward::of(VanillaItems::LAVA_BUCKET()),
                ]],
                ["chance" => 10, "rewards" => [
                    CrateReward::of(VanillaItems::GOLDEN_APPLE()->setCount(32)),
                ]],
                ["chance" => 5, "rewards" => [
                    CrateReward::of(VanillaItems::ENCHANTED_GOLDEN_APPLE()->setCount(16)),
                ]],
                ["chance" => 15, "rewards" => [
                    CrateReward::of(VanillaBlocks::REDSTONE()->asItem()->setCount(64))
                ]],
                ["chance" => 30, "rewards" => [
                    CrateReward::of(VanillaBlocks::QUARTZ()->asItem()->setCount(64))
                ]],
                ["chance" => 5, "rewards" => [
                    CrateReward::of(VanillaBlocks::ENDER_CHEST()->asItem()->setCount(2))
                ]],
                ["chance" => 5, "rewards" => [
                    CrateReward::of(AetherisItemFactory::crateKey("meteorite")->setCount(5))
                ]],
                ["chance" => 10, "rewards" => [
                    CrateReward::of(AetherisItemFactory::starDrop("simple"))
                ]],
            ]),
            'void' => new Crate([
                ["chance" => 20, "rewards" => [
                    CrateReward::of(AetherisItemFactory::bankNote(null, 15000)),
                ]],
                [ "chance" => 15, "rewards" => [
                    CrateReward::of(AetherisItemFactory::bankNote(null, 30000)),
                ]],
                ["chance" => 20, "rewards" => [
                    CrateReward::of(AetherisItemFactory::xpNote(null, 1395)),    
                ]],
                ["chance" => 10, "rewards" => [
                    CrateReward::of(AetherisItemFactory::xpNote(null, 8670)),
                ]],
                ["chance" => 15, "rewards" => [
                    CrateReward::of(AetherisItemFactory::starDrop("unique"))
                ]],
                ["chance" => 15, "rewards" => [
                    CrateReward::of(AetherisItemFactory::starDrop("elite"))
                ]],
            ]),
            "stardust" => new Crate([
                ["chance" => 20, "rewards" => [
                    CrateReward::of(AetherisItemFactory::bankNote(null, 15000)),
                ]]
            ]),
            "meteorite" => new Crate([
                ["chance" => 15, "rewards" => [
                    CrateReward::of(AetherisItemFactory::bankNote(null, 30000)),
                ]],
                ["chance" => 15, "rewards" => [
                    CrateReward::of(AetherisItemFactory::bankNote(null, 5000)),
                ]],
                ["chance" => 15, "rewards" => [
                    CrateReward::of(AetherisItemFactory::starDrop(["exotic", "legendary", "divine"][array_rand(["exotic", "legendary", "divine"])]))                
                ]],
                ["chance" => 15, "rewards" => [
                    CrateReward::of(AetherisItemFactory::xpNote(null, 5000)),
                ]],
                ["chance" => 10, "rewards" => [
                    CrateReward::of(AetherisItemFactory::randomEnchantBook("unique")->setCount(3)),
                ]],
                ["chance" => 10, "rewards" => [
                    CrateReward::of(VanillaBlocks::GOLD()->asItem()->setCount(64)),
                ]],
                ["chance" => 10, "rewards" => [
                    CrateReward::of(AetherisItemFactory::randomEnchantBook("elite")->setCount(2)),
                ]],
                ["chance" => 8, "rewards" => [
                    CrateReward::of(AetherisItemFactory::xpNote(null, 40000)),
                ]],
                ["chance" => 8, "rewards" => [
                    CrateReward::of(AetherisItemFactory::randomEnchantBook("exotic")->setCount(2)),
                ]],
                ["chance" => 8, "rewards" => [
                    CrateReward::of(VanillaItems::NETHERITE_INGOT()->setCount(16)),
                ]],
                ["chance" => 2, "rewards" => [
                    CrateReward::of(AetherisItemFactory::randomEnchantBook("legendary")),
                ]],
                ["chance" => 2, "rewards" => [
                    CrateReward::of(AetherisItemFactory::crateKey("meteorite")),
                ]],
            ]),
        ];
    }

    public static function get(string $key): ?Crate {
        return self::$crates[$key] ?? null;
    }

    public static function roll(string $key): ?Item {
        return self::get($key)?->rollItem();
    }

    public static function scheduleCrateLetterAnimation(): void {
        Loader::getInstance()->getScheduler()->scheduleRepeatingTask(new class() extends Task {
            public function __construct() { }
            public function onRun(): void { CrateManager::updateCrateLetterAnimation(); }
        }, self::ANIMATION_INTERVAL_TICKS);
    }

    /**
     * Animates the crate floating text by cycling a highlight over the crate name.
     */
    public static function updateCrateLetterAnimation(): void {
        self::$meteorCursor = (self::$meteorCursor + 1) % mb_strlen(self::METEORITE_NAME);
        $meteorLine = self::buildAnimatedLine(self::METEORITE_NAME, self::$meteorCursor, '&8', '&6', '&e'); 

        self::$stardustCursor = (self::$stardustCursor + 1) % mb_strlen(self::STARDUST_NAME);
        $stardustLine = self::buildAnimatedLine(self::STARDUST_NAME, self::$stardustCursor, '&8', '&d', '&5');

        self::updateFloatingText('meteorite', $meteorLine);
        self::updateFloatingText('stardust', $stardustLine);
    }

    /**
     * Builds a colored, animated line for crate floating text.
     *
     * @param string $name
     * @param int $cursor
     * @param string $baseColor
     * @param string $highlightColor
     * @return string
     */
    public static function buildAnimatedLine(string $name, int $cursor, string $baseColor, string $highlightColor, string $trailColor = "&e"): string {
        $chars = mb_str_split($name);
        $out = '';
        $len = count($chars);

        foreach ($chars as $i => $ch) {
            if ($i === $cursor) {
                $color = $highlightColor . "&l";
            } elseif ($i === ($cursor - 1 + $len) % $len || $i === ($cursor + 1) % $len) {
                $color = $trailColor;
            } else {
                $color = $baseColor;
            }
            $out .= $color . $ch;
        }
        return "{$baseColor}&r&d► {$out} {$baseColor}&r&d◄";
    }

    /**
     * Updates the first line of a floating text entity for a crate.
     *
     * @param string $key
     * @param string $firstLine
     */
    public static function updateFloatingText(string $key, string $firstLine): void {
        /** @var FloatingTextEntity|null $ent */
        $ent = FloatingTextsInstance::$particles[$key] ?? null;
        if (!$ent) return;

        $world = $ent->getWorld();
        if ($world === null) return;

        $hasNearby = false;
        foreach ($world->getPlayers() as $player) {
            if ($player->getPosition()->distance($ent->getPosition()) < 16) {
                $hasNearby = true;
                break;
            }
        }
        if (!$hasNearby) return;

        $lines = explode("\n", $ent->getText());
        $lines[0] = C::colorize($firstLine);
        $newText = implode("\n", $lines);

        if ($ent->getText() !== $newText) {
            $ent->setText($newText);
            $ent->setNameTag(C::colorize($newText));
        }
    }
}
