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
    root tinytext NOT NULL,
    namespace text NOT NULL,
    class text NOT NULL,

    PRIMARY KEY (uid),
    KEY parent (pid)
);

INSERT INTO tx_dlf_formats VALUES ('1', '0', '0', '0', '0', '0', 'MODS', 'mods', 'http://www.loc.gov/mods/v3', 'tx_dlf_mods');
