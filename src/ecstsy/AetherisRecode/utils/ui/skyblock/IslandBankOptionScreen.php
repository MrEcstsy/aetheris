<?php

declare(strict_types=1);

namespace ecstsy\AetherisRecode\utils\ui\skyblock;

use ecstsy\AetherisRecode\Loader;
use ecstsy\MartianUtilities\utils\screens\BaseScreen;
use InvalidArgumentException;
use pocketmine\player\Player;
use Vecnavium\FormsUI\CustomForm;
use pocketmine\utils\TextFormat as C;

final class IslandBankOptionScreen extends BaseScreen
{
    private CustomForm $form;

    public function __construct(Player $player, string $option)
    {
        $session = Loader::getPlayerManager()->getSession($player);
        $sbSession = Loader::getSkyBlockManager()->getSkyBlockByUuid($session->getSkyblock());

        $this->form = new CustomForm(function (Player $player, $data) use ($sbSession, $session, $option): void {
            if ($data === null) {
                return;
            }

            $amount = $data[0] ?? null;

            if ($amount === null || !is_numeric($amount) || (float)$amount <= 0) {
                $player->sendToastNotification(C::colorize(Loader::SERVER_TITLE), C::colorize("➤ Invalid or non-numerical amount!"));
                return;
            }

            if ($option === "withdraw") {
                if ($sbSession->getBank() < $amount) {
                    $player->sendToastNotification(C::colorize(Loader::SERVER_TITLE), C::colorize("➤ Your island has insufficient funds!"));
                    return;
                }

                $sbSession->removeBank($amount);
                $session->addBalance($amount);

                $player->sendToastNotification(C::colorize(Loader::SERVER_TITLE), C::colorize("➤ Successfully withdrew $" . number_format($amount) . " from your island bank!"));
            } elseif ($option === "deposit") {
                if ($session->getBalance() < $amount) {
                    $player->sendToastNotification(C::colorize(Loader::SERVER_TITLE), C::colorize("➤ You have insufficient funds!"));
                    return;
                }

                $sbSession->addBank($amount);
                $session->removeBalance($amount);

                $player->sendToastNotification(C::colorize(Loader::SERVER_TITLE), C::colorize("➤ Successfully deposited $" . number_format($amount) . " into your island bank!"));
            }
        });

        $this->form->setTitle($option === "withdraw" ? "Island Bank Withdrawal" : "Island Bank Deposit");
        $this->form->addInput(C::colorize("Amount:"), "e.g: 10000");
    }

    public static function displayForm(Player $player, string $option): void
    {
        if (!in_array($option, ["withdraw", "deposit"], true)) {
            throw new InvalidArgumentException("Unknown option: $option");
        }

        $screen = new self($player, $option);
        $player->sendForm($screen->getForm());
    }

    public function getForm(): CustomForm
    {
        return $this->form;
    }
}
