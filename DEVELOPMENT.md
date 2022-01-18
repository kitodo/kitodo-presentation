# Development

## Kitodo.Presentation 4.0

- In Extbase, there is a default naming scheme to map model names to database
  table names. As we currently don't use these for historic reasons, the mapping
  needs to be reconfigured:

  - [ext_typoscript_setup.txt](ext_typoscript_setup.txt) is for compatibility
    with TYPO3 v9.
  - [Classes.php](Configuration/Extbase/Persistence/Classes.php) is for TYPO3
    v10 onwards.
  - `polyfillExtbaseClassesForTYPO3v9` (defined in [Helper.php](Classes/Common/Helper.php))
    is used for TYPO3 v9 compatibility with the expression language function
    `getDocumentType()` ([DocumentTypeFunctionProvider.php](Classes/ExpressionLanguage/DocumentTypeFunctionProvider.php)).

  To simplify this, we may consider to rename database tables according to the
  default naming scheme.
