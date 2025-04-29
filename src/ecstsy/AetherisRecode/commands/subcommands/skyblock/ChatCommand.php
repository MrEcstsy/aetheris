<?php
declare(strict_types=1);

namespace ecstsy\AetherisRecode\commands\subcommands\skyblock;

use CortexPE\Commando\args\RawStringArgument;
use CortexPE\Commando\BaseSubCommand;
use ecstsy\AetherisRecode\Loader;
use ecstsy\AetherisRecode\utils\ChatTypes;
use pocketmine\command\CommandSender;
use pocketmine\player\Player;

final class ChatCommand extends BaseSubCommand {

    public function prepare(): void {
        $this->setPermission($this->getPermission());
        
        $this->registerArgument(0, new RawStringArgument("mode", true));
    }

    public function onRun(CommandSender $sender, string $aliasUsed, array $args): void {
        if(!$sender instanceof Player) return;
        
        $session = Loader::getPlayerManager()->getSession($sender);
        $currentChat = $session->getCurrentChat();
        $input = strtolower($args["mode"] ?? "");
        
        $newChat = null;
        if ($input === "i" || $input === "island") {
            $newChat = ChatTypes::ISLAND;
        } elseif ($input === "p" || $input === "public") {
            $newChat = ChatTypes::ALL;
        } else {
            if ($currentChat === ChatTypes::ALL) {
                $newChat = ChatTypes::ISLAND;
            } else {
                $newChat = ChatTypes::ALL;
            }
        }
        
        $session->setCurrentChat($newChat);
        
        switch($newChat) {
            case ChatTypes::ISLAND:
                $sender->sendMessage("§aYour chat mode is now Island Chat.");
                break;
            default:
                $sender->sendMessage("§aYour chat mode is now Public Chat.");
        }
    }

    public function getPermission(): string {
        return "aetheris.default";
    }
}
