<?php

declare(strict_types=1);

namespace ecstsy\AetherisRecode\utils\ui\skyblock;

use ecstsy\AetherisRecode\Loader;
use ecstsy\MartianUtilities\interfaces\ScreenInterface;
use ecstsy\MartianUtilities\utils\PlayerUtils;
use ecstsy\MartianUtilities\utils\screens\BaseScreen;
use pocketmine\player\Player;
use pocketmine\Server;
use Vecnavium\FormsUI\CustomForm;
use pocketmine\utils\TextFormat as C;

final class IslandInvitePlayerScreen extends BaseScreen {

    public CustomForm $form;

    public function __construct(Player $viewer) {
        $dropdownPlayers = array_values(array_filter(
            Server::getInstance()->getOnlinePlayers(),
            fn(Player $player) => strtolower($player->getName()) !== strtolower($viewer->getName()) &&
                Loader::getPlayerManager()->getSession($player)->getSkyblock() === ''
        ));
    
        $dropdownDisplay = array_map(fn(Player $player) => $player->getName(), $dropdownPlayers);
    
        $noPlayersPlaceholder = "No online players";
        if (empty($dropdownDisplay)) {
            $dropdownDisplay[] = $noPlayersPlaceholder;
        }
    
        $this->form = new CustomForm(function (Player $viewer, $data) use ($dropdownPlayers, $dropdownDisplay, $noPlayersPlaceholder): void {
            if ($data === null) {
                $screen = new IslandMemberManagementScreen($viewer);
                $viewer->sendForm($screen->getForm());
                return;
            }
    
            $typedName = trim($data[0] ?? '');
            $dropdownIndex = $data[1] ?? null;
    
            if (empty($dropdownPlayers) || ($dropdownDisplay[0] === $noPlayersPlaceholder && $typedName === '')) {
                return;
            }
    
            $inviteName = $typedName ?: ($dropdownPlayers[$dropdownIndex]->getName() ?? '');
    
            if ($inviteName === '' || $inviteName === $noPlayersPlaceholder) {
                $viewer->sendToastNotification(C::colorize(Loader::SERVER_TITLE), C::colorize("&c⚠ Invalid player name."));
                return;
            }
    
            $targetPlayer = PlayerUtils::getPlayerByPrefix($inviteName);
            if ($targetPlayer === null) {
                $viewer->sendToastNotification(C::colorize(Loader::SERVER_TITLE), C::colorize("&c⚠ Player &b{$inviteName}&c is not online."));
                return;
            }
    
            $targetSession = Loader::getPlayerManager()->getSession($targetPlayer);
            if ($targetSession->getSkyblock() !== '') {
                $viewer->sendToastNotification(C::colorize(Loader::SERVER_TITLE), C::colorize("&c⚠ Player &b{$inviteName}&c is already in an island."));
                return;
            }
    
            $session = Loader::getPlayerManager()->getSession($viewer);
            $sbSession = Loader::getSkyBlockManager()->getSkyBlockByUuid($session->getSkyblock());
    
            if ($sbSession === null) {
                $viewer->sendToastNotification(C::colorize(Loader::SERVER_TITLE), C::colorize("&c⚠ You do not have an island."));
                return;
            }
    
            $members = array_map(fn($member) => $member['name'], $sbSession->getMembers());
            if (in_array($inviteName, $members, true)) {
                $viewer->sendToastNotification(C::colorize(Loader::SERVER_TITLE), C::colorize("&c⚠ Player &b{$inviteName}&c is already a member."));
                return;
            }
    
            Loader::getSkyBlockManager()->sendInvitation($viewer, $targetPlayer);
            $viewer->sendToastNotification(C::colorize(Loader::SERVER_TITLE), C::colorize("&a✔ Invitation sent to &b{$inviteName}&a!"));
        });
    
        $this->form->setTitle(C::colorize("&r&8Invite Player"));
        $this->form->addInput(C::colorize("&r&fType the player's name:"), "Player name");
        $this->form->addDropdown(C::colorize("&r&fSelect from online players:"), $dropdownDisplay);
    }

    public static function displayForm(Player $player): void {
        $screen = new self($player);
        $player->sendForm($screen->getForm());
    }

    public function getForm(): CustomForm {
        return $this->form;
    }
}