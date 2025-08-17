<?php

declare(strict_types=1);

namespace ecstsy\AetherisRecode\utils\ui\skyblock;

use ecstsy\AetherisRecode\Loader;
use ecstsy\AetherisRecode\skyblock\SkyBlock;
use ecstsy\MartianUtilities\interfaces\ScreenInterface;
use ecstsy\MartianUtilities\utils\screens\BaseScreen;
use pocketmine\player\Player;
use Vecnavium\FormsUI\SimpleForm;
use pocketmine\utils\TextFormat as C;

final class IslandLeaderPromotionScreen extends BaseScreen {

    public SimpleForm $form;

    public function __construct(Player $player, string $memberName, SkyBlock $sbSession)
    {
        $memberSession = Loader::getPlayerManager()->getSessionByName($memberName);
        $form = new SimpleForm(function(Player $player, $data) use ($memberName, $sbSession, $memberSession): void {
            if ($data === null) {
                $screen = new IslandMemberManagementScreen($player, $memberName, $sbSession);
                $player->sendForm($screen->getForm());
                return;
            }
    
            if ($data === 0) {
                $sbSession->updateRole($memberSession->getPlayer()->getUniqueId()->toString(), 'leader');
                $sbSession->updateRole($player->getUniqueId()->toString(), 'co-leader');
                $player->sendToastNotification(C::colorize(Loader::SERVER_TITLE), C::colorize("&a✔ Promoted {$memberName} to Leader. You are no longer the leader."));
            }
        });
        
        $form->setTitle("§r§cConfirm Leader Promotion");
        $form->setContent(C::colorize("&r&fPromoting {$memberName} to leader will transfer all ownership rights and cannot be undone. Proceed with caution!"));
        $form->addButton(C::colorize("&r&aConfirm"));
        $form->addButton(C::colorize("&r&cCancel"));
    }
    
    public function getForm(): SimpleForm
    {
        return $this->form;
    }
}