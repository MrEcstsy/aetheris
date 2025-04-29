<?php

declare(strict_types=1);

namespace ecstsy\AetherisRecode\utils\ui\skyblock;

use ecstsy\AetherisRecode\Loader;
use ecstsy\AetherisRecode\utils\Utils;
use ecstsy\MartianUtilities\utils\GeneralUtils;
use ecstsy\MartianUtilities\utils\screens\BaseScreen;
use pocketmine\player\Player;
use pocketmine\utils\TextFormat as C;
use Vecnavium\FormsUI\CustomForm;

class IslandCreationConfirmScreen extends BaseScreen
{
    private  CustomForm $form;

    public function __construct(string $defaultGenerator)
    {
        $this->form = new CustomForm(function (Player $player, $data) use ($defaultGenerator) {
            if ($data === null) {
                return;
            }

            $islandName = (string) ($data[0] ?? "");
            $choiceIndex = (int) ($data[1] ?? 0);
            $session = Loader::getPlayerManager()->getSession($player);
            $sbSession = Loader::getSkyBlockManager();

            if ($session->getSkyblock() !== null) {
                $player->sendToastNotification(C::colorize(Loader::SERVER_TITLE), C::colorize("&r&f➤ &fYou already have created or joined an island!"));
                return;
            }

            $config = GeneralUtils::getConfiguration(Loader::getInstance(), "config.yml");

            $max = $config->getNested("settings.skyblock.max-char-name");
            if ($islandName === "" || strlen($islandName) > $max) {
                $player->sendMessage(C::RED . "Island name must be 1–{$max} characters.");
                return;
            }

            $validGenerators = ["basic_island", "forest_island", "desert_island"];
            $generatorKey = $validGenerators[$choiceIndex] ?? $defaultGenerator;

            if (!in_array($generatorKey, $validGenerators, true)) {
                $player->sendMessage(C::RED . "Invalid generator selected.");
                return;
            }

            $sbSession->createSkyBlock($player, strtolower($islandName), $islandName, $generatorKey);
            $player->sendToastNotification(C::colorize(Loader::SERVER_TITLE), C::colorize("&r&l✔ Success! &fYour island has been created!"));
        });

        $this->form->setTitle(C::colorize("&r&8Island Creation"));
        $this->form->addLabel(C::colorize("&r&fCreate your dream island!"));
        $this->form->addInput(C::colorize("&r&fIsland Name:"), "name", "");
        $this->form->addDropdown(C::colorize("&r&fSelect Generator:"), ["basic_island", "forest_island", "desert_island"], array_search($defaultGenerator, ["basic_island", "forest_island", "desert_island"]));
    }

    public static function displayForm(Player $player, string $defaultGenerator): void
    {
        $screen = new self($defaultGenerator);
        $player->sendForm($screen->getForm());
    }

    public function getForm(): CustomForm
    {
        return $this->form;
    }
}
