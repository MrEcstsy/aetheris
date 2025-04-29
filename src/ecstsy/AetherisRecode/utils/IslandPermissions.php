<?php

declare(strict_types=1);

namespace ecstsy\AetherisRecode\utils;

final class IslandPermissions
{
    public const KILL_MOBS = 'kill-mobs';
    public const OPEN_CONTAINERS = 'open-containers';
    public const OPEN_DOORS = 'interact-door';
    public const PICKUP = 'pickup';
    public const BREAK = 'break';
    public const PLACE = 'place';
    public const USE_BUCKETS = 'use-buckets';
    public const EDIT_PERMISSIONS = 'change-permissions';
    public const DEMOTE_USERS = 'demote-users';
    public const PROMOTE_USERS = 'promote-users';
    public const TRUST_USERS = 'trust-users';
    public const KICK_USERS = 'kick-users';
    public const USE_REDSTONE = 'use-redstone';
    public const RENAME_ISLAND = 'rename-island';
    public const ISLAND_HOME = 'island-home';
    public const BREAK_SPAWNERS = 'break-spawners';
    public const EDIT_SETTINGS = 'change-settings';
    public const MANAGE_WARPS = 'manage-warps';
    public const EDIT_SIGNS = 'edit-signs';
    public const EDIT_DESCRIPTION = 'change-description';
    public const INVITE_USERS = 'invite-users';

    public static function getAllPermissions(): array
    {
        $reflection = new \ReflectionClass(__CLASS__);
        return array_values($reflection->getConstants());
    }

    public static function getHumanName(string $permission): string
    {
        return match($permission) {
            self::KILL_MOBS => "Kill Mobs",
            self::OPEN_CONTAINERS => "Open Containers",
            self::OPEN_DOORS => "Interact with Doors",
            self::PICKUP => "Pickup Items",
            self::BREAK => "Break Blocks",
            self::PLACE => "Place Blocks",
            self::USE_BUCKETS => "Use Buckets",
            self::EDIT_PERMISSIONS => "Edit Permissions",
            self::DEMOTE_USERS => "Demote Members",
            self::PROMOTE_USERS => "Promote Members",
            self::TRUST_USERS => "Trust Players",
            self::KICK_USERS => "Kick Members",
            self::USE_REDSTONE => "Use Redstone",
            self::RENAME_ISLAND => "Rename Island",
            self::ISLAND_HOME => "Use Island Home",
            self::BREAK_SPAWNERS => "Break Spawners",
            self::EDIT_SETTINGS => "Edit Settings",
            self::MANAGE_WARPS => "Manage Warps",
            self::EDIT_SIGNS => "Edit Signs",
            default => ucwords(str_replace('_', ' ', $permission))
        };
    }
}