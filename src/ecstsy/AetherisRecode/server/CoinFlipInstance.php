<?php

declare(strict_types=1);

namespace ecstsy\AetherisRecode\server;

use ecstsy\AetherisRecode\Loader;
use ecstsy\AetherisRecode\tasks\CoinFlipTask;
use ecstsy\AetherisRecode\utils\QueryStmts;
use pocketmine\player\Player;
use pocketmine\Server;
use Vecnavium\FormsUI\SimpleForm;
use pocketmine\utils\TextFormat as C;

final class CoinFlipInstance {

    public static array $coinFlips = [];

    public const COLORS = [
        "Red" => "§c",
        "Yellow" => "§e",
        "Green" => "§a",
        "Blue" => "§9",
        "Purple" => "§5",
        "Orange" => "§6",
        "Pink" => "§d",
        "Cyan" => "§b"
    ];

    public function __construct(Loader $plugin)
    {
        $this->loadCoinFlips();
    }

    private function loadCoinFlips(): void {
        Loader::getDatabase()->executeSelect(QueryStmts::COINFLIP_SELECT, [], function(array $rows): void {
            foreach ($rows as $row) {
                $this->coinFlips[$row["uuid"]] = [
                    "uuid" => $row["uuid"],
                    "username" => $row["username"],
                    "type" => $row["type"],
                    "wager" => $row["money"]
                ];
            }
        });
    }

    public static function getCoinFlipColorOption(Player $player, int $amount): SimpleForm {
        $form = new SimpleForm(function (Player $player, $data) use ($amount): void {
            if ($data === null) return;

            $colors = array_keys(self::COLORS);
            $color = $colors[$data];

            $session = Loader::getPlayerManager()->getSession($player);

            if ($session->getBalance() < $amount) {
                $player->sendMessage(C::colorize("&r&cError: &4You don't have enough balance."));
                return;
            }

            $session->removeBalance($amount);
            self::addCoinFlip($player, $color, $amount);
            $player->sendMessage(C::colorize("&r&a&l(!) &r&aCoinflip created!"));
        });

        $form->setTitle(C::colorize("&r&8Pick a coin flip color"));

        foreach (self::COLORS as $colorName => $colorCode) {
            $form->addButton(C::colorize("&r&l" . $colorCode . $colorName . "\n&r&8Click to pick"));
        }

        return $form;
    }

    public static function getCoinFlipList(Player $player): SimpleForm {
        $form = new SimpleForm(function (Player $player, $data): void {
            if ($data === null) return;

            $selectedUUID = array_keys(self::$coinFlips)[$data];
            $selectedCF = self::$coinFlips[$selectedUUID];

            self::getCoinFlipOpponentColorForm($player, $selectedUUID, $selectedCF);
        });

        $form->setTitle(C::colorize("&r&8Coin Flips"));

        if (empty(self::$coinFlips)) {
            $form->setContent(C::colorize("&r&8No coinflips available."));
        } else {
            foreach (self::$coinFlips as $cf) {
                if ($cf["uuid"] !== $player->getUniqueId()->toString()) {
                    $form->addButton(C::colorize("&r&8&l" . $cf["username"] . "\n&r&8&o$" . number_format($cf["wager"])));
                }
            }
        }

        return $form;
    }
    
    public static function getCoinFlipOpponentColorForm(Player $player, string $uuid, array $cf): void {
        $form = new SimpleForm(function (Player $player, $data) use ($uuid, $cf): void {
            if ($data === null) return;
    
            $colors = array_keys(self::COLORS);
            unset($colors[array_search($cf["type"], $colors)]);
    
            $opponentColor = array_values($colors)[$data];
    
            $session = Loader::getPlayerManager()->getSession($player);

            if ($session->getBalance() < $cf["wager"]) {
                $player->sendMessage(C::colorize("&r&cError: &4You don't have enough balance."));
                return;
            }
    
            $session->removeBalance($cf["wager"]);
    
            self::startCoinFlip($cf["username"], $player->getName(), $cf["type"], $opponentColor, $cf["wager"]);
        });
    
        $form->setTitle("§8Pick Your CoinFlip Color");
    
        $colors = array_keys(self::COLORS);
        unset($colors[array_search($cf["type"], $colors)]);
    
        foreach ($colors as $color) {
            $colorCode = self::COLORS[$color];
            $form->addButton(C::colorize("&r&l" . $colorCode . $color . "\n&r&8Click to pick"));
        }
    
        $player->sendForm($form);
    }

    public static function startCoinFlip(string $p1Name, string $p2Name, string $p1Color, string $p2Color, int $amount): void {
        $server = Server::getInstance();
        $p1 = $server->getPlayerExact($p1Name);
        $p2 = $server->getPlayerExact($p2Name);

        if ($p1 === null || $p2 === null) {
            return;
        }

        Loader::getInstance()->getScheduler()->scheduleRepeatingTask(new CoinFlipTask($p1, $p2, $p1Color, $p2Color, $amount), 1);

        $p1->sendMessage(C::colorize("&6Your coinflip against &f" . $p2Name . " &6has started!"));
        $p2->sendMessage(C::colorize("&6Your coinflip against &f" . $p1Name . " &6has started!"));
    }

    /**
     * Add a new coin flip to the database and cache it.
     */
    public static function addCoinFlip(Player $player, string $type, int $wager): void
    {
        $uuid = $player->getUniqueId()->toString();
        $username = $player->getName();

        $args = [
            "uuid" => $uuid,
            "username" => $username,
            "type" => $type,
            "money" => $wager,
        ];

        Loader::getDatabase()->executeInsert(QueryStmts::COINFLIP_CREATE, $args);

        self::$coinFlips[$uuid] = [
            "uuid" => $uuid,
            "username" => $username,
            "type" => $type,
            "wager" => $wager
        ];
    }

    /**
     * Remove a coin flip from the database and cache.
     *
     * @param string $uuid
     */
    public static function removeCoinFlip(string $uuid): void
    {
        Loader::getDatabase()->executeChange(QueryStmts::COINFLIP_DELETE, ["uuid" => $uuid]);

        unset(self::$coinFlips[$uuid]);
    }

    /**
     * Get the interval in ticks for the coin flip roll task.
     *
     * @return int
     */
    public static function getRollTaskTickInterval(): int
    {
        return 10;
    }

    /**
     * Get the coin flip data for a specific player.
     *
     * @param string $uuid
     * @return array|null
     */
    public function getCoinFlipData(string $uuid): ?array
    {
        return $this->coinFlips[$uuid] ?? null;
    }

    /**
     * Check if a player has already submitted a coin flip.
     *
     * @param Player $player
     * @return bool
     */
    public static function hasSubmittedCoinFlip(Player $player): bool
    {
        return isset(self::$coinFlips[$player->getUniqueId()->toString()]);
    }
}