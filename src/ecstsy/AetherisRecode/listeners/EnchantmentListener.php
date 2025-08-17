<?php

declare(strict_types=1);

namespace ecstsy\AetherisRecode\listeners;

use ecstsy\AetherisRecode\enchantments\CustomEnchantmentManager;
use ecstsy\AetherisRecode\enchantments\manager\EnchantEffectManager;
use ecstsy\AetherisRecode\enchantments\manager\EnchantmentEventRegistry;
use ecstsy\AetherisRecode\Loader;
use ecstsy\AetherisRecode\utils\Utils;
use ecstsy\MartianUtilities\utils\GeneralUtils;
use pocketmine\block\VanillaBlocks;
use pocketmine\entity\effect\EffectInstance;
use pocketmine\entity\effect\VanillaEffects;
use pocketmine\entity\Living;
use pocketmine\event\block\BlockBreakEvent;
use pocketmine\event\entity\EntityDamageEvent;
use pocketmine\event\inventory\InventoryTransactionEvent;
use pocketmine\event\Listener;
use pocketmine\event\player\PlayerItemHeldEvent;
use pocketmine\event\player\PlayerJoinEvent;
use pocketmine\inventory\ArmorInventory;
use pocketmine\inventory\CallbackInventoryListener;
use pocketmine\inventory\Inventory;
use pocketmine\inventory\PlayerInventory;
use pocketmine\inventory\transaction\action\SlotChangeAction;
use pocketmine\item\Item;
use pocketmine\item\VanillaItems;
use pocketmine\player\Player;

final class EnchantmentListener implements Listener
{

    public function onPlayerJoin(PlayerJoinEvent $event): void
    {
        $player = $event->getPlayer();

        $player->getArmorInventory()->getListeners()->add(new CallbackInventoryListener(
            function (Inventory $inventory, int $slot, Item $oldItem): void {
                EnchantEffectManager::onArmorSlotChange($inventory, $slot, $oldItem);
            },
            function (Inventory $inventory, array $oldContents): void {
            }
        ));

        $player->getInventory()->getListeners()->add(new CallbackInventoryListener(
            function (Inventory $inventory, int $slot, Item $oldItem): void {
                EnchantEffectManager::onInventorySlotChange($inventory, $slot, $oldItem);
            },
            function (Inventory $inventory, array $oldContents): void {
            }
        ));
    }

    /**
     * @priority HIGHEST
     */
    public function onPlayerItemHeld(PlayerItemHeldEvent $event): void
    {
        $player = $event->getPlayer();
        $inventory = $player->getInventory();

        $oldItem = $player->getInventory()->getItemInHand();
        $newItem = $inventory->getItem($event->getSlot());

        EnchantEffectManager::updateHeldItemEffects($player, $oldItem, $newItem);
    }

    /**
     * @priority HIGHEST
     */
    public function onInventoryTransaction(InventoryTransactionEvent $event): void
    {
        foreach ($event->getTransaction()->getActions() as $action) {
            $inv = $event->getTransaction()->getSource()->getInventory();
            $player = $inv instanceof PlayerInventory ? $inv->getHolder() : ($inv instanceof ArmorInventory ? $inv->getHolder() : null);

            if ($player instanceof Player) {
                if ($inv instanceof ArmorInventory && $action instanceof SlotChangeAction) {
                    $slot = $action->getSlot();
                    $oldItem = $action->getSourceItem();
                    EnchantEffectManager::onArmorSlotChange($inv, $slot, $oldItem);
                }
            }
        }
    }

    /**
     * @priority HIGHEST
     */
    public function onEntityDamage(EntityDamageEvent $event): void {
        $entity = $event->getEntity();
        if (!$entity instanceof Player) return;

        $item = $entity->getInventory()->getItemInHand();
        $enchantments = CustomEnchantmentManager::getEnchantments($item);

        foreach ($enchantments as $enchantName => $level) {
            $handlers = EnchantmentEventRegistry::getHandlers('entity_damage');
            if (isset($handlers[$enchantName])) {
                $enchantObj = CustomEnchantmentManager::getEnchantment($enchantName);
                if ($enchantObj !== null) {
                    $handlers[$enchantName]($event, $enchantObj, $level);
                }
            }
        }
    }

    /**
     * @priority HIGHEST
     */
    public function onBlockBreak(BlockBreakEvent $event): void {
        $player = $event->getPlayer();
        $item = $player->getInventory()->getItemInHand();
        $enchantments = CustomEnchantmentManager::getEnchantments($item);

        foreach ($enchantments as $enchantName => $level) {
            $handlers = EnchantmentEventRegistry::getHandlers('block_break');
            if (isset($handlers[$enchantName])) {
                $enchantObj = CustomEnchantmentManager::getEnchantment($enchantName);
                if ($enchantObj !== null) {
                    $handlers[$enchantName]($event, $enchantObj, $level);
                }
            }
        }
    }
}
