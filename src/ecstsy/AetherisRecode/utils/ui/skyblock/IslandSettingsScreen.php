<?php
declare(strict_types=1);

namespace ecstsy\AetherisRecode\utils\ui\skyblock;

use ecstsy\AetherisRecode\Loader;
use ecstsy\AetherisRecode\skyblock\SkyBlock;
use ecstsy\AetherisRecode\utils\IslandSettings;
use ecstsy\AetherisRecode\utils\Utils;
use ecstsy\MartianUtilities\utils\screens\BaseScreen;
use pocketmine\player\Player;
use Vecnavium\FormsUI\SimpleForm;
use pocketmine\utils\TextFormat as C;

final class IslandSettingsScreen extends BaseScreen {

    private SimpleForm $form;

    public function __construct(SkyBlock $sb) {
        $this->form = new SimpleForm(function(Player $p, $data) use ($sb): void {
            if ($data === null) {
                IslandControlScreen::display($p);
                return;
            }
            
            $settings = IslandSettings::getAllSettings();
            $selected = $settings[$data] ?? null;
            
            if ($selected !== null) {
                $current = $sb->getSetting($selected);
                $possible = IslandSettings::getPossibleValues($selected);
                
                $index = array_search($current, $possible);
                $newValue = $possible[($index + 1) % count($possible)] ?? $possible[0];
                
                $sb->setSetting($selected, $newValue);
                $p->sendToastNotification(
                    C::colorize(Loader::SERVER_TITLE),
                    C::colorize("&r&f" . IslandSettings::getHumanName($selected) . " set to &a" . ucfirst($newValue))
                );
            }
            self::displayForm($p, $sb);
        });

        $this->form->setTitle(C::colorize("&r&8Island Settings"));
        $this->form->setContent(C::colorize("&r&fToggle island behavior settings:"));

        foreach (IslandSettings::getAllSettings() as $setting) {
            $current = $sb->getSetting($setting);
            $status = is_bool($current) ? ($current ? "&aEnabled" : "&cDisabled") : "&e" . ucfirst($current);
            $text = C::colorize("&r&f" . IslandSettings::getHumanName($setting) . "\n" . $status);
            $this->form->addButton($text);
        }
    }

    public static function displayForm(Player $player, SkyBlock $sb): void {
        $screen = new self($sb);
        $player->sendForm($screen->getForm());
    }

    public function getForm(): SimpleForm {
        return $this->form;
    }
}