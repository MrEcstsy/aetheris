<?php

declare(strict_types=1);

namespace ecstsy\AetherisRecode\server\items;

use pocketmine\block\VanillaBlocks;
use pocketmine\item\StringToItemParser;
use pocketmine\item\VanillaItems;

final class AetherisItem {

        public static function init(): void {
        AetherisItemFactory::$definitions = [
            'bank_note' => [
                'material' => VanillaItems::PAPER(),
                'name' => "&r&l&bBank Note &r&7(Right Click)",
                'lore' => [
                    '&r&8▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬',
                    "&r&fValue: &a$" . "{amount}",
                    "&r&fSigner: &b{signer}",
                    "&r&8▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬",
                    "&r&7Redeem this note to receive money.",
                ],
                'nbt' => ['aetherisItem' => 'econote', 'worth' => '{rawAmount}'],
            ],
            'xpnote' => [
                'material' => VanillaItems::EXPERIENCE_BOTTLE(),
                'name' => "&r&l&aExperience Bottle &r&7(Right Click)",
                'lore' => [
                    '&r&8▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬',
                    '&r&fExperience: &a{amount} EXP',
                    '&r&fSigner: &a{signer}',
                    "&r&8▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬",
                    "&r&7Redeem this note to receive exp.",
                ],
                'nbt' => ['aetherisItem' => 'xpnote', 'worth' => '{rawAmount}'],
            ],
            'vote_key' => [
                'material' => VanillaBlocks::SUNFLOWER()->asItem(),
                'name'     => '&r&l&aVote Crate &r&7(/vote) Key',
                'lore'     => ['&r&7Use at /warp crates'],
                'nbt'      => ['crate_key' => 'vote'],
            ],
            'void_key' => [
                'material' => VanillaItems::NETHER_STAR(),
                'name'     => '&r&l&bVoid Crate &r&7(I) Key',
                'lore'     => ['&r&7Use at /warp crates'],
                'nbt'      => ['crate_key' => 'void'],
            ],
            'meteorite_key' => [
                'material' => VanillaItems::MAGMA_CREAM(),
                'name'     => '&r&l&eMeteorite Crate &r&7(II) Key',
                'lore'     => ['&r&7Use at /warp crates'],
                'nbt'      => ['crate_key' => 'meteorite'],
            ],
            'stardust_key' => [
                'material' => VanillaItems::GLOWSTONE_DUST(),
                'name'     => '&r&l&dStardust Crate &r&7(III) Key',
                'lore'     => ['&r&7Use at /warp crates'],
                'nbt'      => ['crate_key' => 'stardust'],
            ],
            'enchantment_book' => [
                'material' => VanillaItems::ENCHANTED_BOOK(),
                'name' => '&r{group-color}&l{enchantment} {roman-level}',
                'lore' => [
                    '&r&a{success}% Success Rate',
                    '&r&c{destroy}% Destroy Rate',
                    '&r&e{description}',
                    '&r&7{applies-to} Enchantment',
                    "&r&7Drag n' Drop onto item to enchant.",
                ],
                'nbt' => ['aetherisItem' => 'enchantment_book', 'enchant_book' => '{enchant-no-color}', 'level' => '{level}', 'success' => '{success}', 'destroy' => '{destroy}'],
            ],
            'orb' => [
                'material' => StringToItemParser::getInstance()->parse("ender_eye"),
                'name' => '&r&6&l{upper-type} Enchantment Orb &6[&a{max}&6]',
                'lore' => [
                    "&r&a{success}% Success Rate",
                    "",
                    "&r&6+{new} Enchantment slots",
                    "&r&6{max} Max Enchantment Slots",
                    "",
                    "&r&eIncreases the # of enchantment",
                    "&r&eslots on a {type} by {new},",
                    "&r&eup to a maximum of {max}",
                    "&r&7Drag n'' Drop onto an item to apply."
                ],
                'nbt' => ['aetherisItem' => '{type}', "max" => "{max}", "new" => "{new}", "success" => "{success}"],
            ],
            'whitescroll' => [
                'material' => StringToItemParser::getInstance()->parse("empty_map"),
                'name' => '&r&fWhite Scroll',
                'lore' => [
                    "&r&fPrevents an item from being destroyed",
                    "&r&fdue to a failed Enchantment Book.",
                    "&r7ePlace scroll on item to apply."
                ],
                'nbt' => ["aetherisItem" => 'whitescroll'],
            ],
            'blackscroll' => [
                'material' => VanillaItems::INK_SAC(),
                'name' => '&r&f&lBlack Scroll',
                'lore' => [
                    "&r&7Removes a random enchantment",
                    "&r&7from an item and converts",
                    "&r&7it into a &f{success}% &r&7success book.",
                    "&r&fPlace scroll onto item to extract."
                ],
                'nbt' => ["aetherisItem" => 'blackscroll', "success" => "{success}"],
            ],
            'renametag' => [
                'material' => VanillaItems::NAME_TAG(),
                'name' => '&r&67lItem NameTag &r&7(Right Click)',
                'lore' => [
                    "&r&7Rename and customize your equipment"
                ],
                'nbt' => ['aetherisItem' => 'renametag'],
            ],
            'random_book' => [
                'material' => VanillaItems::BOOK(),
                'name' => '&r{group-color}{group-name}&l Enchantment Book &r&7(Right Click)',
                'lore' => [
                    "&r&7Right Click while holding to receive a random",
                    "&r{group-color}{group-name} &r&7enchantment book.",
                ],
                'nbt' => ['aetherisItem' => 'random_book', 'group' => '{group}'],
            ],
            'member_kit' => [
                'material' => VanillaItems::NETHER_STAR(),
                'name' => '&r&l&bMember Kit &r&7(Right Click)',
                'lore' => [
                    '&r&8▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬',
                    '&r&fKit: &bMember',
                    '&r&8▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬',
                    '&r&7Right Click to receive your kit.',
                ],
                'nbt' => ['aetherisItem' => 'kit_token', 'kit' => 'member'],
            ],
            'initiate_kit' => [
                'material' => VanillaItems::NETHER_STAR(),
                'name' => '&r&l&bInitiate Kit &r&7(Right Click)',
                'lore' => [
                    '&r&8▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬',
                    '&r&fKit: &bInitiate',
                    '&r&8▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬',
                    '&r&7Right Click to receive your kit.',
                ],
                'nbt' => ['aetherisItem' => 'kit_token', 'kit' => 'initiate'],
            ],
            'explorer_kit' => [
                'material' => VanillaItems::NETHER_STAR(),
                'name' => '&r&l&bExplorer Kit &r&7(Right Click)',
                'lore' => [
                    '&r&8▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬',
                    '&r&fKit: &bExplorer',
                    '&r&8▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬',
                    '&r&7Right Click to receive your kit.',
                ],
                'nbt' => ['aetherisItem' => 'kit_token', 'kit' => 'explorer'],
            ],
            'champion_kit' => [
                'material' => VanillaItems::NETHER_STAR(),
                'name' => '&r&l&bChampion Kit &r&7(Right Click)',
                'lore' => [
                    '&r&8▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬',
                    '&r&fKit: &bChampion',
                    '&r&8▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬',
                    '&r&7Right Click to receive your kit.',
                ],
                'nbt' => ['aetherisItem' => 'kit_token', 'kit' => 'champion'],
            ],
            'warden_kit' => [
                'material' => VanillaItems::NETHER_STAR(),
                'name' => '&r&l&bWarden Kit &r&7(Right Click)',
                'lore' => [
                    '&r&8▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬',
                    '&r&fKit: &bWarden',
                    '&r&8▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬',
                    '&r&7Right Click to receive your kit.',
                ],
                'nbt' => ['aetherisItem' => 'kit_token', 'kit' => 'warden'],
            ],
            'aetherian_kit' => [
                'material' => VanillaItems::NETHER_STAR(),
                'name' => '&r&l&bAetherian Kit &r&7(Right Click)',
                'lore' => [
                    '&r&8▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬',
                    '&r&fKit: &bAetherian',
                    '&r&8▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬▬',
                    '&r&7Right Click to receive your kit.',
                ],
                'nbt' => ['aetherisItem' => 'kit_token', 'kit' => 'aetherian'],
            ],
            'xp_pouch_1' => [
                'material' => VanillaBlocks::CHEST()->asItem(),
                'name' => "&r&l&f► &dTier I XP Pouch &r&7(Right Click)",
                'lore' => [
                    "&r&d► &7Open this pouch to receive experience!"
                ],
                'nbt' => ['aetherisItem' => 'xp_pouch', 'tier' => 1],
            ],
            'xp_pouch_2' => [
                'material' => VanillaBlocks::CHEST()->asItem(),
                'name' => "&r&l&f► &dTier II XP Pouch &r&7(Right Click)",
                'lore' => [
                    "&r&d► &7Open this pouch to receive experience!"
                ],
                'nbt' => ['aetherisItem' => 'xp_pouch', 'tier' => 2],
            ],
            'xp_pouch_3' => [
                'material' => VanillaBlocks::CHEST()->asItem(),
                'name' => "&r&l&f► &dTier III XP Pouch &r&7(Right Click)",
                'lore' => [
                    "&r&d► &7Open this pouch to receive experience!"
                ],
                'nbt' => ['aetherisItem' => 'xp_pouch', 'tier' => 3],
            ],
            'money_pouch_1' => [
                'material' => VanillaBlocks::ENDER_CHEST()->asItem(),
                'name' => "&r&l&f► &dTier I Money Pouch &r&7(Right Click)",
                'lore' => [
                    "&r&d► &7Open this pouch to receive money!"
                ],
                'nbt' => ['aetherisItem' => 'money_pouch', 'tier' => 1],
            ],
            'money_pouch_2' => [
                'material' => VanillaBlocks::ENDER_CHEST()->asItem(),
                'name' => "&r&l&f► &dTier II Money Pouch &r&7(Right Click)",
                'lore' => [
                    "&r&d► &7Open this pouch to receive money!"
                ],
                'nbt' => ['aetherisItem' => 'money_pouch', 'tier' => 2],
            ],
            'money_pouch_3' => [
                'material' => VanillaBlocks::ENDER_CHEST()->asItem(),
                'name' => "&r&l&f► &dTier III Money Pouch &r&7(Right Click)",
                'lore' => [
                    "&r&d► &7Open this pouch to receive money!"
                ],
                'nbt' => ['aetherisItem' => 'money_pouch', 'tier' => 3],
            ],
            'simple_stardrop' => [
                'material' => VanillaItems::NETHER_STAR(),
                'name' => '&r★ &lStar Drop &7[&fSimple&7]',
                'lore' => [
                    '&r&7A mysterious relic pulsing with energy.',
                    '&r&7Right-click to unveil its contents.',
                    '',
                    '&r&8• &fRarity: &7Simple',
                    '',
                    '&r&d&lTip: &r&dBetter rarities yield greater rewards.',
                ],
                'nbt' => ['aetherisItem' => 'star_drop', 'rarity' => 'simple'],
            ],
            'unique_stardrop' => [
                'material' => VanillaItems::NETHER_STAR(),
                'name' => '&r★ &lStar Drop &7[&aUnique&7]',
                'lore' => [
                    '&r&7A mysterious relic pulsing with energy.',
                    '&r&7Right-click to unveil its contents.',
                    '',
                    '&r&8• &fRarity: &aUnique',
                    '',
                    '&r&d&lTip: &r&dBetter rarities yield greater rewards.',
                ],
                'nbt' => ['aetherisItem' => 'star_drop', 'rarity' => 'unique'],
            ],
            'elite_stardrop' => [
                'material' => VanillaItems::NETHER_STAR(),
                'name' => '&r★ &lStar Drop &7[&3Elite&7]',
                'lore' => [
                    '&r&7A mysterious relic pulsing with energy.',
                    '&r&7Right-click to unveil its contents.',
                    '',
                    '&r&8• &fRarity: &3Elite',
                    '',
                    '&r&d&lTip: &r&dBetter rarities yield greater rewards.',
                ],
                'nbt' => ['aetherisItem' => 'star_drop', 'rarity' => 'elite'],
            ],
            'exotic_stardrop' => [
                'material' => VanillaItems::NETHER_STAR(),
                'name' => '&r★ &lStar Drop &7[&eExotic&7]',
                'lore' => [
                    '&r&7A mysterious relic pulsing with energy.',
                    '&r&7Right-click to unveil its contents.',
                    '',
                    '&r&8• &fRarity: &eExotic',
                    '',
                    '&r&d&lTip: &r&dBetter rarities yield greater rewards.',
                ],
                'nbt' => ['aetherisItem' => 'star_drop', 'rarity' => 'exotic'],
            ],
            'legendary_stardrop' => [
                'material' => VanillaItems::NETHER_STAR(),
                'name' => '&r★ &lStar Drop &7[&6Legendary&7]',
                'lore' => [
                    '&r&7A mysterious relic pulsing with energy.',
                    '&r&7Right-click to unveil its contents.',
                    '',
                    '&r&8• &fRarity: &6Legendary',
                    '',
                    '&r&d&lTip: &r&dBetter rarities yield greater rewards.',
                ],
                'nbt' => ['aetherisItem' => 'star_drop', 'rarity' => 'legendary'],
            ],
            'divine_stardrop' => [
                'material' => VanillaItems::NETHER_STAR(),
                'name' => '&r★ &lStar Drop &7[&5Divine&7]',
                'lore' => [
                    '&r&7A mysterious relic pulsing with energy.',
                    '&r&7Right-click to unveil its contents.',
                    '',
                    '&r&8• &fRarity: &5Divine',
                    '',
                    '&r&d&lTip: &r&dBetter rarities yield greater rewards.',
                ],
                'nbt' => ['aetherisItem' => 'star_drop', 'rarity' => 'divine'],
            ]
        ];
    }
}