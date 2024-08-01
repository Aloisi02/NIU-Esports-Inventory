DROP TABLE ChangePass;
DROP TABLE Updates;
DROP TABLE Ticket;
DROP TABLE PC;
DROP TABLE VR;
DROP TABLE Console;
DROP TABLE Device;
DROP TABLE Account;
DROP TABLE PCTypes;
DROP TABLE ConsoleTypes;
DROP TABLE VRTypes;
DROP TABLE Inventory;

-- PC types at creation are DELL, IBP 22, IBP 23, OMEN 23, CUSTOM 23
CREATE TABLE PCTypes (
    type varchar(32) NOT NULL,
    PRIMARY KEY (type)
);

-- Console types at creation are PS5, PS4, XBOX ONE, XBOX SERIES S, XBOX SERIES X
CREATE TABLE ConsoleTypes (
    type varchar(32) NOT NULL,
    PRIMARY KEY (type)
);

-- VR types at creation are VIVE PRO Headset, VIVE PRO Controller, 2.0 Base stations? (need to check what's labeled)
CREATE TABLE VRTypes (
    type varchar(32) NOT NULL,
    PRIMARY KEY (type)
);

CREATE TABLE Device (
    id INT NOT NULL, -- Device ID
    archived BOOLEAN DEFAULT 0, -- 0 is false
    PRIMARY KEY (id)
);

CREATE TABLE Console (
    id INT NOT NULL, -- Console ID
    type varchar(32) NOT NULL, -- Type of Console 

    PRIMARY KEY (id),
    FOREIGN KEY (id) REFERENCES Device(id) ON DELETE CASCADE,
    FOREIGN KEY (type) REFERENCES ConsoleTypes(type) ON DELETE CASCADE
);

CREATE TABLE PC (
    id INT NOT NULL, -- PC ID
    type varchar(32) NOT NULL, -- Type of PC

    PRIMARY KEY (id),
    FOREIGN KEY (id) REFERENCES Device(id) ON DELETE CASCADE,
    FOREIGN KEY (type) REFERENCES PCTypes(type) ON DELETE CASCADE
);


CREATE TABLE VR (
    id INT NOT NULL, -- PC ID
    type varchar(32) NOT NULL, -- Type of VR equipment

    PRIMARY KEY (id),
    FOREIGN KEY (id) REFERENCES Device(id) ON DELETE CASCADE,
    FOREIGN KEY (type) REFERENCES VRTypes(type) ON DELETE CASCADE
);


CREATE TABLE Account (
    username varchar(64) NOT NULL,
    password varchar(64) NOT NULL,
    perm INT NOT NULL, -- 0 for base user, 1 for admin, 2 for big admin

    PRIMARY KEY (username)
);


CREATE TABLE Ticket (
    id INT NOT NULL AUTO_INCREMENT,
    device INT NOT NULL, -- Device ID/number

    PRIMARY KEY (id),
    FOREIGN KEY (device) REFERENCES Device(id) ON DELETE CASCADE
);

CREATE TABLE Updates (
    id INT NOT NULL AUTO_INCREMENT,
    ticket INT NOT NULL,
    summary varchar(48) NOT NULL,
    details TEXT,
    status INT NOT NULL, -- Fully functional, mostly functional, needs repair us, needs repair doit, broken
    username varchar(64) NOT NULL, 
    time DATETIME DEFAULT NOW(),

    PRIMARY KEY (id),
    FOREIGN KEY (ticket) REFERENCES Ticket(id) ON DELETE CASCADE
);

CREATE TABLE ChangePass (
    id INT NOT NULL AUTO_INCREMENT,
    token varchar(256) NOT NULL,
    time_created DATETIME DEFAULT NOW(),

    PRIMARY KEY (id)
);

CREATE TABLE Inventory (
    name varchar(64) NOT NULL,
    quantity INT NOT NULL,

    PRIMARY KEY (name)
);