<?php

declare(strict_types=1);

namespace ecstsy\AetherisRecode\enchantments\manager;

final class EnchantmentEventRegistry {

    /** @var array<string, array<string, callable>> */
    private static array $handlers = [];

    public static function registerHandler(string $eventType, string $enchantName, callable $handler): void {
        self::$handlers[$eventType][$enchantName] = $handler;
    }

    /**
     * @return array<string, callable>
     */
    public static function getHandlers(string $eventType): array {
        return self::$handlers[$eventType] ?? [];
    }
}