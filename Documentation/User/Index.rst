.. ==================================================
.. FOR YOUR INFORMATION
.. --------------------------------------------------
.. -*- coding: utf-8 -*- with BOM.

.. include:: ../Includes.txt


.. _user_manual:

###########
User Manual
###########

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
       ``-o|--owner``
   :Required:
       no
   :Description:
       This may be the UID of the library record in `tx_dlf_libraries` which
       should be set as the owner of the document. If omitted, the default is
       to try to read the ownership from the metadata field "owner".
   :Example:
       123

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
       Do not output any message. Useful when using a wrapper script. The
       script may check the return value of the CLI job. This is always 0 on
       success and 1 on failure.
   :Example:

 - :Option:
       ``-v|--verbose``
   :Required:
       no
   :Description:
       Show processed documents uid and location with indexing parameters.
   :Example:

.. _reindex_collections:

Reindex collections
-------------------

With the command `kitodo:reindex` it is possible to reindex one or more
collections or even to reindex all documents on the given page.::

    # reindex collection with uid 1 on page 123 with solr core 'dlfCore1'
    # short notation
    ./vendor/bin/typo3 kitodo:reindex -c 1 -p 123 -s dlfCore1
    # long notation
    ./vendor/bin/typo3 kitodo:reindex --coll 1 --pid 123 --solr dlfCore1

    # reindex collection with uid 1 on page 123 with solr core 'dlfCore1' in given range
    # short notation
    ./vendor/bin/typo3 kitodo:reindex -c 1 -l 1000 -b 0 -p 123 -s dlfCore1
    ./vendor/bin/typo3 kitodo:reindex -c 1 -l 1000 -b 1000 -p 123 -s dlfCore1
    # long notation
    ./vendor/bin/typo3 kitodo:reindex --coll 1 --index-limit=1000 --index-begin=0 --pid 123 ---solr dlfCore1
    ./vendor/bin/typo3 kitodo:reindex --coll 1 --index-limit=1000 --index-begin=1000 --pid 123 --solr dlfCore1

    # reindex collection with uid 1 and 4 on page 123 with solr core 'dlfCore1'
    # short notation
    ./vendor/bin/typo3 kitodo:reindex -c 1,4 -p 123 -s dlfCore1
    # long notation
    ./vendor/bin/typo3 kitodo:reindex --coll 1,4 --pid 123 --solr dlfCore1

    # reindex collection with uid 1 and 4 on page 123 with solr core 'dlfCore1' in given range
    # short notation
    ./vendor/bin/typo3 kitodo:reindex -c 1,4 -l 1000 -b 0 -p 123 -s dlfCore1
    ./vendor/bin/typo3 kitodo:reindex -c 1,4 -l 1000 -b 1000 -p 123 -s dlfCore1
    # long notation
    ./vendor/bin/typo3 kitodo:reindex --coll 1,4 --index-limit=1000 --index-begin=0 --pid 123 ---solr dlfCore1
    ./vendor/bin/typo3 kitodo:reindex --coll 1,4 --index-limit=1000 --index-begin=1000 --pid 123 --solr dlfCore1

    # reindex all documents on page 123 with solr core 'dlfCore1' (caution can result in memory problems for big amount of documents)
    # short notation
    ./vendor/bin/typo3 kitodo:reindex -a -p 123 -s dlfCore1
    # long notation
    ./vendor/bin/typo3 kitodo:reindex --all --pid 123 --solr dlfCore1

    # reindex all documents on page 123 with solr core 'dlfCore1' in given range
    # short notation
    ./vendor/bin/typo3 kitodo:reindex -a -l 1000 -b 0 -p 123 -s dlfCore1
    ./vendor/bin/typo3 kitodo:reindex -a -l 1000 -b 1000 -p 123 -s dlfCore1
    # long notation
    ./vendor/bin/typo3 kitodo:reindex --all --index-limit=1000 --index-begin=0 --pid 123 ---solr dlfCore1
    ./vendor/bin/typo3 kitodo:reindex --all --index-limit=1000 --index-begin=1000 --pid 123 --solr dlfCore1


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
       ``-o|--owner``
   :Required:
       no
   :Description:
       This may be the UID of the library record in `tx_dlf_libraries` which
       should be set as the owner of the documents. If omitted, the default is
       to try to read the ownership from the metadata field "owner".
   :Example:
       123

 - :Option:
       ``-l|--index-limit``
   :Required:
       no
   :Description:
       With this option, all documents in given limit for the given page will
       be reindex.

       Used when it is expected that memory problems can appear due to the high
       amount of documents.
   :Example:
       1000

 - :Option:
       ``-b|--index-begin``
   :Required:
       no
   :Description:
       With this option, all documents beginning from given value for the given page
       will be reindex.

       Used when it is expected that memory problems can appear due to the high
       amount of documents.
   :Example:
       0

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
       Do not output any message. Useful when using a wrapper script. The
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

Harvest OAI-PMH interface
-------------------------

With the command `kitodo:harvest` it is possible to harvest an OAI-PMH
interface and index all fetched records.::

    # example
    ./vendor/bin/typo3 kitodo:harvest --lib=<UID> --pid=<PID> --solr=<CORE> --from=<timestamp> --until=<timestamp> --set=<set>

In order to use the command, you first have to configure a library in the
backend, setting at least a label and oai_base. The latter should be a valid
OAI-PMH base URL (e.g. https://digital.slub-dresden.de/oai/).

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
       ``-l|--lib``
   :Required:
       yes
   :Description:
       This is the UID of the library record with the OAI interface that
       should be harvested. This library is also automatically set as the
       documents' owner.
   :Example:
       123

 - :Option:
       ``-p|--pid``
   :Required:
       yes
   :Description:
       This is the page UID of the library record and therefore the page the
       documents are added to.
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
       ``--from``
   :Required:
       no
   :Description:
       This is a timestamp in the format YYYY-MM-DD. The parameters from and
       until limit harvesting to the given period, e.g. for incremental updates.
   :Example:
       2021-01-01

 - :Option:
       ``--until``
   :Required:
       no
   :Description:
       This is a timestamp in the format YYYY-MM-DD. The parameters from and
       until limit harvesting to the given period, e.g. for incremental updates.
   :Example:
       2021-06-30

 - :Option:
       ``--set``
   :Required:
       no
   :Description:
       This is the name of an OAI set. The parameter limits harvesting to the
       given set.
   :Example:
       'vd18'

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
       Do not output any message. Useful when using a wrapper script. The
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
