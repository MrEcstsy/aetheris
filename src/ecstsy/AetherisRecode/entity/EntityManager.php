<?php

namespace ecstsy\AetherisRecode\entity;

use ecstsy\AetherisRecode\entity\mobs\Blaze;
use ecstsy\AetherisRecode\entity\mobs\Chicken;
use ecstsy\AetherisRecode\entity\mobs\Cow;
use ecstsy\AetherisRecode\entity\mobs\IronGolem;
use ecstsy\AetherisRecode\entity\mobs\Mooshroom;
use ecstsy\AetherisRecode\entity\mobs\Pig;
use ecstsy\AetherisRecode\entity\mobs\Skeleton;
use ecstsy\AetherisRecode\entity\mobs\Slime;
use ecstsy\AetherisRecode\entity\mobs\Squid;
use ecstsy\AetherisRecode\entity\mobs\Zombie;
use ecstsy\AetherisRecode\entity\mobs\ZombiePigman;
use pocketmine\entity\EntityDataHelper;
use pocketmine\entity\EntityFactory;
use pocketmine\entity\Location;
use pocketmine\nbt\tag\CompoundTag;
use pocketmine\utils\SingletonTrait;
use pocketmine\world\World;
use RuntimeException as GlobalRuntimeException;

class EntityManager
{

    use SingletonTrait;

    private array $entities = [
        Blaze::class,
        Chicken::class,
        Cow::class,
        IronGolem::class,
        Mooshroom::class,
        Pig::class,
        Skeleton::class,
        Slime::class,
        Squid::class,
        Zombie::class,
        ZombiePigman::class
    ];

    private array $entityIds = [];

    public function __construct() {
        self::$instance = $this;

        foreach($this->entities as $entity) {
            EntityFactory::getInstance()->register($entity, function(World $world, CompoundTag $nbt) use($entity): SpawnerEntity {
                return new $entity(EntityDataHelper::parseLocation($nbt, $world), $nbt);
            }, [$entity::getNetworkTypeId()]);

            $this->entityIds[$entity::getNetworkTypeId()] = $entity;
        }
    }

    public function getEntityFor(string $entityTypeId, Location $location, $nbt) : SpawnerEntity {
        switch($entityTypeId) {
            case Blaze::getNetworkTypeId():
                return new Blaze($location, $nbt);
            case Chicken::getNetworkTypeId():
                return new Chicken($location, $nbt);
            case Cow::getNetworkTypeId():
                return new Cow($location, $nbt);
            case IronGolem::getNetworkTypeId():
                return new IronGolem($location, $nbt);
            case Mooshroom::getNetworkTypeId():
                return new Mooshroom($location, $nbt);
            case Pig::getNetworkTypeId():
                return new Pig($location, $nbt);
            case Skeleton::getNetworkTypeId():
                return new Skeleton($location, $nbt);
            case Slime::getNetworkTypeId():
                return new Slime($location, $nbt);
            case Squid::getNetworkTypeId():
                return new Squid($location, $nbt);
            case Zombie::getNetworkTypeId():
                return new Zombie($location, $nbt);
            case ZombiePigman::getNetworkTypeId():
                return new ZombiePigman($location, $nbt);
            default:
                throw new GlobalRuntimeException("Invalid entity type id: $entityTypeId");
        }
    }
}