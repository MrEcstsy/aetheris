<?php

declare(strict_types=1);

namespace ecstsy\AetherisRecode\utils\ui\skyblock;

use ecstsy\AetherisRecode\Loader;
use ecstsy\MartianUtilities\utils\screens\BaseScreen;
use pocketmine\player\Player;
use Vecnavium\FormsUI\SimpleForm;
use pocketmine\utils\TextFormat as C;

final class IslandBankManagementScreen extends BaseScreen
{
    private const WITHDRAW = "withdraw";
    private const DEPOSIT = "deposit";
    private const CHEST_INVENTORIES = "chest_inventories";

    private SimpleForm $form;

    public function __construct(Player $player)
    {
        $session = Loader::getPlayerManager()->getSession($player);
        $sbSession = Loader::getSkyBlockManager()->getSkyBlockByUuid($session->getSkyblock());

        $this->form = new SimpleForm(function (Player $player, $data) use ($session): void {
            if ($data === null) {
                $this->redirectToControlScreen($player, $session);
                return;
            }

            match ($data) {
                0 => IslandBankOptionScreen::displayForm($player, self::WITHDRAW),
                1 => IslandBankOptionScreen::displayForm($player, self::DEPOSIT),
                default => null,
            };
        });

        $this->form->setTitle(C::colorize("&r&8Island Bank"));
        $this->form->setContent(C::colorize("&r&fIsland Balance: &a$" . number_format($sbSession->getBank()) . "\n&r&fSelect an option:"));
        $this->form->addButton(C::colorize("&r&8Withdraw"));
        $this->form->addButton(C::colorize("&r&8Deposit"));
    }

    public static function displayForm(Player $player): void
    {
        $screen = new self($player);
        $player->sendForm($screen->getForm());
    }

    public function getForm(): SimpleForm
    {
        return $this->form;
    }

    private function redirectToControlScreen(Player $player, $session): void
    {
        if ($session->getSetting(self::CHEST_INVENTORIES) === true) {
            IslandControlScreen::display($player);
        } else {
            IslandControlScreen::displayForm($player);
        }
    }
}
