<?php

declare(strict_types=1);

namespace ecstsy\AetherisRecode\utils\ui\skyblock;

use ecstsy\AetherisRecode\Loader;
use ecstsy\AetherisRecode\skyblock\SkyBlock;
use ecstsy\AetherisRecode\utils\IslandPermissions;
use ecstsy\AetherisRecode\utils\Utils;
use ecstsy\MartianUtilities\utils\screens\BaseScreen;
use pocketmine\player\Player;
use Vecnavium\FormsUI\SimpleForm;
use pocketmine\utils\TextFormat as C;

final class PermissionConfigScreen extends BaseScreen {

    private SimpleForm $form;

    public function __construct(Player $player, SkyBlock $sb, string $role) {
        $permissions = IslandPermissions::getAllPermissions();

        $this->form = new SimpleForm(function(Player $p, $data) use ($sb, $role, $permissions): void {
            if ($data === null) {
                RoleSettingsScreen::displayForm($p, $sb); 
                return;
            }
            
            $permKey = $permissions[$data] ?? null;
            if ($permKey !== null) {
                $current = $sb->canRole($role, $permKey);
                $sb->setRolePermission($role, $permKey, !$current);
                $p->sendToastNotification(
                    C::colorize(Loader::SERVER_TITLE),
                    C::colorize("&r&f" . IslandPermissions::getHumanName($permKey) . " set to " . 
                               ($current ? "&cDisabled" : "&aEnabled"))
                );
            }
            self::displayForm($p, $sb, $role);
        });

        $this->form->setTitle(C::colorize("&r&8Configure " . ucfirst($role)));
        $this->form->setContent(C::colorize("&r&fToggle permissions for " . ucfirst($role) . ":"));

        foreach ($permissions as $permKey) {
            $current = $sb->canRole($role, $permKey);
            $status = $current ? "&aEnabled" : "&cDisabled";
            $text = C::colorize("&r&f" . IslandPermissions::getHumanName($permKey) . "\n{$status}");
            $this->form->addButton($text);
        }
    }

    public static function displayForm(Player $player, SkyBlock $sb, string $role): void {
        $screen = new self($player, $sb, $role);
        $player->sendForm($screen->getForm());
    }

    public function getForm(): SimpleForm {
        return $this->form;
    }
}