<?php

declare(strict_types=1);

namespace ecstsy\AetherisRecode\commands;

use CortexPE\Commando\BaseCommand;
use ecstsy\AetherisRecode\Loader;
use pocketmine\command\CommandSender;
use pocketmine\player\Player;
use pocketmine\utils\TextFormat as C;
use Vecnavium\FormsUI\SimpleForm;

final class SettingsCommand extends BaseCommand {

    public function prepare(): void {
        $this->setPermission($this->getPermission());
    }

    public function onRun(CommandSender $sender, string $aliasUsed, array $args): void
    {
        if (!$sender instanceof Player) {
            return;
        }

        $session = Loader::getPlayerManager()->getSession($sender);
        $form = new SimpleForm(function (Player $player, $data) use ($session): void {
            if ($data === null) {
                return;
            }

            switch ($data) {
                case 0:
                    $session->toggleSetting("chest_inventories");
                    $settingValue = $session->getSetting("chest_inventories");
                    $color = $settingValue ? '§a' : '§c'; 

                    $player->sendToastNotification(
                        C::colorize(Loader::SERVER_TITLE),
                        C::colorize("&r&7Chest GUI setting has been set to: " . $color . ($settingValue ? "Enabled" : "Disabled"))
                    );
                    break;
                case 1:
                    $session->toggleSetting("broadcasts");
                    $settingValue = $session->getSetting("broadcasts");
                    $color = $settingValue ? '§a' : '§c';
                    
                    $player->sendToastNotification(
                        C::colorize(Loader::SERVER_TITLE),
                        C::colorize("&r&7Announcer setting has been set to: " . $color . ($settingValue ? "Enabled" : "Disabled"))
                    );
                    break;
                case 2:
                    $session->toggleSetting("loot_announcer");
                    $settingValue = $session->getSetting("loot_announcer");
                    $color = $settingValue ? '§a' : '§c';
                    
                    $player->sendToastNotification(
                        C::colorize(Loader::SERVER_TITLE),
                        C::colorize("&r&7Lootbox notification setting has been set to: " . $color . ($settingValue ? "Enabled" : "Disabled"))
                    );
                    break;
                }
        });

        $form->setTitle(C::colorize("&r&8Server Settings"));
        $form->setContent(C::colorize("&r&fToggle the server's features to better modify the server to your liking"));
        $form->addButton("Chest Inventories");
        $form->addButton("Announcer");
        $form->addButton("Lootbox Notifications");

        $sender->sendForm($form);
    }

    public function getPermission(): string
    {
        return "aetheris.default";
    }
}