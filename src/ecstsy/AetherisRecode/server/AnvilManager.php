<?php
declare(strict_types=1);

namespace ecstsy\AetherisRecode\server;

use ecstsy\AetherisRecode\enchantments\CustomEnchantmentManager;
use ecstsy\AetherisRecode\enchantments\CustomEnchantments;
use ecstsy\AetherisRecode\Loader;
use ecstsy\AetherisRecode\utils\inventory\anvils\AnvilRegistry;
use ecstsy\AetherisRecode\utils\inventory\anvils\RepairService;
use ecstsy\AetherisRecode\utils\inventory\anvils\EnchantCombineService;
use pocketmine\block\inventory\AnvilInventory;
use pocketmine\item\VanillaItems;
use pocketmine\network\mcpe\protocol\ItemStackRequestPacket;
use pocketmine\network\mcpe\NetworkSession;
use pocketmine\network\mcpe\protocol\types\inventory\ContainerUIIds;
use pocketmine\network\mcpe\protocol\types\inventory\stackrequest\PlaceStackRequestAction;
use pocketmine\player\Player;
use muqsit\simplepackethandler\SimplePacketHandler;
use pocketmine\item\Durable;
use pocketmine\network\mcpe\protocol\InventoryTransactionPacket;
use pocketmine\network\mcpe\protocol\ServerboundPacket;
use pocketmine\scheduler\ClosureTask;
use pocketmine\Server;
use pocketmine\utils\SingletonTrait;

final class AnvilManager {
    use SingletonTrait;

    public function __construct(
        private AnvilRegistry        $registry,
        private RepairService        $repairService,
        private EnchantCombineService $enchantService
    ) {
        self::setInstance($this);

        SimplePacketHandler::createInterceptor(Loader::getInstance())
            ->interceptIncoming(function(ItemStackRequestPacket $pkt, NetworkSession $ns): bool {
                return $this->handleTakeResult($pkt, $ns);
            });

        SimplePacketHandler::createInterceptor(Loader::getInstance())
            ->interceptIncoming(function(InventoryTransactionPacket $pkt, NetworkSession $ns): bool {
                $player = $ns->getPlayer();
                if ($player instanceof Player && $player->getCurrentWindow() instanceof AnvilInventory) {
                    $this->updateAnvilPreview($player, $player->getCurrentWindow());
                }
                return true;
            });

    }

    public function handleTakeResult(ItemStackRequestPacket $pkt, NetworkSession $ns): bool {
        $player = $ns->getPlayer();
        if (!$player instanceof Player) return true;

        $inv = $player->getCurrentWindow();
        if (!$inv instanceof AnvilInventory) return true;

        foreach ($pkt->getRequests() as $req) {
            foreach ($req->getActions() as $act) {
                if (
                    $act instanceof PlaceStackRequestAction
                    && $act->getSource()->getContainerName() === ContainerUIIds::CREATED_OUTPUT
                ) {
                    return $this->processResult($player, $inv, $req->getFilterStrings());
                }
            }
        }

        return true;
    }

    /**
     * @param Player $player
     * @param AnvilInventory $inv
     * @param string[] $filterStrings  
     * @return bool  
     */
    public function processResult(Player $player, AnvilInventory $inv, array $filterStrings): bool {
        $base = $inv->getItem(AnvilInventory::SLOT_INPUT);
        $mat  = $inv->getItem(AnvilInventory::SLOT_MATERIAL);

        if (
            $mat->getTypeId() === VanillaItems::ENCHANTED_BOOK()->getTypeId()
            && count($mat->getEnchantments()) > 0
        ) {
            foreach ($mat->getEnchantments() as $e) {
                $base->addEnchantment($e);
            }
            $this->giveResultAndClear($player, $inv, $base);
            return false;
        }

        $ces = $mat->getNamedTag()->getCompoundTag("AetherisCES");
        if ($ces !== null) {
            $newName = $filterStrings[0] ?? null;
            if ($newName !== null && $base->getCustomName() !== $newName) {
                $base->setCustomName($newName);
            }
            if ($base instanceof Durable) {
                $this->repairService->applyRepair($inv, $base, $mat, clone $base);
            }
            foreach ($ces->getValue() as $ename => $lvlTag) {
                $custom = CustomEnchantments::getEnchantmentByName((string)$ename);
                if ($custom !== null) {
                    CustomEnchantmentManager::applyEnchantment($base, $custom, $lvlTag->getValue());
                }
            }
            $this->giveResultAndClear($player, $inv, $base);
            return false;
        }

        return true;
    }

    private function giveResultAndClear(Player $player, AnvilInventory $inv, $resultItem): void {
        $player->getInventory()->addItem($resultItem);

        Loader::getInstance()->getScheduler()->scheduleDelayedTask(
            new ClosureTask(function() use($inv): void {
                $inv->setItem(AnvilInventory::SLOT_INPUT,    VanillaItems::AIR());
                $inv->setItem(AnvilInventory::SLOT_MATERIAL, VanillaItems::AIR());
            }),
            2  
        );
    }

    public function updateAnvilPreview(Player $player, AnvilInventory $inv): void {
        $base = $inv->getItem(AnvilInventory::SLOT_INPUT);
        $mat  = $inv->getItem(AnvilInventory::SLOT_MATERIAL);

        if ($base->isNull() || $mat->isNull()) {
            $inv->setItem(2, VanillaItems::AIR());
            return;
        }

        $ces = $mat->getNamedTag()->getCompoundTag("AetherisCES");
        if ($ces !== null) {
            $result = clone $base;

            foreach ($ces->getValue() as $ename => $lvlTag) {
                $custom = CustomEnchantments::getEnchantmentByName((string)$ename);
                if ($custom !== null) {
                    CustomEnchantmentManager::applyEnchantment($result, $custom, $lvlTag->getValue());
                }
            }

            $inv->setItem(2, $result);
        } else {
            $inv->setItem(2, VanillaItems::AIR());
        }
    }

}
