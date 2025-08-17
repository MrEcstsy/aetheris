<?php

declare(strict_types=1);

namespace ecstsy\AetherisRecode\enchantments\manager;

use ecstsy\AetherisRecode\enchantments\CustomEnchantmentManager;
use pocketmine\entity\effect\EffectInstance;
use pocketmine\entity\effect\StringToEffectParser;
use pocketmine\entity\effect\VanillaEffects;
use pocketmine\inventory\ArmorInventory;
use pocketmine\inventory\Inventory;
use pocketmine\inventory\PlayerInventory;
use pocketmine\item\Item;
use pocketmine\player\Player;

final class EnchantEffectManager {

    private const ARMOR_ONLY_ENCHANTS = [
        'glowing',
        'aquatic',
        'gears',
    ];

    public const EFFECT_ENCHANTS = [
        "haste" => ["haste", 3],
        'aquatic' => ['water_breathing', 1],
        'gears' => ['speed', 3],
        'glowing' => ['night_vision', 1],
    ];

    public static function onArmorSlotChange(ArmorInventory $inventory, int $slot, Item $oldItem): void {
        $player = $inventory->getHolder();
        if (!$player instanceof Player) return;

        $newItem = $inventory->getItem($slot);
        if ($newItem->equals($oldItem, false)) {
            return;
        }

        $armorItems = [];
        for ($i = 0; $i < $inventory->getSize(); $i++) {
            $armorItems[] = $inventory->getItem($i);
        }

        $activeEnchants = [];
        foreach ($armorItems as $item) {
            foreach (CustomEnchantmentManager::getEnchantments($item) as $enchant => $level) {
                if (isset(self::EFFECT_ENCHANTS[$enchant])) {
                    $activeEnchants[$enchant] = max($activeEnchants[$enchant] ?? 0, $level);
                }
            }
        }

        foreach (self::EFFECT_ENCHANTS as $enchant => [$effectId, $maxLevel]) {
            $effect = StringToEffectParser::getInstance()->parse($effectId);
            if ($effect === null) continue;

            if (!isset($activeEnchants[$enchant])) {
                $current = $player->getEffects()->get($effect);
                if ($current !== null && $current->getAmplifier() < $maxLevel) {
                    $player->getEffects()->remove($effect);
                }
            }
        }

        foreach ($activeEnchants as $enchant => $level) {
            [$effectId, $maxLevel] = self::EFFECT_ENCHANTS[$enchant];
            $effect = StringToEffectParser::getInstance()->parse($effectId);
            if ($effect === null) continue;

            $current = $player->getEffects()->get($effect);
            if ($current === null || $current->getAmplifier() + 1 < $level) {
                $player->getEffects()->add(new EffectInstance(
                    $effect,
                    2147483647,
                    $level - 1,
                    false
                ));
            }
        }
    }

    public static function onInventorySlotChange(Inventory $inventory, int $slot, Item $oldItem): void {
        if ($inventory instanceof PlayerInventory || $inventory instanceof ArmorInventory) {
            $player = $inventory->getHolder();
            if (!$player instanceof Player) return;

            $heldSlot = $player->getInventory()->getHeldItemIndex();
            $newItem = $inventory->getItem($slot);

            if ($inventory instanceof PlayerInventory && $slot === $heldSlot) {
                foreach (self::EFFECT_ENCHANTS as $enchant => [$effectId, $maxLevel]) {
                    $effect = StringToEffectParser::getInstance()->parse($effectId);
                    if ($effect === null) continue;

                    if (!$oldItem->isNull() && CustomEnchantmentManager::hasEnchantment($oldItem, $enchant)) {
                        $current = $player->getEffects()->get($effect);
                        if ($current !== null && $current->getAmplifier() < $maxLevel) {
                            $player->getEffects()->remove($effect);
                        }
                    }

                    if (!$newItem->isNull() && CustomEnchantmentManager::hasEnchantment($newItem, $enchant)) {
                        $level = CustomEnchantmentManager::getLevel($newItem, $enchant);
                        $current = $player->getEffects()->get($effect);
                        if ($current === null || $current->getAmplifier() + 1 < $level) {
                            $player->getEffects()->add(new EffectInstance(
                                $effect,
                                2147483647,
                                $level - 1,
                                false
                            ));
                        }
                    }
                }
            }
        }
    }

    public static function updateHeldItemEffects(Player $player, ?Item $oldItem, ?Item $newItem): void {
        foreach (self::EFFECT_ENCHANTS as $enchant => [$effectId, $maxLevel]) {
            if (in_array($enchant, self::ARMOR_ONLY_ENCHANTS, true)) {
                continue; 
            }            $effect = StringToEffectParser::getInstance()->parse($effectId);
            if ($effect === null) continue;

            if ($oldItem !== null && CustomEnchantmentManager::hasEnchantment($oldItem, $enchant)) {
                $current = $player->getEffects()->get($effect);
                if ($current !== null && $current->getAmplifier() < $maxLevel) {
                    $player->getEffects()->remove($effect);
                }
            }
            if ($newItem !== null && CustomEnchantmentManager::hasEnchantment($newItem, $enchant)) {
                $level = CustomEnchantmentManager::getLevel($newItem, $enchant);
                $current = $player->getEffects()->get($effect);
                if ($current === null || $current->getAmplifier() + 1 < $level) {
                    $player->getEffects()->add(new EffectInstance(
                        $effect,
                        2147483647,
                        $level - 1,
                        false
                    ));
                }
            }
        }
    }

    // NEEDED?
    public static function onArmorContentsChange(ArmorInventory $inventory, array $oldContents): void {
        foreach ($oldContents as $slot => $oldItem) {
            $newItem = $inventory->getItem($slot);
            if (!$newItem->equals($oldItem, false)) {
                self::onArmorSlotChange($inventory, $slot, $oldItem);
            }
        }
    }
}