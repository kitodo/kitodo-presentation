--
-- Table structure for table 'tx_dlf_documents'
--
CREATE TABLE tx_dlf_documents (
    uid int(11) NOT NULL auto_increment,
    pid int(11) DEFAULT '0' NOT NULL,
    tstamp int(11) DEFAULT '0' NOT NULL,
    crdate int(11) DEFAULT '0' NOT NULL,
    cruser_id int(11) DEFAULT '0' NOT NULL,
    deleted tinyint(4) DEFAULT '0' NOT NULL,
    hidden tinyint(4) DEFAULT '0' NOT NULL,
    fe_group varchar(100) DEFAULT '' NOT NULL,
    prod_id tinytext NOT NULL,
    location text NOT NULL,
    record_id tinytext NOT NULL,
    opac_id tinytext NOT NULL,
    union_id tinytext NOT NULL,
    urn tinytext NOT NULL,
    purl tinytext NOT NULL,
    title text NOT NULL,
    title_sorting text NOT NULL,
    author tinytext NOT NULL,
    author_sorting tinytext NOT NULL,
    year tinytext NOT NULL,
    year_sorting tinytext NOT NULL,
    place tinytext NOT NULL,
    place_sorting tinytext NOT NULL,
    metadata longtext NOT NULL,
    structure int(11) DEFAULT '0' NOT NULL,
    partof int(11) DEFAULT '0' NOT NULL,
    volume tinytext NOT NULL,
    volume_sorting int(11) DEFAULT '0' NOT NULL,
    collections int(11) DEFAULT '0' NOT NULL,
    owner int(11) DEFAULT '0' NOT NULL,
    solrcore int(11) DEFAULT '0' NOT NULL,
    status tinyint(4) unsigned DEFAULT '0' NOT NULL,

    PRIMARY KEY (uid),
    KEY parent (pid)
);

--
-- Table structure for table 'tx_dlf_structures'
--
CREATE TABLE tx_dlf_structures (
    uid int(11) NOT NULL auto_increment,
    pid int(11) DEFAULT '0' NOT NULL,
    tstamp int(11) DEFAULT '0' NOT NULL,
    crdate int(11) DEFAULT '0' NOT NULL,
    cruser_id int(11) DEFAULT '0' NOT NULL,
    deleted tinyint(4) DEFAULT '0' NOT NULL,
    sys_language_uid int(11) DEFAULT '0' NOT NULL,
    l18n_parent int(11) DEFAULT '0' NOT NULL,
    l18n_diffsource mediumblob NOT NULL,
    hidden tinyint(4) DEFAULT '0' NOT NULL,
    toplevel tinyint(4) DEFAULT '0' NOT NULL,
    label tinytext NOT NULL,
    index_name tinytext NOT NULL,
    oai_name tinytext NOT NULL,
    status tinyint(4) unsigned DEFAULT '0' NOT NULL,

    PRIMARY KEY (uid),
    KEY parent (pid)
);

--
-- Table structure for table 'tx_dlf_metadata'
--
CREATE TABLE tx_dlf_metadata (
    uid int(11) NOT NULL auto_increment,
    pid int(11) DEFAULT '0' NOT NULL,
    tstamp int(11) DEFAULT '0' NOT NULL,
    crdate int(11) DEFAULT '0' NOT NULL,
    cruser_id int(11) DEFAULT '0' NOT NULL,
    deleted tinyint(4) DEFAULT '0' NOT NULL,
    sys_language_uid int(11) DEFAULT '0' NOT NULL,
    l18n_parent int(11) DEFAULT '0' NOT NULL,
    l18n_diffsource mediumblob NOT NULL,
    hidden tinyint(4) DEFAULT '0' NOT NULL,
    sorting int(11) DEFAULT '0' NOT NULL,
    label tinytext NOT NULL,
    index_name tinytext NOT NULL,
    encoded int(11) DEFAULT '0' NOT NULL,
    xpath text NOT NULL,
    default_value text NOT NULL,
    wrap text NOT NULL,
    tokenized tinyint(4) DEFAULT '0' NOT NULL,
    stored tinyint(4) DEFAULT '0' NOT NULL,
    indexed tinyint(4) DEFAULT '0' NOT NULL,
    boost float(4,2) DEFAULT '1.00' NOT NULL,
    is_sortable tinyint(4) DEFAULT '0' NOT NULL,
    is_facet tinyint(4) DEFAULT '0' NOT NULL,
    is_listed tinyint(4) DEFAULT '0' NOT NULL,
    status tinyint(4) unsigned DEFAULT '0' NOT NULL,

    PRIMARY KEY (uid),
    KEY parent (pid)
);

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

--
-- Table structure for table 'tx_dlf_collections'
--
CREATE TABLE tx_dlf_collections (
    uid int(11) NOT NULL auto_increment,
    pid int(11) DEFAULT '0' NOT NULL,
    tstamp int(11) DEFAULT '0' NOT NULL,
    crdate int(11) DEFAULT '0' NOT NULL,
    cruser_id int(11) DEFAULT '0' NOT NULL,
    fe_cruser_id int(11) DEFAULT '0' NOT NULL,
    fe_admin_lock tinyint(4) DEFAULT '0' NOT NULL,
    deleted tinyint(4) DEFAULT '0' NOT NULL,
    sys_language_uid int(11) DEFAULT '0' NOT NULL,
    l18n_parent int(11) DEFAULT '0' NOT NULL,
    l18n_diffsource mediumblob NOT NULL,
    hidden tinyint(4) DEFAULT '0' NOT NULL,
    fe_group varchar(100) DEFAULT '' NOT NULL,
    label text NOT NULL,
    index_name tinytext NOT NULL,
    oai_name tinytext NOT NULL,
    description text NOT NULL,
    documents int(11) DEFAULT '0' NOT NULL,
    owner int(11) DEFAULT '0' NOT NULL,
    status tinyint(4) unsigned DEFAULT '0' NOT NULL,

    PRIMARY KEY (uid),
    KEY parent (pid)
);

--
-- Table structure for table 'tx_dlf_libraries'
--
CREATE TABLE tx_dlf_libraries (
    uid int(11) NOT NULL auto_increment,
    pid int(11) DEFAULT '0' NOT NULL,
    tstamp int(11) DEFAULT '0' NOT NULL,
    crdate int(11) DEFAULT '0' NOT NULL,
    cruser_id int(11) DEFAULT '0' NOT NULL,
    deleted tinyint(4) DEFAULT '0' NOT NULL,
    sys_language_uid int(11) DEFAULT '0' NOT NULL,
    l18n_parent int(11) DEFAULT '0' NOT NULL,
    l18n_diffsource mediumblob NOT NULL,
    label tinytext NOT NULL,
    index_name tinytext NOT NULL,
    website tinytext NOT NULL,
    contact tinytext NOT NULL,
    image mediumblob NOT NULL,
    oai_label tinytext NOT NULL,
    oai_base int(11) DEFAULT '0' NOT NULL,
    opac_label tinytext NOT NULL,
    opac_base tinytext NOT NULL,
    union_label tinytext NOT NULL,
    union_base tinytext NOT NULL,

    PRIMARY KEY (uid),
    KEY parent (pid)
);

--
-- Table structure for table 'tx_dlf_tokens'
--
CREATE TABLE tx_dlf_tokens (
    uid int(11) NOT NULL auto_increment,
    tstamp int(11) DEFAULT '0' NOT NULL,
	token tinytext NOT NULL,
	options longtext NOT NULL,
	ident varchar(30) DEFAULT '' NOT NULL,

    PRIMARY KEY (uid)
);

--
-- Table structure for table 'tx_dlf_relations'
--
CREATE TABLE tx_dlf_relations (
    uid int(11) NOT NULL auto_increment,
    uid_local int(11) DEFAULT '0' NOT NULL,
    uid_foreign int(11) DEFAULT '0' NOT NULL,
    tablenames varchar(30) DEFAULT '' NOT NULL,
    sorting int(11) DEFAULT '0' NOT NULL,
    sorting_foreign int(11) DEFAULT '0' NOT NULL,
    ident varchar(30) DEFAULT '' NOT NULL,

    PRIMARY KEY (uid),
    KEY uid_local (uid_local),
    KEY uid_foreign (uid_foreign)
);

