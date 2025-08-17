<?php

declare(strict_types=1);

namespace ecstsy\AetherisRecode\utils;

final class QueryStmts {

    // PLAYER QUERY
    public const PLAYERS_INIT   = "aetheris_players.init";
    public const PLAYERS_SELECT = "aetheris_players.select";
    public const PLAYERS_CREATE = "aetheris_players.create";
    public const PLAYERS_UPDATE = "aetheris_players.update";
    public const PLAYERS_DELETE = "aetheris_players.delete";
        
    // HOMES QUERY
    public const HOMES_INIT   = "aetheris_players.homes.initialize";
    public const HOMES_SELECT = "aetheris_players.homes.select";
    public const HOMES_CREATE = "aetheris_players.homes.create";
    public const HOMES_UPDATE = "aetheris_players.homes.update";
    public const HOMES_DELETE = "aetheris_players.homes.delete";
    
    // WARPS QUERY 
    public const WARPS_INIT   = "aetheris_players.warps.initialize";
    public const WARPS_SELECT = "aetheris_players.warps.select";
    public const WARPS_CREATE = "aetheris_players.warps.create";
    public const WARPS_UPDATE = "aetheris_players.warps.update";
    public const WARPS_DELETE = "aetheris_players.warps.delete";

    // ISLANDS QUERY
    public const ISLANDS_INIT   = "aetheris_islands.init";
    public const ISLANDS_SELECT = "aetheris_islands.select";
    public const ISLANDS_CREATE = "aetheris_islands.create";
    public const ISLANDS_UPDATE = "aetheris_islands.update";
    public const ISLANDS_DELETE = "aetheris_islands.delete";

    // BOUNTIES QUERY
    public const BOUNTIES_INIT   = "aetheris_bounties.init";
    public const BOUNTIES_SELECT = "aetheris_bounties.select";
    public const BOUNTIES_CREATE = "aetheris_bounties.create";
    public const BOUNTIES_DELETE = "aetheris_bounties.delete"; 

    // SKILLS QUERY
    public const SKILLS_INIT   = "aetheris_skills.init";
    public const SKILLS_SELECT = "aetheris_skills.select";
    public const SKILLS_CREATE = "aetheris_skills.create";
    public const SKILLS_UPDATE = "aetheris_skills.update";
    public const SKILLS_DELETE = "aetheris_skills.delete";
    
    // COINFLIP QUERY
    public const COINFLIP_INIT   = "coinflip.initialize";
    public const COINFLIP_SELECT = "coinflip.select";
    public const COINFLIP_CREATE = "coinflip.create";
    public const COINFLIP_DELETE = "coinflip.delete";

    // JACKPOT QUERY
    public const JACKPOT_INIT      = "aetheris_jackpot.init";
    public const JACKPOT_SELECT    = "aetheris_jackpot.select";
    public const JACKPOT_UPDATE    = "aetheris_jackpot.update";
    public const JACKPOT_INSERT    = "aetheris_jackpot.insert";

    // JACKPOT STATS QUERY
    public const JACKPOT_STATS_INIT    = "aetheris_jackpot_stats.init";
    public const JACKPOT_STATS_SELECT  = "aetheris_jackpot_stats.select";
    public const JACKPOT_STATS_UPDATE  = "aetheris_jackpot_stats.update";
    public const JACKPOT_STATS_INSERT  = "aetheris_jackpot_stats.insert";

    public const PUNISHMENTS_INIT           = "aetheris_punishments.init";
    public const PUNISHMENTS_INSERT         = "aetheris_punishments.insert";
    public const PUNISHMENTS_SELECT_BY_UUID = "aetheris_punishments.select_by_uuid";
    public const PUNISHMENTS_LATEST_STRIKES = "aetheris_punishments.latest_strikes";


    // Active Punishments
    public const ACTIVE_PUNISHMENTS_INIT           = "aetheris_active_punishments.init";
    public const ACTIVE_PUNISHMENTS_INSERT         = "aetheris_active_punishments.insert";
    public const ACTIVE_PUNISHMENTS_DELETE         = "aetheris_active_punishments.delete";
    public const ACTIVE_PUNISHMENTS_SELECT         = "aetheris_active_punishments.select";
    public const ACTIVE_PUNISHMENTS_SELECT_BY_UUID = "aetheris_active_punishments.select_by_uuid";
    
    public const ANTICHEAT_LOGS_INIT = "aetheris_anticheat_logs.init";
    public const ANTICHEAT_LOGS_INSERT = "aetheris_anticheat_logs.insert";
    public const ANTICHEAT_LOGS_SELECT_ALL = "aetheris_anticheat_logs.select_all";
    public const ANTICHEAT_LOGS_SELECT_BY_UUID = "aetheris_anticheat_logs.select_by_uuid";
}