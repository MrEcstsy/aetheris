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
}