CREATE TABLE IF NOT EXISTS assoc (
    uid INT NOT NULL UNIQUE AUTO_INCREMENT,
    name VARCHAR(32) NOT NULL,
    xuid VARCHAR(16) NOT NULL UNIQUE,
    PRIMARY KEY(uid)
);