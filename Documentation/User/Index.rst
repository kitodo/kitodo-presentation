.. ==================================================
.. FOR YOUR INFORMATION
.. --------------------------------------------------
.. -*- coding: utf-8 -*- with BOM.

.. include:: ../Includes.txt


.. _user_manual:

===========
User Manual
===========

.. contents::
    :local:
    :depth: 2


.. _indexing_documents:

Indexing Documents
==================

New documents may be indexed via the TYPO3 command line interface (CLI).

Index single document
---------------------

The command `kitodo:index` is used for indexing a single document::

    ./vendor/bin/typo3 kitodo:index -d http://example.com/path/mets.xml -p 123 -s dlfCore1


.. t3-field-list-table::
 :header-rows: 1

 - :Option:
       Option
   :Required:
       Required
   :Description:
       Description
   :Example:
       Example

 - :Option:
      ``-d|--doc``
   :Required:
       yes
   :Description:
       This may be an UID of an existing document in `tx_dlf_documents` or the
       URL of a METS XML file. If the URL is already known as location in
       `tx_dlf_documents`, the file is processed anyway and the records in
       database and solr index are updated.

       Hint: Do not encode the URL! If you have spaces in path, use quotation
       marks.
   :Example:
       123 or http://example.com/path/mets.xml

 - :Option:
       ``-p|--pid``
   :Required:
       yes
   :Description:
       The page UID of the Kitodo.Presentation data folder. This keeps all
       records of documents, metadata, structures, solrcores etc.
   :Example:
       123

 - :Option:
       ``-s|--solr``
   :Required:
       yes
   :Description:
       This may be the UID of the solrcore record in `tx_dlf_solrcores`.
       Alternatively you may write the index name of the solr core.

       The solr core must exist in table tx_dlf_solrcores on page "pid".
       Otherwise an error is shown and the processing won't start.
   :Example:
       123 or 'dlfCore1'

 - :Option:
       ``--dry-run``
   :Required:
       no
   :Description:
       Nothing will be written to database or index. The solr-setting will be
       checked and the documents location URL will be shown.
   :Example:

 - :Option:
       ``-q|--quite``
   :Required:
       no
   :Description:
       Do not output any message. Usefull when using a wrapper script. The
       script may check the return value of the CLI job. This is always 0 on
       success and 1 on failure.
   :Example:

Reindex collections
-------------------

With the command `kitodo:reindex` it is possible to reindex one or more
collections or even to reindex all documents on the given page.::

    # reindex collection with uid 1 on page 123 with solr core 'dlfCore1'
    ./vendor/bin/typo3 kitodo:reindex -c 1 -p 123 -s dlfCore1

    # reindex collection with uid 1 and 4 on page 123 with solr core 'dlfCore1'
    ./vendor/bin/typo3 kitodo:reindex -c 1,4 -p 123 -s dlfCore1

    # reindex all documents on page 123 with solr core 'dlfCore1'
    ./vendor/bin/typo3 kitodo:reindex -a -p 123 -s dlfCore1



.. t3-field-list-table::
 :header-rows: 1

 - :Option:
       Option
   :Required:
       Required
   :Description:
       Description
   :Example:
       Example

 - :Option:
       ``-a|--all``
   :Required:
       no
   :Description:
       With this option, all documents from the given page will be reindex.
   :Example:

 - :Option:
       ``-c|--coll``
   :Required:
       no
   :Description:
       This may be a single collection UID or a list of UIDs to reindex.
   :Example:
       1 or 1,2,3

 - :Option:
       ``-p|--pid``
   :Required:
       yes
   :Description:
       The page UID of the Kitodo.Presentation data folder. This keeps all
       records of documents, metadata, structures, solrcores etc.
   :Example:
       123

 - :Option:
       ``-s|--solr``
   :Required:
       yes
   :Description:
       This may be the UID of the solrcore record in `tx_dlf_solrcores`.
       Alternatively you may write the index name of the solr core.

       The solr core must exist in table tx_dlf_solrcores on page "pid".
       Otherwise an error is shown and the processing won't start.
   :Example:
       123 or 'dlfCore1'

 - :Option:
       ``--dry-run``
   :Required:
       no
   :Description:
       Nothing will be written to database or index. All documents will be
       listed which would be processed on a real run.
   :Example:

 - :Option:
       ``-q|--quite``
   :Required:
       no
   :Description:
       Do not output any message. Usefull when using a wrapper script. The
       script may check the return value of the CLI job. This is always 0 on
       success and 1 on failure.
   :Example:

 - :Option:
       ``-v|--verbose``
   :Required:
       no
   :Description:
       Show each processed documents uid and location with timestamp and
       amount of processed/all documents.
   :Example:
