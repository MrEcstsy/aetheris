<?php
declare(strict_types=1);

namespace ecstsy\AetherisRecode\server;

use ecstsy\AetherisRecode\entity\other\FloatingTextEntity;
use ecstsy\AetherisRecode\event\FloatingTextCountUpdateEvent;
use pocketmine\entity\Location;
use pocketmine\nbt\tag\CompoundTag;
use pocketmine\nbt\tag\DoubleTag;
use pocketmine\nbt\tag\FloatTag;
use pocketmine\nbt\tag\ListTag;
use pocketmine\Server;
use pocketmine\event\Listener;
use pocketmine\player\Player;
use pocketmine\utils\TextFormat as C;

final class FloatingTextsInstance {

    /** 
     * Definition structure:
     *  - pos:    x,y,z,world
     *  - text:   array of lines (can contain {count})
     * @var array<string, array{pos: array, text: string[]}>
     */
    public static array $definitions  = [
        "spawn" => [
            "pos" => ["x" => 306.027, "y" => 222.000, "z" => -277.510, "world" => 'world'],
            "text" => [
                "&r&f&lETHEREAL &dHUB",
                " ",
                "&r&d► &fNow you are on &dAetheris ◄",
                "&r&d► &fThere are currently &d{count} &fplayers online &d◄",
                " ",
                "  ",
                "&r&d    Website ► &fetherealhub.net",
                "&r&d  Store ► &fstore.etherealhub.net",
                "&r&d   Vote ► &fvote.etherealhub.net",
                " ",
                "&r&d    ► &fFor more information &d◄",
                "&r&d       /menu",
                " ",
                "&r&f   aetheris.&detherealhub&f.net"
            ],
        ],
        "crates" => [
            "pos" => ["x" => 327.370, "y" => 217.500, "z" => -260.661, "world" => 'world'],
            "text" => [
                "&r&5&k►&r&d&k►&r&f&k► &r&f&lWARP &7- &dCRATES &r&f&k◄&r&d&k◄&r&5&k◄",
            ],
        ],
        "enchant" => [
            "pos" => ["x" => 285.485, "y" => 212.000, "z" => -289.452, "world" => 'world'],
            "text" => [
                "&r&5&k►&r&d&k►&r&f&k► &r&f&lWARP &7- &dENCHANT &r&f&k◄&r&d&k◄&r&5&k◄",
            ],
        ],
        "warzone-portal" => [
            "pos" => ["x" => 273.990, "y" => 217.500, "z" => -245.078, "world" => 'world'],
            "text" => [
                "&r&5&k►&r&d&k►&r&f&k► &r&d&lWARZONE &7- &fPORTAL &r&f&k◄&r&d&k◄&r&5&k◄",
            ]
        ]
    ];

    /** @var FloatingTextEntity[] */
    public static array $particles = [];

    public static function register(): void {
        self::removeAll();
        
        self::spawnAll();
    }

    public static function removeAll(): void {
        foreach(Server::getInstance()->getWorldManager()->getWorlds() as $w){
            foreach($w->getEntities() as $e){
                if($e instanceof FloatingTextEntity){
                    $e->flagForDespawn();
                }
            }
        }
        self::$particles = [];
    }

    public static function spawnAll(): void {
        $srv = Server::getInstance();
        foreach(self::$definitions as $id => $def){
            $world = $srv->getWorldManager()->getWorldByName($def["pos"]["world"]);
            if($world === null) continue;

            $count = count($srv->getOnlinePlayers());
            $text  = implode("\n", array_map(
                fn(string $line) => str_replace("{count}", (string)$count, $line),
                $def["text"]
            ));

            $nbt = self::makeDefaultNBT($def["pos"]);
            $loc = new Location(
                $def["pos"]["x"], $def["pos"]["y"], $def["pos"]["z"],
                $world, 0.0, 0.0
            );
            $ent = new FloatingTextEntity($loc, $nbt);
            $ent->setText(C::colorize($text));
            $ent->spawnToAll();
            self::$particles[$id] = $ent;
        }
    }

    public static function makeDefaultNBT(array $pos): CompoundTag {
        return CompoundTag::create()
            ->setTag("Pos", new ListTag([
                new DoubleTag($pos["x"]), new DoubleTag($pos["y"]), new DoubleTag($pos["z"])
            ]))
            ->setTag("Motion", new ListTag([
                new DoubleTag(0.0), new DoubleTag(0.0), new DoubleTag(0.0)
            ]))
            ->setTag("Rotation", new ListTag([
                new FloatTag(0.0), new FloatTag(0.0)
            ]));
    }

    /**
     * Always call after spawnAll, from your join-listener:
     *   FloatingTextsInstance::showToPlayer($player);
     */
    public static function showToPlayer(Player $player): void {
        foreach(self::$particles as $e){
            $e->spawnTo($player);
        }
    }
}
