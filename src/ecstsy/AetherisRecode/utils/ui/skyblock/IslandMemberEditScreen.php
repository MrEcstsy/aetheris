<?php

declare(strict_types=1);

namespace ecstsy\AetherisRecode\utils\ui\skyblock;

use ecstsy\AetherisRecode\Loader;
use ecstsy\AetherisRecode\skyblock\SkyBlock;
use ecstsy\AetherisRecode\utils\Utils;
use ecstsy\MartianUtilities\interfaces\ScreenInterface;
use ecstsy\MartianUtilities\utils\screens\BaseScreen;
use pocketmine\player\Player;
use Ramsey\Uuid\Uuid;
use Vecnavium\FormsUI\SimpleForm;
use pocketmine\utils\TextFormat as C;
use Vecnavium\FormsUI\CustomForm;

final class IslandMemberEditScreen extends BaseScreen{

    public SimpleForm $form;

    public function __construct(Player $player, string $memberName, SkyBlock $sbSession)
    {
        $memberData = array_values(array_filter(
            $sbSession->getMembers(),
            fn($member) => $member['name'] === $memberName
        ))[0] ?? null;
    
        if ($memberData === null) {
            $player->sendMessage(C::colorize("&cMember not found!"));
            $screen = new IslandMemberManagementScreen($player);
            return $player->sendForm($screen->getForm());
        }
    
        $role = ucfirst($memberData['role']);
        $joinDate = date("l, F jS Y", $memberData['join_date']);
        $playerRole = strtolower($sbSession->getRole($player->getUniqueId()->toString()));
    
        $memberUuid = $memberData['uuid'] ?? null;
        $memberSession = $memberUuid !== null ? Loader::getPlayerManager()->getSessionByUuid(Uuid::fromString($memberUuid)) : null;
    
        $this->form = new SimpleForm(function(Player $player, $data) use ($memberName, $sbSession, $playerRole, $role, $memberSession): void {
            if ($data === null) {
                $screen = new IslandMemberManagementScreen($player, $memberName, $sbSession);
                $player->sendForm($screen->getForm());
                return;
            }

            $targetRole = strtolower($role);

            switch ($data) {
                case 0: 
                    if (Utils::canPromote($playerRole, $targetRole)) {
                        $newRole = Utils::getNextRole($targetRole);
                        $sbSession->updateRole($memberSession->getUuid()->toString(), $newRole);
                        $player->sendToastNotification(C::colorize(Loader::SERVER_TITLE), C::colorize("&a✔ Promoted {$memberName} to " . ucfirst($newRole) . "."));
                    } else {
                        $player->sendToastNotification(C::colorize(Loader::SERVER_TITLE), C::colorize("&c⚠ You cannot promote {$memberName}."));
                    }
                    break;
    
                case 1: 
                    if (Utils::canDemote($playerRole, $targetRole)) {
                        $newRole = Utils::getPreviousRole($targetRole);
                        $sbSession->updateRole($memberSession->getUuid()->toString(), $newRole);
                        $player->sendToastNotification(C::colorize(Loader::SERVER_TITLE), C::colorize("&c✔ Demoted {$memberName} to " . ucfirst($newRole) . "."));
                    } else {
                        $player->sendToastNotification(C::colorize(Loader::SERVER_TITLE), C::colorize("&c⚠ You cannot demote {$memberName}."));
                    }
                    break;
    
                case 2: 
                    $sbSession->removeMember($memberSession->getPlayer());
                    $player->sendToastNotification(C::colorize(Loader::SERVER_TITLE), C::colorize("&c✔ Kicked {$memberName} from the island."));
                    break;
    
                case 3: 
                    if ($playerRole === 'leader') {
                        $screen = new IslandLeaderPromotionScreen($player, $memberName, $sbSession);
                        $player->sendForm($screen->getForm());
                    }
                    break;
            }
        });
    
        $this->form->setTitle("§r§8Manage Member: §b{$memberName}");
        $this->form->setContent(C::colorize("&r&fRole: &e" . ucfirst($role) . "\n&r&fJoined: &e{$joinDate}"));

        if (Utils::canPromote($playerRole, $role)) {
            $this->form->addButton(C::colorize("&r&aPromote"));
        }
    
        if (Utils::canDemote($playerRole, $role)) {
            $this->form->addButton(C::colorize("&r&cDemote"));
        }
    
        if (Utils::canDemote($playerRole, $role)) { 
            $this->form->addButton(C::colorize("&r&4Kick Member"));
        }    
        if (strtolower($playerRole) === 'leader') {
            $this->form->addButton(C::colorize("&r&6Promote to Leader"));
        }
    }

    public static function displayForm(Player $player, string $memberName, SkyBlock $sbSession): void {
        $screen = new self($player, $memberName, $sbSession);
        $player->sendForm($screen->getForm());
    }

    public function getForm(): CustomForm|null|SimpleForm
    {
        return $this->form;
    }
}