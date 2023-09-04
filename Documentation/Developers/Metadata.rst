========
Metadata
========

Kitodo.Presentation allows a per-tenant configuration of which metadata is extracted from documents, and how it is indexed and displayed.

Formats
=======

A METS document may embed or reference resources in many formats:

-  Within the ``<mdWrap>`` tag, it may embed sections of metadata in formats such as MODS or TEIHDR (TEI Header).

-  Via the ``<FLocat>`` tag, it may reference fulltext in formats such as ALTO.

Data format records, which are stored on the root page (PID = 0), tell Kitodo how to extract and process these formats, and may be used to define the entries shown in the metadata plugin.
A record contains the following fields:

.. t3-field-list-table::
   :header-rows:  1

   - :---: Make description column three times the width of field column.
     :field,1:       Field
     :description,3: Description

   - :field:         Format Name
     :description:   Name of the type that is used to reference it.

                     -  For metadata embedded via ``<mdWrap>``, this corresponds to its ``MDTYPE`` or ``OTHERMDTYPE`` attribute.

                     -  For XML fulltext files, this corresponds to the capitalized root tag of the file.

   - :field:         Root Element
     :description:   The XML root element used by this format. In METS, this is used to locate the sub-root within an ``<mdWrap>``.

   - :field:         Namespace URI
     :description:   The XML namespace URI used by this format. It is registered within the parser and may be used to declare namespace prefixes.

   - :field:         Class Name
     :description:   (Optional) Fully qualified name of the PHP class that handles the format. Some formats are pre-defined in the ``Kitodo\Dlf\Format`` namespace.

                     For metadata, this is used to programmatically extract values by implementing ``MetadataInterface``.
                     This may be useful, for example, when the value is needed universally, is difficult to extract via XPath, or requires post-processing.

                     For fulltext, this is used to parse the fulltext file by implementing the ``FulltextInterface``.


TypoScript Wrap
===============

The TypoScript wrap controls how the metadata plugin displays the entries.
There are three objects that may be set, each of which is passed to ``stdWrap`` with a different aspect of the metadata table.

-  ``key``: First, the (localized) *label* of the metadata entry, such as "Title" or "Year", is transformed using the ``key`` object.

-  ``value``: Each *value* of the entry is transformed using the ``value`` object.

-  ``all``: The combined output (label and values) is processed using the ``all`` object and appended to the output (unless there is no value or all processed values are blank).

Finally, all entries are wrapped in a definition list (``<dl>`` tag). If one of the objects is not specified, the unprocessed output is taken as-is.

Displaying Multiple Values
--------------------------

To allow TypoScript wrap to reference metadata values,
all configured entries of the current metadata section are set as data before calling ``stdWrap``.
The index names are used as key.
Whenever there are multiple values, they are joined by the ``separator`` that is configured in the metadata plugin.

To see where this is useful, consider the following MODS access condition.

.. code-block:: xml

   <mods:accessCondition
      xmlns:xlink="http://www.w3.org/1999/xlink"
      type="use and reproduction"
      xlink:href="http://creativecommons.org/licenses/by-sa/4.0/"
   >
       CC BY-SA 4.0
   </mods:accessCondition>

In the metadata table, we may want to render an ``<a>`` tag that links to the license summary page and displays the license name as link text.
To achieve this, we may create two metadata entries:

*  Create a hidden entry called ``useandreproduction_link`` that has ``xpath`` set to ``./mods:accessCondition[@type="use and reproduction"]/@xlink:href``.
   This entry is used purely to extract the license URL.

*  The main entry sets ``xpath`` to ``./mods:accessCondition[@type="use and reproduction"]`` (so it extracts the license name)
   and uses a ``wrap`` along the lines of

   .. code-block:: typoscript

      key.wrap = <dt>|</dt>
      value.required = 1
      value.typolink.parameter.field = useandreproduction_link
      value.wrap = <dd>|</dd>
