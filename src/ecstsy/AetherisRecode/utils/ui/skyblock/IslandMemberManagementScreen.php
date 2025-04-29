<?php

declare(strict_types=1);

namespace ecstsy\AetherisRecode\utils\ui\skyblock;

use ecstsy\AetherisRecode\Loader;
use ecstsy\MartianUtilities\utils\screens\BaseScreen;
use pocketmine\player\Player;
use Vecnavium\FormsUI\SimpleForm;
use pocketmine\utils\TextFormat as C;
use Vecnavium\FormsUI\CustomForm;

final class IslandMemberManagementScreen extends BaseScreen {

    public SimpleForm $form;

    public function __construct(Player $player)
    {
        $session = Loader::getPlayerManager()->getSession($player);
        $sbSession = Loader::getSkyBlockManager()->getSkyBlockByUuid($session->getSkyblock());
    
        if ($sbSession === null) {
            $player->sendMessage(C::colorize("&cYou are not associated with any island!"));
            return null;
        }

        $members = array_values($sbSession->getMembers());

        $this->form = new SimpleForm(function(Player $player, $data) use ($sbSession, $session): void {
            if ($data === null) {

                if ($session->getSetting("chest_inventories") === true) {
                    IslandControlScreen::display($player);
                } else {
                    IslandControlScreen::displayForm($player);
                }
                return;
            }
    
            if ($data === 0) {
                IslandInvitePlayerScreen::displayForm($player);
                return;
            }
    
            $selectedIndex = $data - 1;

            $filteredMembers = array_filter(
                $sbSession->getMemberNames(),
                fn($member) => strtolower(is_array($member) ? $member['name'] : $member) !== strtolower($player->getName())
            );
        
            $filteredMembers = array_values($filteredMembers); 
        
            if (isset($filteredMembers[$selectedIndex])) {
                $selectedMember = is_array($filteredMembers[$selectedIndex])
                    ? $filteredMembers[$selectedIndex]['name']
                    : $filteredMembers[$selectedIndex];
        
                IslandMemberEditScreen::displayForm($player, $selectedMember, $sbSession);
            }
        });
    
        $this->form->setTitle(C::colorize("&r&8Island Member Management"));
        $this->form->setContent(C::colorize("&r&fManage your island members below:"));
    
        $this->form->addButton(C::colorize("&r&aInvite Player"));
    
        foreach ($sbSession->getMemberNames() as $member) {
            $memberName = is_array($member) ? $member['name'] : $member;
            if (strtolower($memberName) !== strtolower($player->getName())) {
                $this->form->addButton(C::colorize("&r&b{$memberName}"));
            }
        }
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
}