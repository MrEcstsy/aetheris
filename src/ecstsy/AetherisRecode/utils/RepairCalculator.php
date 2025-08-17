<?php
namespace ecstsy\AetherisRecode\utils;

use pocketmine\item\Durable;

final class RepairCalculator {
    private static int $unitsUsed = 0;

    public static function unitsUsed(): int {
        return self::$unitsUsed;
    }

    public static function calcCombineCost(Durable $src, Durable $mat, Durable $res): int {
        self::$unitsUsed = 1;
        if ($mat->getDamage() === 0) {
            $res->setDamage(0);
            return 2;
        }
        $max = $src->getMaxDurability();
        $newDur = ($max - $src->getDamage()) + ($max - $mat->getDamage()) + (int)($max * 0.12);
        $res->setDamage(max(0, $max - min($newDur, $max)));
        return 2;
    }

    public static function calcUnitRepairCost(Durable $src, Durable $mat, Durable $res): int {
        $max = $src->getMaxDurability();
        $needed = $max - $src->getDamage();
        $restored = 0;
        $count = 0;
        for ($i = 1; $i <= $mat->getCount(); $i++) {
            $restored = min($max, $needed + (int)floor($max * 0.25) * $i);
            if ($restored >= $max) {
                $count = $i;
                break;
            }
            $count = $i;
        }
        self::$unitsUsed = $count;
        $res->setDamage($max - $restored);
        return $count;
    }
}
