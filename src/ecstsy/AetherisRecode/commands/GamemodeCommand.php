<?php

declare(strict_types=1);

namespace ecstsy\AetherisRecode\commands;

use CortexPE\Commando\args\RawStringArgument;
use CortexPE\Commando\BaseCommand;
use ecstsy\AetherisRecode\commands\arguments\PlayerArgument;
use ecstsy\AetherisRecode\Loader;
use ecstsy\MartianUtilities\utils\PlayerUtils;
use pocketmine\command\CommandSender;
use pocketmine\player\GameMode;
use pocketmine\player\Player;
use pocketmine\utils\TextFormat as C;

final class GamemodeCommand extends BaseCommand {

    public function prepare(): void {
        $this->setPermission($this->getPermission());
        
        $this->registerArgument(0, new RawStringArgument("mode", true));
        $this->registerArgument(1, new PlayerArgument("player", true));
    }

    public function onRun(CommandSender $sender, string $aliasUsed, array $args): void
    {
        if (!$sender instanceof Player) {
            $sender->sendMessage(C::colorize("&r&f/gm &e<survival&c|&wcreative&c|&eadventure&c|&espectator> &7[player]"));
            return;
        }

        $modeName = strtolower($aliasUsed);

        $shortcuts = [
            "gms" => GameMode::SURVIVAL(),
            "gmc" => GameMode::CREATIVE(),
            "gma" => GameMode::ADVENTURE(),
            "gmsp" => GameMode::SPECTATOR(),
        ];

        if (isset($shortcuts[$modeName])) {
            $gamemode = $shortcuts[$modeName];
        } else {
            $raw = $args["mode"] ?? "";
            switch (strtolower($raw)) {
                case "s":
                case "survival":
                    $gamemode = GameMode::SURVIVAL();
                    break;
                case "c":
                case "creative":
                    $gamemode = GameMode::CREATIVE();
                    break;
                case "a":
                case "adventure":
                    $gamemode = GameMode::ADVENTURE();
                    break;
                case "sp":
                case "spectator":
                    $gamemode = GameMode::SPECTATOR();
                    break;
                default:
                    $sender->sendMessage(C::YELLOW . "Usage(s):");
                    $sender->sendMessage(C::WHITE  . "/gm " . C::GOLD . "<survival|creative|adventure|spectator> [player]");
                    return;
            }
        }

        /** @var Player|null $target */
        $target = $args["player"] ?? null;

        if ($target !== null && $target !== $sender) {
            if ($sender->hasPermission($this->getPermission())) {
                $sender->sendMessage(C::RED . "You don't have permission to change others' gamemode.");
                return;
            }
        } else {
            $target = $sender;
        }

        $target->setGamemode($gamemode);
        $msg = C::colorize(Loader::SERVER_PREFIX . "&fSet game mode &d" . $gamemode->getEnglishName() . " &ffor &d" . $target->getNameTag());
        $sender->sendMessage($msg);
        PlayerUtils::playSound($sender, "random.levelup");
    }

    public function getPermission(): string {
        return "pocketmine.command.gamemode";
    }
}