<?php
declare(strict_types=1);

namespace ecstsy\AetherisRecode\utils\ui\skyblock;

use ecstsy\AetherisRecode\Loader;
use ecstsy\AetherisRecode\skyblock\SkyBlock;
use ecstsy\AetherisRecode\utils\Utils;
use ecstsy\MartianUtilities\utils\screens\BaseScreen;
use pocketmine\player\Player;
use Vecnavium\FormsUI\SimpleForm;
use pocketmine\utils\TextFormat as C;

final class RoleSettingsScreen extends BaseScreen {

    private SimpleForm $form;

    public function __construct(Player $player, SkyBlock $sb) {
        $roles = Utils::getValidRoles();

        $this->form = new SimpleForm(function(Player $p, $data) use ($sb, $roles): void {
            if ($data === null) {
                IslandControlScreen::display($p);
                return;
            }
            
            $selectedRole = array_keys($roles)[$data] ?? null;
            if ($selectedRole !== null) {
                PermissionConfigScreen::displayForm($p, $sb, $selectedRole);
            }
        });

        $this->form->setTitle(C::colorize("&r&8Role Permissions"));
        $this->form->setContent(C::colorize("&r&fSelect a role to configure:"));

        foreach ($roles as $role => $color) {
            $this->form->addButton(C::colorize("{$color}" . ucfirst($role) . "\n&r&fClick to configure"));
        }
    }

    public static function displayForm(Player $player, SkyBlock $sb): void {
        $screen = new self($player, $sb);
        $player->sendForm($screen->getForm());
    }

    public function getForm(): SimpleForm {
        return $this->form;
    }
}