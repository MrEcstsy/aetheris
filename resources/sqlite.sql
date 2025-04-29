-- #!sqlite
-- # { aetheris_players
-- #   { init 
CREATE TABLE IF NOT EXISTS aetheris_players (
    uuid VARCHAR(36) PRIMARY KEY,
    username VARCHAR(16),
    balance int DEFAULT 0,
    cooldowns TEXT,
    kills INT DEFAULT 0,
    deaths INT DEFAULT 0,
    bounty INT DEFAULT 0,
    settings TEXT,
    island VARCHAR(32) DEFAULT NULL,
    collection TEXT
);
-- #   }

-- # { select
SELECT *
FROM aetheris_players;
-- #   }

-- # { create
-- #    :uuid string
-- #    :username string
-- #    :balance int
-- #    :cooldowns string
-- #    :kills int
-- #    :deaths int
-- #    :bounty int
-- #    :settings string
-- #    :island ?string
-- #    :collection string
INSERT OR REPLACE INTO aetheris_players (uuid, username, balance, cooldowns, kills, deaths, bounty, settings, island, collection)
VALUES (:uuid, :username, :balance, :cooldowns, :kills, :deaths, :bounty, :settings, :island, :collection);
-- #   }

-- # { update
-- #    :uuid string
-- #    :balance int
-- #    :cooldowns string
-- #    :kills int
-- #    :deaths int
-- #    :bounty int
-- #    :settings string
-- #    :island ?string
-- #    :collection string
UPDATE aetheris_players
SET balance = :balance, 
    cooldowns = :cooldowns, 
    kills = :kills, 
    deaths = :deaths, 
    bounty = :bounty,
    settings = :settings,
    island = :island,
    collection = :collection
WHERE uuid = :uuid;
-- # }

-- # { delete
-- #    :uuid string
DELETE FROM aetheris_players
WHERE uuid = :uuid;
-- #   }
-- # }

-- # { aetheris_islands
-- #   { init
CREATE TABLE IF NOT EXISTS aetheris_islands (
    island_id VARCHAR(36) NOT NULL PRIMARY KEY,            
    name VARCHAR(16),              
    description TEXT,
    value INT DEFAULT 0,
    leader_uuid VARCHAR(36) NOT NULL,       
    leader_name VARCHAR(16) NOT NULL,
    members TEXT,                           
    world VARCHAR(64) NOT NULL,     
    role_permissions TEXT,        
    settings TEXT,                          
    spawn TEXT,                             
    bank_balance INT DEFAULT 0,
    max_members INT DEFAULt 5
);
-- #   }

-- # { select
SELECT *
FROM aetheris_islands;
-- #   }

-- # { create
-- #    :island_id string
-- #    :name string
-- #    :description string
-- #    :value int
-- #    :leader_uuid string
-- #    :leader_name string
-- #    :members string
-- #    :world string
-- #    :role_permissions string
-- #    :settings string
-- #    :spawn string
-- #    :bank_balance int
-- #    :max_members int
INSERT OR REPLACE INTO aetheris_islands (island_id, name, description, value, leader_uuid, leader_name, members, world, role_permissions, settings, spawn, bank_balance, max_members)
VALUES (:island_id, :name, :description, :value, :leader_uuid, :leader_name, :members, :world, :role_permissions, :settings, :spawn, :bank_balance, :max_members);
-- #   }

-- # { update
-- #    :island_id string
-- #    :name string
-- #    :description string
-- #    :value int
-- #    :leader_uuid string
-- #    :leader_name string
-- #    :members string
-- #    :world string
-- #    :role_permissions string
-- #    :settings string
-- #    :spawn string
-- #    :bank_balance int
-- #    :max_members int
UPDATE aetheris_islands
SET island_id = :island_id,
    name = :name,
    description = :description,
    value = :value,
    leader_uuid = :leader_uuid,
    leader_name = :leader_name,
    members = :members,
    world = :world,
    role_permissions = :role_permissions,
    settings = :settings,
    spawn = :spawn,
    bank_balance = :bank_balance,
    max_members = :max_members
WHERE island_id = :island_id;
-- #   }

-- # { delete
-- #    :island_id string
DELETE FROM aetheris_islands
WHERE island_id = :island_id;
-- #   }
-- # }

-- # { aetheris_bounties
-- #   { init
CREATE TABLE IF NOT EXISTS aetheris_bounties (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    placed_by VARCHAR(36) NOT NULL,        
    target_uuid VARCHAR(36) NOT NULL,       
    amount INT NOT NULL                     
);
-- #   }

-- # { select
SELECT *
FROM aetheris_bounties;
-- #   }

-- # { create
-- #    :placed_by string
-- #    :target_uuid string
-- #    :amount int
INSERT INTO aetheris_bounties (placed_by, target_uuid, amount)
VALUES (:placed_by, :target_uuid, :amount);
-- #   }

-- # { delete
-- #    :id int
DELETE FROM aetheris_bounties
WHERE id = :id;
-- #   }
-- # }

-- # { aetheris_skills 
-- #   { init
CREATE TABLE IF NOT EXISTS aetheris_skills (
    uuid VARCHAR(36) NOT NULL,
    skill_name varchar(16) NOT NULL,
    level INT DEFAULT 0,
    experience INT DEFAULT 0,
    PRIMARY KEY (uuid, skill_name),
    FOREIGN KEY (uuid) REFERENCES aetheris_players(uuid) 
);
-- #   }

-- # { select
SELECT *
FROM aetheris_skills;
-- #   }

-- # { create
-- #    :uuid string
-- #    :skill_name string
-- #    :level int
-- #    :experience int
INSERT INTO aetheris_skills (uuid, skill_name, level, experience)
VALUES (:uuid, :skill_name, :level, :experience);
-- #   }

-- # { update
-- #    :uuid string
-- #    :skill_name string
-- #    :level int
-- #    :experience int
UPDATE aetheris_skills
SET uuid = :uuid,
    skill_name = :skill_name,
    level = :level,
    experience = :experience
WHERE uuid = :uuid AND skill_name = :skill_name;
-- #   }

-- # { delete
-- #    :uuid string
-- #    :skill_name string
DELETE FROM aetheris_skills
WHERE uuid = :uuid AND skill_name = :skill_name;
-- #   }
-- # }

-- # { coinflip
-- #  { initialize
CREATE TABLE IF NOT EXISTS CoinFlips (
    uuid VARCHAR(36) NOT NULL,
    username VARCHAR(16) NOT NULL,
    type TEXT NOT NULL,
    money INT NOT NULL,
    PRIMARY KEY (uuid, type)
);
-- #  }
-- #  { select
SELECT *
FROM CoinFlips;
-- #  }

-- #  { create
-- #      :uuid string
-- #      :username string
-- #      :type string
-- #      :money int
INSERT OR REPLACE INTO CoinFlips (uuid, username, type, money)
VALUES (:uuid, :username, :type, :money);
-- #  }
-- #  { delete
-- #      :uuid string
DELETE FROM CoinFlips
WHERE uuid=:uuid;
-- #  }
-- # }