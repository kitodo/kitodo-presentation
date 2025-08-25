===============
Validation
===============

.. contents::
    :local:
    :depth: 2

Validators
=======

DOMDocumentValidationStack
--------------------------

``Kitodo\Dlf\Validation\DOMDocumentValidationStack`` implementation of ``Kitodo\Dlf\Validation\AbstractDlfValidationStack`` for validating DOMDocument against the configurable validators.

The configuration is an array validator configurations each with following entries:

.. t3-field-list-table::
   :header-rows: 1

   - :field:                    className
     :description:              Fully qualified class name of validator class derived from ``Kitodo\Dlf\Validation\AbstractDlfValidator``

   - :field:                    configuration
     :description:              Specific configuration of validator


XmlSchemasValidator
--------------------------

``Kitodo\Dlf\Validation\XmlSchemasValidator`` combines the configured schemes into one schema and validates the provided DOMDocument against this.

The configuration is an array validator configurations each with following entries:

.. t3-field-list-table::
   :header-rows: 1

   - :field:                    Key
     :description:              Description

   - :field:                    namespace
     :description:              Specifies the URI of the namespace to import

   - :field:                    schemaLocation
     :description:              Specifies the URI to the schema for the imported namespace


SaxonXslToSvrlValidator
--------------------------

``Kitodo\Dlf\Validation\SaxonXslToSvrlValidator`` validates the DOMDocument against an XSL Schematron and converts error output to validation errors.

To use the validator, the XSL Schematron must be available alongside the XSL processor as a JAR file, and the required Java version of the processor must be installed.

.. t3-field-list-table::
   :header-rows: 1

   - :field:                    Key
     :description:              Description

   - :field:                    jar
     :description:              Absolute path to the Saxon JAR file

   - :field:                    xsl
     :description:              Absolute path to the XSL Schematron


.. _DOMDocumentValidation Middleware:

DOMDocumentValidation Middleware
=======

``Kitodo\Dlf\Validation\DOMDocumentValidation`` middleware must be used by setting the ``middleware`` parameter to ``dlf/domDocumentValidation``. Additionally, the ``url`` parameter must contain the URL of the DOMDocument content to be validated, and the ``type`` parameter must specify the corresponding validation configuration type.

.. _DOMDocumentValidation Middleware Configuration:

Configuration
--------------------------

The validation middleware can be configured through the plugin settings in TypoScript with the block called ``domDocumentValidation``. Under this block, configuration sections (referred to as type) for different validations can be defined. When directly referencing the middleware or using the :ref:`Plugin Validation Form`, this type must be provided as the ``type`` parameter.

   .. code-block::

      plugin.tx_dlf {
          settings {
              domDocumentValidation {
                  typeA {
                     validator {
                        ...
                     },
                     validatorStack {
                        ...
                     },
                     ...
                  },
                  typeB {
                     ...
                  },
                  ...



Validators derived from ``Kitodo\Dlf\Validation\AbstractDlfValidator`` can be configured here. This also includes the use of validation stack implementations derived from ``Kitodo\Dlf\Validation\AbstractDlfValidationStack``, which use ``DOMDocument`` as the ``valueClassName`` for validation. This allows for multiple levels of nesting.

In the background of the middleware, the ``Kitodo\Dlf\Validation\DOMDocumentValidationStack`` is used, to which the configured validators are assigned.

For each validator, the title and description can be defined as XLF labels and are returned in the response according to the selected site language.

The description label can include placeholders. The syntax changes slightly in this case: a separate block is used for the description, consisting of the XLF label as the key and an additional block containing arguments.
These arguments are then inserted into the placeholders within the label in the given order. The ``EXT:`` prefix can also be used as an argument value, which will be replaced with the corresponding extension path.

   .. code-block::

      plugin.tx_dlf {
          settings {
             domDocumentValidation {
                 typeA {
                    10 {
                       title = LLL:EXT:.../locallang.xlf:title
                       description = LLL:EXT:.../locallang.xlf:description
                    }
                    ...
                 },
                 typeB {
                      10 {
                          title = LLL:EXT:.../locallang.xlf:title
                          description {
                              key = LLL:EXT:.../locallang.xlf:description
                              arguments {
                                  0 = EXT:...
                                  1 = Test
                                  ...
                              }
                          }
                          ...

TypoScript Example
^^^^^^^^^^^^^^^^^^^^^^^^^

   .. code-block::

      plugin.tx_dlf {
          settings {
              storagePid = {$config.storagePid}
              domDocumentValidation {
                  dfgviewer {
                       10 {
                           title = LLL:EXT:dfgviewer/Resources/Private/Language/locallang_validation.xlf:validator.xmlschemas.title
                           description {
                               key = LLL:EXT:dfgviewer/Resources/Private/Language/locallang_validation.xlf:validator.xmlschemas.description
                               arguments {
                                   0 = EXT:dfgviewer/Resources/Public/Xsd/Mets/1.12.1.xsd
                                   1 = METS 1.12.1
                                   2 = EXT:dfgviewer/Resources/Public/Xsd/Mods/3.8.xsd
                                   3 = MODS 3.8
                               }
                           }
                           className = Kitodo\Dlf\Validation\XmlSchemasValidator
                           configuration {
                              mets {
                                  namespace = http://www.loc.gov/METS/
                                  schemaLocation = EXT:dfgviewer/Resources/Public/Xsd/Mets/1.12.1.xsd
                              }
                              mods {
                                  namespace = http://www.loc.gov/mods/v3
                                  schemaLocation = EXT:dfgviewer/Resources/Public/Xsd/Mods/3.8.xsd
                              }
                          }
                       },
                       ...
                  }
                  ...
