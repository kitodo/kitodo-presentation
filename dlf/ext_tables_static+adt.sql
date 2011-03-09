--
-- Table structure for table 'tx_dlf_formats'
--
CREATE TABLE tx_dlf_formats (
    uid int(11) NOT NULL auto_increment,
    pid int(11) DEFAULT '0' NOT NULL,
    tstamp int(11) DEFAULT '0' NOT NULL,
    crdate int(11) DEFAULT '0' NOT NULL,
    cruser_id int(11) DEFAULT '0' NOT NULL,
    deleted tinyint(4) DEFAULT '0' NOT NULL,
    type tinytext NOT NULL,
    other_type tinyint(4) DEFAULT '0' NOT NULL,
    root tinytext NOT NULL,
    namespace text NOT NULL,
    class text NOT NULL,

    PRIMARY KEY (uid),
    KEY parent (pid)
);

INSERT INTO tx_dlf_formats VALUES ('', '0', '0', '0', '0', '0', 'MODS', '0', 'mods', 'http://www.loc.gov/mods/v3', 'tx_dlf_mods');

--
-- Table structure for table 'tx_dlf_solrcores'
--
CREATE TABLE tx_dlf_solrcores (
    uid int(11) NOT NULL auto_increment,
    pid int(11) DEFAULT '0' NOT NULL,
    tstamp int(11) DEFAULT '0' NOT NULL,
    crdate int(11) DEFAULT '0' NOT NULL,
    cruser_id int(11) DEFAULT '0' NOT NULL,
    deleted tinyint(4) DEFAULT '0' NOT NULL,
    label tinytext NOT NULL,
    index_name tinytext NOT NULL,

    PRIMARY KEY (uid),
    KEY parent (pid)
);

INSERT INTO tx_dlf_solrcores VALUES ('', '0', '0', '0', '0', '0', 'Default Core', 'dlfCore0');
