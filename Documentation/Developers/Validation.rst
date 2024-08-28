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

   - :field:                    Key
     :description:              Description

   - :field:                    title
     :description:              Title of the validator

   - :field:                    className
     :description:              Fully qualified class name of validator class derived from ``Kitodo\Dlf\Validation\AbstractDlfValidator``

   - :field:                    breakOnError
     :description:              Indicates whether the validation of the validation stack should be interrupted in case of errors.

   - :field:                    configuration
     :description:              Specific configuration of validator


XmlSchemesValidator
--------------------------

``Kitodo\Dlf\Validation\XmlSchemesValidator`` combines the configured schemes into one schema and validates the provided DOMDocument against this.

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

Middleware
=======

Configuration
--------------------------

The validation middleware can be configured through the plugin settings in TypoScript with the block called ``validation``.

   .. code-block::

      plugin.tx_dlf {
          settings {
              validation {
                  KEY {
                     ...
                  },
                  ...


The ``KEY`` is used in the validation middleware for identifying the validation configuration through the ``validation`` parameter.

   .. code-block::

      plugin.tx_dlf {
          settings {
              validation {
                  // ?middleware=dlf/validation&validation=specificValidatorKey&url=...
                  specificValidatorKey {
                     className = Kitodo\Dlf\Validation\XmlSchemesValidator
                     configuration {
                            ...
                     }
                  },
                  // ?middleware=dlf/validation&validation=specificValidationStackKey&url=...
                  specificValidationStackKey {
                     className = Kitodo\Dlf\Validation\DOMDocumentValidationStack
                     validators {
                           10 {
                              ...
                           },
                           ...
                     }
                  },
                  ...

Following fields are necessary for binding validator or validation stack to the ``KEY``.

.. t3-field-list-table::
   :header-rows: 1

   - :field:                    Key
     :description:              Description

   - :field:                    className
     :description:              Fully qualified class name of validator class derived from ``Kitodo\Dlf\Validation\AbstractDlfValidator`` or of validation stack class derived from ``Kitodo\Dlf\Validation\AbstractDlfValidationStack``

   - :field:                    configuration
     :description:              Block of specific configuration of validator. (Only for validator class derived from ``Kitodo\Dlf\Validation\AbstractDlfValidator``)

   - :field:                    validators
     :description:              Blocks of validators or nested validation stacks. (Only for validation stack class derived from ``Kitodo\Dlf\Validation\AbstractDlfValidationStack``)


TypoScript Example
--------------------------

   .. code-block::

      plugin.tx_dlf {
          settings {
              storagePid = {$config.storagePid}
              validation {
                  mets {
                      className = Kitodo\Dlf\Validation\DOMDocumentValidationStack
                      validators {
                          10 {
                              title = XML-Schemes Validator
                              className = Kitodo\Dlf\Validation\XmlSchemesValidator
                              breakOnError = false
                              configuration {
                                  oai {
                                      namespace = http://www.openarchives.org/OAI/2.0/
                                      schemaLocation = https://www.openarchives.org/OAI/2.0/OAI-PMH.xsd
                                  }
                                  mets {
                                      namespace = http://www.loc.gov/METS/
                                      schemaLocation = http://www.loc.gov/standards/mets/mets.xsd
                                  }
                                  mods {
                                      namespace = http://www.loc.gov/mods/v3
                                      schemaLocation = http://www.loc.gov/standards/mods/mods.xsd
                                  }
                              }
                          }
                     }
                  }
