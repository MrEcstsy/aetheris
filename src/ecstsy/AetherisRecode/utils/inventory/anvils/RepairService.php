<?php
namespace ecstsy\AetherisRecode\utils\inventory\anvils;

use ecstsy\AetherisRecode\utils\RepairCalculator;
use pocketmine\item\Durable;
use pocketmine\block\inventory\AnvilInventory;

final class RepairService {

    /**
     * Repairs or combines damage on $source by consuming $material,
     * returns the XP cost incurred.
     */
    public function applyRepair(AnvilInventory $inv, Durable $source, Durable $material, Durable $result): int {
        $xp = 0;

        if ($material instanceof Durable && get_class($material) === get_class($source)) {
            $xp += RepairCalculator::calcCombineCost($source, $material, $result);
        }
        else {
            $xp += RepairCalculator::calcUnitRepairCost($source, $material, $result);
            $inv->setItem(AnvilInventory::SLOT_MATERIAL, $material->setCount($material->getCount() - RepairCalculator::unitsUsed()));
        }

        $inv->setItem(AnvilInventory::SLOT_INPUT, $result);
        return $xp;
    }
}
