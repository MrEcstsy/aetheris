<?php

declare(strict_types=1);

namespace ecstsy\AetherisRecode\server\items;

use ecstsy\AetherisRecode\enchantments\CustomEnchantment;
use ecstsy\AetherisRecode\enchantments\CustomEnchantments;
use ecstsy\AetherisRecode\enchantments\Groups;
use ecstsy\MartianUtilities\utils\GeneralUtils;
use pocketmine\block\VanillaBlocks;
use pocketmine\item\Item;
use pocketmine\item\StringToItemParser;
use pocketmine\item\VanillaItems;
use pocketmine\nbt\tag\CompoundTag;
use pocketmine\player\Player;
use pocketmine\utils\TextFormat as C;

final class AetherisItemFactory {

    public static array $definitions = [];

    public static function create(string $key, array $args = []): Item {
        $def = self::$definitions[$key] ?? throw new \InvalidArgumentException("Unknown item definition: $key");

        /** @var Item $item */
        $item = clone $def['material'];

        $item->setCustomName(C::colorize(self::template($def['name'] ?? '', $args)));
        $loreLines = [];
        foreach ($def['lore'] ?? [] as $line) {
            $loreLines[] = C::colorize(self::template((string) $line, $args));
        }
        if ($loreLines) {
            $item->setLore($loreLines);
        }

        $root = $item->getNamedTag();
        $aTag = new CompoundTag();
        foreach ($def['nbt'] ?? [] as $tag => $valueTpl) {
            $resolved = self::template((string) $valueTpl, $args);
            if (is_numeric($resolved) && (string)(int)$resolved === $resolved) {
                $aTag->setInt($tag, (int)$resolved);
            } else {
                $aTag->setString($tag, $resolved);
            }
        }
        $root->setTag("Aetheris", $aTag);

        return $item;
    }

    public static function bankNote(?Player $player, int $amount): Item {
        $signer = $player?->getName() ?? 'Ethereal Hub';
        return self::create('bank_note', [
            'amount' => number_format($amount),
            'rawAmount' => $amount,
            'signer' => $signer
        ]);
    }

    public static function xpNote(?Player $player, int $amount): Item {
        $signer = $player?->getName() ?? 'Ethereal Hub';
        return self::create('xpnote', [
            'amount' => number_format($amount),
            'rawAmount' => $amount,
            'signer' => $signer
        ]);
    }

    public static function crateKey(string $type): Item {
        return self::create(strtolower($type) . '_key');
    }

    public static function enchantmentBook(CustomEnchantment $enchantment, int $level = 1, ?int $forcedSuccessChance = null, ?int $forcedDestroyChance = null): ?Item {
        $success = $forcedSuccessChance !== null ? $forcedSuccessChance : mt_rand(1, 100);
        $destroy = $forcedDestroyChance !== null ? $forcedDestroyChance : mt_rand(1, 100);

        $appliesTo = array_map('ucfirst', $enchantment->getApplicableItems());

        return self::create('enchantment_book', [
            'enchantment' => CustomEnchantments::getEnchantmentDisplayName($enchantment->getName(), C::colorize(Groups::translateGroupToColor($enchantment->getRarity()))),
            'enchant-no-color' => $enchantment->getName(),
            'level' => $level,
            'roman-level' => GeneralUtils::getRomanNumeral($level),
            'success' => $success,
            'destroy' => $destroy,
            'description' => $enchantment->getDescription(),
            'group-color' => C::colorize(Groups::translateGroupToColor($enchantment->getRarity())),
            'applies-to' => implode(", ", $appliesTo),
        ]);
    }

    public static function enchantOrb(string $type, int $max, int $new, int $success): ?Item {
        if (!in_array($type, ['weapon', 'armor', 'tool'])) {
            throw new \InvalidArgumentException("Invalid orb type: $type");
        }

        return self::create('orb', [
            'type' => $type,
            'upper-type' => strtoupper($type),
            'max' => $max,
            'new' => $new,
            'success' => $success,
        ]);
    }

    public static function whitescroll(): Item{ 
        return self::create('whitescroll');
    }

    public static function blackscroll(int $success = 100): Item {
        return self::create('blackscroll', [
            'success' => $success
        ]);
    }

    public static function renametag(): Item {
        return self::create('renametag');
    }

    public static function randomEnchantBook(string $group): Item {
        return self::create('random_book', [
            'group-name' => ucfirst($group),
            'group-color' => C::colorize(Groups::translateGroupToColor(Groups::getGroupId($group))),
            'group' => $group,
        ]);
    }

    public static function kitToken(string $kitName): Item {
        return self::create($kitName);
    }

    public static function currencyPouch(string $type): Item {
        return self::create($type);
    }

    public static function starDrop(string $starDropRarity): ?Item {
        if (!in_array($starDropRarity, ['simple', 'unique', 'elite', 'exotic', 'legendary', 'divine'])) {
            throw new \InvalidArgumentException("Invalid rarity: $starDropRarity");
        }

        return self::create($starDropRarity . "_stardrop", [
            'rarity' => $starDropRarity,
            'rarity-name' => ucfirst($starDropRarity)
        ]);
    }

    private static function template(string $template, array $args): string {
        foreach ($args as $k => $v) {
            $template = str_replace("{" . $k . "}", (string) $v, $template);
            $template = str_replace("{{$k}}", (string) $v, $template);
        }
        return $template;
    }
}