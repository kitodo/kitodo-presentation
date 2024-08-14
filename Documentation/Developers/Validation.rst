===============
Validation
===============

.. contents::
    :local:
    :depth: 2

Validators
=======

XML-Schemes Validator
--------------------------



Saxon Validator
--------------------------

Configure Validation Stack
=======



Example
=======

.. code-block::

plugin.tx_dlf {
    settings {
        storagePid = {$config.storagePid}
        validationStacks {
            mets {
                10 {
                    title = XML Schemes Validator
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
                        dfgviewer {
                            namespace = http://dfg-viewer.de/
                            schemaLocation = /var/www/html/public/typo3conf/ext/dfgviewer/Resources/Public/Xsd/dfg-viewer.xsd
                        }
                    }
                }
                20 {
                    title = DDB-METS/MODS XSL Validator
                    className = Kitodo\Dlf\Validation\SaxonValidator
                    breakOnError = false
                    configuration {
                        jar = /var/www/html/public/typo3conf/ext/dlf/Resources/Private/Saxon/saxon-he-10.6.jar
                        xsl = /var/www/html/public/typo3conf/ext/dlf/Resources/Private/Saxon/ddb_validierung_mets-mods-ap-digitalisierte-medien.xsl
                    }
                }
            }
        }
    }
}
