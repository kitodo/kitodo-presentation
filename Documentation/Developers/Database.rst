===============
Database Tables
===============

This is a reference of all database tables defined by Kitodo.Presentation.

.. tip:: This page is auto-generated. If you would like to edit it, please use doc-comments in the model class, COMMENT fields in ``ext_tables.sql`` if the table does not have one, or TCA labels. Then, you may re-generate the page by running ``composer docs:db`` inside the Kitodo.Presentation base folder.

tx_dlf_actionlog: Action protocol
=================================

Extbase domain model: ``Kitodo\Dlf\Domain\Model\ActionLog``

(Basket Plugin) Action log for mails and printouts.

.. t3-field-list-table::
   :header-rows: 1

   - :field:                    Field
     :description:              Description

   - :field:                    **uid**  *integer*
     :description:              

   - :field:                    pid  *integer*
     :description:              

   - :field:                    crdate  *integer*
     :description:              

   - :field:                    deleted  *smallint*
     :description:              

   - :field:                    user_id  *integer*
     :description:              *User ID*

   - :field:                    file_name  *string*
     :description:              *Filename*

   - :field:                    count_pages  *integer*
     :description:              *Page count*

   - :field:                    name  *string*
     :description:              *Name*

   - :field:                    label  *string*
     :description:              *Action protocol*




tx_dlf_basket: Basket
=====================

Extbase domain model: ``Kitodo\Dlf\Domain\Model\Basket``

(Basket Plugin) A basket that is bound to a frontend session.

.. t3-field-list-table::
   :header-rows: 1

   - :field:                    Field
     :description:              Description

   - :field:                    **uid**  *integer*
     :description:              

   - :field:                    pid  *integer*
     :description:              

   - :field:                    tstamp  *integer*
     :description:              

   - :field:                    fe_user_id  *integer*
     :description:              *FE user ID*

   - :field:                    deleted  *smallint*
     :description:              

   - :field:                    sys_language_uid  *integer*
     :description:              

   - :field:                    l18n_parent  *integer*
     :description:              

   - :field:                    l18n_diffsource  *blob*
     :description:              

   - :field:                    label  *string*
     :description:              *Basket*

   - :field:                    session_id  *string*
     :description:              *Session ID*

   - :field:                    doc_ids  *string*
     :description:              *Document ID*




tx_dlf_collections: Collections
===============================

Extbase domain model: ``Kitodo\Dlf\Domain\Model\Collection``

Domain model of the 'Collection'.

.. t3-field-list-table::
   :header-rows: 1

   - :field:                    Field
     :description:              Description

   - :field:                    **uid**  *integer*
     :description:              

   - :field:                    pid  *integer*
     :description:              

   - :field:                    tstamp  *integer*
     :description:              

   - :field:                    crdate  *integer*
     :description:              

   - :field:                    cruser_id  *integer*
     :description:              

   - :field:                    fe_cruser_id  *integer*
     :description:              *Frontend User*

   - :field:                    fe_admin_lock  *smallint*
     :description:              *Disallow frontend editing?*

   - :field:                    deleted  *smallint*
     :description:              

   - :field:                    sys_language_uid  *integer*
     :description:              *Language*

   - :field:                    l18n_parent  *integer*
     :description:              *Transl.Orig*

   - :field:                    l18n_diffsource  *blob*
     :description:              

   - :field:                    hidden  *smallint*
     :description:              *Hide*

   - :field:                    fe_group  *string*
     :description:              *Access*

   - :field:                    label  *string*
     :description:              *Display Label*

   - :field:                    index_name  *string*
     :description:              *Index Name*

   - :field:                    index_search  *text*
     :description:              *Define (virtual) collection via Solr Query*

   - :field:                    oai_name  *string*
     :description:              *OAI-PMH Mapping*

   - :field:                    description  *text*
     :description:              *Description*

   - :field:                    thumbnail  *string*
     :description:              *Thumbnail*
                                
                                thumbnail

   - :field:                    priority  *smallint*
     :description:              *Priority*

   - :field:                    documents  *integer*
     :description:              *Documents*

   - :field:                    owner  *integer*
     :description:              *Owner*

   - :field:                    status  *smallint*
     :description:              *Status*




tx_dlf_documents: Documents
===========================

Extbase domain model: ``Kitodo\Dlf\Domain\Model\Document``

Domain model of the 'Document'.

.. t3-field-list-table::
   :header-rows: 1

   - :field:                    Field
     :description:              Description

   - :field:                    **uid**  *integer*
     :description:              

   - :field:                    pid  *integer*
     :description:              

   - :field:                    tstamp  *integer*
     :description:              *Last Modified*

   - :field:                    crdate  *integer*
     :description:              *Created At*

   - :field:                    cruser_id  *integer*
     :description:              

   - :field:                    deleted  *smallint*
     :description:              

   - :field:                    hidden  *smallint*
     :description:              *Hide*

   - :field:                    starttime  *integer*
     :description:              *Start*

   - :field:                    endtime  *integer*
     :description:              *Stop*

   - :field:                    fe_group  *string*
     :description:              *Access*

   - :field:                    prod_id  *string*
     :description:              *Production Identifier*

   - :field:                    location  *string*
     :description:              *Location of METS file / IIIF manifest (URI)*

   - :field:                    record_id  *string*
     :description:              *Record Identifier*

   - :field:                    opac_id  *string*
     :description:              *OPAC/Local Identifier*

   - :field:                    union_id  *string*
     :description:              *Union Catalog/Foreign Identifier*

   - :field:                    urn  *string*
     :description:              *Uniform Resource Name (URN)*

   - :field:                    purl  *string*
     :description:              *Persistent Uniform Resource Locator (PURL)*

   - :field:                    title  *text*
     :description:              *Title*

   - :field:                    title_sorting  *text*
     :description:              *Title (Sorting)*

   - :field:                    author  *string*
     :description:              *Author*

   - :field:                    year  *string*
     :description:              *Year of Publication*

   - :field:                    place  *string*
     :description:              *Place of Publication*

   - :field:                    thumbnail  *string*
     :description:              *Thumbnail*

   - :field:                    structure  *integer*
     :description:              *Typ of Document*

   - :field:                    partof  *integer*
     :description:              *Part of ...*

   - :field:                    volume  *string*
     :description:              *Number of Volume*

   - :field:                    volume_sorting  *string*
     :description:              *Number of Volume (Sorting)*

   - :field:                    license  *string*
     :description:              *License*

   - :field:                    terms  *string*
     :description:              *Terms of Use*

   - :field:                    restrictions  *string*
     :description:              *Restrictions on Access*

   - :field:                    out_of_print  *text*
     :description:              *Out Of Print Works*

   - :field:                    rights_info  *text*
     :description:              *Rights Information*

   - :field:                    collections  *integer*
     :description:              *Collections*

   - :field:                    mets_label  *text*
     :description:              *METS @LABEL*

   - :field:                    mets_orderlabel  *text*
     :description:              *METS @ORDERLABEL*

   - :field:                    owner  *integer*
     :description:              *Owner*

   - :field:                    solrcore  *integer*
     :description:              

   - :field:                    status  *smallint*
     :description:              *Status*

   - :field:                    document_format  *string*
     :description:              *METS or IIIF*




tx_dlf_formats: Data Formats
============================

Extbase domain model: ``Kitodo\Dlf\Domain\Model\Format``

Configured data formats and namespaces like MODS, ALTO, IIIF etc.
They are referenced by ``tx_dlf_metadataformat.encoded``.
The formats OAI, METS and XLINK are pre-defined.

Data formats are modeled after XML, though JSON may be used with a pseudo root and namespace.

For more information, see the documentation page on metadata.

.. t3-field-list-table::
   :header-rows: 1

   - :field:                    Field
     :description:              Description

   - :field:                    **uid**  *integer*
     :description:              

   - :field:                    pid  *integer*
     :description:              

   - :field:                    tstamp  *integer*
     :description:              

   - :field:                    crdate  *integer*
     :description:              

   - :field:                    cruser_id  *integer*
     :description:              

   - :field:                    deleted  *smallint*
     :description:              

   - :field:                    type  *string*
     :description:              *Format Name (e.g. in METS)*
                                
                                Name of the type that is used to reference it.

   - :field:                    root  *string*
     :description:              *Root Element*
                                
                                The XML root element used by this format.

   - :field:                    namespace  *string*
     :description:              *Namespace URI*
                                
                                The XML namespace URI used by this format.

   - :field:                    class  *string*
     :description:              *Class Name*
                                
                                Fully qualified name of the PHP class that handles the format, or the empty string if no such class is configured.




tx_dlf_libraries: Libraries
===========================

Extbase domain model: ``Kitodo\Dlf\Domain\Model\Library``

A library institution with the following use cases:

- Each ``tx_dlf_document`` is *owned* by exactly one ``tx_dlf_library``. The
  owner is set on indexing, and it is shown in the metadata plugin. If no
  library is configured, the fallback library is named 'default'.

- The OAI-PMH plugin has a configuration option ``library`` that is used to
  identify the OAI repository.

.. t3-field-list-table::
   :header-rows: 1

   - :field:                    Field
     :description:              Description

   - :field:                    **uid**  *integer*
     :description:              

   - :field:                    pid  *integer*
     :description:              

   - :field:                    tstamp  *integer*
     :description:              

   - :field:                    crdate  *integer*
     :description:              

   - :field:                    cruser_id  *integer*
     :description:              

   - :field:                    deleted  *smallint*
     :description:              

   - :field:                    sys_language_uid  *integer*
     :description:              *Language*

   - :field:                    l18n_parent  *integer*
     :description:              *Transl.Orig*

   - :field:                    l18n_diffsource  *blob*
     :description:              

   - :field:                    label  *string*
     :description:              *Name*

   - :field:                    index_name  *string*
     :description:              *Index Name*

   - :field:                    website  *string*
     :description:              *Website*

   - :field:                    contact  *string*
     :description:              *Contact*
                                
                                Contact email address of the library (used as ``adminEmail`` in responses
                                to OAI ``Identify`` requests).

   - :field:                    image  *string*
     :description:              *Logo*
                                
                                image

   - :field:                    oai_label  *string*
     :description:              *Open Archives Interface (OAI) Label*
                                
                                The label that is used as ``repositoryName`` in responses to OAI
                                ``Identify`` requests.

   - :field:                    oai_base  *string*
     :description:              *Open Archives Interface (OAI) Base URL*
                                
                                OAI base URL used when harvesting the library via ``kitodo:harvest``.

   - :field:                    opac_label  *string*
     :description:              *Online Public Access Catalog (OPAC) Label*

   - :field:                    opac_base  *string*
     :description:              *Online Public Access Catalog (OPAC) Base URL*

   - :field:                    union_label  *string*
     :description:              *Union Catalog Label*

   - :field:                    union_base  *string*
     :description:              *Union Catalog Base URL*




tx_dlf_mail: Email
==================

Extbase domain model: ``Kitodo\Dlf\Domain\Model\Mail``

(Basket Plugin) Recipient mail addresses for sending documents.

.. t3-field-list-table::
   :header-rows: 1

   - :field:                    Field
     :description:              Description

   - :field:                    **uid**  *integer*
     :description:              

   - :field:                    pid  *integer*
     :description:              

   - :field:                    deleted  *smallint*
     :description:              

   - :field:                    sorting  *integer*
     :description:              

   - :field:                    mail  *string*
     :description:              *Address*

   - :field:                    name  *string*
     :description:              *Name*

   - :field:                    label  *string*
     :description:              *Email*




tx_dlf_metadata: Metadata
=========================

Extbase domain model: ``Kitodo\Dlf\Domain\Model\Metadata``

A metadata kind (title, year, ...) and its configuration for display and indexing.

.. t3-field-list-table::
   :header-rows: 1

   - :field:                    Field
     :description:              Description

   - :field:                    **uid**  *integer*
     :description:              

   - :field:                    pid  *integer*
     :description:              

   - :field:                    tstamp  *integer*
     :description:              

   - :field:                    crdate  *integer*
     :description:              

   - :field:                    cruser_id  *integer*
     :description:              

   - :field:                    deleted  *smallint*
     :description:              

   - :field:                    sys_language_uid  *integer*
     :description:              *Language*

   - :field:                    l18n_parent  *integer*
     :description:              *Transl.Orig*

   - :field:                    l18n_diffsource  *blob*
     :description:              

   - :field:                    hidden  *smallint*
     :description:              *Hide*

   - :field:                    sorting  *integer*
     :description:              Order (relative position) of this entry in metadata plugin and backend list.

   - :field:                    label  *string*
     :description:              *Display Label*

   - :field:                    index_name  *string*
     :description:              *Index Name*

   - :field:                    format  *integer*
     :description:              *Data Format*
                                
                                The formats that encode this metadatum (local IRRE field to ``tx_dlf_metadataformat``).

   - :field:                    default_value  *string*
     :description:              *Default Value*

   - :field:                    wrap  *text*
     :description:              *TypoScript-Wrap*

   - :field:                    index_tokenized  *smallint*
     :description:              *Tokenize in Search Index?*

   - :field:                    index_stored  *smallint*
     :description:              *Store in Search Index?*

   - :field:                    index_indexed  *smallint*
     :description:              *Index in Search Index?*

   - :field:                    index_boost  *float*
     :description:              *Field boost*

   - :field:                    is_sortable  *smallint*
     :description:              *Prepare for sorting?*

   - :field:                    is_facet  *smallint*
     :description:              *Prepare for faceting?*

   - :field:                    is_listed  *smallint*
     :description:              *Show in titledata/listview?*

   - :field:                    index_autocomplete  *smallint*
     :description:              *Use for search suggestion?*

   - :field:                    status  *smallint*
     :description:              *Status*




tx_dlf_metadataformat: Metadata Format
======================================

Extbase domain model: ``Kitodo\Dlf\Domain\Model\MetadataFormat``

This specifies a way how a metadatum (``tx_dlf_metadata``) may be encoded in a specific data format (``tx_dlf_format``).

For instance, the title of a document may be obtained from either the MODS
title field, or from the TEIHDR caption. This is modeled as two ``tx_dlf_metadaformat``
that refer to the same ``tx_dlf_metadata`` but different ``tx_dlf_format``.

This contains the xpath expressions on the model 'Metadata'.

.. t3-field-list-table::
   :header-rows: 1

   - :field:                    Field
     :description:              Description

   - :field:                    **uid**  *integer*
     :description:              

   - :field:                    pid  *integer*
     :description:              

   - :field:                    tstamp  *integer*
     :description:              

   - :field:                    crdate  *integer*
     :description:              

   - :field:                    cruser_id  *integer*
     :description:              

   - :field:                    deleted  *smallint*
     :description:              

   - :field:                    sys_language_uid  *integer*
     :description:              *Language*

   - :field:                    l18n_parent  *integer*
     :description:              *Transl.Orig*

   - :field:                    parent_id  *integer*
     :description:              UID of the ``tx_dlf_metadata`` that is encoded by this metadata entry.

   - :field:                    encoded  *integer*
     :description:              *Encoding*
                                
                                UID of the ``tx_dlf_format`` in which this metadata entry is encoded.

   - :field:                    xpath  *string*
     :description:              *XPath (relative to //dmdSec/mdWrap/xmlData/root and with namespace) or JSONPath (relative to resource JSON object)*
                                
                                XPath/JSONPath expression to extract the metadatum (relative to the data format root).

   - :field:                    xpath_sorting  *string*
     :description:              *XPath / JSONPath for sorting (optional)*
                                
                                XPath/JSONPath expression to extract sorting variant (suffixed ``_sorting``) of the metadatum.

   - :field:                    mandatory  *smallint*
     :description:              *Mandatory field?*
                                
                                Whether or not the field is mandatory. Not used at the moment (originally planned to be used in METS validator).




tx_dlf_printer: Printer
=======================

Extbase domain model: ``Kitodo\Dlf\Domain\Model\Printer``

(Basket Plugin) External printers for sending documents.

.. t3-field-list-table::
   :header-rows: 1

   - :field:                    Field
     :description:              Description

   - :field:                    **uid**  *integer*
     :description:              

   - :field:                    pid  *integer*
     :description:              

   - :field:                    deleted  *smallint*
     :description:              

   - :field:                    print  *string*
     :description:              *CLI command(##fileName##)*

   - :field:                    label  *string*
     :description:              *Label*




tx_dlf_relations
================

Pivot table for many-to-many relations between tables. In particular, this is used to match documents and collections by using ident=docs_colls.

.. t3-field-list-table::
   :header-rows: 1

   - :field:                    Field
     :description:              Description

   - :field:                    **uid**  *integer*
     :description:              

   - :field:                    uid_local  *integer*
     :description:              

   - :field:                    uid_foreign  *integer*
     :description:              

   - :field:                    tablenames  *string*
     :description:              

   - :field:                    sorting  *integer*
     :description:              

   - :field:                    sorting_foreign  *integer*
     :description:              

   - :field:                    ident  *string*
     :description:              An identifier to describe which tables are matched.




tx_dlf_solrcores: Solr Cores
============================

Extbase domain model: ``Kitodo\Dlf\Domain\Model\SolrCore``

Cores on the application-wide Solr instance that are available for indexing.
They may be used, for example, as a parameter to the CLI indexing commands, and are referenced by ``tx_dlf_document.solrcore``.
In particular, this holds the index name of the used Solr core.

.. t3-field-list-table::
   :header-rows: 1

   - :field:                    Field
     :description:              Description

   - :field:                    **uid**  *integer*
     :description:              

   - :field:                    pid  *integer*
     :description:              

   - :field:                    tstamp  *integer*
     :description:              

   - :field:                    crdate  *integer*
     :description:              

   - :field:                    cruser_id  *integer*
     :description:              

   - :field:                    deleted  *smallint*
     :description:              

   - :field:                    label  *string*
     :description:              *Display Label*
                                
                                Label of the core that is displayed in the backend.

   - :field:                    index_name  *string*
     :description:              *Solr Core*
                                
                                The actual name of the Solr core.




tx_dlf_structures: Structures
=============================

Extbase domain model: ``Kitodo\Dlf\Domain\Model\Structure``

Domain model of 'Structure'.

.. t3-field-list-table::
   :header-rows: 1

   - :field:                    Field
     :description:              Description

   - :field:                    **uid**  *integer*
     :description:              

   - :field:                    pid  *integer*
     :description:              

   - :field:                    tstamp  *integer*
     :description:              

   - :field:                    crdate  *integer*
     :description:              

   - :field:                    cruser_id  *integer*
     :description:              

   - :field:                    deleted  *smallint*
     :description:              

   - :field:                    sys_language_uid  *integer*
     :description:              *Language*

   - :field:                    l18n_parent  *integer*
     :description:              *Transl.Orig*

   - :field:                    l18n_diffsource  *blob*
     :description:              

   - :field:                    hidden  *smallint*
     :description:              *Hide*

   - :field:                    toplevel  *smallint*
     :description:              *Toplevel Unit?*

   - :field:                    label  *string*
     :description:              *Display Label*

   - :field:                    index_name  *string*
     :description:              *Index Name*

   - :field:                    oai_name  *string*
     :description:              *OAI-PMH Mapping*

   - :field:                    thumbnail  *integer*
     :description:              *Get thumbnail from...*

   - :field:                    status  *smallint*
     :description:              *Status*




tx_dlf_tokens
=============

Extbase domain model: ``Kitodo\Dlf\Domain\Model\Token``

Resumption tokens for OAI-PMH interface.

.. t3-field-list-table::
   :header-rows: 1

   - :field:                    Field
     :description:              Description

   - :field:                    **uid**  *integer*
     :description:              

   - :field:                    pid  *integer*
     :description:              

   - :field:                    tstamp  *integer*
     :description:              Timestamp of the token used to determine if it has expired.

   - :field:                    token  *string*
     :description:              The resumption token string.

   - :field:                    options  *text*
     :description:              Data that is used to resume the previous request.

   - :field:                    ident  *string*
     :description:              Originally an identifier for the kind of token ('oai'). Not used at the moment.




