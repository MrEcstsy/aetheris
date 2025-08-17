<?php

declare(strict_types=1);

namespace ecstsy\AetherisRecode\utils\ui\skyblock;

use ecstsy\AetherisRecode\Loader;
use ecstsy\AetherisRecode\utils\inventory\CustomSizedInvMenu;
use ecstsy\AetherisRecode\utils\Utils;
use ecstsy\MartianUtilities\utils\PlayerUtils;
use ecstsy\MartianUtilities\utils\screens\BaseScreen;
use muqsit\invmenu\InvMenu;
use muqsit\invmenu\transaction\DeterministicInvMenuTransaction;
use muqsit\invmenu\type\InvMenuTypeIds;
use pocketmine\block\utils\DyeColor;
use pocketmine\block\utils\MobHeadType;
use pocketmine\block\VanillaBlocks;
use pocketmine\item\VanillaItems;
use pocketmine\player\Player;
use pocketmine\Server;
use pocketmine\world\Position;
use Vecnavium\FormsUI\SimpleForm;
use pocketmine\utils\TextFormat as C;
use Ramsey\Uuid\Uuid;

final class IslandControlScreen extends BaseScreen
{
    public InvMenu $menu;
    public SimpleForm $form;

    public function __construct(private Player $player)
    {
        $session = Loader::getPlayerManager()->getSession($player);
        $sbSession = Loader::getSkyBlockManager()->getSkyBlockByUuid($session->getSkyblock());
        $memberNames = implode(", ", $sbSession->getMemberNames());

        $this->form = new SimpleForm(function (Player $player, $data) use ($sbSession, $session): void {
            if ($data === null) {
                return;
            }

            match ($data) {
                0 => IslandBankManagementScreen::displayForm($player),
                1 => $player->sendForm((new IslandMemberManagementScreen($player))->getForm()),
                2 => $player->sendForm((new IslandSettingsScreen($session->getIsland()))->getForm()),
                3 => Utils::timedTeleport(
                    $player,
                    new Position(
                        $sbSession->getSpawn()->getX(),
                        $sbSession->getSpawn()->getY(),
                        $sbSession->getSpawn()->getZ(),
                        Server::getInstance()->getWorldManager()->getWorldByName($sbSession->getWorld())
                    ),
                    "Preparing to teleport...",
                    "Teleported to your island."
                ),
                default => null,
            };
        });

        $this->form->setTitle(C::colorize("&r&8" . $sbSession->getName() . "'s Command Post"));
        $this->form->setContent(C::colorize("&r&7Island Overview:\n&r&fIsland Name: &e" . $sbSession->getName() . "\n&r&fLeader: &e" . Server::getInstance()->getPlayerByUUID(Uuid::fromString($sbSession->getLeader()))->getName() . "\n&r&fBank Balance: &e$" . number_format($sbSession->getBank()) . "\n&r&fMembers: &e" . $memberNames));
        $this->form->addButton(C::colorize("&r&8Bank Management"));
        $this->form->addButton(C::colorize("&r&8Member Management"));
        $this->form->addButton(C::colorize("&r&8Island Settings"));
        $this->form->addButton(C::colorize("&r&8Teleport to Island"));

        $this->menu = CustomSizedInvMenu::create(9);
        $inventory = $this->menu->getInventory();

        $this->menu->setName(C::colorize("&r&8" . $sbSession->getName() . "'s Command Post"));

        $dividerPane = VanillaBlocks::STAINED_GLASS_PANE()->setColor(DyeColor::BLACK())->asItem();
        $dividerPane->setCustomName(C::colorize("&r&8"));

        for ($i = 0; $i < $inventory->getSize(); $i++) {
            $inventory->setItem($i, $dividerPane);
        }

        $itemIcons = [
            [
                'name' => "&r&bIsland Overview",
                'lore' => ["&r&fIsland Name: &e" . $sbSession->getName(), "&r&fLeader: &e" . Server::getInstance()->getPlayerByUUID(Uuid::fromString($sbSession->getLeader()))->getName(), "&r&fBank Balance: &e$" . number_format($sbSession->getBank()), "&r&fMembers: &e" . $memberNames],
                'icon' => VanillaItems::PAPER(),
                'slot' => 0
            ],
            [
                'name' => "&r&6Bank Management",
                'lore' => ["&r&fCurrent Balance: &e$" . number_format($sbSession->getBank()), "&r&fClick to manage the bank."],
                'icon' => VanillaItems::GOLD_NUGGET(),
                'slot' => 2
            ],
            [
                'name' => '&r&aMember Management',
                'lore' => ['&r&fView and manage island members.'],
                'icon' => VanillaBlocks::MOB_HEAD()->setMobHeadType(MobHeadType::PLAYER())->asItem(),
                'slot' => 4
            ],
            [
                'name' => '&r&aIsland Settings',
                'lore' => ['&r&fConfigure your island settings.'],
                'icon' => VanillaItems::REDSTONE_DUST(),
                'slot' => 6
            ],
            [
                'name' => '&r&dTeleport to Island',
                'lore' => ['&r&fClick to teleport to your island spawn.'],
                'icon' => VanillaItems::ENDER_PEARL(),
                'slot' => 8
            ]
        ];

        foreach ($itemIcons as $icon) {
            $item = $icon['icon'];
            $item->setCustomName(C::colorize($icon['name']));
            $item->setLore(array_map(fn($lore) => C::colorize($lore), $icon['lore']));
            $inventory->setItem($icon['slot'], $item);
        }

        $this->menu->setListener(InvMenu::readonly(function (DeterministicInvMenuTransaction $transaction): void {
            $player  = $transaction->getPlayer();
            $slot    = $transaction->getAction()->getSlot();
            $session = Loader::getPlayerManager()->getSession($player);
            $sbUuid  = $session->getSkyblock();
            $sbSession = Loader::getSkyBlockManager()->getSkyBlockByUuid($sbUuid);
        
            PlayerUtils::playSound($player, "mob.enderdragon.flap");
        
            switch ($slot) {
                case 2:
                    $player->removeCurrentWindow();
                    $transaction->then(fn() => IslandBankManagementScreen::displayForm($player));
                    break;
        
                case 4:
                    $player->removeCurrentWindow();
                    $transaction->then(fn() => IslandMemberManagementScreen::displayForm($player));
                    break;
        
                case 6:
                    $player->removeCurrentWindow();
                    $transaction->then(fn() => IslandSettingsScreen::displayForm($player, $session->getIsland()));
                    break;
        
                case 8:
                    $player->removeCurrentWindow();
                    Utils::timedTeleport(
                        $player,
                        new Position(
                            $sbSession->getSpawn()->getX(),
                            $sbSession->getSpawn()->getY(),
                            $sbSession->getSpawn()->getZ(),
                            Server::getInstance()->getWorldManager()->getWorldByName($sbSession->getWorld())
                        ),
                        "Preparing to teleport…",
                        "Teleported to your island."
                    );
                    break;
        
                default:
                    break;
            }
    
        }));
    }

    public function getMenu(): ?InvMenu
    {
        return $this->menu;
    }
    public function getForm(): SimpleForm
    {
        return $this->form;
    }

    public static function display($player): void
    {
        $screen = new self($player);
        $screen->getMenu()->send($player);
    }

    public static function displayForm($player): void
    {
        $screen = new self($player);
        $player->sendForm($screen->getForm());
    }
}
