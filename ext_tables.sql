--
-- Table structure for table 'tx_dlf_documents'
--
CREATE TABLE tx_dlf_documents (
    uid int(11) NOT NULL auto_increment,
    pid int(11) DEFAULT '0' NOT NULL,
    tstamp int(11) DEFAULT '0' NOT NULL,
    crdate int(11) DEFAULT '0' NOT NULL,
    cruser_id int(11) DEFAULT '0' NOT NULL,
    deleted smallint(6) DEFAULT '0' NOT NULL,
    hidden smallint(6) DEFAULT '0' NOT NULL,
    starttime int(11) DEFAULT '0' NOT NULL,
    endtime int(11) DEFAULT '0' NOT NULL,
    fe_group varchar(100) DEFAULT '' NOT NULL,
    prod_id varchar(255) DEFAULT '' NOT NULL,
    location varchar(255) DEFAULT '' NOT NULL,
    record_id varchar(255) DEFAULT '' NOT NULL,
    opac_id varchar(255) DEFAULT '' NOT NULL,
    union_id varchar(255) DEFAULT '' NOT NULL,
    urn varchar(255) DEFAULT '' NOT NULL,
    purl varchar(255) DEFAULT '' NOT NULL,
    title text NOT NULL,
    title_sorting text NOT NULL,
    author varchar(255) DEFAULT '' NOT NULL,
    year varchar(255) DEFAULT '' NOT NULL,
    place varchar(255) DEFAULT '' NOT NULL,
    thumbnail varchar(255) DEFAULT '' NOT NULL,
    metadata text NOT NULL,
    metadata_sorting text NOT NULL,
    structure int(11) DEFAULT '0' NOT NULL,
    partof int(11) DEFAULT '0' NOT NULL,
    volume varchar(255) DEFAULT '' NOT NULL,
    volume_sorting varchar(255) DEFAULT '' NOT NULL,
    license varchar(255) DEFAULT '' NOT NULL,
    terms varchar(255) DEFAULT '' NOT NULL,
    restrictions varchar(255) DEFAULT '' NOT NULL,
    out_of_print text NOT NULL,
    rights_info text NOT NULL,
    collections int(11) DEFAULT '0' NOT NULL,
    mets_label varchar(255) DEFAULT '' NOT NULL,
    mets_orderlabel varchar(255) DEFAULT '' NOT NULL,
    owner int(11) DEFAULT '0' NOT NULL,
    solrcore int(11) DEFAULT '0' NOT NULL,
    status smallint(6) unsigned DEFAULT '0' NOT NULL,
    document_format varchar(100) DEFAULT '' NOT NULL,

    PRIMARY KEY (uid),
    KEY parent (pid),
    KEY location (location),
    KEY record_id (record_id),
    KEY partof (partof)
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
    deleted smallint(6) DEFAULT '0' NOT NULL,
    sys_language_uid int(11) DEFAULT '0' NOT NULL,
    l18n_parent int(11) DEFAULT '0' NOT NULL,
    l18n_diffsource mediumblob NOT NULL,
    hidden smallint(6) DEFAULT '0' NOT NULL,
    toplevel smallint(6) DEFAULT '0' NOT NULL,
    label varchar(255) DEFAULT '' NOT NULL,
    index_name varchar(255) DEFAULT '' NOT NULL,
    oai_name varchar(255) DEFAULT '' NOT NULL,
    thumbnail int(11) DEFAULT '0' NOT NULL,
    status smallint(6) unsigned DEFAULT '0' NOT NULL,

    PRIMARY KEY (uid),
    KEY parent (pid),
    KEY language (l18n_parent,sys_language_uid),
    KEY index_name (index_name)
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
    deleted smallint(6) DEFAULT '0' NOT NULL,
    sys_language_uid int(11) DEFAULT '0' NOT NULL,
    l18n_parent int(11) DEFAULT '0' NOT NULL,
    l18n_diffsource mediumblob NOT NULL,
    hidden smallint(6) DEFAULT '0' NOT NULL,
    sorting int(11) DEFAULT '0' NOT NULL,
    label varchar(255) DEFAULT '' NOT NULL,
    index_name varchar(255) DEFAULT '' NOT NULL,
    format int(11) DEFAULT '0' NOT NULL,
    default_value varchar(255) DEFAULT '' NOT NULL,
    wrap text NOT NULL,
    index_tokenized smallint(6) DEFAULT '0' NOT NULL,
    index_stored smallint(6) DEFAULT '0' NOT NULL,
    index_indexed smallint(6) DEFAULT '0' NOT NULL,
    index_boost float(4,2) DEFAULT '1.00' NOT NULL,
    is_sortable smallint(6) DEFAULT '0' NOT NULL,
    is_facet smallint(6) DEFAULT '0' NOT NULL,
    is_listed smallint(6) DEFAULT '0' NOT NULL,
    index_autocomplete smallint(6) DEFAULT '0' NOT NULL,
    status smallint(6) unsigned DEFAULT '0' NOT NULL,

    PRIMARY KEY (uid),
    KEY parent (pid),
    KEY language (l18n_parent,sys_language_uid),
    KEY index_name (index_name),
    KEY index_autocomplete (index_autocomplete),
    KEY is_sortable (is_sortable),
    KEY is_facet (is_facet),
    KEY is_listed (is_listed)
);

--
-- Table structure for table 'tx_dlf_metadataformat'
--
CREATE TABLE tx_dlf_metadataformat (
    uid int(11) NOT NULL auto_increment,
    pid int(11) DEFAULT '0' NOT NULL,
    tstamp int(11) DEFAULT '0' NOT NULL,
    crdate int(11) DEFAULT '0' NOT NULL,
    cruser_id int(11) DEFAULT '0' NOT NULL,
    deleted smallint(6) DEFAULT '0' NOT NULL,
    parent_id int(11) DEFAULT '0' NOT NULL,
    encoded int(11) DEFAULT '0' NOT NULL,
    xpath varchar(1024) DEFAULT '' NOT NULL,
    xpath_sorting varchar(1024) DEFAULT '' NOT NULL,
    mandatory smallint(6) DEFAULT '0' NOT NULL,

    PRIMARY KEY (uid),
    KEY parent (pid),
    KEY parent_id (parent_id)
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
    deleted smallint(6) DEFAULT '0' NOT NULL,
    type varchar(255) DEFAULT '' NOT NULL,
    root varchar(255) DEFAULT '' NOT NULL,
    namespace varchar(255) DEFAULT '' NOT NULL,
    class varchar(255) DEFAULT '' NOT NULL,

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
    deleted smallint(6) DEFAULT '0' NOT NULL,
    label varchar(255) DEFAULT '' NOT NULL,
    index_name varchar(255) DEFAULT '' NOT NULL,

    PRIMARY KEY (uid),
    KEY parent (pid),
    KEY index_name (index_name)
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
    fe_admin_lock smallint(6) DEFAULT '0' NOT NULL,
    deleted smallint(6) DEFAULT '0' NOT NULL,
    sys_language_uid int(11) DEFAULT '0' NOT NULL,
    l18n_parent int(11) DEFAULT '0' NOT NULL,
    l18n_diffsource mediumblob NOT NULL,
    hidden smallint(6) DEFAULT '0' NOT NULL,
    fe_group varchar(100) DEFAULT '' NOT NULL,
    label varchar(255) DEFAULT '' NOT NULL,
    index_name varchar(255) DEFAULT '' NOT NULL,
    index_search text NOT NULL,
    oai_name varchar(255) DEFAULT '' NOT NULL,
    description text NOT NULL,
    thumbnail text NOT NULL,
    priority smallint(6) DEFAULT '3' NOT NULL,
    documents int(11) DEFAULT '0' NOT NULL,
    owner int(11) DEFAULT '0' NOT NULL,
    status smallint(6) unsigned DEFAULT '0' NOT NULL,

    PRIMARY KEY (uid),
    KEY parent (pid),
    KEY language (l18n_parent,sys_language_uid),
    KEY index_name (index_name),
    KEY oai_name (oai_name),
    KEY pid_cruser (pid,fe_cruser_id)
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
    deleted smallint(6) DEFAULT '0' NOT NULL,
    sys_language_uid int(11) DEFAULT '0' NOT NULL,
    l18n_parent int(11) DEFAULT '0' NOT NULL,
    l18n_diffsource mediumblob NOT NULL,
    label varchar(255) DEFAULT '' NOT NULL,
    index_name varchar(255) DEFAULT '' NOT NULL,
    website varchar(255) DEFAULT '' NOT NULL,
    contact varchar(255) DEFAULT '' NOT NULL,
    image mediumblob NOT NULL,
    oai_label varchar(255) DEFAULT '' NOT NULL,
    oai_base varchar(255) DEFAULT '' NOT NULL,
    opac_label varchar(255) DEFAULT '' NOT NULL,
    opac_base varchar(255) DEFAULT '' NOT NULL,
    union_label varchar(255) DEFAULT '' NOT NULL,
    union_base varchar(255) DEFAULT '' NOT NULL,

    PRIMARY KEY (uid),
    KEY parent (pid),
    KEY language (l18n_parent,sys_language_uid),
    KEY index_name (index_name)
);

--
-- Table structure for table 'tx_dlf_tokens'
--
CREATE TABLE tx_dlf_tokens (
    uid int(11) NOT NULL auto_increment,
    tstamp int(11) DEFAULT '0' NOT NULL,
    token varchar(255) DEFAULT '' NOT NULL,
    options mediumtext NOT NULL,
    ident varchar(30) DEFAULT '' NOT NULL,

    PRIMARY KEY (uid),
    KEY token (token)
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
    KEY local_foreign (uid_local,uid_foreign,ident)
);

--
-- Table structure for table 'tx_dlf_basket'
--
CREATE TABLE tx_dlf_basket (
    uid int(11) NOT NULL auto_increment,
    pid int(11) DEFAULT '0' NOT NULL,
    tstamp int(11) DEFAULT '0' NOT NULL,
    fe_user_id int(11) DEFAULT '0' NOT NULL,
    deleted smallint(6) DEFAULT '0' NOT NULL,
    sys_language_uid int(11) DEFAULT '0' NOT NULL,
    l18n_parent int(11) DEFAULT '0' NOT NULL,
    l18n_diffsource mediumblob NOT NULL,
    label varchar(255) DEFAULT '' NOT NULL,
    session_id varchar(32) DEFAULT '' NOT NULL,
    doc_ids varchar(255) DEFAULT '' NOT NULL,

    PRIMARY KEY (uid),
    KEY parent (pid),
    KEY language (l18n_parent,sys_language_uid)
);

--
-- Table structure for table 'tx_dlf_printer'
--
CREATE TABLE tx_dlf_printer (
    uid int(11) NOT NULL auto_increment,
    pid int(11) DEFAULT '0' NOT NULL,
    deleted smallint(6) DEFAULT '0' NOT NULL,
    print varchar(255) DEFAULT '' NOT NULL,
    label varchar(255) DEFAULT '' NOT NULL,

    PRIMARY KEY (uid),
    KEY parent (pid)
);

--
-- Table structure for table 'tx_dlf_mail'
--
CREATE TABLE tx_dlf_mail (
    uid int(11) NOT NULL auto_increment,
    pid int(11) DEFAULT '0' NOT NULL,
    deleted smallint(6) DEFAULT '0' NOT NULL,
    sorting int(11) DEFAULT '0' NOT NULL,
    mail varchar(255) DEFAULT '' NOT NULL,
    name varchar(255) DEFAULT '' NOT NULL,
    label varchar(255) DEFAULT '' NOT NULL,

    PRIMARY KEY (uid),
    KEY parent (pid)
);

--
-- Table structure for table 'tx_dlf_actionlog'
--
CREATE TABLE tx_dlf_actionlog (
    uid int(11) NOT NULL auto_increment,
    pid int(11) DEFAULT '0' NOT NULL,
    crdate int(11) DEFAULT '0' NOT NULL,
    deleted smallint(6) DEFAULT '0' NOT NULL,
    user_id int(11) DEFAULT '0' NOT NULL,
    file_name varchar(255) DEFAULT '' NOT NULL,
    count_pages int(11) DEFAULT '0' NOT NULL,
    name varchar(100) DEFAULT '' NOT NULL,
    label varchar(255) DEFAULT '' NOT NULL,

    PRIMARY KEY (uid),
    KEY parent (pid)
);
