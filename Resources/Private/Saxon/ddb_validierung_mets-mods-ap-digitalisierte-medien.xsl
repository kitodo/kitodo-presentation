<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<xsl:stylesheet xmlns:dv="http://dfg-viewer.de/"
                xmlns:iso="http://purl.oclc.org/dsdl/schematron"
                xmlns:maps="dcg:maps"
                xmlns:mets="http://www.loc.gov/METS/"
                xmlns:mods="http://www.loc.gov/mods/v3"
                xmlns:oai="http://www.openarchives.org/OAI/2.0/"
                xmlns:saxon="http://saxon.sf.net/"
                xmlns:schold="http://www.ascc.net/xml/schematron"
                xmlns:xhtml="http://www.w3.org/1999/xhtml"
                xmlns:xlink="http://www.w3.org/1999/xlink"
                xmlns:xs="http://www.w3.org/2001/XMLSchema"
                xmlns:xsd="http://www.w3.org/2001/XMLSchema"
                xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"
                xmlns:xsl="http://www.w3.org/1999/XSL/Transform"
                version="2.0"><!--Implementers: please note that overriding process-prolog or process-root is 
    the preferred method for meta-stylesheets to use where possible. -->
   <xsl:param name="archiveDirParameter"/>
   <xsl:param name="archiveNameParameter"/>
   <xsl:param name="fileNameParameter"/>
   <xsl:param name="fileDirParameter"/>
   <xsl:variable name="document-uri">
      <xsl:value-of select="document-uri(/)"/>
   </xsl:variable>
   <!--PHASES-->
   <!--PROLOG-->
   <xsl:output xmlns:svrl="http://purl.oclc.org/dsdl/svrl"
               method="xml"
               omit-xml-declaration="no"
               standalone="yes"
               indent="yes"/>
   <!--XSD TYPES FOR XSLT2-->
   <!--KEYS AND FUNCTIONS-->
   <xsl:key name="mets_ids" match="mets:*[@ID]" use="@ID"/>
   <xsl:key name="dmdsec_ids" match="mets:dmdSec" use="@ID"/>
   <xsl:key name="amdsec_ids" match="mets:amdSec" use="@ID"/>
   <xsl:key name="fileGrp_DEFAULT_file_ids"
            match="mets:fileGrp[@USE='DEFAULT']/mets:file[@ID]"
            use="@ID"/>
   <xsl:key name="structMap_PHYSICAL_ids"
            match="mets:structMap[@TYPE='PHYSICAL']//mets:div"
            use="@ID"/>
   <xsl:key name="structMap_PHYSICAL_fptr_FILEID"
            match="mets:structMap[@TYPE='PHYSICAL']//mets:fptr"
            use="@FILEID"/>
   <xsl:key name="structMap_LOGICAL_dmdids"
            match="mets:structMap[@TYPE='LOGICAL']//mets:div[@DMDID]"
            use="tokenize(@DMDID, ' ')"/>
   <xsl:key name="structMap_LOGICAL_admids"
            match="mets:structMap[@TYPE='LOGICAL']//mets:div[@ADMID]"
            use="tokenize(@ADMID, ' ')"/>
   <xsl:key name="structLink_from_ids"
            match="mets:structLink/mets:smLink"
            use="@xlink:from"/>
   <xsl:key name="structLink_to_ids"
            match="mets:structLink/mets:smLink"
            use="@xlink:to"/>
   <xsl:key name="license_uris"
            match="maps:license_uris/maps:license_uri"
            use="text()"/>
   <xsl:key name="mets_ap_dv_license_values"
            match="maps:mets_ap_dv_license_values/maps:mets_ap_dv_license_value"
            use="text()"/>
   <xsl:key name="iso639-1_codes"
            match="maps:iso639-1_codes/maps:iso639-1_code"
            use="text()"/>
   <xsl:key name="iso639-2_codes"
            match="maps:iso639-2_codes/maps:iso639-2_code"
            use="text()"/>
   <xsl:key name="marc_relator_codes"
            match="maps:marc_relator_codes/maps:marc_relator_code"
            use="text()"/>
   <!--DEFAULT RULES-->
   <!--MODE: SCHEMATRON-SELECT-FULL-PATH-->
   <!--This mode can be used to generate an ugly though full XPath for locators-->
   <xsl:template match="*" mode="schematron-select-full-path">
      <xsl:apply-templates select="." mode="schematron-get-full-path"/>
   </xsl:template>
   <!--MODE: SCHEMATRON-FULL-PATH-->
   <!--This mode can be used to generate an ugly though full XPath for locators-->
   <xsl:template match="*" mode="schematron-get-full-path">
      <xsl:apply-templates select="parent::*" mode="schematron-get-full-path"/>
      <xsl:text>/</xsl:text>
      <xsl:choose>
         <xsl:when test="namespace-uri()=''">
            <xsl:value-of select="name()"/>
         </xsl:when>
         <xsl:otherwise>
            <xsl:text>*:</xsl:text>
            <xsl:value-of select="local-name()"/>
            <xsl:text>[namespace-uri()='</xsl:text>
            <xsl:value-of select="namespace-uri()"/>
            <xsl:text>']</xsl:text>
         </xsl:otherwise>
      </xsl:choose>
      <xsl:variable name="preceding"
                    select="count(preceding-sibling::*[local-name()=local-name(current())                                   and namespace-uri() = namespace-uri(current())])"/>
      <xsl:text>[</xsl:text>
      <xsl:value-of select="1+ $preceding"/>
      <xsl:text>]</xsl:text>
   </xsl:template>
   <xsl:template match="@*" mode="schematron-get-full-path">
      <xsl:apply-templates select="parent::*" mode="schematron-get-full-path"/>
      <xsl:text>/</xsl:text>
      <xsl:choose>
         <xsl:when test="namespace-uri()=''">@<xsl:value-of select="name()"/>
         </xsl:when>
         <xsl:otherwise>
            <xsl:text>@*[local-name()='</xsl:text>
            <xsl:value-of select="local-name()"/>
            <xsl:text>' and namespace-uri()='</xsl:text>
            <xsl:value-of select="namespace-uri()"/>
            <xsl:text>']</xsl:text>
         </xsl:otherwise>
      </xsl:choose>
   </xsl:template>
   <!--MODE: SCHEMATRON-FULL-PATH-2-->
   <!--This mode can be used to generate prefixed XPath for humans-->
   <xsl:template match="node() | @*" mode="schematron-get-full-path-2">
      <xsl:for-each select="ancestor-or-self::*">
         <xsl:text>/</xsl:text>
         <xsl:value-of select="name(.)"/>
         <xsl:if test="preceding-sibling::*[name(.)=name(current())]">
            <xsl:text>[</xsl:text>
            <xsl:value-of select="count(preceding-sibling::*[name(.)=name(current())])+1"/>
            <xsl:text>]</xsl:text>
         </xsl:if>
      </xsl:for-each>
      <xsl:if test="not(self::*)">
         <xsl:text/>/@<xsl:value-of select="name(.)"/>
      </xsl:if>
   </xsl:template>
   <!--MODE: SCHEMATRON-FULL-PATH-3-->
   <!--This mode can be used to generate prefixed XPath for humans 
	(Top-level element has index)-->
   <xsl:template match="node() | @*" mode="schematron-get-full-path-3">
      <xsl:for-each select="ancestor-or-self::*">
         <xsl:text>/</xsl:text>
         <xsl:value-of select="name(.)"/>
         <xsl:if test="parent::*">
            <xsl:text>[</xsl:text>
            <xsl:value-of select="count(preceding-sibling::*[name(.)=name(current())])+1"/>
            <xsl:text>]</xsl:text>
         </xsl:if>
      </xsl:for-each>
      <xsl:if test="not(self::*)">
         <xsl:text/>/@<xsl:value-of select="name(.)"/>
      </xsl:if>
   </xsl:template>
   <!--MODE: GENERATE-ID-FROM-PATH -->
   <xsl:template match="/" mode="generate-id-from-path"/>
   <xsl:template match="text()" mode="generate-id-from-path">
      <xsl:apply-templates select="parent::*" mode="generate-id-from-path"/>
      <xsl:value-of select="concat('.text-', 1+count(preceding-sibling::text()), '-')"/>
   </xsl:template>
   <xsl:template match="comment()" mode="generate-id-from-path">
      <xsl:apply-templates select="parent::*" mode="generate-id-from-path"/>
      <xsl:value-of select="concat('.comment-', 1+count(preceding-sibling::comment()), '-')"/>
   </xsl:template>
   <xsl:template match="processing-instruction()" mode="generate-id-from-path">
      <xsl:apply-templates select="parent::*" mode="generate-id-from-path"/>
      <xsl:value-of select="concat('.processing-instruction-', 1+count(preceding-sibling::processing-instruction()), '-')"/>
   </xsl:template>
   <xsl:template match="@*" mode="generate-id-from-path">
      <xsl:apply-templates select="parent::*" mode="generate-id-from-path"/>
      <xsl:value-of select="concat('.@', name())"/>
   </xsl:template>
   <xsl:template match="*" mode="generate-id-from-path" priority="-0.5">
      <xsl:apply-templates select="parent::*" mode="generate-id-from-path"/>
      <xsl:text>.</xsl:text>
      <xsl:value-of select="concat('.',name(),'-',1+count(preceding-sibling::*[name()=name(current())]),'-')"/>
   </xsl:template>
   <!--MODE: GENERATE-ID-2 -->
   <xsl:template match="/" mode="generate-id-2">U</xsl:template>
   <xsl:template match="*" mode="generate-id-2" priority="2">
      <xsl:text>U</xsl:text>
      <xsl:number level="multiple" count="*"/>
   </xsl:template>
   <xsl:template match="node()" mode="generate-id-2">
      <xsl:text>U.</xsl:text>
      <xsl:number level="multiple" count="*"/>
      <xsl:text>n</xsl:text>
      <xsl:number count="node()"/>
   </xsl:template>
   <xsl:template match="@*" mode="generate-id-2">
      <xsl:text>U.</xsl:text>
      <xsl:number level="multiple" count="*"/>
      <xsl:text>_</xsl:text>
      <xsl:value-of select="string-length(local-name(.))"/>
      <xsl:text>_</xsl:text>
      <xsl:value-of select="translate(name(),':','.')"/>
   </xsl:template>
   <!--Strip characters-->
   <xsl:template match="text()" priority="-1"/>
   <!--SCHEMA SETUP-->
   <xsl:template match="/">
      <svrl:schematron-output xmlns:svrl="http://purl.oclc.org/dsdl/svrl"
                              title="Validierung der Fachstelle Bibliothek der Deutschen Digitalen Bibliothek für das Anwendungsprofil für die Verwendung von METS/MODS in der DDB"
                              schemaVersion="v2024-02-29T15:50:50">
         <xsl:comment>
            <xsl:value-of select="$archiveDirParameter"/>   
		 <xsl:value-of select="$archiveNameParameter"/>  
		 <xsl:value-of select="$fileNameParameter"/>  
		 <xsl:value-of select="$fileDirParameter"/>
         </xsl:comment>
         <svrl:ns-prefix-in-attribute-values uri="http://www.loc.gov/METS/" prefix="mets"/>
         <svrl:ns-prefix-in-attribute-values uri="http://www.loc.gov/mods/v3" prefix="mods"/>
         <svrl:ns-prefix-in-attribute-values uri="http://www.w3.org/1999/xlink" prefix="xlink"/>
         <svrl:ns-prefix-in-attribute-values uri="http://dfg-viewer.de/" prefix="dv"/>
         <svrl:ns-prefix-in-attribute-values uri="http://www.w3.org/2001/XMLSchema-instance" prefix="xsi"/>
         <svrl:ns-prefix-in-attribute-values uri="dcg:maps" prefix="maps"/>
         <svrl:ns-prefix-in-attribute-values uri="http://www.openarchives.org/OAI/2.0/" prefix="oai"/>
         <svrl:active-pattern>
            <xsl:attribute name="document">
               <xsl:value-of select="document-uri(/)"/>
            </xsl:attribute>
            <xsl:apply-templates/>
         </svrl:active-pattern>
         <xsl:apply-templates select="/" mode="M33"/>
         <svrl:active-pattern>
            <xsl:attribute name="document">
               <xsl:value-of select="document-uri(/)"/>
            </xsl:attribute>
            <xsl:apply-templates/>
         </svrl:active-pattern>
         <xsl:apply-templates select="/" mode="M34"/>
         <svrl:active-pattern>
            <xsl:attribute name="document">
               <xsl:value-of select="document-uri(/)"/>
            </xsl:attribute>
            <xsl:apply-templates/>
         </svrl:active-pattern>
         <xsl:apply-templates select="/" mode="M35"/>
         <svrl:active-pattern>
            <xsl:attribute name="document">
               <xsl:value-of select="document-uri(/)"/>
            </xsl:attribute>
            <xsl:apply-templates/>
         </svrl:active-pattern>
         <xsl:apply-templates select="/" mode="M36"/>
         <svrl:active-pattern>
            <xsl:attribute name="document">
               <xsl:value-of select="document-uri(/)"/>
            </xsl:attribute>
            <xsl:apply-templates/>
         </svrl:active-pattern>
         <xsl:apply-templates select="/" mode="M37"/>
         <svrl:active-pattern>
            <xsl:attribute name="document">
               <xsl:value-of select="document-uri(/)"/>
            </xsl:attribute>
            <xsl:apply-templates/>
         </svrl:active-pattern>
         <xsl:apply-templates select="/" mode="M38"/>
         <svrl:active-pattern>
            <xsl:attribute name="document">
               <xsl:value-of select="document-uri(/)"/>
            </xsl:attribute>
            <xsl:apply-templates/>
         </svrl:active-pattern>
         <xsl:apply-templates select="/" mode="M39"/>
         <svrl:active-pattern>
            <xsl:attribute name="document">
               <xsl:value-of select="document-uri(/)"/>
            </xsl:attribute>
            <xsl:apply-templates/>
         </svrl:active-pattern>
         <xsl:apply-templates select="/" mode="M40"/>
         <svrl:active-pattern>
            <xsl:attribute name="document">
               <xsl:value-of select="document-uri(/)"/>
            </xsl:attribute>
            <xsl:apply-templates/>
         </svrl:active-pattern>
         <xsl:apply-templates select="/" mode="M41"/>
         <svrl:active-pattern>
            <xsl:attribute name="document">
               <xsl:value-of select="document-uri(/)"/>
            </xsl:attribute>
            <xsl:apply-templates/>
         </svrl:active-pattern>
         <xsl:apply-templates select="/" mode="M42"/>
         <svrl:active-pattern>
            <xsl:attribute name="document">
               <xsl:value-of select="document-uri(/)"/>
            </xsl:attribute>
            <xsl:apply-templates/>
         </svrl:active-pattern>
         <xsl:apply-templates select="/" mode="M43"/>
         <svrl:active-pattern>
            <xsl:attribute name="document">
               <xsl:value-of select="document-uri(/)"/>
            </xsl:attribute>
            <xsl:apply-templates/>
         </svrl:active-pattern>
         <xsl:apply-templates select="/" mode="M44"/>
         <svrl:active-pattern>
            <xsl:attribute name="document">
               <xsl:value-of select="document-uri(/)"/>
            </xsl:attribute>
            <xsl:apply-templates/>
         </svrl:active-pattern>
         <xsl:apply-templates select="/" mode="M45"/>
         <svrl:active-pattern>
            <xsl:attribute name="document">
               <xsl:value-of select="document-uri(/)"/>
            </xsl:attribute>
            <xsl:apply-templates/>
         </svrl:active-pattern>
         <xsl:apply-templates select="/" mode="M46"/>
         <svrl:active-pattern>
            <xsl:attribute name="document">
               <xsl:value-of select="document-uri(/)"/>
            </xsl:attribute>
            <xsl:apply-templates/>
         </svrl:active-pattern>
         <xsl:apply-templates select="/" mode="M47"/>
         <svrl:active-pattern>
            <xsl:attribute name="document">
               <xsl:value-of select="document-uri(/)"/>
            </xsl:attribute>
            <xsl:apply-templates/>
         </svrl:active-pattern>
         <xsl:apply-templates select="/" mode="M48"/>
         <svrl:active-pattern>
            <xsl:attribute name="document">
               <xsl:value-of select="document-uri(/)"/>
            </xsl:attribute>
            <xsl:apply-templates/>
         </svrl:active-pattern>
         <xsl:apply-templates select="/" mode="M49"/>
         <svrl:active-pattern>
            <xsl:attribute name="document">
               <xsl:value-of select="document-uri(/)"/>
            </xsl:attribute>
            <xsl:apply-templates/>
         </svrl:active-pattern>
         <xsl:apply-templates select="/" mode="M50"/>
         <svrl:active-pattern>
            <xsl:attribute name="document">
               <xsl:value-of select="document-uri(/)"/>
            </xsl:attribute>
            <xsl:apply-templates/>
         </svrl:active-pattern>
         <xsl:apply-templates select="/" mode="M51"/>
         <svrl:active-pattern>
            <xsl:attribute name="document">
               <xsl:value-of select="document-uri(/)"/>
            </xsl:attribute>
            <xsl:apply-templates/>
         </svrl:active-pattern>
         <xsl:apply-templates select="/" mode="M52"/>
         <svrl:active-pattern>
            <xsl:attribute name="document">
               <xsl:value-of select="document-uri(/)"/>
            </xsl:attribute>
            <xsl:apply-templates/>
         </svrl:active-pattern>
         <xsl:apply-templates select="/" mode="M53"/>
         <svrl:active-pattern>
            <xsl:attribute name="document">
               <xsl:value-of select="document-uri(/)"/>
            </xsl:attribute>
            <xsl:apply-templates/>
         </svrl:active-pattern>
         <xsl:apply-templates select="/" mode="M54"/>
         <svrl:active-pattern>
            <xsl:attribute name="document">
               <xsl:value-of select="document-uri(/)"/>
            </xsl:attribute>
            <xsl:apply-templates/>
         </svrl:active-pattern>
         <xsl:apply-templates select="/" mode="M55"/>
         <svrl:active-pattern>
            <xsl:attribute name="document">
               <xsl:value-of select="document-uri(/)"/>
            </xsl:attribute>
            <xsl:apply-templates/>
         </svrl:active-pattern>
         <xsl:apply-templates select="/" mode="M56"/>
         <svrl:active-pattern>
            <xsl:attribute name="document">
               <xsl:value-of select="document-uri(/)"/>
            </xsl:attribute>
            <xsl:apply-templates/>
         </svrl:active-pattern>
         <xsl:apply-templates select="/" mode="M57"/>
         <svrl:active-pattern>
            <xsl:attribute name="document">
               <xsl:value-of select="document-uri(/)"/>
            </xsl:attribute>
            <xsl:apply-templates/>
         </svrl:active-pattern>
         <xsl:apply-templates select="/" mode="M58"/>
         <svrl:active-pattern>
            <xsl:attribute name="document">
               <xsl:value-of select="document-uri(/)"/>
            </xsl:attribute>
            <xsl:apply-templates/>
         </svrl:active-pattern>
         <xsl:apply-templates select="/" mode="M59"/>
         <svrl:active-pattern>
            <xsl:attribute name="document">
               <xsl:value-of select="document-uri(/)"/>
            </xsl:attribute>
            <xsl:apply-templates/>
         </svrl:active-pattern>
         <xsl:apply-templates select="/" mode="M60"/>
         <svrl:active-pattern>
            <xsl:attribute name="document">
               <xsl:value-of select="document-uri(/)"/>
            </xsl:attribute>
            <xsl:apply-templates/>
         </svrl:active-pattern>
         <xsl:apply-templates select="/" mode="M61"/>
         <svrl:active-pattern>
            <xsl:attribute name="document">
               <xsl:value-of select="document-uri(/)"/>
            </xsl:attribute>
            <xsl:apply-templates/>
         </svrl:active-pattern>
         <xsl:apply-templates select="/" mode="M62"/>
         <svrl:active-pattern>
            <xsl:attribute name="document">
               <xsl:value-of select="document-uri(/)"/>
            </xsl:attribute>
            <xsl:apply-templates/>
         </svrl:active-pattern>
         <xsl:apply-templates select="/" mode="M63"/>
         <svrl:active-pattern>
            <xsl:attribute name="document">
               <xsl:value-of select="document-uri(/)"/>
            </xsl:attribute>
            <xsl:apply-templates/>
         </svrl:active-pattern>
         <xsl:apply-templates select="/" mode="M64"/>
         <svrl:active-pattern>
            <xsl:attribute name="document">
               <xsl:value-of select="document-uri(/)"/>
            </xsl:attribute>
            <xsl:apply-templates/>
         </svrl:active-pattern>
         <xsl:apply-templates select="/" mode="M65"/>
         <svrl:active-pattern>
            <xsl:attribute name="document">
               <xsl:value-of select="document-uri(/)"/>
            </xsl:attribute>
            <xsl:apply-templates/>
         </svrl:active-pattern>
         <xsl:apply-templates select="/" mode="M66"/>
         <svrl:active-pattern>
            <xsl:attribute name="document">
               <xsl:value-of select="document-uri(/)"/>
            </xsl:attribute>
            <xsl:apply-templates/>
         </svrl:active-pattern>
         <xsl:apply-templates select="/" mode="M67"/>
         <svrl:active-pattern>
            <xsl:attribute name="document">
               <xsl:value-of select="document-uri(/)"/>
            </xsl:attribute>
            <xsl:apply-templates/>
         </svrl:active-pattern>
         <xsl:apply-templates select="/" mode="M68"/>
         <svrl:active-pattern>
            <xsl:attribute name="document">
               <xsl:value-of select="document-uri(/)"/>
            </xsl:attribute>
            <xsl:apply-templates/>
         </svrl:active-pattern>
         <xsl:apply-templates select="/" mode="M69"/>
         <svrl:active-pattern>
            <xsl:attribute name="document">
               <xsl:value-of select="document-uri(/)"/>
            </xsl:attribute>
            <xsl:apply-templates/>
         </svrl:active-pattern>
         <xsl:apply-templates select="/" mode="M70"/>
         <svrl:active-pattern>
            <xsl:attribute name="document">
               <xsl:value-of select="document-uri(/)"/>
            </xsl:attribute>
            <xsl:apply-templates/>
         </svrl:active-pattern>
         <xsl:apply-templates select="/" mode="M71"/>
         <svrl:active-pattern>
            <xsl:attribute name="document">
               <xsl:value-of select="document-uri(/)"/>
            </xsl:attribute>
            <xsl:apply-templates/>
         </svrl:active-pattern>
         <xsl:apply-templates select="/" mode="M72"/>
         <svrl:active-pattern>
            <xsl:attribute name="document">
               <xsl:value-of select="document-uri(/)"/>
            </xsl:attribute>
            <xsl:apply-templates/>
         </svrl:active-pattern>
         <xsl:apply-templates select="/" mode="M73"/>
         <svrl:active-pattern>
            <xsl:attribute name="document">
               <xsl:value-of select="document-uri(/)"/>
            </xsl:attribute>
            <xsl:apply-templates/>
         </svrl:active-pattern>
         <xsl:apply-templates select="/" mode="M74"/>
         <svrl:active-pattern>
            <xsl:attribute name="document">
               <xsl:value-of select="document-uri(/)"/>
            </xsl:attribute>
            <xsl:apply-templates/>
         </svrl:active-pattern>
         <xsl:apply-templates select="/" mode="M75"/>
         <svrl:active-pattern>
            <xsl:attribute name="document">
               <xsl:value-of select="document-uri(/)"/>
            </xsl:attribute>
            <xsl:apply-templates/>
         </svrl:active-pattern>
         <xsl:apply-templates select="/" mode="M76"/>
         <svrl:active-pattern>
            <xsl:attribute name="document">
               <xsl:value-of select="document-uri(/)"/>
            </xsl:attribute>
            <xsl:apply-templates/>
         </svrl:active-pattern>
         <xsl:apply-templates select="/" mode="M77"/>
         <svrl:active-pattern>
            <xsl:attribute name="document">
               <xsl:value-of select="document-uri(/)"/>
            </xsl:attribute>
            <xsl:apply-templates/>
         </svrl:active-pattern>
         <xsl:apply-templates select="/" mode="M78"/>
         <svrl:active-pattern>
            <xsl:attribute name="document">
               <xsl:value-of select="document-uri(/)"/>
            </xsl:attribute>
            <xsl:apply-templates/>
         </svrl:active-pattern>
         <xsl:apply-templates select="/" mode="M79"/>
         <svrl:active-pattern>
            <xsl:attribute name="document">
               <xsl:value-of select="document-uri(/)"/>
            </xsl:attribute>
            <xsl:apply-templates/>
         </svrl:active-pattern>
         <xsl:apply-templates select="/" mode="M80"/>
         <svrl:active-pattern>
            <xsl:attribute name="document">
               <xsl:value-of select="document-uri(/)"/>
            </xsl:attribute>
            <xsl:apply-templates/>
         </svrl:active-pattern>
         <xsl:apply-templates select="/" mode="M81"/>
         <svrl:active-pattern>
            <xsl:attribute name="document">
               <xsl:value-of select="document-uri(/)"/>
            </xsl:attribute>
            <xsl:apply-templates/>
         </svrl:active-pattern>
         <xsl:apply-templates select="/" mode="M82"/>
         <svrl:active-pattern>
            <xsl:attribute name="document">
               <xsl:value-of select="document-uri(/)"/>
            </xsl:attribute>
            <xsl:apply-templates/>
         </svrl:active-pattern>
         <xsl:apply-templates select="/" mode="M83"/>
         <svrl:active-pattern>
            <xsl:attribute name="document">
               <xsl:value-of select="document-uri(/)"/>
            </xsl:attribute>
            <xsl:apply-templates/>
         </svrl:active-pattern>
         <xsl:apply-templates select="/" mode="M84"/>
         <svrl:active-pattern>
            <xsl:attribute name="document">
               <xsl:value-of select="document-uri(/)"/>
            </xsl:attribute>
            <xsl:apply-templates/>
         </svrl:active-pattern>
         <xsl:apply-templates select="/" mode="M85"/>
         <svrl:active-pattern>
            <xsl:attribute name="document">
               <xsl:value-of select="document-uri(/)"/>
            </xsl:attribute>
            <xsl:apply-templates/>
         </svrl:active-pattern>
         <xsl:apply-templates select="/" mode="M86"/>
         <svrl:active-pattern>
            <xsl:attribute name="document">
               <xsl:value-of select="document-uri(/)"/>
            </xsl:attribute>
            <xsl:apply-templates/>
         </svrl:active-pattern>
         <xsl:apply-templates select="/" mode="M87"/>
         <svrl:active-pattern>
            <xsl:attribute name="document">
               <xsl:value-of select="document-uri(/)"/>
            </xsl:attribute>
            <xsl:apply-templates/>
         </svrl:active-pattern>
         <xsl:apply-templates select="/" mode="M88"/>
         <svrl:active-pattern>
            <xsl:attribute name="document">
               <xsl:value-of select="document-uri(/)"/>
            </xsl:attribute>
            <xsl:apply-templates/>
         </svrl:active-pattern>
         <xsl:apply-templates select="/" mode="M89"/>
         <svrl:active-pattern>
            <xsl:attribute name="document">
               <xsl:value-of select="document-uri(/)"/>
            </xsl:attribute>
            <xsl:apply-templates/>
         </svrl:active-pattern>
         <xsl:apply-templates select="/" mode="M90"/>
         <svrl:active-pattern>
            <xsl:attribute name="document">
               <xsl:value-of select="document-uri(/)"/>
            </xsl:attribute>
            <xsl:apply-templates/>
         </svrl:active-pattern>
         <xsl:apply-templates select="/" mode="M91"/>
         <svrl:active-pattern>
            <xsl:attribute name="document">
               <xsl:value-of select="document-uri(/)"/>
            </xsl:attribute>
            <xsl:apply-templates/>
         </svrl:active-pattern>
         <xsl:apply-templates select="/" mode="M92"/>
         <svrl:active-pattern>
            <xsl:attribute name="document">
               <xsl:value-of select="document-uri(/)"/>
            </xsl:attribute>
            <xsl:apply-templates/>
         </svrl:active-pattern>
         <xsl:apply-templates select="/" mode="M93"/>
         <svrl:active-pattern>
            <xsl:attribute name="document">
               <xsl:value-of select="document-uri(/)"/>
            </xsl:attribute>
            <xsl:apply-templates/>
         </svrl:active-pattern>
         <xsl:apply-templates select="/" mode="M94"/>
         <svrl:active-pattern>
            <xsl:attribute name="document">
               <xsl:value-of select="document-uri(/)"/>
            </xsl:attribute>
            <xsl:apply-templates/>
         </svrl:active-pattern>
         <xsl:apply-templates select="/" mode="M95"/>
         <svrl:active-pattern>
            <xsl:attribute name="document">
               <xsl:value-of select="document-uri(/)"/>
            </xsl:attribute>
            <xsl:apply-templates/>
         </svrl:active-pattern>
         <xsl:apply-templates select="/" mode="M96"/>
         <svrl:active-pattern>
            <xsl:attribute name="document">
               <xsl:value-of select="document-uri(/)"/>
            </xsl:attribute>
            <xsl:apply-templates/>
         </svrl:active-pattern>
         <xsl:apply-templates select="/" mode="M97"/>
         <svrl:active-pattern>
            <xsl:attribute name="document">
               <xsl:value-of select="document-uri(/)"/>
            </xsl:attribute>
            <xsl:apply-templates/>
         </svrl:active-pattern>
         <xsl:apply-templates select="/" mode="M98"/>
         <svrl:active-pattern>
            <xsl:attribute name="document">
               <xsl:value-of select="document-uri(/)"/>
            </xsl:attribute>
            <xsl:apply-templates/>
         </svrl:active-pattern>
         <xsl:apply-templates select="/" mode="M99"/>
         <svrl:active-pattern>
            <xsl:attribute name="document">
               <xsl:value-of select="document-uri(/)"/>
            </xsl:attribute>
            <xsl:apply-templates/>
         </svrl:active-pattern>
         <xsl:apply-templates select="/" mode="M100"/>
         <svrl:active-pattern>
            <xsl:attribute name="document">
               <xsl:value-of select="document-uri(/)"/>
            </xsl:attribute>
            <xsl:apply-templates/>
         </svrl:active-pattern>
         <xsl:apply-templates select="/" mode="M101"/>
         <svrl:active-pattern>
            <xsl:attribute name="document">
               <xsl:value-of select="document-uri(/)"/>
            </xsl:attribute>
            <xsl:apply-templates/>
         </svrl:active-pattern>
         <xsl:apply-templates select="/" mode="M102"/>
         <svrl:active-pattern>
            <xsl:attribute name="document">
               <xsl:value-of select="document-uri(/)"/>
            </xsl:attribute>
            <xsl:apply-templates/>
         </svrl:active-pattern>
         <xsl:apply-templates select="/" mode="M103"/>
         <svrl:active-pattern>
            <xsl:attribute name="document">
               <xsl:value-of select="document-uri(/)"/>
            </xsl:attribute>
            <xsl:apply-templates/>
         </svrl:active-pattern>
         <xsl:apply-templates select="/" mode="M104"/>
         <svrl:active-pattern>
            <xsl:attribute name="document">
               <xsl:value-of select="document-uri(/)"/>
            </xsl:attribute>
            <xsl:apply-templates/>
         </svrl:active-pattern>
         <xsl:apply-templates select="/" mode="M105"/>
         <svrl:active-pattern>
            <xsl:attribute name="document">
               <xsl:value-of select="document-uri(/)"/>
            </xsl:attribute>
            <xsl:apply-templates/>
         </svrl:active-pattern>
         <xsl:apply-templates select="/" mode="M106"/>
         <svrl:active-pattern>
            <xsl:attribute name="document">
               <xsl:value-of select="document-uri(/)"/>
            </xsl:attribute>
            <xsl:apply-templates/>
         </svrl:active-pattern>
         <xsl:apply-templates select="/" mode="M107"/>
         <svrl:active-pattern>
            <xsl:attribute name="document">
               <xsl:value-of select="document-uri(/)"/>
            </xsl:attribute>
            <xsl:apply-templates/>
         </svrl:active-pattern>
         <xsl:apply-templates select="/" mode="M108"/>
         <svrl:active-pattern>
            <xsl:attribute name="document">
               <xsl:value-of select="document-uri(/)"/>
            </xsl:attribute>
            <xsl:apply-templates/>
         </svrl:active-pattern>
         <xsl:apply-templates select="/" mode="M109"/>
         <svrl:active-pattern>
            <xsl:attribute name="document">
               <xsl:value-of select="document-uri(/)"/>
            </xsl:attribute>
            <xsl:apply-templates/>
         </svrl:active-pattern>
         <xsl:apply-templates select="/" mode="M110"/>
         <svrl:active-pattern>
            <xsl:attribute name="document">
               <xsl:value-of select="document-uri(/)"/>
            </xsl:attribute>
            <xsl:apply-templates/>
         </svrl:active-pattern>
         <xsl:apply-templates select="/" mode="M111"/>
         <svrl:active-pattern>
            <xsl:attribute name="document">
               <xsl:value-of select="document-uri(/)"/>
            </xsl:attribute>
            <xsl:apply-templates/>
         </svrl:active-pattern>
         <xsl:apply-templates select="/" mode="M112"/>
         <svrl:active-pattern>
            <xsl:attribute name="document">
               <xsl:value-of select="document-uri(/)"/>
            </xsl:attribute>
            <xsl:apply-templates/>
         </svrl:active-pattern>
         <xsl:apply-templates select="/" mode="M113"/>
         <svrl:active-pattern>
            <xsl:attribute name="document">
               <xsl:value-of select="document-uri(/)"/>
            </xsl:attribute>
            <xsl:apply-templates/>
         </svrl:active-pattern>
         <xsl:apply-templates select="/" mode="M114"/>
         <svrl:active-pattern>
            <xsl:attribute name="document">
               <xsl:value-of select="document-uri(/)"/>
            </xsl:attribute>
            <xsl:apply-templates/>
         </svrl:active-pattern>
         <xsl:apply-templates select="/" mode="M115"/>
         <svrl:active-pattern>
            <xsl:attribute name="document">
               <xsl:value-of select="document-uri(/)"/>
            </xsl:attribute>
            <xsl:apply-templates/>
         </svrl:active-pattern>
         <xsl:apply-templates select="/" mode="M116"/>
         <svrl:active-pattern>
            <xsl:attribute name="document">
               <xsl:value-of select="document-uri(/)"/>
            </xsl:attribute>
            <xsl:apply-templates/>
         </svrl:active-pattern>
         <xsl:apply-templates select="/" mode="M117"/>
         <svrl:active-pattern>
            <xsl:attribute name="document">
               <xsl:value-of select="document-uri(/)"/>
            </xsl:attribute>
            <xsl:apply-templates/>
         </svrl:active-pattern>
         <xsl:apply-templates select="/" mode="M118"/>
         <svrl:active-pattern>
            <xsl:attribute name="document">
               <xsl:value-of select="document-uri(/)"/>
            </xsl:attribute>
            <xsl:apply-templates/>
         </svrl:active-pattern>
         <xsl:apply-templates select="/" mode="M119"/>
         <svrl:active-pattern>
            <xsl:attribute name="document">
               <xsl:value-of select="document-uri(/)"/>
            </xsl:attribute>
            <xsl:apply-templates/>
         </svrl:active-pattern>
         <xsl:apply-templates select="/" mode="M120"/>
         <svrl:active-pattern>
            <xsl:attribute name="document">
               <xsl:value-of select="document-uri(/)"/>
            </xsl:attribute>
            <xsl:apply-templates/>
         </svrl:active-pattern>
         <xsl:apply-templates select="/" mode="M121"/>
         <svrl:active-pattern>
            <xsl:attribute name="document">
               <xsl:value-of select="document-uri(/)"/>
            </xsl:attribute>
            <xsl:apply-templates/>
         </svrl:active-pattern>
         <xsl:apply-templates select="/" mode="M122"/>
         <svrl:active-pattern>
            <xsl:attribute name="document">
               <xsl:value-of select="document-uri(/)"/>
            </xsl:attribute>
            <xsl:apply-templates/>
         </svrl:active-pattern>
         <xsl:apply-templates select="/" mode="M123"/>
         <svrl:active-pattern>
            <xsl:attribute name="document">
               <xsl:value-of select="document-uri(/)"/>
            </xsl:attribute>
            <xsl:apply-templates/>
         </svrl:active-pattern>
         <xsl:apply-templates select="/" mode="M124"/>
         <svrl:active-pattern>
            <xsl:attribute name="document">
               <xsl:value-of select="document-uri(/)"/>
            </xsl:attribute>
            <xsl:apply-templates/>
         </svrl:active-pattern>
         <xsl:apply-templates select="/" mode="M125"/>
         <svrl:active-pattern>
            <xsl:attribute name="document">
               <xsl:value-of select="document-uri(/)"/>
            </xsl:attribute>
            <xsl:apply-templates/>
         </svrl:active-pattern>
         <xsl:apply-templates select="/" mode="M126"/>
         <svrl:active-pattern>
            <xsl:attribute name="document">
               <xsl:value-of select="document-uri(/)"/>
            </xsl:attribute>
            <xsl:apply-templates/>
         </svrl:active-pattern>
         <xsl:apply-templates select="/" mode="M127"/>
         <svrl:active-pattern>
            <xsl:attribute name="document">
               <xsl:value-of select="document-uri(/)"/>
            </xsl:attribute>
            <xsl:apply-templates/>
         </svrl:active-pattern>
         <xsl:apply-templates select="/" mode="M128"/>
         <svrl:active-pattern>
            <xsl:attribute name="document">
               <xsl:value-of select="document-uri(/)"/>
            </xsl:attribute>
            <xsl:apply-templates/>
         </svrl:active-pattern>
         <xsl:apply-templates select="/" mode="M129"/>
         <svrl:active-pattern>
            <xsl:attribute name="document">
               <xsl:value-of select="document-uri(/)"/>
            </xsl:attribute>
            <xsl:apply-templates/>
         </svrl:active-pattern>
         <xsl:apply-templates select="/" mode="M130"/>
         <svrl:active-pattern>
            <xsl:attribute name="document">
               <xsl:value-of select="document-uri(/)"/>
            </xsl:attribute>
            <xsl:apply-templates/>
         </svrl:active-pattern>
         <xsl:apply-templates select="/" mode="M131"/>
         <svrl:active-pattern>
            <xsl:attribute name="document">
               <xsl:value-of select="document-uri(/)"/>
            </xsl:attribute>
            <xsl:apply-templates/>
         </svrl:active-pattern>
         <xsl:apply-templates select="/" mode="M132"/>
         <svrl:active-pattern>
            <xsl:attribute name="document">
               <xsl:value-of select="document-uri(/)"/>
            </xsl:attribute>
            <xsl:apply-templates/>
         </svrl:active-pattern>
         <xsl:apply-templates select="/" mode="M133"/>
         <svrl:active-pattern>
            <xsl:attribute name="document">
               <xsl:value-of select="document-uri(/)"/>
            </xsl:attribute>
            <xsl:apply-templates/>
         </svrl:active-pattern>
         <xsl:apply-templates select="/" mode="M134"/>
         <svrl:active-pattern>
            <xsl:attribute name="document">
               <xsl:value-of select="document-uri(/)"/>
            </xsl:attribute>
            <xsl:apply-templates/>
         </svrl:active-pattern>
         <xsl:apply-templates select="/" mode="M135"/>
         <svrl:active-pattern>
            <xsl:attribute name="document">
               <xsl:value-of select="document-uri(/)"/>
            </xsl:attribute>
            <xsl:apply-templates/>
         </svrl:active-pattern>
         <xsl:apply-templates select="/" mode="M136"/>
         <svrl:active-pattern>
            <xsl:attribute name="document">
               <xsl:value-of select="document-uri(/)"/>
            </xsl:attribute>
            <xsl:apply-templates/>
         </svrl:active-pattern>
         <xsl:apply-templates select="/" mode="M137"/>
      </svrl:schematron-output>
   </xsl:template>
   <!--SCHEMATRON PATTERNS-->
   <svrl:text xmlns:svrl="http://purl.oclc.org/dsdl/svrl">Validierung der Fachstelle Bibliothek der Deutschen Digitalen Bibliothek für das Anwendungsprofil für die Verwendung von METS/MODS in der DDB</svrl:text>
   <xsl:variable name="license_uris">
      <license_uris xmlns="dcg:maps" xmlns:sch="http://purl.oclc.org/dsdl/schematron">
         <license_uri>http://creativecommons.org/publicdomain/mark/1.0/</license_uri>
         <license_uri>https://creativecommons.org/publicdomain/mark/1.0/</license_uri>
         <license_uri>http://creativecommons.org/publicdomain/zero/1.0/</license_uri>
         <license_uri>https://creativecommons.org/publicdomain/zero/1.0/</license_uri>
         <license_uri>http://creativecommons.org/licenses/by/3.0/</license_uri>
         <license_uri>https://creativecommons.org/licenses/by/3.0/</license_uri>
         <license_uri>http://creativecommons.org/licenses/by-sa/3.0/</license_uri>
         <license_uri>https://creativecommons.org/licenses/by-sa/3.0/</license_uri>
         <license_uri>http://creativecommons.org/licenses/by-nd/3.0/</license_uri>
         <license_uri>https://creativecommons.org/licenses/by-nd/3.0/</license_uri>
         <license_uri>http://creativecommons.org/licenses/by-nc/3.0/</license_uri>
         <license_uri>https://creativecommons.org/licenses/by-nc/3.0/</license_uri>
         <license_uri>http://creativecommons.org/licenses/by-nc-sa/3.0/</license_uri>
         <license_uri>https://creativecommons.org/licenses/by-nc-sa/3.0/</license_uri>
         <license_uri>http://creativecommons.org/licenses/by-nc-nd/3.0/</license_uri>
         <license_uri>https://creativecommons.org/licenses/by-nc-nd/3.0/</license_uri>
         <license_uri>http://creativecommons.org/licenses/by/3.0/de/</license_uri>
         <license_uri>https://creativecommons.org/licenses/by/3.0/de/</license_uri>
         <license_uri>http://creativecommons.org/licenses/by-sa/3.0/de/</license_uri>
         <license_uri>https://creativecommons.org/licenses/by-sa/3.0/de/</license_uri>
         <license_uri>http://creativecommons.org/licenses/by-nd/3.0/de/</license_uri>
         <license_uri>https://creativecommons.org/licenses/by-nd/3.0/de/</license_uri>
         <license_uri>http://creativecommons.org/licenses/by-nc/3.0/de/</license_uri>
         <license_uri>https://creativecommons.org/licenses/by-nc/3.0/de/</license_uri>
         <license_uri>http://creativecommons.org/licenses/by-nc-sa/3.0/de/</license_uri>
         <license_uri>https://creativecommons.org/licenses/by-nc-sa/3.0/de/</license_uri>
         <license_uri>http://creativecommons.org/licenses/by-nc-nd/3.0/de/</license_uri>
         <license_uri>https://creativecommons.org/licenses/by-nc-nd/3.0/de/</license_uri>
         <license_uri>http://creativecommons.org/licenses/by/4.0/</license_uri>
         <license_uri>https://creativecommons.org/licenses/by/4.0/</license_uri>
         <license_uri>http://creativecommons.org/licenses/by-sa/4.0/</license_uri>
         <license_uri>https://creativecommons.org/licenses/by-sa/4.0/</license_uri>
         <license_uri>http://creativecommons.org/licenses/by-nd/4.0/</license_uri>
         <license_uri>https://creativecommons.org/licenses/by-nd/4.0/</license_uri>
         <license_uri>http://creativecommons.org/licenses/by-nc/4.0/</license_uri>
         <license_uri>https://creativecommons.org/licenses/by-nc/4.0/</license_uri>
         <license_uri>http://creativecommons.org/licenses/by-nc-sa/4.0/</license_uri>
         <license_uri>https://creativecommons.org/licenses/by-nc-sa/4.0/</license_uri>
         <license_uri>http://creativecommons.org/licenses/by-nc-nd/4.0/</license_uri>
         <license_uri>https://creativecommons.org/licenses/by-nc-nd/4.0/</license_uri>
         <license_uri>http://rightsstatements.org/vocab/InC/1.0/</license_uri>
         <license_uri>https://rightsstatements.org/vocab/InC/1.0/</license_uri>
         <license_uri>http://rightsstatements.org/vocab/InC-EDU/1.0/</license_uri>
         <license_uri>https://rightsstatements.org/vocab/InC-EDU/1.0/</license_uri>
         <license_uri>http://rightsstatements.org/vocab/InC-OW-EU/1.0/</license_uri>
         <license_uri>https://rightsstatements.org/vocab/InC-OW-EU/1.0/</license_uri>
         <license_uri>http://rightsstatements.org/vocab/CNE/1.0/</license_uri>
         <license_uri>https://rightsstatements.org/vocab/CNE/1.0/</license_uri>
         <license_uri>http://rightsstatements.org/vocab/NoC-NC/1.0/</license_uri>
         <license_uri>https://rightsstatements.org/vocab/NoC-NC/1.0/</license_uri>
         <license_uri>http://rightsstatements.org/vocab/NoC-OKLR/1.0/</license_uri>
         <license_uri>https://rightsstatements.org/vocab/NoC-OKLR/1.0/</license_uri>
      </license_uris>
   </xsl:variable>
   <xsl:variable name="iso639-1_codes">
      <iso639-1_codes xmlns="dcg:maps" xmlns:sch="http://purl.oclc.org/dsdl/schematron">
         <iso639-1_code>aa</iso639-1_code>
         <iso639-1_code>ab</iso639-1_code>
         <iso639-1_code>ae</iso639-1_code>
         <iso639-1_code>af</iso639-1_code>
         <iso639-1_code>ak</iso639-1_code>
         <iso639-1_code>am</iso639-1_code>
         <iso639-1_code>an</iso639-1_code>
         <iso639-1_code>ar</iso639-1_code>
         <iso639-1_code>as</iso639-1_code>
         <iso639-1_code>av</iso639-1_code>
         <iso639-1_code>ay</iso639-1_code>
         <iso639-1_code>az</iso639-1_code>
         <iso639-1_code>ba</iso639-1_code>
         <iso639-1_code>be</iso639-1_code>
         <iso639-1_code>bg</iso639-1_code>
         <iso639-1_code>bh</iso639-1_code>
         <iso639-1_code>bi</iso639-1_code>
         <iso639-1_code>bm</iso639-1_code>
         <iso639-1_code>bn</iso639-1_code>
         <iso639-1_code>bo</iso639-1_code>
         <iso639-1_code>br</iso639-1_code>
         <iso639-1_code>bs</iso639-1_code>
         <iso639-1_code>ca</iso639-1_code>
         <iso639-1_code>ce</iso639-1_code>
         <iso639-1_code>ch</iso639-1_code>
         <iso639-1_code>co</iso639-1_code>
         <iso639-1_code>cr</iso639-1_code>
         <iso639-1_code>cs</iso639-1_code>
         <iso639-1_code>cu</iso639-1_code>
         <iso639-1_code>cv</iso639-1_code>
         <iso639-1_code>cy</iso639-1_code>
         <iso639-1_code>da</iso639-1_code>
         <iso639-1_code>de</iso639-1_code>
         <iso639-1_code>dv</iso639-1_code>
         <iso639-1_code>dz</iso639-1_code>
         <iso639-1_code>ee</iso639-1_code>
         <iso639-1_code>el</iso639-1_code>
         <iso639-1_code>en</iso639-1_code>
         <iso639-1_code>eo</iso639-1_code>
         <iso639-1_code>es</iso639-1_code>
         <iso639-1_code>et</iso639-1_code>
         <iso639-1_code>eu</iso639-1_code>
         <iso639-1_code>fa</iso639-1_code>
         <iso639-1_code>ff</iso639-1_code>
         <iso639-1_code>fi</iso639-1_code>
         <iso639-1_code>fj</iso639-1_code>
         <iso639-1_code>fo</iso639-1_code>
         <iso639-1_code>fr</iso639-1_code>
         <iso639-1_code>fy</iso639-1_code>
         <iso639-1_code>ga</iso639-1_code>
         <iso639-1_code>gd</iso639-1_code>
         <iso639-1_code>gl</iso639-1_code>
         <iso639-1_code>gn</iso639-1_code>
         <iso639-1_code>gu</iso639-1_code>
         <iso639-1_code>gv</iso639-1_code>
         <iso639-1_code>ha</iso639-1_code>
         <iso639-1_code>he</iso639-1_code>
         <iso639-1_code>hi</iso639-1_code>
         <iso639-1_code>ho</iso639-1_code>
         <iso639-1_code>hr</iso639-1_code>
         <iso639-1_code>ht</iso639-1_code>
         <iso639-1_code>hu</iso639-1_code>
         <iso639-1_code>hy</iso639-1_code>
         <iso639-1_code>hz</iso639-1_code>
         <iso639-1_code>ia</iso639-1_code>
         <iso639-1_code>id</iso639-1_code>
         <iso639-1_code>ie</iso639-1_code>
         <iso639-1_code>ig</iso639-1_code>
         <iso639-1_code>ii</iso639-1_code>
         <iso639-1_code>ik</iso639-1_code>
         <iso639-1_code>io</iso639-1_code>
         <iso639-1_code>is</iso639-1_code>
         <iso639-1_code>it</iso639-1_code>
         <iso639-1_code>iu</iso639-1_code>
         <iso639-1_code>ja</iso639-1_code>
         <iso639-1_code>jv</iso639-1_code>
         <iso639-1_code>ka</iso639-1_code>
         <iso639-1_code>kg</iso639-1_code>
         <iso639-1_code>ki</iso639-1_code>
         <iso639-1_code>kj</iso639-1_code>
         <iso639-1_code>kk</iso639-1_code>
         <iso639-1_code>kl</iso639-1_code>
         <iso639-1_code>km</iso639-1_code>
         <iso639-1_code>kn</iso639-1_code>
         <iso639-1_code>ko</iso639-1_code>
         <iso639-1_code>kr</iso639-1_code>
         <iso639-1_code>ks</iso639-1_code>
         <iso639-1_code>ku</iso639-1_code>
         <iso639-1_code>kv</iso639-1_code>
         <iso639-1_code>kw</iso639-1_code>
         <iso639-1_code>ky</iso639-1_code>
         <iso639-1_code>la</iso639-1_code>
         <iso639-1_code>lb</iso639-1_code>
         <iso639-1_code>lg</iso639-1_code>
         <iso639-1_code>li</iso639-1_code>
         <iso639-1_code>ln</iso639-1_code>
         <iso639-1_code>lo</iso639-1_code>
         <iso639-1_code>lt</iso639-1_code>
         <iso639-1_code>lu</iso639-1_code>
         <iso639-1_code>lv</iso639-1_code>
         <iso639-1_code>mg</iso639-1_code>
         <iso639-1_code>mh</iso639-1_code>
         <iso639-1_code>mi</iso639-1_code>
         <iso639-1_code>mk</iso639-1_code>
         <iso639-1_code>ml</iso639-1_code>
         <iso639-1_code>mn</iso639-1_code>
         <iso639-1_code>mr</iso639-1_code>
         <iso639-1_code>ms</iso639-1_code>
         <iso639-1_code>mt</iso639-1_code>
         <iso639-1_code>my</iso639-1_code>
         <iso639-1_code>na</iso639-1_code>
         <iso639-1_code>nb</iso639-1_code>
         <iso639-1_code>nd</iso639-1_code>
         <iso639-1_code>ne</iso639-1_code>
         <iso639-1_code>ng</iso639-1_code>
         <iso639-1_code>nl</iso639-1_code>
         <iso639-1_code>nn</iso639-1_code>
         <iso639-1_code>no</iso639-1_code>
         <iso639-1_code>nr</iso639-1_code>
         <iso639-1_code>nv</iso639-1_code>
         <iso639-1_code>ny</iso639-1_code>
         <iso639-1_code>oc</iso639-1_code>
         <iso639-1_code>oj</iso639-1_code>
         <iso639-1_code>om</iso639-1_code>
         <iso639-1_code>or</iso639-1_code>
         <iso639-1_code>os</iso639-1_code>
         <iso639-1_code>pa</iso639-1_code>
         <iso639-1_code>pi</iso639-1_code>
         <iso639-1_code>pl</iso639-1_code>
         <iso639-1_code>ps</iso639-1_code>
         <iso639-1_code>pt</iso639-1_code>
         <iso639-1_code>qu</iso639-1_code>
         <iso639-1_code>rm</iso639-1_code>
         <iso639-1_code>rn</iso639-1_code>
         <iso639-1_code>ro</iso639-1_code>
         <iso639-1_code>ru</iso639-1_code>
         <iso639-1_code>rw</iso639-1_code>
         <iso639-1_code>sa</iso639-1_code>
         <iso639-1_code>sc</iso639-1_code>
         <iso639-1_code>sd</iso639-1_code>
         <iso639-1_code>se</iso639-1_code>
         <iso639-1_code>sg</iso639-1_code>
         <iso639-1_code>si</iso639-1_code>
         <iso639-1_code>sk</iso639-1_code>
         <iso639-1_code>sl</iso639-1_code>
         <iso639-1_code>sm</iso639-1_code>
         <iso639-1_code>sn</iso639-1_code>
         <iso639-1_code>so</iso639-1_code>
         <iso639-1_code>sq</iso639-1_code>
         <iso639-1_code>sr</iso639-1_code>
         <iso639-1_code>ss</iso639-1_code>
         <iso639-1_code>st</iso639-1_code>
         <iso639-1_code>su</iso639-1_code>
         <iso639-1_code>sv</iso639-1_code>
         <iso639-1_code>sw</iso639-1_code>
         <iso639-1_code>ta</iso639-1_code>
         <iso639-1_code>te</iso639-1_code>
         <iso639-1_code>tg</iso639-1_code>
         <iso639-1_code>th</iso639-1_code>
         <iso639-1_code>ti</iso639-1_code>
         <iso639-1_code>tk</iso639-1_code>
         <iso639-1_code>tl</iso639-1_code>
         <iso639-1_code>tn</iso639-1_code>
         <iso639-1_code>to</iso639-1_code>
         <iso639-1_code>tr</iso639-1_code>
         <iso639-1_code>ts</iso639-1_code>
         <iso639-1_code>tt</iso639-1_code>
         <iso639-1_code>tw</iso639-1_code>
         <iso639-1_code>ty</iso639-1_code>
         <iso639-1_code>ug</iso639-1_code>
         <iso639-1_code>uk</iso639-1_code>
         <iso639-1_code>ur</iso639-1_code>
         <iso639-1_code>uz</iso639-1_code>
         <iso639-1_code>ve</iso639-1_code>
         <iso639-1_code>vi</iso639-1_code>
         <iso639-1_code>vo</iso639-1_code>
         <iso639-1_code>wa</iso639-1_code>
         <iso639-1_code>wo</iso639-1_code>
         <iso639-1_code>xh</iso639-1_code>
         <iso639-1_code>yi</iso639-1_code>
         <iso639-1_code>yo</iso639-1_code>
         <iso639-1_code>za</iso639-1_code>
         <iso639-1_code>zh</iso639-1_code>
         <iso639-1_code>zu</iso639-1_code>
      </iso639-1_codes>
   </xsl:variable>
   <xsl:variable name="iso639-2_codes">
      <iso639-2_codes xmlns="dcg:maps" xmlns:sch="http://purl.oclc.org/dsdl/schematron">
         <iso639-2_code>aar</iso639-2_code>
         <iso639-2_code>abk</iso639-2_code>
         <iso639-2_code>ace</iso639-2_code>
         <iso639-2_code>ach</iso639-2_code>
         <iso639-2_code>ada</iso639-2_code>
         <iso639-2_code>ady</iso639-2_code>
         <iso639-2_code>afa</iso639-2_code>
         <iso639-2_code>afh</iso639-2_code>
         <iso639-2_code>afr</iso639-2_code>
         <iso639-2_code>ain</iso639-2_code>
         <iso639-2_code>aka</iso639-2_code>
         <iso639-2_code>akk</iso639-2_code>
         <iso639-2_code>alb</iso639-2_code>
         <iso639-2_code>sqi</iso639-2_code>
         <iso639-2_code>ale</iso639-2_code>
         <iso639-2_code>alg</iso639-2_code>
         <iso639-2_code>alt</iso639-2_code>
         <iso639-2_code>amh</iso639-2_code>
         <iso639-2_code>ang</iso639-2_code>
         <iso639-2_code>anp</iso639-2_code>
         <iso639-2_code>apa</iso639-2_code>
         <iso639-2_code>ara</iso639-2_code>
         <iso639-2_code>arc</iso639-2_code>
         <iso639-2_code>arg</iso639-2_code>
         <iso639-2_code>arm</iso639-2_code>
         <iso639-2_code>hye</iso639-2_code>
         <iso639-2_code>arn</iso639-2_code>
         <iso639-2_code>arp</iso639-2_code>
         <iso639-2_code>art</iso639-2_code>
         <iso639-2_code>arw</iso639-2_code>
         <iso639-2_code>asm</iso639-2_code>
         <iso639-2_code>ast</iso639-2_code>
         <iso639-2_code>ath</iso639-2_code>
         <iso639-2_code>aus</iso639-2_code>
         <iso639-2_code>ava</iso639-2_code>
         <iso639-2_code>ave</iso639-2_code>
         <iso639-2_code>awa</iso639-2_code>
         <iso639-2_code>aym</iso639-2_code>
         <iso639-2_code>aze</iso639-2_code>
         <iso639-2_code>bad</iso639-2_code>
         <iso639-2_code>bai</iso639-2_code>
         <iso639-2_code>bak</iso639-2_code>
         <iso639-2_code>bal</iso639-2_code>
         <iso639-2_code>bam</iso639-2_code>
         <iso639-2_code>ban</iso639-2_code>
         <iso639-2_code>baq</iso639-2_code>
         <iso639-2_code>eus</iso639-2_code>
         <iso639-2_code>bas</iso639-2_code>
         <iso639-2_code>bat</iso639-2_code>
         <iso639-2_code>bej</iso639-2_code>
         <iso639-2_code>bel</iso639-2_code>
         <iso639-2_code>bem</iso639-2_code>
         <iso639-2_code>ben</iso639-2_code>
         <iso639-2_code>ber</iso639-2_code>
         <iso639-2_code>bho</iso639-2_code>
         <iso639-2_code>bih</iso639-2_code>
         <iso639-2_code>bik</iso639-2_code>
         <iso639-2_code>bin</iso639-2_code>
         <iso639-2_code>bis</iso639-2_code>
         <iso639-2_code>bla</iso639-2_code>
         <iso639-2_code>bnt</iso639-2_code>
         <iso639-2_code>tib</iso639-2_code>
         <iso639-2_code>bod</iso639-2_code>
         <iso639-2_code>bos</iso639-2_code>
         <iso639-2_code>bra</iso639-2_code>
         <iso639-2_code>bre</iso639-2_code>
         <iso639-2_code>btk</iso639-2_code>
         <iso639-2_code>bua</iso639-2_code>
         <iso639-2_code>bug</iso639-2_code>
         <iso639-2_code>bul</iso639-2_code>
         <iso639-2_code>bur</iso639-2_code>
         <iso639-2_code>mya</iso639-2_code>
         <iso639-2_code>byn</iso639-2_code>
         <iso639-2_code>cad</iso639-2_code>
         <iso639-2_code>cai</iso639-2_code>
         <iso639-2_code>car</iso639-2_code>
         <iso639-2_code>cat</iso639-2_code>
         <iso639-2_code>cau</iso639-2_code>
         <iso639-2_code>ceb</iso639-2_code>
         <iso639-2_code>cel</iso639-2_code>
         <iso639-2_code>cze</iso639-2_code>
         <iso639-2_code>ces</iso639-2_code>
         <iso639-2_code>cha</iso639-2_code>
         <iso639-2_code>chb</iso639-2_code>
         <iso639-2_code>che</iso639-2_code>
         <iso639-2_code>chg</iso639-2_code>
         <iso639-2_code>chi</iso639-2_code>
         <iso639-2_code>zho</iso639-2_code>
         <iso639-2_code>chk</iso639-2_code>
         <iso639-2_code>chm</iso639-2_code>
         <iso639-2_code>chn</iso639-2_code>
         <iso639-2_code>cho</iso639-2_code>
         <iso639-2_code>chp</iso639-2_code>
         <iso639-2_code>chr</iso639-2_code>
         <iso639-2_code>chu</iso639-2_code>
         <iso639-2_code>chv</iso639-2_code>
         <iso639-2_code>chy</iso639-2_code>
         <iso639-2_code>cmc</iso639-2_code>
         <iso639-2_code>cnr</iso639-2_code>
         <iso639-2_code>cop</iso639-2_code>
         <iso639-2_code>cor</iso639-2_code>
         <iso639-2_code>cos</iso639-2_code>
         <iso639-2_code>cpe</iso639-2_code>
         <iso639-2_code>cpf</iso639-2_code>
         <iso639-2_code>cpp</iso639-2_code>
         <iso639-2_code>cre</iso639-2_code>
         <iso639-2_code>crh</iso639-2_code>
         <iso639-2_code>crp</iso639-2_code>
         <iso639-2_code>csb</iso639-2_code>
         <iso639-2_code>cus</iso639-2_code>
         <iso639-2_code>wel</iso639-2_code>
         <iso639-2_code>cym</iso639-2_code>
         <iso639-2_code>dak</iso639-2_code>
         <iso639-2_code>dan</iso639-2_code>
         <iso639-2_code>dar</iso639-2_code>
         <iso639-2_code>day</iso639-2_code>
         <iso639-2_code>del</iso639-2_code>
         <iso639-2_code>den</iso639-2_code>
         <iso639-2_code>ger</iso639-2_code>
         <iso639-2_code>deu</iso639-2_code>
         <iso639-2_code>dgr</iso639-2_code>
         <iso639-2_code>din</iso639-2_code>
         <iso639-2_code>div</iso639-2_code>
         <iso639-2_code>doi</iso639-2_code>
         <iso639-2_code>dra</iso639-2_code>
         <iso639-2_code>dsb</iso639-2_code>
         <iso639-2_code>dua</iso639-2_code>
         <iso639-2_code>dum</iso639-2_code>
         <iso639-2_code>dut</iso639-2_code>
         <iso639-2_code>nld</iso639-2_code>
         <iso639-2_code>dyu</iso639-2_code>
         <iso639-2_code>dzo</iso639-2_code>
         <iso639-2_code>efi</iso639-2_code>
         <iso639-2_code>egy</iso639-2_code>
         <iso639-2_code>eka</iso639-2_code>
         <iso639-2_code>gre</iso639-2_code>
         <iso639-2_code>ell</iso639-2_code>
         <iso639-2_code>elx</iso639-2_code>
         <iso639-2_code>eng</iso639-2_code>
         <iso639-2_code>enm</iso639-2_code>
         <iso639-2_code>epo</iso639-2_code>
         <iso639-2_code>est</iso639-2_code>
         <iso639-2_code>ewe</iso639-2_code>
         <iso639-2_code>ewo</iso639-2_code>
         <iso639-2_code>fan</iso639-2_code>
         <iso639-2_code>fao</iso639-2_code>
         <iso639-2_code>per</iso639-2_code>
         <iso639-2_code>fas</iso639-2_code>
         <iso639-2_code>fat</iso639-2_code>
         <iso639-2_code>fij</iso639-2_code>
         <iso639-2_code>fil</iso639-2_code>
         <iso639-2_code>fin</iso639-2_code>
         <iso639-2_code>fiu</iso639-2_code>
         <iso639-2_code>fon</iso639-2_code>
         <iso639-2_code>fre</iso639-2_code>
         <iso639-2_code>fra</iso639-2_code>
         <iso639-2_code>frm</iso639-2_code>
         <iso639-2_code>fro</iso639-2_code>
         <iso639-2_code>frr</iso639-2_code>
         <iso639-2_code>frs</iso639-2_code>
         <iso639-2_code>fry</iso639-2_code>
         <iso639-2_code>ful</iso639-2_code>
         <iso639-2_code>fur</iso639-2_code>
         <iso639-2_code>gaa</iso639-2_code>
         <iso639-2_code>gay</iso639-2_code>
         <iso639-2_code>gba</iso639-2_code>
         <iso639-2_code>gem</iso639-2_code>
         <iso639-2_code>geo</iso639-2_code>
         <iso639-2_code>kat</iso639-2_code>
         <iso639-2_code>gez</iso639-2_code>
         <iso639-2_code>gil</iso639-2_code>
         <iso639-2_code>gla</iso639-2_code>
         <iso639-2_code>gle</iso639-2_code>
         <iso639-2_code>glg</iso639-2_code>
         <iso639-2_code>glv</iso639-2_code>
         <iso639-2_code>gmh</iso639-2_code>
         <iso639-2_code>goh</iso639-2_code>
         <iso639-2_code>gon</iso639-2_code>
         <iso639-2_code>gor</iso639-2_code>
         <iso639-2_code>got</iso639-2_code>
         <iso639-2_code>grb</iso639-2_code>
         <iso639-2_code>grc</iso639-2_code>
         <iso639-2_code>grn</iso639-2_code>
         <iso639-2_code>gsw</iso639-2_code>
         <iso639-2_code>guj</iso639-2_code>
         <iso639-2_code>gwi</iso639-2_code>
         <iso639-2_code>hai</iso639-2_code>
         <iso639-2_code>hat</iso639-2_code>
         <iso639-2_code>hau</iso639-2_code>
         <iso639-2_code>haw</iso639-2_code>
         <iso639-2_code>heb</iso639-2_code>
         <iso639-2_code>her</iso639-2_code>
         <iso639-2_code>hil</iso639-2_code>
         <iso639-2_code>him</iso639-2_code>
         <iso639-2_code>hin</iso639-2_code>
         <iso639-2_code>hit</iso639-2_code>
         <iso639-2_code>hmn</iso639-2_code>
         <iso639-2_code>hmo</iso639-2_code>
         <iso639-2_code>hrv</iso639-2_code>
         <iso639-2_code>hsb</iso639-2_code>
         <iso639-2_code>hun</iso639-2_code>
         <iso639-2_code>hup</iso639-2_code>
         <iso639-2_code>iba</iso639-2_code>
         <iso639-2_code>ibo</iso639-2_code>
         <iso639-2_code>ice</iso639-2_code>
         <iso639-2_code>isl</iso639-2_code>
         <iso639-2_code>ido</iso639-2_code>
         <iso639-2_code>iii</iso639-2_code>
         <iso639-2_code>ijo</iso639-2_code>
         <iso639-2_code>iku</iso639-2_code>
         <iso639-2_code>ile</iso639-2_code>
         <iso639-2_code>ilo</iso639-2_code>
         <iso639-2_code>ina</iso639-2_code>
         <iso639-2_code>inc</iso639-2_code>
         <iso639-2_code>ind</iso639-2_code>
         <iso639-2_code>ine</iso639-2_code>
         <iso639-2_code>inh</iso639-2_code>
         <iso639-2_code>ipk</iso639-2_code>
         <iso639-2_code>ira</iso639-2_code>
         <iso639-2_code>iro</iso639-2_code>
         <iso639-2_code>ita</iso639-2_code>
         <iso639-2_code>jav</iso639-2_code>
         <iso639-2_code>jbo</iso639-2_code>
         <iso639-2_code>jpn</iso639-2_code>
         <iso639-2_code>jpr</iso639-2_code>
         <iso639-2_code>jrb</iso639-2_code>
         <iso639-2_code>kaa</iso639-2_code>
         <iso639-2_code>kab</iso639-2_code>
         <iso639-2_code>kac</iso639-2_code>
         <iso639-2_code>kal</iso639-2_code>
         <iso639-2_code>kam</iso639-2_code>
         <iso639-2_code>kan</iso639-2_code>
         <iso639-2_code>kar</iso639-2_code>
         <iso639-2_code>kas</iso639-2_code>
         <iso639-2_code>kau</iso639-2_code>
         <iso639-2_code>kaw</iso639-2_code>
         <iso639-2_code>kaz</iso639-2_code>
         <iso639-2_code>kbd</iso639-2_code>
         <iso639-2_code>kha</iso639-2_code>
         <iso639-2_code>khi</iso639-2_code>
         <iso639-2_code>khm</iso639-2_code>
         <iso639-2_code>kho</iso639-2_code>
         <iso639-2_code>kik</iso639-2_code>
         <iso639-2_code>kin</iso639-2_code>
         <iso639-2_code>kir</iso639-2_code>
         <iso639-2_code>kmb</iso639-2_code>
         <iso639-2_code>kok</iso639-2_code>
         <iso639-2_code>kom</iso639-2_code>
         <iso639-2_code>kon</iso639-2_code>
         <iso639-2_code>kor</iso639-2_code>
         <iso639-2_code>kos</iso639-2_code>
         <iso639-2_code>kpe</iso639-2_code>
         <iso639-2_code>krc</iso639-2_code>
         <iso639-2_code>krl</iso639-2_code>
         <iso639-2_code>kro</iso639-2_code>
         <iso639-2_code>kru</iso639-2_code>
         <iso639-2_code>kua</iso639-2_code>
         <iso639-2_code>kum</iso639-2_code>
         <iso639-2_code>kur</iso639-2_code>
         <iso639-2_code>kut</iso639-2_code>
         <iso639-2_code>lad</iso639-2_code>
         <iso639-2_code>lah</iso639-2_code>
         <iso639-2_code>lam</iso639-2_code>
         <iso639-2_code>lao</iso639-2_code>
         <iso639-2_code>lat</iso639-2_code>
         <iso639-2_code>lav</iso639-2_code>
         <iso639-2_code>lez</iso639-2_code>
         <iso639-2_code>lim</iso639-2_code>
         <iso639-2_code>lin</iso639-2_code>
         <iso639-2_code>lit</iso639-2_code>
         <iso639-2_code>lol</iso639-2_code>
         <iso639-2_code>loz</iso639-2_code>
         <iso639-2_code>ltz</iso639-2_code>
         <iso639-2_code>lua</iso639-2_code>
         <iso639-2_code>lub</iso639-2_code>
         <iso639-2_code>lug</iso639-2_code>
         <iso639-2_code>lui</iso639-2_code>
         <iso639-2_code>lun</iso639-2_code>
         <iso639-2_code>luo</iso639-2_code>
         <iso639-2_code>lus</iso639-2_code>
         <iso639-2_code>mac</iso639-2_code>
         <iso639-2_code>mkd</iso639-2_code>
         <iso639-2_code>mad</iso639-2_code>
         <iso639-2_code>mag</iso639-2_code>
         <iso639-2_code>mah</iso639-2_code>
         <iso639-2_code>mai</iso639-2_code>
         <iso639-2_code>mak</iso639-2_code>
         <iso639-2_code>mal</iso639-2_code>
         <iso639-2_code>man</iso639-2_code>
         <iso639-2_code>mao</iso639-2_code>
         <iso639-2_code>mri</iso639-2_code>
         <iso639-2_code>map</iso639-2_code>
         <iso639-2_code>mar</iso639-2_code>
         <iso639-2_code>mas</iso639-2_code>
         <iso639-2_code>may</iso639-2_code>
         <iso639-2_code>msa</iso639-2_code>
         <iso639-2_code>mdf</iso639-2_code>
         <iso639-2_code>mdr</iso639-2_code>
         <iso639-2_code>men</iso639-2_code>
         <iso639-2_code>mga</iso639-2_code>
         <iso639-2_code>mic</iso639-2_code>
         <iso639-2_code>min</iso639-2_code>
         <iso639-2_code>mis</iso639-2_code>
         <iso639-2_code>mkh</iso639-2_code>
         <iso639-2_code>mlg</iso639-2_code>
         <iso639-2_code>mlt</iso639-2_code>
         <iso639-2_code>mnc</iso639-2_code>
         <iso639-2_code>mni</iso639-2_code>
         <iso639-2_code>mno</iso639-2_code>
         <iso639-2_code>moh</iso639-2_code>
         <iso639-2_code>mon</iso639-2_code>
         <iso639-2_code>mos</iso639-2_code>
         <iso639-2_code>mul</iso639-2_code>
         <iso639-2_code>mun</iso639-2_code>
         <iso639-2_code>mus</iso639-2_code>
         <iso639-2_code>mwl</iso639-2_code>
         <iso639-2_code>mwr</iso639-2_code>
         <iso639-2_code>myn</iso639-2_code>
         <iso639-2_code>myv</iso639-2_code>
         <iso639-2_code>nah</iso639-2_code>
         <iso639-2_code>nai</iso639-2_code>
         <iso639-2_code>nap</iso639-2_code>
         <iso639-2_code>nau</iso639-2_code>
         <iso639-2_code>nav</iso639-2_code>
         <iso639-2_code>nbl</iso639-2_code>
         <iso639-2_code>nde</iso639-2_code>
         <iso639-2_code>ndo</iso639-2_code>
         <iso639-2_code>nds</iso639-2_code>
         <iso639-2_code>nep</iso639-2_code>
         <iso639-2_code>new</iso639-2_code>
         <iso639-2_code>nia</iso639-2_code>
         <iso639-2_code>nic</iso639-2_code>
         <iso639-2_code>niu</iso639-2_code>
         <iso639-2_code>nno</iso639-2_code>
         <iso639-2_code>nob</iso639-2_code>
         <iso639-2_code>nog</iso639-2_code>
         <iso639-2_code>non</iso639-2_code>
         <iso639-2_code>nor</iso639-2_code>
         <iso639-2_code>nqo</iso639-2_code>
         <iso639-2_code>nso</iso639-2_code>
         <iso639-2_code>nub</iso639-2_code>
         <iso639-2_code>nwc</iso639-2_code>
         <iso639-2_code>nya</iso639-2_code>
         <iso639-2_code>nym</iso639-2_code>
         <iso639-2_code>nyn</iso639-2_code>
         <iso639-2_code>nyo</iso639-2_code>
         <iso639-2_code>nzi</iso639-2_code>
         <iso639-2_code>oci</iso639-2_code>
         <iso639-2_code>oji</iso639-2_code>
         <iso639-2_code>ori</iso639-2_code>
         <iso639-2_code>orm</iso639-2_code>
         <iso639-2_code>osa</iso639-2_code>
         <iso639-2_code>oss</iso639-2_code>
         <iso639-2_code>ota</iso639-2_code>
         <iso639-2_code>oto</iso639-2_code>
         <iso639-2_code>paa</iso639-2_code>
         <iso639-2_code>pag</iso639-2_code>
         <iso639-2_code>pal</iso639-2_code>
         <iso639-2_code>pam</iso639-2_code>
         <iso639-2_code>pan</iso639-2_code>
         <iso639-2_code>pap</iso639-2_code>
         <iso639-2_code>pau</iso639-2_code>
         <iso639-2_code>peo</iso639-2_code>
         <iso639-2_code>phi</iso639-2_code>
         <iso639-2_code>phn</iso639-2_code>
         <iso639-2_code>pli</iso639-2_code>
         <iso639-2_code>pol</iso639-2_code>
         <iso639-2_code>pon</iso639-2_code>
         <iso639-2_code>por</iso639-2_code>
         <iso639-2_code>pra</iso639-2_code>
         <iso639-2_code>pro</iso639-2_code>
         <iso639-2_code>pus</iso639-2_code>
         <iso639-2_code>qaa-qtz</iso639-2_code>
         <iso639-2_code>que</iso639-2_code>
         <iso639-2_code>raj</iso639-2_code>
         <iso639-2_code>rap</iso639-2_code>
         <iso639-2_code>rar</iso639-2_code>
         <iso639-2_code>roa</iso639-2_code>
         <iso639-2_code>roh</iso639-2_code>
         <iso639-2_code>rom</iso639-2_code>
         <iso639-2_code>rum</iso639-2_code>
         <iso639-2_code>ron</iso639-2_code>
         <iso639-2_code>run</iso639-2_code>
         <iso639-2_code>rup</iso639-2_code>
         <iso639-2_code>rus</iso639-2_code>
         <iso639-2_code>sad</iso639-2_code>
         <iso639-2_code>sag</iso639-2_code>
         <iso639-2_code>sah</iso639-2_code>
         <iso639-2_code>sai</iso639-2_code>
         <iso639-2_code>sal</iso639-2_code>
         <iso639-2_code>sam</iso639-2_code>
         <iso639-2_code>san</iso639-2_code>
         <iso639-2_code>sas</iso639-2_code>
         <iso639-2_code>sat</iso639-2_code>
         <iso639-2_code>scn</iso639-2_code>
         <iso639-2_code>sco</iso639-2_code>
         <iso639-2_code>sel</iso639-2_code>
         <iso639-2_code>sem</iso639-2_code>
         <iso639-2_code>sga</iso639-2_code>
         <iso639-2_code>sgn</iso639-2_code>
         <iso639-2_code>shn</iso639-2_code>
         <iso639-2_code>sid</iso639-2_code>
         <iso639-2_code>sin</iso639-2_code>
         <iso639-2_code>sio</iso639-2_code>
         <iso639-2_code>sit</iso639-2_code>
         <iso639-2_code>sla</iso639-2_code>
         <iso639-2_code>slo</iso639-2_code>
         <iso639-2_code>slk</iso639-2_code>
         <iso639-2_code>slv</iso639-2_code>
         <iso639-2_code>sma</iso639-2_code>
         <iso639-2_code>sme</iso639-2_code>
         <iso639-2_code>smi</iso639-2_code>
         <iso639-2_code>smj</iso639-2_code>
         <iso639-2_code>smn</iso639-2_code>
         <iso639-2_code>smo</iso639-2_code>
         <iso639-2_code>sms</iso639-2_code>
         <iso639-2_code>sna</iso639-2_code>
         <iso639-2_code>snd</iso639-2_code>
         <iso639-2_code>snk</iso639-2_code>
         <iso639-2_code>sog</iso639-2_code>
         <iso639-2_code>som</iso639-2_code>
         <iso639-2_code>son</iso639-2_code>
         <iso639-2_code>sot</iso639-2_code>
         <iso639-2_code>spa</iso639-2_code>
         <iso639-2_code>srd</iso639-2_code>
         <iso639-2_code>srn</iso639-2_code>
         <iso639-2_code>srp</iso639-2_code>
         <iso639-2_code>srr</iso639-2_code>
         <iso639-2_code>ssa</iso639-2_code>
         <iso639-2_code>ssw</iso639-2_code>
         <iso639-2_code>suk</iso639-2_code>
         <iso639-2_code>sun</iso639-2_code>
         <iso639-2_code>sus</iso639-2_code>
         <iso639-2_code>sux</iso639-2_code>
         <iso639-2_code>swa</iso639-2_code>
         <iso639-2_code>swe</iso639-2_code>
         <iso639-2_code>syc</iso639-2_code>
         <iso639-2_code>syr</iso639-2_code>
         <iso639-2_code>tah</iso639-2_code>
         <iso639-2_code>tai</iso639-2_code>
         <iso639-2_code>tam</iso639-2_code>
         <iso639-2_code>tat</iso639-2_code>
         <iso639-2_code>tel</iso639-2_code>
         <iso639-2_code>tem</iso639-2_code>
         <iso639-2_code>ter</iso639-2_code>
         <iso639-2_code>tet</iso639-2_code>
         <iso639-2_code>tgk</iso639-2_code>
         <iso639-2_code>tgl</iso639-2_code>
         <iso639-2_code>tha</iso639-2_code>
         <iso639-2_code>tig</iso639-2_code>
         <iso639-2_code>tir</iso639-2_code>
         <iso639-2_code>tiv</iso639-2_code>
         <iso639-2_code>tkl</iso639-2_code>
         <iso639-2_code>tlh</iso639-2_code>
         <iso639-2_code>tli</iso639-2_code>
         <iso639-2_code>tmh</iso639-2_code>
         <iso639-2_code>tog</iso639-2_code>
         <iso639-2_code>ton</iso639-2_code>
         <iso639-2_code>tpi</iso639-2_code>
         <iso639-2_code>tsi</iso639-2_code>
         <iso639-2_code>tsn</iso639-2_code>
         <iso639-2_code>tso</iso639-2_code>
         <iso639-2_code>tuk</iso639-2_code>
         <iso639-2_code>tum</iso639-2_code>
         <iso639-2_code>tup</iso639-2_code>
         <iso639-2_code>tur</iso639-2_code>
         <iso639-2_code>tut</iso639-2_code>
         <iso639-2_code>tvl</iso639-2_code>
         <iso639-2_code>twi</iso639-2_code>
         <iso639-2_code>tyv</iso639-2_code>
         <iso639-2_code>udm</iso639-2_code>
         <iso639-2_code>uga</iso639-2_code>
         <iso639-2_code>uig</iso639-2_code>
         <iso639-2_code>ukr</iso639-2_code>
         <iso639-2_code>umb</iso639-2_code>
         <iso639-2_code>und</iso639-2_code>
         <iso639-2_code>urd</iso639-2_code>
         <iso639-2_code>uzb</iso639-2_code>
         <iso639-2_code>vai</iso639-2_code>
         <iso639-2_code>ven</iso639-2_code>
         <iso639-2_code>vie</iso639-2_code>
         <iso639-2_code>vol</iso639-2_code>
         <iso639-2_code>vot</iso639-2_code>
         <iso639-2_code>wak</iso639-2_code>
         <iso639-2_code>wal</iso639-2_code>
         <iso639-2_code>war</iso639-2_code>
         <iso639-2_code>was</iso639-2_code>
         <iso639-2_code>wen</iso639-2_code>
         <iso639-2_code>wln</iso639-2_code>
         <iso639-2_code>wol</iso639-2_code>
         <iso639-2_code>xal</iso639-2_code>
         <iso639-2_code>xho</iso639-2_code>
         <iso639-2_code>yao</iso639-2_code>
         <iso639-2_code>yap</iso639-2_code>
         <iso639-2_code>yid</iso639-2_code>
         <iso639-2_code>yor</iso639-2_code>
         <iso639-2_code>ypk</iso639-2_code>
         <iso639-2_code>zap</iso639-2_code>
         <iso639-2_code>zbl</iso639-2_code>
         <iso639-2_code>zen</iso639-2_code>
         <iso639-2_code>zgh</iso639-2_code>
         <iso639-2_code>zha</iso639-2_code>
         <iso639-2_code>znd</iso639-2_code>
         <iso639-2_code>zul</iso639-2_code>
         <iso639-2_code>zun</iso639-2_code>
         <iso639-2_code>zxx</iso639-2_code>
         <iso639-2_code>zza</iso639-2_code>
      </iso639-2_codes>
   </xsl:variable>
   <xsl:variable name="marc_relator_codes">
      <marc_relator_codes xmlns="dcg:maps" xmlns:sch="http://purl.oclc.org/dsdl/schematron">
         <marc_relator_code>abr</marc_relator_code>
         <marc_relator_code>acp</marc_relator_code>
         <marc_relator_code>act</marc_relator_code>
         <marc_relator_code>adi</marc_relator_code>
         <marc_relator_code>adp</marc_relator_code>
         <marc_relator_code>aft</marc_relator_code>
         <marc_relator_code>anc</marc_relator_code>
         <marc_relator_code>anl</marc_relator_code>
         <marc_relator_code>anm</marc_relator_code>
         <marc_relator_code>ann</marc_relator_code>
         <marc_relator_code>ant</marc_relator_code>
         <marc_relator_code>ape</marc_relator_code>
         <marc_relator_code>apl</marc_relator_code>
         <marc_relator_code>app</marc_relator_code>
         <marc_relator_code>aqt</marc_relator_code>
         <marc_relator_code>arc</marc_relator_code>
         <marc_relator_code>ard</marc_relator_code>
         <marc_relator_code>arr</marc_relator_code>
         <marc_relator_code>art</marc_relator_code>
         <marc_relator_code>asg</marc_relator_code>
         <marc_relator_code>asn</marc_relator_code>
         <marc_relator_code>ato</marc_relator_code>
         <marc_relator_code>att</marc_relator_code>
         <marc_relator_code>auc</marc_relator_code>
         <marc_relator_code>aud</marc_relator_code>
         <marc_relator_code>aue</marc_relator_code>
         <marc_relator_code>aui</marc_relator_code>
         <marc_relator_code>aup</marc_relator_code>
         <marc_relator_code>aus</marc_relator_code>
         <marc_relator_code>aut</marc_relator_code>
         <marc_relator_code>bdd</marc_relator_code>
         <marc_relator_code>bjd</marc_relator_code>
         <marc_relator_code>bka</marc_relator_code>
         <marc_relator_code>bkd</marc_relator_code>
         <marc_relator_code>bkp</marc_relator_code>
         <marc_relator_code>blw</marc_relator_code>
         <marc_relator_code>bnd</marc_relator_code>
         <marc_relator_code>bpd</marc_relator_code>
         <marc_relator_code>brd</marc_relator_code>
         <marc_relator_code>brl</marc_relator_code>
         <marc_relator_code>bsl</marc_relator_code>
         <marc_relator_code>cad</marc_relator_code>
         <marc_relator_code>cas</marc_relator_code>
         <marc_relator_code>ccp</marc_relator_code>
         <marc_relator_code>chr</marc_relator_code>
         <marc_relator_code>cli</marc_relator_code>
         <marc_relator_code>cll</marc_relator_code>
         <marc_relator_code>clr</marc_relator_code>
         <marc_relator_code>clt</marc_relator_code>
         <marc_relator_code>cmm</marc_relator_code>
         <marc_relator_code>cmp</marc_relator_code>
         <marc_relator_code>cmt</marc_relator_code>
         <marc_relator_code>cnd</marc_relator_code>
         <marc_relator_code>cng</marc_relator_code>
         <marc_relator_code>cns</marc_relator_code>
         <marc_relator_code>coe</marc_relator_code>
         <marc_relator_code>col</marc_relator_code>
         <marc_relator_code>com</marc_relator_code>
         <marc_relator_code>con</marc_relator_code>
         <marc_relator_code>cop</marc_relator_code>
         <marc_relator_code>cor</marc_relator_code>
         <marc_relator_code>cos</marc_relator_code>
         <marc_relator_code>cot</marc_relator_code>
         <marc_relator_code>cou</marc_relator_code>
         <marc_relator_code>cov</marc_relator_code>
         <marc_relator_code>cpc</marc_relator_code>
         <marc_relator_code>cpe</marc_relator_code>
         <marc_relator_code>cph</marc_relator_code>
         <marc_relator_code>cpl</marc_relator_code>
         <marc_relator_code>cpt</marc_relator_code>
         <marc_relator_code>cre</marc_relator_code>
         <marc_relator_code>crp</marc_relator_code>
         <marc_relator_code>crr</marc_relator_code>
         <marc_relator_code>crt</marc_relator_code>
         <marc_relator_code>csl</marc_relator_code>
         <marc_relator_code>csp</marc_relator_code>
         <marc_relator_code>cst</marc_relator_code>
         <marc_relator_code>ctb</marc_relator_code>
         <marc_relator_code>cte</marc_relator_code>
         <marc_relator_code>ctg</marc_relator_code>
         <marc_relator_code>ctr</marc_relator_code>
         <marc_relator_code>cts</marc_relator_code>
         <marc_relator_code>ctt</marc_relator_code>
         <marc_relator_code>cur</marc_relator_code>
         <marc_relator_code>cwt</marc_relator_code>
         <marc_relator_code>dbd</marc_relator_code>
         <marc_relator_code>dbp</marc_relator_code>
         <marc_relator_code>dfd</marc_relator_code>
         <marc_relator_code>dfe</marc_relator_code>
         <marc_relator_code>dft</marc_relator_code>
         <marc_relator_code>dgc</marc_relator_code>
         <marc_relator_code>dgg</marc_relator_code>
         <marc_relator_code>dgs</marc_relator_code>
         <marc_relator_code>dis</marc_relator_code>
         <marc_relator_code>djo</marc_relator_code>
         <marc_relator_code>dln</marc_relator_code>
         <marc_relator_code>dnc</marc_relator_code>
         <marc_relator_code>dnr</marc_relator_code>
         <marc_relator_code>dpc</marc_relator_code>
         <marc_relator_code>dpt</marc_relator_code>
         <marc_relator_code>drm</marc_relator_code>
         <marc_relator_code>drt</marc_relator_code>
         <marc_relator_code>dsr</marc_relator_code>
         <marc_relator_code>dst</marc_relator_code>
         <marc_relator_code>dtc</marc_relator_code>
         <marc_relator_code>dte</marc_relator_code>
         <marc_relator_code>dtm</marc_relator_code>
         <marc_relator_code>dto</marc_relator_code>
         <marc_relator_code>dub</marc_relator_code>
         <marc_relator_code>edc</marc_relator_code>
         <marc_relator_code>edd</marc_relator_code>
         <marc_relator_code>edm</marc_relator_code>
         <marc_relator_code>edt</marc_relator_code>
         <marc_relator_code>egr</marc_relator_code>
         <marc_relator_code>elg</marc_relator_code>
         <marc_relator_code>elt</marc_relator_code>
         <marc_relator_code>eng</marc_relator_code>
         <marc_relator_code>enj</marc_relator_code>
         <marc_relator_code>etr</marc_relator_code>
         <marc_relator_code>evp</marc_relator_code>
         <marc_relator_code>exp</marc_relator_code>
         <marc_relator_code>fac</marc_relator_code>
         <marc_relator_code>fds</marc_relator_code>
         <marc_relator_code>fld</marc_relator_code>
         <marc_relator_code>flm</marc_relator_code>
         <marc_relator_code>fmd</marc_relator_code>
         <marc_relator_code>fmk</marc_relator_code>
         <marc_relator_code>fmo</marc_relator_code>
         <marc_relator_code>fmp</marc_relator_code>
         <marc_relator_code>fnd</marc_relator_code>
         <marc_relator_code>fon</marc_relator_code>
         <marc_relator_code>fpy</marc_relator_code>
         <marc_relator_code>frg</marc_relator_code>
         <marc_relator_code>gdv</marc_relator_code>
         <marc_relator_code>gis</marc_relator_code>
         <marc_relator_code>his</marc_relator_code>
         <marc_relator_code>hnr</marc_relator_code>
         <marc_relator_code>hst</marc_relator_code>
         <marc_relator_code>ill</marc_relator_code>
         <marc_relator_code>ilu</marc_relator_code>
         <marc_relator_code>ins</marc_relator_code>
         <marc_relator_code>inv</marc_relator_code>
         <marc_relator_code>isb</marc_relator_code>
         <marc_relator_code>itr</marc_relator_code>
         <marc_relator_code>ive</marc_relator_code>
         <marc_relator_code>ivr</marc_relator_code>
         <marc_relator_code>jud</marc_relator_code>
         <marc_relator_code>jug</marc_relator_code>
         <marc_relator_code>lbr</marc_relator_code>
         <marc_relator_code>lbt</marc_relator_code>
         <marc_relator_code>ldr</marc_relator_code>
         <marc_relator_code>led</marc_relator_code>
         <marc_relator_code>lee</marc_relator_code>
         <marc_relator_code>lel</marc_relator_code>
         <marc_relator_code>len</marc_relator_code>
         <marc_relator_code>let</marc_relator_code>
         <marc_relator_code>lgd</marc_relator_code>
         <marc_relator_code>lie</marc_relator_code>
         <marc_relator_code>lil</marc_relator_code>
         <marc_relator_code>lit</marc_relator_code>
         <marc_relator_code>lsa</marc_relator_code>
         <marc_relator_code>lse</marc_relator_code>
         <marc_relator_code>lso</marc_relator_code>
         <marc_relator_code>ltg</marc_relator_code>
         <marc_relator_code>lyr</marc_relator_code>
         <marc_relator_code>mcp</marc_relator_code>
         <marc_relator_code>mdc</marc_relator_code>
         <marc_relator_code>med</marc_relator_code>
         <marc_relator_code>mfp</marc_relator_code>
         <marc_relator_code>mfr</marc_relator_code>
         <marc_relator_code>mka</marc_relator_code>
         <marc_relator_code>mod</marc_relator_code>
         <marc_relator_code>mon</marc_relator_code>
         <marc_relator_code>mrb</marc_relator_code>
         <marc_relator_code>mrk</marc_relator_code>
         <marc_relator_code>msd</marc_relator_code>
         <marc_relator_code>mte</marc_relator_code>
         <marc_relator_code>mtk</marc_relator_code>
         <marc_relator_code>mup</marc_relator_code>
         <marc_relator_code>mus</marc_relator_code>
         <marc_relator_code>mxe</marc_relator_code>
         <marc_relator_code>nan</marc_relator_code>
         <marc_relator_code>nrt</marc_relator_code>
         <marc_relator_code>onp</marc_relator_code>
         <marc_relator_code>opn</marc_relator_code>
         <marc_relator_code>org</marc_relator_code>
         <marc_relator_code>orm</marc_relator_code>
         <marc_relator_code>osp</marc_relator_code>
         <marc_relator_code>oth</marc_relator_code>
         <marc_relator_code>own</marc_relator_code>
         <marc_relator_code>pad</marc_relator_code>
         <marc_relator_code>pan</marc_relator_code>
         <marc_relator_code>pat</marc_relator_code>
         <marc_relator_code>pbd</marc_relator_code>
         <marc_relator_code>pbl</marc_relator_code>
         <marc_relator_code>pdr</marc_relator_code>
         <marc_relator_code>pfr</marc_relator_code>
         <marc_relator_code>pht</marc_relator_code>
         <marc_relator_code>plt</marc_relator_code>
         <marc_relator_code>pma</marc_relator_code>
         <marc_relator_code>pmn</marc_relator_code>
         <marc_relator_code>pop</marc_relator_code>
         <marc_relator_code>ppm</marc_relator_code>
         <marc_relator_code>ppt</marc_relator_code>
         <marc_relator_code>pra</marc_relator_code>
         <marc_relator_code>prc</marc_relator_code>
         <marc_relator_code>prd</marc_relator_code>
         <marc_relator_code>pre</marc_relator_code>
         <marc_relator_code>prf</marc_relator_code>
         <marc_relator_code>prg</marc_relator_code>
         <marc_relator_code>prm</marc_relator_code>
         <marc_relator_code>prn</marc_relator_code>
         <marc_relator_code>pro</marc_relator_code>
         <marc_relator_code>prp</marc_relator_code>
         <marc_relator_code>prs</marc_relator_code>
         <marc_relator_code>prt</marc_relator_code>
         <marc_relator_code>prv</marc_relator_code>
         <marc_relator_code>pta</marc_relator_code>
         <marc_relator_code>pte</marc_relator_code>
         <marc_relator_code>ptf</marc_relator_code>
         <marc_relator_code>pth</marc_relator_code>
         <marc_relator_code>ptt</marc_relator_code>
         <marc_relator_code>pup</marc_relator_code>
         <marc_relator_code>rap</marc_relator_code>
         <marc_relator_code>rbr</marc_relator_code>
         <marc_relator_code>rcd</marc_relator_code>
         <marc_relator_code>rce</marc_relator_code>
         <marc_relator_code>rcp</marc_relator_code>
         <marc_relator_code>rdd</marc_relator_code>
         <marc_relator_code>red</marc_relator_code>
         <marc_relator_code>ren</marc_relator_code>
         <marc_relator_code>res</marc_relator_code>
         <marc_relator_code>rev</marc_relator_code>
         <marc_relator_code>rpc</marc_relator_code>
         <marc_relator_code>rps</marc_relator_code>
         <marc_relator_code>rpt</marc_relator_code>
         <marc_relator_code>rpy</marc_relator_code>
         <marc_relator_code>rse</marc_relator_code>
         <marc_relator_code>rsg</marc_relator_code>
         <marc_relator_code>rsp</marc_relator_code>
         <marc_relator_code>rsr</marc_relator_code>
         <marc_relator_code>rst</marc_relator_code>
         <marc_relator_code>rth</marc_relator_code>
         <marc_relator_code>rtm</marc_relator_code>
         <marc_relator_code>rxa</marc_relator_code>
         <marc_relator_code>sad</marc_relator_code>
         <marc_relator_code>sce</marc_relator_code>
         <marc_relator_code>scl</marc_relator_code>
         <marc_relator_code>scr</marc_relator_code>
         <marc_relator_code>sde</marc_relator_code>
         <marc_relator_code>sds</marc_relator_code>
         <marc_relator_code>sec</marc_relator_code>
         <marc_relator_code>sfx</marc_relator_code>
         <marc_relator_code>sgd</marc_relator_code>
         <marc_relator_code>sgn</marc_relator_code>
         <marc_relator_code>sht</marc_relator_code>
         <marc_relator_code>sll</marc_relator_code>
         <marc_relator_code>sng</marc_relator_code>
         <marc_relator_code>spk</marc_relator_code>
         <marc_relator_code>spn</marc_relator_code>
         <marc_relator_code>spy</marc_relator_code>
         <marc_relator_code>srv</marc_relator_code>
         <marc_relator_code>std</marc_relator_code>
         <marc_relator_code>stg</marc_relator_code>
         <marc_relator_code>stl</marc_relator_code>
         <marc_relator_code>stm</marc_relator_code>
         <marc_relator_code>stn</marc_relator_code>
         <marc_relator_code>str</marc_relator_code>
         <marc_relator_code>swd</marc_relator_code>
         <marc_relator_code>tau</marc_relator_code>
         <marc_relator_code>tcd</marc_relator_code>
         <marc_relator_code>tch</marc_relator_code>
         <marc_relator_code>ths</marc_relator_code>
         <marc_relator_code>tld</marc_relator_code>
         <marc_relator_code>tlg</marc_relator_code>
         <marc_relator_code>tlh</marc_relator_code>
         <marc_relator_code>tlp</marc_relator_code>
         <marc_relator_code>trc</marc_relator_code>
         <marc_relator_code>trl</marc_relator_code>
         <marc_relator_code>tyd</marc_relator_code>
         <marc_relator_code>tyg</marc_relator_code>
         <marc_relator_code>uvp</marc_relator_code>
         <marc_relator_code>vac</marc_relator_code>
         <marc_relator_code>vdg</marc_relator_code>
         <marc_relator_code>vfx</marc_relator_code>
         <marc_relator_code>wac</marc_relator_code>
         <marc_relator_code>wal</marc_relator_code>
         <marc_relator_code>wam</marc_relator_code>
         <marc_relator_code>wat</marc_relator_code>
         <marc_relator_code>wdc</marc_relator_code>
         <marc_relator_code>wde</marc_relator_code>
         <marc_relator_code>win</marc_relator_code>
         <marc_relator_code>wit</marc_relator_code>
         <marc_relator_code>wpr</marc_relator_code>
         <marc_relator_code>wst</marc_relator_code>
      </marc_relator_codes>
   </xsl:variable>
   <xsl:variable name="mets_ap_dv_license_values">
      <mets_ap_dv_license_values xmlns="dcg:maps" xmlns:sch="http://purl.oclc.org/dsdl/schematron">
         <mets_ap_dv_license_value to="http://creativecommons.org/publicdomain/mark/1.0/">pdm</mets_ap_dv_license_value>
         <mets_ap_dv_license_value to="http://creativecommons.org/publicdomain/zero/1.0/">cc0</mets_ap_dv_license_value>
         <mets_ap_dv_license_value to="http://creativecommons.org/licenses/by/4.0/">cc-by</mets_ap_dv_license_value>
         <mets_ap_dv_license_value to="http://creativecommons.org/licenses/by-sa/4.0/">cc-by-sa</mets_ap_dv_license_value>
         <mets_ap_dv_license_value to="http://creativecommons.org/licenses/by-nd/4.0/">cc-by-nd</mets_ap_dv_license_value>
         <mets_ap_dv_license_value to="http://creativecommons.org/licenses/by-nc/4.0/">cc-by-nc</mets_ap_dv_license_value>
         <mets_ap_dv_license_value to="http://creativecommons.org/licenses/by-nc-sa/4.0/">cc-by-nc-sa</mets_ap_dv_license_value>
         <mets_ap_dv_license_value to="http://creativecommons.org/licenses/by-nc-nd/4.0/">cc-by-nc-nd</mets_ap_dv_license_value>
         <mets_ap_dv_license_value to="http://rightsstatements.org/vocab/CNE/1.0/">reserved</mets_ap_dv_license_value>
      </mets_ap_dv_license_values>
   </xsl:variable>
   <xsl:variable name="work_dmdid">
      <xsl:variable xmlns:sch="http://purl.oclc.org/dsdl/schematron" name="root">
         <xsl:copy-of select="/"/>
      </xsl:variable>
      <xsl:for-each xmlns:sch="http://purl.oclc.org/dsdl/schematron"
                    select="tokenize(//mets:mets/mets:structMap[@TYPE='LOGICAL']/descendant::mets:div[not(mets:mptr)][1]/@DMDID, ' ')">
         <xsl:variable name="ID" select="."/>
         <xsl:if test="$root//mets:mets/mets:dmdSec[@ID=$ID]/mets:mdWrap/mets:xmlData/mods:mods">
            <xsl:value-of select="$ID"/>
         </xsl:if>
      </xsl:for-each>
   </xsl:variable>
   <xsl:param name="is_anchor"
              select="if ( //mets:mets/mets:structLink or //mets:mets/mets:fileSec/mets:fileGrp[@USE='DEFAULT'] ) then false() else true()"/>
   <xsl:param name="work_amdid"
              select="//mets:mets/mets:structMap[@TYPE='LOGICAL']//mets:div[tokenize(@DMDID, ' ') = $work_dmdid]/@ADMID"/>
   <!--PATTERN -->
   <!--RULE -->
   <xsl:template match="mods:*" priority="1000" mode="M33">
      <svrl:fired-rule xmlns:svrl="http://purl.oclc.org/dsdl/svrl" context="mods:*"/>
      <!--ASSERT warn-->
      <xsl:choose>
         <xsl:when test="string-length(normalize-space(text()[1])) &gt; 0 or element()"/>
         <xsl:otherwise>
            <svrl:failed-assert xmlns:svrl="http://purl.oclc.org/dsdl/svrl"
                                test="string-length(normalize-space(text()[1])) &gt; 0 or element()">
               <xsl:attribute name="id">all_01</xsl:attribute>
               <xsl:attribute name="role">warn</xsl:attribute>
               <xsl:attribute name="location">
                  <xsl:apply-templates select="." mode="schematron-select-full-path"/>
               </xsl:attribute>
               <svrl:text>Jedes MODS-Element muss entweder mindestens ein Unterelement oder Text enthalten. Leere MODS-Elemente verhindern nicht das Einspielen Ihrer Daten in die DDB, können aber auf Probleme beim Erzeugen des Datensatzes und damit verbundene Informationsverluste hinweisen.</svrl:text>
               <svrl:property id="dmd_id">
                  <xsl:value-of xmlns:sch="http://purl.oclc.org/dsdl/schematron"
                                select="ancestor-or-self::mets:dmdSec/@ID"/>
               </svrl:property>
            </svrl:failed-assert>
         </xsl:otherwise>
      </xsl:choose>
      <xsl:apply-templates select="*" mode="M33"/>
   </xsl:template>
   <xsl:template match="text()" priority="-1" mode="M33"/>
   <xsl:template match="@*|node()" priority="-2" mode="M33">
      <xsl:apply-templates select="*" mode="M33"/>
   </xsl:template>
   <!--PATTERN -->
   <!--RULE -->
   <xsl:template match="mods:*[mods:*]" priority="1000" mode="M34">
      <svrl:fired-rule xmlns:svrl="http://purl.oclc.org/dsdl/svrl" context="mods:*[mods:*]"/>
      <!--REPORT error-->
      <xsl:if test="matches(string-join(text(), ''), '\w')">
         <svrl:successful-report xmlns:svrl="http://purl.oclc.org/dsdl/svrl"
                                 test="matches(string-join(text(), ''), '\w')">
            <xsl:attribute name="id">all_02</xsl:attribute>
            <xsl:attribute name="role">error</xsl:attribute>
            <xsl:attribute name="location">
               <xsl:apply-templates select="." mode="schematron-select-full-path"/>
            </xsl:attribute>
            <svrl:text>MODS-Elemente, die Unterelemente enthalten, dürfen keinen Text enthalten. Enthält ein MODS-Element mit mindestens einem Unterelement ebenfalls Text, wird der Text bei der Transformation des Datensatzes entfernt.</svrl:text>
            <svrl:property id="dmd_id">
               <xsl:value-of xmlns:sch="http://purl.oclc.org/dsdl/schematron"
                             select="ancestor-or-self::mets:dmdSec/@ID"/>
            </svrl:property>
         </svrl:successful-report>
      </xsl:if>
      <xsl:apply-templates select="*" mode="M34"/>
   </xsl:template>
   <xsl:template match="text()" priority="-1" mode="M34"/>
   <xsl:template match="@*|node()" priority="-2" mode="M34">
      <xsl:apply-templates select="*" mode="M34"/>
   </xsl:template>
   <!--PATTERN -->
   <!--RULE -->
   <xsl:template match="mets:xmlData/mods:mods/mods:*" priority="1000" mode="M35">
      <svrl:fired-rule xmlns:svrl="http://purl.oclc.org/dsdl/svrl"
                       context="mets:xmlData/mods:mods/mods:*"/>
      <!--ASSERT fatal-->
      <xsl:choose>
         <xsl:when test="./local-name() = ( 'titleInfo', 'name', 'typeOfResource', 'genre', 'originInfo', 'language', 'physicalDescription', 'abstract', 'tableOfContents', 'targetAudience', 'note', 'subject', 'classification', 'relatedItem', 'identifier', 'location', 'accessCondition', 'part', 'extension', 'recordInfo' )"/>
         <xsl:otherwise>
            <svrl:failed-assert xmlns:svrl="http://purl.oclc.org/dsdl/svrl"
                                test="./local-name() = ( 'titleInfo', 'name', 'typeOfResource', 'genre', 'originInfo', 'language', 'physicalDescription', 'abstract', 'tableOfContents', 'targetAudience', 'note', 'subject', 'classification', 'relatedItem', 'identifier', 'location', 'accessCondition', 'part', 'extension', 'recordInfo' )">
               <xsl:attribute name="id">all_03</xsl:attribute>
               <xsl:attribute name="role">fatal</xsl:attribute>
               <xsl:attribute name="location">
                  <xsl:apply-templates select="." mode="schematron-select-full-path"/>
               </xsl:attribute>
               <svrl:text>Der Datensatz verwendet Elemente in der obersten MODS-Ebene, die dort nicht zulässig sind. Da diese zu Problemen in der Verarbeitung des Datensatzes führen können, wird der Datensatz nicht in die DDB eingespielt.Eine Liste der zulässigen MODS-Elemente auf der obersten Ebene finden sie in den MODS User Guidelines (https://www.loc.gov/standards/mods/userguide/generalapp.html#top_level).</svrl:text>
               <svrl:property id="dmd_id">
                  <xsl:value-of xmlns:sch="http://purl.oclc.org/dsdl/schematron"
                                select="ancestor-or-self::mets:dmdSec/@ID"/>
               </svrl:property>
               <svrl:property id="local_name">
                  <xsl:value-of xmlns:sch="http://purl.oclc.org/dsdl/schematron" select="local-name()"/>
               </svrl:property>
            </svrl:failed-assert>
         </xsl:otherwise>
      </xsl:choose>
      <xsl:apply-templates select="*" mode="M35"/>
   </xsl:template>
   <xsl:template match="text()" priority="-1" mode="M35"/>
   <xsl:template match="@*|node()" priority="-2" mode="M35">
      <xsl:apply-templates select="*" mode="M35"/>
   </xsl:template>
   <!--PATTERN -->
   <!--RULE -->
   <xsl:template match="mods:*/@valueURI" priority="1000" mode="M36">
      <svrl:fired-rule xmlns:svrl="http://purl.oclc.org/dsdl/svrl" context="mods:*/@valueURI"/>
      <!--ASSERT error-->
      <xsl:choose>
         <xsl:when test="matches(., '^https?://[^ ]+$')"/>
         <xsl:otherwise>
            <svrl:failed-assert xmlns:svrl="http://purl.oclc.org/dsdl/svrl"
                                test="matches(., '^https?://[^ ]+$')">
               <xsl:attribute name="id">all_04</xsl:attribute>
               <xsl:attribute name="role">error</xsl:attribute>
               <xsl:attribute name="location">
                  <xsl:apply-templates select="." mode="schematron-select-full-path"/>
               </xsl:attribute>
               <svrl:text>Das Attribut <xsl:text/>valueURI<xsl:text/> muss immer einen URL enthalten. Enthält es keinen URL, wird <xsl:text/>valueURI<xsl:text/> bei der Transformation des Datensatzes entfernt.</svrl:text>
               <svrl:property id="dmd_id">
                  <xsl:value-of xmlns:sch="http://purl.oclc.org/dsdl/schematron"
                                select="ancestor-or-self::mets:dmdSec/@ID"/>
               </svrl:property>
            </svrl:failed-assert>
         </xsl:otherwise>
      </xsl:choose>
      <xsl:apply-templates select="*" mode="M36"/>
   </xsl:template>
   <xsl:template match="text()" priority="-1" mode="M36"/>
   <xsl:template match="@*|node()" priority="-2" mode="M36">
      <xsl:apply-templates select="*" mode="M36"/>
   </xsl:template>
   <!--PATTERN -->
   <!--RULE -->
   <xsl:template match="mets:xmlData/mods:mods/mods:*//mods:mods"
                 priority="1000"
                 mode="M37">
      <svrl:fired-rule xmlns:svrl="http://purl.oclc.org/dsdl/svrl"
                       context="mets:xmlData/mods:mods/mods:*//mods:mods"/>
      <!--REPORT error-->
      <xsl:if test=".[not(./ancestor::mods:extension)]">
         <svrl:successful-report xmlns:svrl="http://purl.oclc.org/dsdl/svrl"
                                 test=".[not(./ancestor::mods:extension)]">
            <xsl:attribute name="id">all_05</xsl:attribute>
            <xsl:attribute name="role">error</xsl:attribute>
            <xsl:attribute name="location">
               <xsl:apply-templates select="." mode="schematron-select-full-path"/>
            </xsl:attribute>
            <svrl:text>Der Datensatz enthält innerhalb des MODS-Wurzelelements <xsl:text/>mods:mods<xsl:text/> im Element <xsl:text/>mets:dmdSec<xsl:text/> weitere <xsl:text/>mods:mods<xsl:text/>-Elemente. Diese können zu Problemen in der Verarbeitung des Datensatzes führen und werden daher bei der Transformation des Datensatzes entfernt. </svrl:text>
            <svrl:property id="dmd_id">
               <xsl:value-of xmlns:sch="http://purl.oclc.org/dsdl/schematron"
                             select="ancestor-or-self::mets:dmdSec/@ID"/>
            </svrl:property>
         </svrl:successful-report>
      </xsl:if>
      <xsl:apply-templates select="*" mode="M37"/>
   </xsl:template>
   <xsl:template match="text()" priority="-1" mode="M37"/>
   <xsl:template match="@*|node()" priority="-2" mode="M37">
      <xsl:apply-templates select="*" mode="M37"/>
   </xsl:template>
   <!--PATTERN -->
   <!--RULE -->
   <xsl:template match="mods:*[starts-with(@valueURI, 'http://d-nb.info/gnd/') or starts-with(@valueURI, 'https://d-nb.info/gnd/')]"
                 priority="1000"
                 mode="M38">
      <svrl:fired-rule xmlns:svrl="http://purl.oclc.org/dsdl/svrl"
                       context="mods:*[starts-with(@valueURI, 'http://d-nb.info/gnd/') or starts-with(@valueURI, 'https://d-nb.info/gnd/')]"/>
      <!--ASSERT error-->
      <xsl:choose>
         <xsl:when test="matches(substring-after(@valueURI, '/gnd/'), '^[0-9]*-[0-9xX]{1}$|^[0-9xX]*$')"/>
         <xsl:otherwise>
            <svrl:failed-assert xmlns:svrl="http://purl.oclc.org/dsdl/svrl"
                                test="matches(substring-after(@valueURI, '/gnd/'), '^[0-9]*-[0-9xX]{1}$|^[0-9xX]*$')">
               <xsl:attribute name="id">all_06</xsl:attribute>
               <xsl:attribute name="role">error</xsl:attribute>
               <xsl:attribute name="location">
                  <xsl:apply-templates select="." mode="schematron-select-full-path"/>
               </xsl:attribute>
               <svrl:text>Der Datensatz enthält <xsl:text/>valueURI<xsl:text/> Attribute mit einem ungültigen GND-URI. Diese werden bei der Transformation des Datensatzes entfernt.</svrl:text>
               <svrl:property id="dmd_id">
                  <xsl:value-of xmlns:sch="http://purl.oclc.org/dsdl/schematron"
                                select="ancestor-or-self::mets:dmdSec/@ID"/>
               </svrl:property>
            </svrl:failed-assert>
         </xsl:otherwise>
      </xsl:choose>
      <xsl:apply-templates select="*" mode="M38"/>
   </xsl:template>
   <xsl:template match="text()" priority="-1" mode="M38"/>
   <xsl:template match="@*|node()" priority="-2" mode="M38">
      <xsl:apply-templates select="*" mode="M38"/>
   </xsl:template>
   <!--PATTERN -->
   <!--RULE -->
   <xsl:template match="oai:record/oai:metadata/element()[local-name() = 'mets']"
                 priority="1000"
                 mode="M39">
      <svrl:fired-rule xmlns:svrl="http://purl.oclc.org/dsdl/svrl"
                       context="oai:record/oai:metadata/element()[local-name() = 'mets']"/>
      <!--ASSERT fatal-->
      <xsl:choose>
         <xsl:when test="./namespace-uri() = 'http://www.loc.gov/METS/'"/>
         <xsl:otherwise>
            <svrl:failed-assert xmlns:svrl="http://purl.oclc.org/dsdl/svrl"
                                test="./namespace-uri() = 'http://www.loc.gov/METS/'">
               <xsl:attribute name="id">all_07</xsl:attribute>
               <xsl:attribute name="role">fatal</xsl:attribute>
               <xsl:attribute name="location">
                  <xsl:apply-templates select="." mode="schematron-select-full-path"/>
               </xsl:attribute>
               <svrl:text>Der Datensatz verwendet einen ungültigen Namensraum für METS-Elemente. Der korrekte Namensraum für METS-Elemente ist <xsl:text/>http://www.loc.gov/METS/<xsl:text/>.
Verwenden die METS-Elemente einen ungültigen Namensraum ist eine Verarbeitung des Datensatzes nicht möglich und er wird nicht in die DDB eingespielt.</svrl:text>
            </svrl:failed-assert>
         </xsl:otherwise>
      </xsl:choose>
      <xsl:apply-templates select="*" mode="M39"/>
   </xsl:template>
   <xsl:template match="text()" priority="-1" mode="M39"/>
   <xsl:template match="@*|node()" priority="-2" mode="M39">
      <xsl:apply-templates select="*" mode="M39"/>
   </xsl:template>
   <!--PATTERN -->
   <!--RULE -->
   <xsl:template match="oai:record/oai:metadata/mets:mets/mets:dmdSec[1]/mets:mdWrap/mets:xmlData/element()[local-name() = 'mods']"
                 priority="1000"
                 mode="M40">
      <svrl:fired-rule xmlns:svrl="http://purl.oclc.org/dsdl/svrl"
                       context="oai:record/oai:metadata/mets:mets/mets:dmdSec[1]/mets:mdWrap/mets:xmlData/element()[local-name() = 'mods']"/>
      <!--ASSERT fatal-->
      <xsl:choose>
         <xsl:when test="./namespace-uri() = 'http://www.loc.gov/mods/v3'"/>
         <xsl:otherwise>
            <svrl:failed-assert xmlns:svrl="http://purl.oclc.org/dsdl/svrl"
                                test="./namespace-uri() = 'http://www.loc.gov/mods/v3'">
               <xsl:attribute name="id">all_08</xsl:attribute>
               <xsl:attribute name="role">fatal</xsl:attribute>
               <xsl:attribute name="location">
                  <xsl:apply-templates select="." mode="schematron-select-full-path"/>
               </xsl:attribute>
               <svrl:text>Der Datensatz verwendet einen ungültigen Namensraum für MODS-Elemente. Der korrekte Namensraum für MODS-Elemente ist <xsl:text/>http://www.loc.gov/mods/v3<xsl:text/>.
Verwenden die MODS-Elemente einen ungültigen Namensraum ist eine Verarbeitung des Datensatzes nicht möglich und er wird nicht in die DDB eingespielt.</svrl:text>
            </svrl:failed-assert>
         </xsl:otherwise>
      </xsl:choose>
      <xsl:apply-templates select="*" mode="M40"/>
   </xsl:template>
   <xsl:template match="text()" priority="-1" mode="M40"/>
   <xsl:template match="@*|node()" priority="-2" mode="M40">
      <xsl:apply-templates select="*" mode="M40"/>
   </xsl:template>
   <!--PATTERN -->
   <!--RULE -->
   <xsl:template match="oai:record/oai:metadata/mets:mets/mets:amdSec[1]/mets:rightsMD/mets:mdWrap/mets:xmlData/element()[local-name() = 'rights']"
                 priority="1000"
                 mode="M41">
      <svrl:fired-rule xmlns:svrl="http://purl.oclc.org/dsdl/svrl"
                       context="oai:record/oai:metadata/mets:mets/mets:amdSec[1]/mets:rightsMD/mets:mdWrap/mets:xmlData/element()[local-name() = 'rights']"/>
      <!--ASSERT fatal-->
      <xsl:choose>
         <xsl:when test="./namespace-uri() = 'http://dfg-viewer.de/'"/>
         <xsl:otherwise>
            <svrl:failed-assert xmlns:svrl="http://purl.oclc.org/dsdl/svrl"
                                test="./namespace-uri() = 'http://dfg-viewer.de/'">
               <xsl:attribute name="id">all_09</xsl:attribute>
               <xsl:attribute name="role">fatal</xsl:attribute>
               <xsl:attribute name="location">
                  <xsl:apply-templates select="." mode="schematron-select-full-path"/>
               </xsl:attribute>
               <svrl:text>Der Datensatz verwendet einen ungültigen Namensraum für DFG-Main-Elemente (<xsl:text/>dv<xsl:text/>). Der korrekte Namensraum für DFG-Main-Elemente ist <xsl:text/>http://dfg-viewer.de/<xsl:text/>.
Verwenden die DFG-Main-Elemente einen ungültigen Namensraum ist eine Verarbeitung des Datensatzes nicht möglich und er wird nicht in die DDB eingespielt.</svrl:text>
            </svrl:failed-assert>
         </xsl:otherwise>
      </xsl:choose>
      <xsl:apply-templates select="*" mode="M41"/>
   </xsl:template>
   <xsl:template match="text()" priority="-1" mode="M41"/>
   <xsl:template match="@*|node()" priority="-2" mode="M41">
      <xsl:apply-templates select="*" mode="M41"/>
   </xsl:template>
   <!--PATTERN -->
   <!--RULE -->
   <xsl:template match="mets:dmdSec[@ID=$work_dmdid]/mets:mdWrap/mets:xmlData/mods:mods"
                 priority="1000"
                 mode="M42">
      <svrl:fired-rule xmlns:svrl="http://purl.oclc.org/dsdl/svrl"
                       context="mets:dmdSec[@ID=$work_dmdid]/mets:mdWrap/mets:xmlData/mods:mods"/>
      <!--ASSERT fatal-->
      <xsl:choose>
         <xsl:when test="mods:titleInfo[not(@type)][1]/mods:title[1][string-length(normalize-space(text())) &gt; 0] or mods:titleInfo[@type='uniform'][1]/mods:title[1][string-length(normalize-space(text())) &gt; 0] or mods:relatedItem[@type='host']"/>
         <xsl:otherwise>
            <svrl:failed-assert xmlns:svrl="http://purl.oclc.org/dsdl/svrl"
                                test="mods:titleInfo[not(@type)][1]/mods:title[1][string-length(normalize-space(text())) &gt; 0] or mods:titleInfo[@type='uniform'][1]/mods:title[1][string-length(normalize-space(text())) &gt; 0] or mods:relatedItem[@type='host']">
               <xsl:attribute name="id">titleInfo_01</xsl:attribute>
               <xsl:attribute name="role">fatal</xsl:attribute>
               <xsl:attribute name="location">
                  <xsl:apply-templates select="." mode="schematron-select-full-path"/>
               </xsl:attribute>
               <svrl:text>Das primäre <xsl:text/>mets:dmdSec<xsl:text/>-Element von Einteiligen Dokumenten muss ein <xsl:text/>mods:titleInfo<xsl:text/>-Element ohne Attribut <xsl:text/>type<xsl:text/> oder mit <xsl:text/>type<xsl:text/> mit dem Wert <xsl:text/>uniform<xsl:text/> besitzen. Darüber hinaus muss das <xsl:text/>mods:titleInfo<xsl:text/> das Unterelement <xsl:text/>mods:title<xsl:text/> enthalten.
Teile von Mehrteiligen Dokumenten können alternativ ein <xsl:text/>mods:relatedItem<xsl:text/>-Element mit dem Attribut <xsl:text/>type<xsl:text/> mit dem Wert <xsl:text/>host<xsl:text/> enthalten, das auf den Ankersatz des Mehrteiligen Dokuments verweist.
Ist dies nicht der Fall, wird der Datensatz nicht in die DDB eingespielt.Weitere Informationen zu diesen Elementen finden Sie im DDB-METS/MODS-Anwendungsprofil auf den Seiten mods:titleInfo (https://wiki.deutsche-digitale-bibliothek.de/x/xcIeB) und mods:relatedItem (https://wiki.deutsche-digitale-bibliothek.de/x/K8MeB) sowie im Bereich Aufbau einer METS/MODS-Datei für die DDB (https://wiki.deutsche-digitale-bibliothek.de/x/VcIeB).</svrl:text>
               <svrl:property id="dmd_id">
                  <xsl:value-of xmlns:sch="http://purl.oclc.org/dsdl/schematron"
                                select="ancestor-or-self::mets:dmdSec/@ID"/>
               </svrl:property>
            </svrl:failed-assert>
         </xsl:otherwise>
      </xsl:choose>
      <xsl:apply-templates select="*" mode="M42"/>
   </xsl:template>
   <xsl:template match="text()" priority="-1" mode="M42"/>
   <xsl:template match="@*|node()" priority="-2" mode="M42">
      <xsl:apply-templates select="*" mode="M42"/>
   </xsl:template>
   <!--PATTERN -->
   <!--RULE -->
   <xsl:template match="mets:dmdSec[@ID!=$work_dmdid]/mets:mdWrap/mets:xmlData/mods:mods"
                 priority="1000"
                 mode="M43">
      <svrl:fired-rule xmlns:svrl="http://purl.oclc.org/dsdl/svrl"
                       context="mets:dmdSec[@ID!=$work_dmdid]/mets:mdWrap/mets:xmlData/mods:mods"/>
      <!--ASSERT error-->
      <xsl:choose>
         <xsl:when test="mods:titleInfo[not(@type)][1]/mods:title[1][string-length(normalize-space(text())) &gt; 0] or $is_anchor"/>
         <xsl:otherwise>
            <svrl:failed-assert xmlns:svrl="http://purl.oclc.org/dsdl/svrl"
                                test="mods:titleInfo[not(@type)][1]/mods:title[1][string-length(normalize-space(text())) &gt; 0] or $is_anchor">
               <xsl:attribute name="id">titleInfo_02</xsl:attribute>
               <xsl:attribute name="role">error</xsl:attribute>
               <xsl:attribute name="location">
                  <xsl:apply-templates select="." mode="schematron-select-full-path"/>
               </xsl:attribute>
               <svrl:text>Jedes nicht-primäre <xsl:text/>mets:dmdSec<xsl:text/>-Element muss mindestens ein <xsl:text/>mods:titleInfo<xsl:text/>-Element mit einem mindestens drei Zeichen langem Text im Unterelement <xsl:text/>mods:title<xsl:text/> enthalten.
Ist dies nicht der Fall, wird <xsl:text/>mets:dmdSec<xsl:text/> bei der Transformation des Datensatzes entfernt.Weitere Informationen zu diesen Elementen und Ihrem Kontext finden Sie im DDB-METS/MODS-Anwendungsprofil auf den Seiten mods:titleInfo (https://wiki.deutsche-digitale-bibliothek.de/x/xcIeB) und METS/MODS für Unselbständige Dokumente (https://wiki.deutsche-digitale-bibliothek.de/x/BgKuB).</svrl:text>
               <svrl:property id="dmd_id">
                  <xsl:value-of xmlns:sch="http://purl.oclc.org/dsdl/schematron"
                                select="ancestor-or-self::mets:dmdSec/@ID"/>
               </svrl:property>
            </svrl:failed-assert>
         </xsl:otherwise>
      </xsl:choose>
      <xsl:apply-templates select="*" mode="M43"/>
   </xsl:template>
   <xsl:template match="text()" priority="-1" mode="M43"/>
   <xsl:template match="@*|node()" priority="-2" mode="M43">
      <xsl:apply-templates select="*" mode="M43"/>
   </xsl:template>
   <!--PATTERN -->
   <!--RULE -->
   <xsl:template match="mets:dmdSec/mets:mdWrap/mets:xmlData/mods:mods"
                 priority="1000"
                 mode="M44">
      <svrl:fired-rule xmlns:svrl="http://purl.oclc.org/dsdl/svrl"
                       context="mets:dmdSec/mets:mdWrap/mets:xmlData/mods:mods"/>
      <!--REPORT error-->
      <xsl:if test="mods:titleInfo[not(@type)][2] or (not(mods:titleInfo[not(@type)]) and mods:titleInfo[@type='uniform'][2])">
         <svrl:successful-report xmlns:svrl="http://purl.oclc.org/dsdl/svrl"
                                 test="mods:titleInfo[not(@type)][2] or (not(mods:titleInfo[not(@type)]) and mods:titleInfo[@type='uniform'][2])">
            <xsl:attribute name="id">titleInfo_03</xsl:attribute>
            <xsl:attribute name="role">error</xsl:attribute>
            <xsl:attribute name="location">
               <xsl:apply-templates select="." mode="schematron-select-full-path"/>
            </xsl:attribute>
            <svrl:text>Das Element <xsl:text/>mets:dmdSec<xsl:text/> muss genau ein <xsl:text/>mods:titleInfo<xsl:text/>-Element enthalten. Dieses muss entweder genau ein <xsl:text/>mods:titleInfo<xsl:text/> ohne das Attribut <xsl:text/>type<xsl:text/> enthalten oder kein <xsl:text/>mods:titleInfo<xsl:text/> ohne <xsl:text/>type<xsl:text/> und genau ein <xsl:text/>mods:titleInfo<xsl:text/> mit <xsl:text/>type<xsl:text/> und dem Wert <xsl:text/>uniform<xsl:text/>.
Ist dies nicht der Fall, kann die DDB keinen eindeutigen Objekttitel für die Anzeige des Datensatzes in der DDB ermitteln. Bei der Transformation des Datensatzes werden daher alle weiteren Vorkommen von <xsl:text/>mods:titleInfo<xsl:text/> ohne <xsl:text/>type<xsl:text/> bzw. <xsl:text/>mods:titleInfo<xsl:text/> mit <xsl:text/>type<xsl:text/> und dem Wert <xsl:text/>uniform<xsl:text/> entfernt.Weitere Informationen zu diesem Element und seinem Kontext finden Sie im DDB-METS/MODS-Anwendungsprofil auf der Seite mods:titleInfo (https://wiki.deutsche-digitale-bibliothek.de/x/xcIeB) und im Bereich Aufbau einer METS/MODS-Datei für die DDB (https://wiki.deutsche-digitale-bibliothek.de/x/VcIeB).</svrl:text>
            <svrl:property id="dmd_id">
               <xsl:value-of xmlns:sch="http://purl.oclc.org/dsdl/schematron"
                             select="ancestor-or-self::mets:dmdSec/@ID"/>
            </svrl:property>
         </svrl:successful-report>
      </xsl:if>
      <xsl:apply-templates select="*" mode="M44"/>
   </xsl:template>
   <xsl:template match="text()" priority="-1" mode="M44"/>
   <xsl:template match="@*|node()" priority="-2" mode="M44">
      <xsl:apply-templates select="*" mode="M44"/>
   </xsl:template>
   <!--PATTERN -->
   <!--RULE -->
   <xsl:template match="mets:xmlData/mods:mods/mods:titleInfo[@type]"
                 priority="1000"
                 mode="M45">
      <svrl:fired-rule xmlns:svrl="http://purl.oclc.org/dsdl/svrl"
                       context="mets:xmlData/mods:mods/mods:titleInfo[@type]"/>
      <!--ASSERT error-->
      <xsl:choose>
         <xsl:when test="./@type = ('abbreviated', 'translated', 'alternative', 'uniform')"/>
         <xsl:otherwise>
            <svrl:failed-assert xmlns:svrl="http://purl.oclc.org/dsdl/svrl"
                                test="./@type = ('abbreviated', 'translated', 'alternative', 'uniform')">
               <xsl:attribute name="id">titleInfo_04</xsl:attribute>
               <xsl:attribute name="role">error</xsl:attribute>
               <xsl:attribute name="location">
                  <xsl:apply-templates select="." mode="schematron-select-full-path"/>
               </xsl:attribute>
               <svrl:text>Das Attribut <xsl:text/>type<xsl:text/> im Element <xsl:text/>mods:titleInfo<xsl:text/> darf nur die folgenden Werte enthalten:
 * <xsl:text/>abbreviated<xsl:text/>
 * <xsl:text/>translated<xsl:text/>
 * <xsl:text/>alternative<xsl:text/>
 * <xsl:text/>uniform<xsl:text/>
Ist dies nicht der Fall, wird <xsl:text/>mods:titleInfo<xsl:text/> bei der Bereinigung des Datensatzes entfernt.Weitere Informationen zu diesem Attribut finden Sie im DDB-METS/MODS-Anwendungsprofil auf der Seite mods:titleInfo (https://wiki.deutsche-digitale-bibliothek.de/x/xcIeB).</svrl:text>
               <svrl:property id="dmd_id">
                  <xsl:value-of xmlns:sch="http://purl.oclc.org/dsdl/schematron"
                                select="ancestor-or-self::mets:dmdSec/@ID"/>
               </svrl:property>
               <svrl:property id="type">
                  <xsl:value-of xmlns:sch="http://purl.oclc.org/dsdl/schematron" select="./@type"/>
               </svrl:property>
            </svrl:failed-assert>
         </xsl:otherwise>
      </xsl:choose>
      <xsl:apply-templates select="*" mode="M45"/>
   </xsl:template>
   <xsl:template match="text()" priority="-1" mode="M45"/>
   <xsl:template match="@*|node()" priority="-2" mode="M45">
      <xsl:apply-templates select="*" mode="M45"/>
   </xsl:template>
   <!--PATTERN -->
   <!--RULE -->
   <xsl:template match="mets:xmlData/mods:mods/mods:titleInfo"
                 priority="1000"
                 mode="M46">
      <svrl:fired-rule xmlns:svrl="http://purl.oclc.org/dsdl/svrl"
                       context="mets:xmlData/mods:mods/mods:titleInfo"/>
      <!--REPORT error-->
      <xsl:if test="mods:title[2]">
         <svrl:successful-report xmlns:svrl="http://purl.oclc.org/dsdl/svrl" test="mods:title[2]">
            <xsl:attribute name="id">titleInfo_06</xsl:attribute>
            <xsl:attribute name="role">error</xsl:attribute>
            <xsl:attribute name="location">
               <xsl:apply-templates select="." mode="schematron-select-full-path"/>
            </xsl:attribute>
            <svrl:text>Das Element <xsl:text/>mods:titleInfo<xsl:text/> darf das Element <xsl:text/>mods:title<xsl:text/> nur einmal enthalten. Enthält <xsl:text/>mods:titleInfo<xsl:text/> mehr als ein <xsl:text/>mods:title<xsl:text/>, wird bei der Transformation des Datensatzes das erste Vorkommen von <xsl:text/>mods:title<xsl:text/> übernommen, alle anderen <xsl:text/>mods:title<xsl:text/> werden entfernt.Weitere Informationen zu diesem Element finden Sie im DDB-METS/MODS-Anwendungsprofil auf der Seite mods:titleInfo (https://wiki.deutsche-digitale-bibliothek.de/x/xcIeB).</svrl:text>
            <svrl:property id="dmd_id">
               <xsl:value-of xmlns:sch="http://purl.oclc.org/dsdl/schematron"
                             select="ancestor-or-self::mets:dmdSec/@ID"/>
            </svrl:property>
         </svrl:successful-report>
      </xsl:if>
      <xsl:apply-templates select="*" mode="M46"/>
   </xsl:template>
   <xsl:template match="text()" priority="-1" mode="M46"/>
   <xsl:template match="@*|node()" priority="-2" mode="M46">
      <xsl:apply-templates select="*" mode="M46"/>
   </xsl:template>
   <!--PATTERN -->
   <!--RULE -->
   <xsl:template match="mets:xmlData/mods:mods/mods:titleInfo"
                 priority="1000"
                 mode="M47">
      <svrl:fired-rule xmlns:svrl="http://purl.oclc.org/dsdl/svrl"
                       context="mets:xmlData/mods:mods/mods:titleInfo"/>
      <!--REPORT error-->
      <xsl:if test="mods:nonSort[2]">
         <svrl:successful-report xmlns:svrl="http://purl.oclc.org/dsdl/svrl" test="mods:nonSort[2]">
            <xsl:attribute name="id">titleInfo_07</xsl:attribute>
            <xsl:attribute name="role">error</xsl:attribute>
            <xsl:attribute name="location">
               <xsl:apply-templates select="." mode="schematron-select-full-path"/>
            </xsl:attribute>
            <svrl:text>Das Element <xsl:text/>mods:titleInfo<xsl:text/> darf das Element <xsl:text/>mods:nonSort<xsl:text/> nur einmal enthalten. Enthält <xsl:text/>mods:titleInfo<xsl:text/> mehr als ein <xsl:text/>mods:nonSort<xsl:text/>, wird bei der Transformation des Datensatzes das erste Vorkommen von <xsl:text/>mods:nonSort<xsl:text/> übernommen, alle anderen <xsl:text/>mods:nonSort<xsl:text/> werden entfernt.Weitere Informationen zu diesem Element finden Sie im DDB-METS/MODS-Anwendungsprofil auf der Seite mods:titleInfo (https://wiki.deutsche-digitale-bibliothek.de/x/xcIeB).</svrl:text>
            <svrl:property id="dmd_id">
               <xsl:value-of xmlns:sch="http://purl.oclc.org/dsdl/schematron"
                             select="ancestor-or-self::mets:dmdSec/@ID"/>
            </svrl:property>
         </svrl:successful-report>
      </xsl:if>
      <xsl:apply-templates select="*" mode="M47"/>
   </xsl:template>
   <xsl:template match="text()" priority="-1" mode="M47"/>
   <xsl:template match="@*|node()" priority="-2" mode="M47">
      <xsl:apply-templates select="*" mode="M47"/>
   </xsl:template>
   <!--PATTERN -->
   <!--RULE -->
   <xsl:template match="mets:xmlData/mods:mods/mods:titleInfo[not(@type='abbreviated')]/mods:title"
                 priority="1000"
                 mode="M48">
      <svrl:fired-rule xmlns:svrl="http://purl.oclc.org/dsdl/svrl"
                       context="mets:xmlData/mods:mods/mods:titleInfo[not(@type='abbreviated')]/mods:title"/>
      <!--ASSERT caution-->
      <xsl:choose>
         <xsl:when test="string-length(text()[1]) &gt; 2"/>
         <xsl:otherwise>
            <svrl:failed-assert xmlns:svrl="http://purl.oclc.org/dsdl/svrl"
                                test="string-length(text()[1]) &gt; 2">
               <xsl:attribute name="id">titleInfo_08</xsl:attribute>
               <xsl:attribute name="role">caution</xsl:attribute>
               <xsl:attribute name="location">
                  <xsl:apply-templates select="." mode="schematron-select-full-path"/>
               </xsl:attribute>
               <svrl:text>Der Text im Element <xsl:text/>mods:title<xsl:text/> im Element <xsl:text/>mods:titleInfo<xsl:text/> besteht aus weniger als drei Zeichen und ist daher wenig aussagekräftig.
Bitte beachten Sie dazu, dass in der DDB jedes <xsl:text/>mets:dmdSec<xsl:text/>-Element ein Objekt erzeugt. Der Wert in <xsl:text/>mods:title<xsl:text/> wird prominent und ggf. unabhängig vom Gesamtobjekt in der Trefferliste angezeigt. Daher erschweren nicht aussagekräftige Objekttitel die Nutzung Ihrer Objekte in der DDB.
Handelt es sich bei dem Text in <xsl:text/>mods:title<xsl:text/> um eine Abkürzung, verwenden Sie bitte das Attribut <xsl:text/>type<xsl:text/> mit dem Wert <xsl:text/>abbreviated<xsl:text/> im Elternelement <xsl:text/>mods:titleInfo<xsl:text/>. Enthält <xsl:text/>mods:title<xsl:text/> eine Bandzählung geben Sie diese im Element <xsl:text/>mods:part<xsl:text/> an.
Nicht aussagekräftige Titel verhindern nicht das Einspielen Ihrer Daten in die DDB, wir bitten Sie jedoch zu prüfen, ob es sich tatsächlich um einen Titel handelt und ggf. die nötigen Korrekturen bis zur nächsten Datenlieferung vorzunehmen.Weitere Informationen zu diesem Element und seinem Kontext finden Sie im DDB-METS/MODS-Anwendungsprofil auf der Seite mods:titleInfo (https://wiki.deutsche-digitale-bibliothek.de/x/xcIeB) und im Bereich Aufbau einer METS/MODS-Datei für die DDB (https://wiki.deutsche-digitale-bibliothek.de/x/VcIeB).</svrl:text>
               <svrl:property id="dmd_id">
                  <xsl:value-of xmlns:sch="http://purl.oclc.org/dsdl/schematron"
                                select="ancestor-or-self::mets:dmdSec/@ID"/>
               </svrl:property>
            </svrl:failed-assert>
         </xsl:otherwise>
      </xsl:choose>
      <xsl:apply-templates select="*" mode="M48"/>
   </xsl:template>
   <xsl:template match="text()" priority="-1" mode="M48"/>
   <xsl:template match="@*|node()" priority="-2" mode="M48">
      <xsl:apply-templates select="*" mode="M48"/>
   </xsl:template>
   <!--PATTERN -->
   <!--RULE -->
   <xsl:template match="mods:titleInfo" priority="1000" mode="M49">
      <svrl:fired-rule xmlns:svrl="http://purl.oclc.org/dsdl/svrl" context="mods:titleInfo"/>
      <!--ASSERT error-->
      <xsl:choose>
         <xsl:when test="mods:title[string-length(text()[1]) &gt; 0]"/>
         <xsl:otherwise>
            <svrl:failed-assert xmlns:svrl="http://purl.oclc.org/dsdl/svrl"
                                test="mods:title[string-length(text()[1]) &gt; 0]">
               <xsl:attribute name="id">titleInfo_09</xsl:attribute>
               <xsl:attribute name="role">error</xsl:attribute>
               <xsl:attribute name="location">
                  <xsl:apply-templates select="." mode="schematron-select-full-path"/>
               </xsl:attribute>
               <svrl:text>Das Element <xsl:text/>mods:titleInfo<xsl:text/> muss das Element <xsl:text/>mods:title<xsl:text/> enthalten.
Ist dies nicht der Fall, wird <xsl:text/>mods:titleInfo<xsl:text/> bei der Transformation des Datensatzes entfernt.Weitere Informationen zu diesem Element finden Sie im DDB-METS/MODS-Anwendungsprofil auf der Seite mods:titleInfo (https://wiki.deutsche-digitale-bibliothek.de/x/xcIeB).</svrl:text>
               <svrl:property id="dmd_id">
                  <xsl:value-of xmlns:sch="http://purl.oclc.org/dsdl/schematron"
                                select="ancestor-or-self::mets:dmdSec/@ID"/>
               </svrl:property>
            </svrl:failed-assert>
         </xsl:otherwise>
      </xsl:choose>
      <xsl:apply-templates select="*" mode="M49"/>
   </xsl:template>
   <xsl:template match="text()" priority="-1" mode="M49"/>
   <xsl:template match="@*|node()" priority="-2" mode="M49">
      <xsl:apply-templates select="*" mode="M49"/>
   </xsl:template>
   <!--PATTERN -->
   <!--RULE -->
   <xsl:template match="mets:xmlData/mods:mods/mods:name" priority="1000" mode="M50">
      <svrl:fired-rule xmlns:svrl="http://purl.oclc.org/dsdl/svrl"
                       context="mets:xmlData/mods:mods/mods:name"/>
      <!--ASSERT error-->
      <xsl:choose>
         <xsl:when test="mods:namePart[string-length(text()[1]) &gt; 0] or mods:displayForm[string-length(text()[1]) &gt; 0]"/>
         <xsl:otherwise>
            <svrl:failed-assert xmlns:svrl="http://purl.oclc.org/dsdl/svrl"
                                test="mods:namePart[string-length(text()[1]) &gt; 0] or mods:displayForm[string-length(text()[1]) &gt; 0]">
               <xsl:attribute name="id">name_01</xsl:attribute>
               <xsl:attribute name="role">error</xsl:attribute>
               <xsl:attribute name="location">
                  <xsl:apply-templates select="." mode="schematron-select-full-path"/>
               </xsl:attribute>
               <svrl:text>Das Element <xsl:text/>mods:name<xsl:text/> muss das Element <xsl:text/>mods:displayForm<xsl:text/> enthalten.
Enthält <xsl:text/>mods:name<xsl:text/> mindestens ein <xsl:text/>mods:namePart<xsl:text/>-Element, wird bei der Bereinigung des Datensatzes aus den entsprechenden <xsl:text/>mods:namePart<xsl:text/> ein <xsl:text/>mods:displayForm<xsl:text/> generiert.
Enthält <xsl:text/>mods:name<xsl:text/> weder <xsl:text/>mods:displayForm<xsl:text/> noch ein <xsl:text/>mods:namePart<xsl:text/> wird <xsl:text/>mods:name<xsl:text/> bei der Bereinigung des Datensatzes entfernt.Weitere Informationen zu diesen Elementen finden Sie im DDB-METS/MODS-Anwendungsprofil auf der Seite mods:name (https://wiki.deutsche-digitale-bibliothek.de/x/ycIeB).</svrl:text>
               <svrl:property id="dmd_id">
                  <xsl:value-of xmlns:sch="http://purl.oclc.org/dsdl/schematron"
                                select="ancestor-or-self::mets:dmdSec/@ID"/>
               </svrl:property>
            </svrl:failed-assert>
         </xsl:otherwise>
      </xsl:choose>
      <xsl:apply-templates select="*" mode="M50"/>
   </xsl:template>
   <xsl:template match="text()" priority="-1" mode="M50"/>
   <xsl:template match="@*|node()" priority="-2" mode="M50">
      <xsl:apply-templates select="*" mode="M50"/>
   </xsl:template>
   <!--PATTERN -->
   <!--RULE -->
   <xsl:template match="mets:xmlData/mods:mods/mods:name" priority="1000" mode="M51">
      <svrl:fired-rule xmlns:svrl="http://purl.oclc.org/dsdl/svrl"
                       context="mets:xmlData/mods:mods/mods:name"/>
      <!--REPORT error-->
      <xsl:if test="mods:displayForm[2]">
         <svrl:successful-report xmlns:svrl="http://purl.oclc.org/dsdl/svrl" test="mods:displayForm[2]">
            <xsl:attribute name="id">name_02</xsl:attribute>
            <xsl:attribute name="role">error</xsl:attribute>
            <xsl:attribute name="location">
               <xsl:apply-templates select="." mode="schematron-select-full-path"/>
            </xsl:attribute>
            <svrl:text>Das Element <xsl:text/>mods:name<xsl:text/> darf das Element <xsl:text/>mods:displayForm<xsl:text/> nur einmal enthalten. Enthält <xsl:text/>mods:name<xsl:text/> mehr als ein <xsl:text/>mods:displayForm<xsl:text/>, wird bei der Transformation des Datensatzes das erste Vorkommen von <xsl:text/>mods:displayForm<xsl:text/> übernommen, alle anderen <xsl:text/>mods:displayForm<xsl:text/> werden entfernt.Weitere Informationen zu diesem Element finden Sie im DDB-METS/MODS-Anwendungsprofil auf der Seite mods:name (https://wiki.deutsche-digitale-bibliothek.de/x/ycIeB).</svrl:text>
            <svrl:property id="dmd_id">
               <xsl:value-of xmlns:sch="http://purl.oclc.org/dsdl/schematron"
                             select="ancestor-or-self::mets:dmdSec/@ID"/>
            </svrl:property>
         </svrl:successful-report>
      </xsl:if>
      <xsl:apply-templates select="*" mode="M51"/>
   </xsl:template>
   <xsl:template match="text()" priority="-1" mode="M51"/>
   <xsl:template match="@*|node()" priority="-2" mode="M51">
      <xsl:apply-templates select="*" mode="M51"/>
   </xsl:template>
   <!--PATTERN -->
   <!--RULE -->
   <xsl:template match="mets:xmlData/mods:mods/mods:name" priority="1000" mode="M52">
      <svrl:fired-rule xmlns:svrl="http://purl.oclc.org/dsdl/svrl"
                       context="mets:xmlData/mods:mods/mods:name"/>
      <!--REPORT caution-->
      <xsl:if test="mods:displayForm[contains(text()[1], ';')] or mods:namePart[contains(text()[1], ';')]">
         <svrl:successful-report xmlns:svrl="http://purl.oclc.org/dsdl/svrl"
                                 test="mods:displayForm[contains(text()[1], ';')] or mods:namePart[contains(text()[1], ';')]">
            <xsl:attribute name="id">name_03</xsl:attribute>
            <xsl:attribute name="role">caution</xsl:attribute>
            <xsl:attribute name="location">
               <xsl:apply-templates select="." mode="schematron-select-full-path"/>
            </xsl:attribute>
            <svrl:text>Das Element <xsl:text/>mods:name<xsl:text/> enthält im Unterelement <xsl:text/>mods:namePart<xsl:text/> bzw. im Unterelement <xsl:text/>mods:displayForm<xsl:text/> ein <xsl:text/>;<xsl:text/> (Semikolon). Dies ist ein Hinweis, dass die Elemente eine Aufzählung enthalten und damit mehrere Personen bzw. Organisationen beschreiben.
Jede Person bzw. Organisation muss in einem eigenen <xsl:text/>mods:name<xsl:text/> mit entsprechenden Unterelementen beschrieben sein. Ist dies nicht der Fall, kann dies zu Fehldarstellungen in der DDB führen.
Ein Semikolon in den o. g. Elementen verhindert nicht das Einspielen des Datensatzes in die DDB, wir bitten Sie jedoch zu prüfen, ob es sich um eine Aufzählung handelt und ggf. die nötigen Korrekturen bis zur nächsten Datenlieferung vorzunehmen.Weitere Informationen zu diesem Element finden Sie im DDB-METS/MODS-Anwendungsprofil auf der Seite mods:name (https://wiki.deutsche-digitale-bibliothek.de/x/ycIeB).</svrl:text>
            <svrl:property id="dmd_id">
               <xsl:value-of xmlns:sch="http://purl.oclc.org/dsdl/schematron"
                             select="ancestor-or-self::mets:dmdSec/@ID"/>
            </svrl:property>
         </svrl:successful-report>
      </xsl:if>
      <xsl:apply-templates select="*" mode="M52"/>
   </xsl:template>
   <xsl:template match="text()" priority="-1" mode="M52"/>
   <xsl:template match="@*|node()" priority="-2" mode="M52">
      <xsl:apply-templates select="*" mode="M52"/>
   </xsl:template>
   <!--PATTERN -->
   <!--RULE -->
   <xsl:template match="mets:xmlData/mods:mods/mods:name[not(@type)]"
                 priority="1001"
                 mode="M53">
      <svrl:fired-rule xmlns:svrl="http://purl.oclc.org/dsdl/svrl"
                       context="mets:xmlData/mods:mods/mods:name[not(@type)]"/>
      <!--ASSERT warn-->
      <xsl:choose>
         <xsl:when test="@type"/>
         <xsl:otherwise>
            <svrl:failed-assert xmlns:svrl="http://purl.oclc.org/dsdl/svrl" test="@type">
               <xsl:attribute name="id">name_04</xsl:attribute>
               <xsl:attribute name="role">warn</xsl:attribute>
               <xsl:attribute name="location">
                  <xsl:apply-templates select="." mode="schematron-select-full-path"/>
               </xsl:attribute>
               <svrl:text>Das Element <xsl:text/>mods:name<xsl:text/> muss das Attribut <xsl:text/>type<xsl:text/> enthalten. Es dient zur Unterscheidung der Art des Namens und erlaubt die folgenden Werte:
 * <xsl:text/>personal<xsl:text/> (Person)
 * <xsl:text/>corporate<xsl:text/> (Organisation)
 * <xsl:text/>family<xsl:text/> (Familie)
 * <xsl:text/>conference<xsl:text/> (Konferenz)
Das Fehlen von <xsl:text/>type<xsl:text/> verhindert nicht das Einspielen des Datensatzes in die DDB, wir bitten Sie jedoch, den Sachverhalt zu prüfen und die nötigen Korrekturen bis zur nächsten Datenlieferung vorzunehmen.Weitere Informationen zu diesem Attribut finden Sie im DDB-METS/MODS-Anwendungsprofil auf der Seite mods:name (https://wiki.deutsche-digitale-bibliothek.de/x/ycIeB).</svrl:text>
               <svrl:property id="dmd_id">
                  <xsl:value-of xmlns:sch="http://purl.oclc.org/dsdl/schematron"
                                select="ancestor-or-self::mets:dmdSec/@ID"/>
               </svrl:property>
            </svrl:failed-assert>
         </xsl:otherwise>
      </xsl:choose>
      <xsl:apply-templates select="*" mode="M53"/>
   </xsl:template>
   <!--RULE -->
   <xsl:template match="mets:xmlData/mods:mods/mods:name" priority="1000" mode="M53">
      <svrl:fired-rule xmlns:svrl="http://purl.oclc.org/dsdl/svrl"
                       context="mets:xmlData/mods:mods/mods:name"/>
      <!--ASSERT warn-->
      <xsl:choose>
         <xsl:when test="@type = ('personal', 'corporate', 'family', 'conference')"/>
         <xsl:otherwise>
            <svrl:failed-assert xmlns:svrl="http://purl.oclc.org/dsdl/svrl"
                                test="@type = ('personal', 'corporate', 'family', 'conference')">
               <xsl:attribute name="id">name_05</xsl:attribute>
               <xsl:attribute name="role">warn</xsl:attribute>
               <xsl:attribute name="location">
                  <xsl:apply-templates select="." mode="schematron-select-full-path"/>
               </xsl:attribute>
               <svrl:text>Das Attribut <xsl:text/>type<xsl:text/> im Element <xsl:text/>mods:name<xsl:text/> darf nur die folgenden Werte enthalten:
 * <xsl:text/>personal<xsl:text/> (Person)
 * <xsl:text/>corporate<xsl:text/> (Organisation)
 * <xsl:text/>family<xsl:text/> (Familie)
 * <xsl:text/>conference<xsl:text/> (Konferenz)
Die Verwendung falscher Attributwerte verhindert nicht das Einspielen des Datensatzes in die DDB, wir bitten Sie jedoch, den Sachverhalt zu prüfen und die nötigen Korrekturen bis zur nächsten Datenlieferung vorzunehmen.Weitere Informationen zu diesem Attribut finden Sie im DDB-METS/MODS-Anwendungsprofil auf der Seite mods:name (https://wiki.deutsche-digitale-bibliothek.de/x/ycIeB).</svrl:text>
               <svrl:property id="dmd_id">
                  <xsl:value-of xmlns:sch="http://purl.oclc.org/dsdl/schematron"
                                select="ancestor-or-self::mets:dmdSec/@ID"/>
               </svrl:property>
               <svrl:property id="type">
                  <xsl:value-of xmlns:sch="http://purl.oclc.org/dsdl/schematron" select="./@type"/>
               </svrl:property>
            </svrl:failed-assert>
         </xsl:otherwise>
      </xsl:choose>
      <xsl:apply-templates select="*" mode="M53"/>
   </xsl:template>
   <xsl:template match="text()" priority="-1" mode="M53"/>
   <xsl:template match="@*|node()" priority="-2" mode="M53">
      <xsl:apply-templates select="*" mode="M53"/>
   </xsl:template>
   <!--PATTERN -->
   <!--RULE -->
   <xsl:template match="mets:xmlData/mods:mods/mods:name[@type='personal']/mods:namePart[not(@type)]"
                 priority="1001"
                 mode="M54">
      <svrl:fired-rule xmlns:svrl="http://purl.oclc.org/dsdl/svrl"
                       context="mets:xmlData/mods:mods/mods:name[@type='personal']/mods:namePart[not(@type)]"/>
      <!--ASSERT warn-->
      <xsl:choose>
         <xsl:when test="@type"/>
         <xsl:otherwise>
            <svrl:failed-assert xmlns:svrl="http://purl.oclc.org/dsdl/svrl" test="@type">
               <xsl:attribute name="id">name_07</xsl:attribute>
               <xsl:attribute name="role">warn</xsl:attribute>
               <xsl:attribute name="location">
                  <xsl:apply-templates select="." mode="schematron-select-full-path"/>
               </xsl:attribute>
               <svrl:text>Das Element <xsl:text/>mods:namePart<xsl:text/> im Element <xsl:text/>mods:name[@type='personal']<xsl:text/> muss das Attribut <xsl:text/>type<xsl:text/> enthalten. Es dient zur Unterscheidung von Bestandteilen eines persönlichen Namens und erlaubt die folgenden Werte:
 * <xsl:text/>family<xsl:text/> (Nachname)
 * <xsl:text/>given<xsl:text/> (Vorname)
 * <xsl:text/>termsOfAddress<xsl:text/> (Titel und andere Namenszusätze)
 * <xsl:text/>date<xsl:text/> (Lebensdaten der Person)
Sollen die Bestandteile eines Namens in der DDB in einer bestimmten Reihenfolge angezeigt werden, verwenden Sie bitte das Element <xsl:text/>mods:displayForm<xsl:text/>.
Das Fehlen von <xsl:text/>type<xsl:text/> verhindert nicht das Einspielen des Datensatzes in die DDB, wir bitten Sie jedoch, den Sachverhalt zu prüfen und die nötigen Korrekturen bis zur nächsten Datenlieferung vorzunehmen.Weitere Informationen zu diesem Attribut und den genannten Elementen finden Sie im DDB-METS/MODS-Anwendungsprofil auf der Seite mods:name (https://wiki.deutsche-digitale-bibliothek.de/x/ycIeB).</svrl:text>
               <svrl:property id="dmd_id">
                  <xsl:value-of xmlns:sch="http://purl.oclc.org/dsdl/schematron"
                                select="ancestor-or-self::mets:dmdSec/@ID"/>
               </svrl:property>
            </svrl:failed-assert>
         </xsl:otherwise>
      </xsl:choose>
      <xsl:apply-templates select="*" mode="M54"/>
   </xsl:template>
   <!--RULE -->
   <xsl:template match="mets:xmlData/mods:mods/mods:name[@type='personal']/mods:namePart"
                 priority="1000"
                 mode="M54">
      <svrl:fired-rule xmlns:svrl="http://purl.oclc.org/dsdl/svrl"
                       context="mets:xmlData/mods:mods/mods:name[@type='personal']/mods:namePart"/>
      <!--ASSERT error-->
      <xsl:choose>
         <xsl:when test="@type = ('date', 'family', 'given', 'termsOfAddress')"/>
         <xsl:otherwise>
            <svrl:failed-assert xmlns:svrl="http://purl.oclc.org/dsdl/svrl"
                                test="@type = ('date', 'family', 'given', 'termsOfAddress')">
               <xsl:attribute name="id">name_08</xsl:attribute>
               <xsl:attribute name="role">error</xsl:attribute>
               <xsl:attribute name="location">
                  <xsl:apply-templates select="." mode="schematron-select-full-path"/>
               </xsl:attribute>
               <svrl:text>Das Attribut <xsl:text/>type<xsl:text/> im Element <xsl:text/>mods:namePart<xsl:text/> darf nur die folgenden Werte enthalten:
 * <xsl:text/>family<xsl:text/> (Nachname)
 * <xsl:text/>given<xsl:text/> (Vorname)
 * <xsl:text/>termsOfAddress<xsl:text/> (Titel und andere Namenszusätze)
 * <xsl:text/>date<xsl:text/> (Lebensdaten der Person)
Enthält <xsl:text/>type<xsl:text/> einen ungültigen Wert, wird <xsl:text/>mods:namePart<xsl:text/> bei der Transformation des Datensatzes entfernt.Weitere Informationen zu diesem Attribut finden Sie im DDB-METS/MODS-Anwendungsprofil auf der Seite mods:name (https://wiki.deutsche-digitale-bibliothek.de/x/ycIeB).</svrl:text>
               <svrl:property id="dmd_id">
                  <xsl:value-of xmlns:sch="http://purl.oclc.org/dsdl/schematron"
                                select="ancestor-or-self::mets:dmdSec/@ID"/>
               </svrl:property>
               <svrl:property id="type">
                  <xsl:value-of xmlns:sch="http://purl.oclc.org/dsdl/schematron" select="./@type"/>
               </svrl:property>
            </svrl:failed-assert>
         </xsl:otherwise>
      </xsl:choose>
      <xsl:apply-templates select="*" mode="M54"/>
   </xsl:template>
   <xsl:template match="text()" priority="-1" mode="M54"/>
   <xsl:template match="@*|node()" priority="-2" mode="M54">
      <xsl:apply-templates select="*" mode="M54"/>
   </xsl:template>
   <!--PATTERN -->
   <!--RULE -->
   <xsl:template match="mets:xmlData/mods:mods/mods:name/mods:*[@valueURI]"
                 priority="1001"
                 mode="M55">
      <svrl:fired-rule xmlns:svrl="http://purl.oclc.org/dsdl/svrl"
                       context="mets:xmlData/mods:mods/mods:name/mods:*[@valueURI]"/>
      <!--REPORT error-->
      <xsl:if test="@valueURI">
         <svrl:successful-report xmlns:svrl="http://purl.oclc.org/dsdl/svrl" test="@valueURI">
            <xsl:attribute name="id">name_09</xsl:attribute>
            <xsl:attribute name="role">error</xsl:attribute>
            <xsl:attribute name="location">
               <xsl:apply-templates select="." mode="schematron-select-full-path"/>
            </xsl:attribute>
            <svrl:text>Das Attribut <xsl:text/>valueURI<xsl:text/> ist in Unterelementen des Elements <xsl:text/>mods:name<xsl:text/> nicht zulässig. Bitte verwenden Sie <xsl:text/>valueURI<xsl:text/> nur in <xsl:text/>mods:name<xsl:text/>.
Enthält ein Unterelement von <xsl:text/>mods:name<xsl:text/>
               <xsl:text/>valueURI<xsl:text/> wird <xsl:text/>valueURI<xsl:text/> bei der Transformation des Datensatzes entfernt.Weitere Informationen zu diesem Attribut finden Sie im DDB-METS/MODS-Anwendungsprofil auf der Seite mods:name (https://wiki.deutsche-digitale-bibliothek.de/x/ycIeB).</svrl:text>
            <svrl:property id="dmd_id">
               <xsl:value-of xmlns:sch="http://purl.oclc.org/dsdl/schematron"
                             select="ancestor-or-self::mets:dmdSec/@ID"/>
            </svrl:property>
         </svrl:successful-report>
      </xsl:if>
      <xsl:apply-templates select="*" mode="M55"/>
   </xsl:template>
   <!--RULE -->
   <xsl:template match="mets:xmlData/mods:mods/mods:name[@valueURI]"
                 priority="1000"
                 mode="M55">
      <svrl:fired-rule xmlns:svrl="http://purl.oclc.org/dsdl/svrl"
                       context="mets:xmlData/mods:mods/mods:name[@valueURI]"/>
      <!--ASSERT error-->
      <xsl:choose>
         <xsl:when test="starts-with(@valueURI, 'http://d-nb.info/gnd/') or starts-with(@valueURI, 'https://d-nb.info/gnd/')"/>
         <xsl:otherwise>
            <svrl:failed-assert xmlns:svrl="http://purl.oclc.org/dsdl/svrl"
                                test="starts-with(@valueURI, 'http://d-nb.info/gnd/') or starts-with(@valueURI, 'https://d-nb.info/gnd/')">
               <xsl:attribute name="id">name_10</xsl:attribute>
               <xsl:attribute name="role">error</xsl:attribute>
               <xsl:attribute name="location">
                  <xsl:apply-templates select="." mode="schematron-select-full-path"/>
               </xsl:attribute>
               <svrl:text>Die DDB wertet das Attribut <xsl:text/>valueURI<xsl:text/> im Element <xsl:text/>mods:name<xsl:text/> nur aus, wenn es im einen GND-URI enthält. Ist dies nicht der Fall, wird <xsl:text/>valueURI<xsl:text/> bei der Transformation des Datensatzes entfernt.Weitere Informationen zu diesem Attribut finden Sie im DDB-METS/MODS-Anwendungsprofil auf der Seite mods:name (https://wiki.deutsche-digitale-bibliothek.de/x/ycIeB).</svrl:text>
               <svrl:property id="dmd_id">
                  <xsl:value-of xmlns:sch="http://purl.oclc.org/dsdl/schematron"
                                select="ancestor-or-self::mets:dmdSec/@ID"/>
               </svrl:property>
            </svrl:failed-assert>
         </xsl:otherwise>
      </xsl:choose>
      <xsl:apply-templates select="*" mode="M55"/>
   </xsl:template>
   <xsl:template match="text()" priority="-1" mode="M55"/>
   <xsl:template match="@*|node()" priority="-2" mode="M55">
      <xsl:apply-templates select="*" mode="M55"/>
   </xsl:template>
   <!--PATTERN -->
   <!--RULE -->
   <xsl:template match="mets:xmlData/mods:mods/mods:name[not(mods:role/mods:roleTerm)]"
                 priority="1002"
                 mode="M56">
      <svrl:fired-rule xmlns:svrl="http://purl.oclc.org/dsdl/svrl"
                       context="mets:xmlData/mods:mods/mods:name[not(mods:role/mods:roleTerm)]"/>
      <!--ASSERT warn-->
      <xsl:choose>
         <xsl:when test="mods:role/mods:roleTerm"/>
         <xsl:otherwise>
            <svrl:failed-assert xmlns:svrl="http://purl.oclc.org/dsdl/svrl" test="mods:role/mods:roleTerm">
               <xsl:attribute name="id">name_11</xsl:attribute>
               <xsl:attribute name="role">warn</xsl:attribute>
               <xsl:attribute name="location">
                  <xsl:apply-templates select="." mode="schematron-select-full-path"/>
               </xsl:attribute>
               <svrl:text>Das Element <xsl:text/>mods:name<xsl:text/> muss mindestens ein <xsl:text/>mods:role<xsl:text/>-Element mit dem Unterelement <xsl:text/>mods:roleTerm<xsl:text/> mit einem gültigen MARC Relator Code (http://id.loc.gov/vocabulary/relators) enthalten.
Fehlt <xsl:text/>mods:role<xsl:text/> mit dem Unterelement <xsl:text/>mods:roleTerm<xsl:text/> wird bei der Transformation des Datensatzes ein <xsl:text/>mods:role<xsl:text/> mit dem Unterelement <xsl:text/>mods:roleTerm<xsl:text/> mit dem Wert <xsl:text/>ctb<xsl:text/> (contributor) erzeugt.Weitere Informationen zu diesen Elementen finden Sie im DDB-METS/MODS-Anwendungsprofil auf der Seite mods:name (https://wiki.deutsche-digitale-bibliothek.de/x/ycIeB).</svrl:text>
               <svrl:property id="dmd_id">
                  <xsl:value-of xmlns:sch="http://purl.oclc.org/dsdl/schematron"
                                select="ancestor-or-self::mets:dmdSec/@ID"/>
               </svrl:property>
            </svrl:failed-assert>
         </xsl:otherwise>
      </xsl:choose>
      <xsl:apply-templates select="*" mode="M56"/>
   </xsl:template>
   <!--RULE -->
   <xsl:template match="mets:xmlData/mods:mods/mods:name/mods:role[not(mods:roleTerm[@type='code'][@authority='marcrelator'])]"
                 priority="1001"
                 mode="M56">
      <svrl:fired-rule xmlns:svrl="http://purl.oclc.org/dsdl/svrl"
                       context="mets:xmlData/mods:mods/mods:name/mods:role[not(mods:roleTerm[@type='code'][@authority='marcrelator'])]"/>
      <!--ASSERT error-->
      <xsl:choose>
         <xsl:when test="mods:roleTerm[@type='code'][@authority='marcrelator']"/>
         <xsl:otherwise>
            <svrl:failed-assert xmlns:svrl="http://purl.oclc.org/dsdl/svrl"
                                test="mods:roleTerm[@type='code'][@authority='marcrelator']">
               <xsl:attribute name="id">name_12</xsl:attribute>
               <xsl:attribute name="role">error</xsl:attribute>
               <xsl:attribute name="location">
                  <xsl:apply-templates select="." mode="schematron-select-full-path"/>
               </xsl:attribute>
               <svrl:text>Das Element <xsl:text/>mods:name<xsl:text/> muss mindestens ein <xsl:text/>mods:role<xsl:text/>-Element mit einem gültigen Unterelement <xsl:text/>mods:roleTerm<xsl:text/> enthalten. Ein gültiges <xsl:text/>mods:roleTerm<xsl:text/> muss die Attribute <xsl:text/>type<xsl:text/> mit dem Wert <xsl:text/>code<xsl:text/> und <xsl:text/>authority<xsl:text/> mit dem Wert <xsl:text/>marcrelator<xsl:text/> enthalten.
Ist dies nicht der Fall, wird <xsl:text/>mods:role<xsl:text/> bei der Transformation des Datensatzes entfernt und ein <xsl:text/>mods:role<xsl:text/> mit dem Unterelement <xsl:text/>mods:roleTerm<xsl:text/> mit dem Wert <xsl:text/>ctb<xsl:text/> (contributor) erzeugt.Weitere Informationen zu diesen Elementen und Attributen finden Sie im DDB-METS/MODS-Anwendungsprofil auf der Seite mods:name (https://wiki.deutsche-digitale-bibliothek.de/x/ycIeB).</svrl:text>
               <svrl:property id="dmd_id">
                  <xsl:value-of xmlns:sch="http://purl.oclc.org/dsdl/schematron"
                                select="ancestor-or-self::mets:dmdSec/@ID"/>
               </svrl:property>
            </svrl:failed-assert>
         </xsl:otherwise>
      </xsl:choose>
      <xsl:apply-templates select="*" mode="M56"/>
   </xsl:template>
   <!--RULE -->
   <xsl:template match="mets:xmlData/mods:mods/mods:name/mods:role/mods:roleTerm[@type='code'][@authority='marcrelator']"
                 priority="1000"
                 mode="M56">
      <svrl:fired-rule xmlns:svrl="http://purl.oclc.org/dsdl/svrl"
                       context="mets:xmlData/mods:mods/mods:name/mods:role/mods:roleTerm[@type='code'][@authority='marcrelator']"/>
      <!--ASSERT error-->
      <xsl:choose>
         <xsl:when test="key('marc_relator_codes', text()[1], $marc_relator_codes)"/>
         <xsl:otherwise>
            <svrl:failed-assert xmlns:svrl="http://purl.oclc.org/dsdl/svrl"
                                test="key('marc_relator_codes', text()[1], $marc_relator_codes)">
               <xsl:attribute name="id">name_13</xsl:attribute>
               <xsl:attribute name="role">error</xsl:attribute>
               <xsl:attribute name="location">
                  <xsl:apply-templates select="." mode="schematron-select-full-path"/>
               </xsl:attribute>
               <svrl:text>Das Element <xsl:text/>mods:role<xsl:text/> muss ein <xsl:text/>mods:roleTerm<xsl:text/>-Element mit einem Wert aus dem MARC Relator Code Vokabular (http://id.loc.gov/vocabulary/relators) enthalten.
Ist dies nicht der Fall, wird <xsl:text/>mods:role<xsl:text/> bei der Transformation des Datensatzes entfernt und ein <xsl:text/>mods:role<xsl:text/> mit dem Unterelement <xsl:text/>mods:roleTerm<xsl:text/> mit dem Wert <xsl:text/>ctb<xsl:text/> (contributor) erzeugt.Weitere Informationen zu diesem Element finden Sie im DDB-METS/MODS-Anwendungsprofil auf der Seite mods:name (https://wiki.deutsche-digitale-bibliothek.de/x/ycIeB).</svrl:text>
               <svrl:property id="dmd_id">
                  <xsl:value-of xmlns:sch="http://purl.oclc.org/dsdl/schematron"
                                select="ancestor-or-self::mets:dmdSec/@ID"/>
               </svrl:property>
               <svrl:property id="text">
                  <xsl:value-of xmlns:sch="http://purl.oclc.org/dsdl/schematron" select="text()"/>
               </svrl:property>
            </svrl:failed-assert>
         </xsl:otherwise>
      </xsl:choose>
      <xsl:apply-templates select="*" mode="M56"/>
   </xsl:template>
   <xsl:template match="text()" priority="-1" mode="M56"/>
   <xsl:template match="@*|node()" priority="-2" mode="M56">
      <xsl:apply-templates select="*" mode="M56"/>
   </xsl:template>
   <!--PATTERN -->
   <!--RULE -->
   <xsl:template match="mets:xmlData/mods:mods/mods:originInfo"
                 priority="1000"
                 mode="M57">
      <svrl:fired-rule xmlns:svrl="http://purl.oclc.org/dsdl/svrl"
                       context="mets:xmlData/mods:mods/mods:originInfo"/>
      <!--REPORT error-->
      <xsl:if test="mods:dateIssued[not(@point)][position() &gt; 1] or mods:dateCreated[not(@point)][position() &gt; 1] or mods:dateCaptured[not(@point)][position() &gt; 1] or (mods:dateIssued[not(@point)] and mods:dateIssued[@point]) or (mods:dateCreated[not(@point)] and mods:dateCreated[@point]) or (mods:dateCaptured[not(@point)] and mods:dateCaptured[@point])">
         <svrl:successful-report xmlns:svrl="http://purl.oclc.org/dsdl/svrl"
                                 test="mods:dateIssued[not(@point)][position() &gt; 1] or mods:dateCreated[not(@point)][position() &gt; 1] or mods:dateCaptured[not(@point)][position() &gt; 1] or (mods:dateIssued[not(@point)] and mods:dateIssued[@point]) or (mods:dateCreated[not(@point)] and mods:dateCreated[@point]) or (mods:dateCaptured[not(@point)] and mods:dateCaptured[@point])">
            <xsl:attribute name="id">originInfo_01</xsl:attribute>
            <xsl:attribute name="role">error</xsl:attribute>
            <xsl:attribute name="location">
               <xsl:apply-templates select="." mode="schematron-select-full-path"/>
            </xsl:attribute>
            <svrl:text>Die Elemente <xsl:text/>mods:dateIssued<xsl:text/>, <xsl:text/>mods:dateCreated<xsl:text/> und <xsl:text/>mods:dateCaptured<xsl:text/> im Element <xsl:text/>mods:originInfo<xsl:text/> dürfen ohne das Attribut <xsl:text/>point<xsl:text/> mit den Werten <xsl:text/>start<xsl:text/> und <xsl:text/>end<xsl:text/> nicht wiederholt werden.
Fehlt <xsl:text/>point<xsl:text/> in den o. g. Elementen wird bei der Transformation des Datensatzes das erste Vorkommen des jeweiligen Elements übernommen und alle anderen entfernt.
Bitte nutzen Sie für Zeitangaben in textlicher Form das Element <xsl:text/>mods:displayDate<xsl:text/>.Weitere Informationen zu diesen Elementen und Attributen finden Sie im DDB-METS/MODS-Anwendungsprofil auf der Seite mods:originInfo (https://wiki.deutsche-digitale-bibliothek.de/x/DcMeB).</svrl:text>
            <svrl:property id="dmd_id">
               <xsl:value-of xmlns:sch="http://purl.oclc.org/dsdl/schematron"
                             select="ancestor-or-self::mets:dmdSec/@ID"/>
            </svrl:property>
         </svrl:successful-report>
      </xsl:if>
      <xsl:apply-templates select="*" mode="M57"/>
   </xsl:template>
   <xsl:template match="text()" priority="-1" mode="M57"/>
   <xsl:template match="@*|node()" priority="-2" mode="M57">
      <xsl:apply-templates select="*" mode="M57"/>
   </xsl:template>
   <!--PATTERN -->
   <!--RULE -->
   <xsl:template match="mets:xmlData/mods:mods/mods:originInfo[ not(@eventType='digitization' or mods:edition[text()= '[Electronic ed.]']) and not(mods:dateIssued or mods:dateCreated) ]"
                 priority="1001"
                 mode="M58">
      <svrl:fired-rule xmlns:svrl="http://purl.oclc.org/dsdl/svrl"
                       context="mets:xmlData/mods:mods/mods:originInfo[ not(@eventType='digitization' or mods:edition[text()= '[Electronic ed.]']) and not(mods:dateIssued or mods:dateCreated) ]"/>
      <!--ASSERT warn-->
      <xsl:choose>
         <xsl:when test="mods:dateIssued or mods:dateCreated"/>
         <xsl:otherwise>
            <svrl:failed-assert xmlns:svrl="http://purl.oclc.org/dsdl/svrl"
                                test="mods:dateIssued or mods:dateCreated">
               <xsl:attribute name="id">originInfo_17</xsl:attribute>
               <xsl:attribute name="role">warn</xsl:attribute>
               <xsl:attribute name="location">
                  <xsl:apply-templates select="." mode="schematron-select-full-path"/>
               </xsl:attribute>
               <svrl:text>Das Element <xsl:text/>mods:originInfo<xsl:text/>, das nicht die Angaben zur Digitalisierung des Dokuments enthält, muss das Element <xsl:text/>mods:dateIssued<xsl:text/> oder das Element <xsl:text/>mods:dateCreated<xsl:text/> mit einem ISO 8601-konformen Wert enthalten.
Das Fehlen eines ISO 8601-konformen Wertes verhindert nicht das Einspielen des Datensatzes in die DDB, führt aber u. a. zu Problemen bei der Filterung von Suchergebnissen nach Datumsangabe zu Problemen. Wir bitten Sie daher, den Sachverhalt zu prüfen und die nötigen Korrekturen bis zur nächsten Datenlieferung vorzunehmen.Weitere Informationen zu diesen Elementen finden Sie im DDB-METS/MODS-Anwendungsprofil auf der Seite mods:originInfo (https://wiki.deutsche-digitale-bibliothek.de/x/DcMeB).</svrl:text>
               <svrl:property id="dmd_id">
                  <xsl:value-of xmlns:sch="http://purl.oclc.org/dsdl/schematron"
                                select="ancestor-or-self::mets:dmdSec/@ID"/>
               </svrl:property>
            </svrl:failed-assert>
         </xsl:otherwise>
      </xsl:choose>
      <xsl:apply-templates select="*" mode="M58"/>
   </xsl:template>
   <!--RULE -->
   <xsl:template match="mets:xmlData/mods:mods/mods:originInfo/mods:dateIssued | mets:xmlData/mods:mods/mods:originInfo/mods:dateCreated"
                 priority="1000"
                 mode="M58">
      <svrl:fired-rule xmlns:svrl="http://purl.oclc.org/dsdl/svrl"
                       context="mets:xmlData/mods:mods/mods:originInfo/mods:dateIssued | mets:xmlData/mods:mods/mods:originInfo/mods:dateCreated"/>
      <!--ASSERT warn-->
      <xsl:choose>
         <xsl:when test="matches(text()[1], '^((-\d\d\d\d+)|(\d\d\d\d))(-\d\d)?(-\d\d)?$')"/>
         <xsl:otherwise>
            <svrl:failed-assert xmlns:svrl="http://purl.oclc.org/dsdl/svrl"
                                test="matches(text()[1], '^((-\d\d\d\d+)|(\d\d\d\d))(-\d\d)?(-\d\d)?$')">
               <xsl:attribute name="id">originInfo_02</xsl:attribute>
               <xsl:attribute name="role">warn</xsl:attribute>
               <xsl:attribute name="location">
                  <xsl:apply-templates select="." mode="schematron-select-full-path"/>
               </xsl:attribute>
               <svrl:text>Die Elemente <xsl:text/>mods:dateIssued<xsl:text/> bzw. <xsl:text/>mods:dateCreated<xsl:text/> müssen einen ISO 8601-konformen Wert enthalten. Bitte nutzen Sie für unsichere bzw. ungenaue Zeitangaben in textlicher Form das Element <xsl:text/>mods:displayDate<xsl:text/>.
Die Verwendung von nicht ISO 8601-konformen Werten in <xsl:text/>mods:dateIssued<xsl:text/> bzw. <xsl:text/>mods:dateCreated<xsl:text/> kann zu Informationsverlusten und Einschränkungen in der Suche in der DDB führen.Weitere Informationen zu diesen Elementen finden Sie im DDB-METS/MODS-Anwendungsprofil auf der Seite mods:originInfo (https://wiki.deutsche-digitale-bibliothek.de/x/DcMeB).</svrl:text>
               <svrl:property id="dmd_id">
                  <xsl:value-of xmlns:sch="http://purl.oclc.org/dsdl/schematron"
                                select="ancestor-or-self::mets:dmdSec/@ID"/>
               </svrl:property>
            </svrl:failed-assert>
         </xsl:otherwise>
      </xsl:choose>
      <xsl:apply-templates select="*" mode="M58"/>
   </xsl:template>
   <xsl:template match="text()" priority="-1" mode="M58"/>
   <xsl:template match="@*|node()" priority="-2" mode="M58">
      <xsl:apply-templates select="*" mode="M58"/>
   </xsl:template>
   <!--PATTERN -->
   <!--RULE -->
   <xsl:template match="mets:xmlData/mods:mods/mods:originInfo/mods:place/mods:placeTerm"
                 priority="1000"
                 mode="M59">
      <svrl:fired-rule xmlns:svrl="http://purl.oclc.org/dsdl/svrl"
                       context="mets:xmlData/mods:mods/mods:originInfo/mods:place/mods:placeTerm"/>
      <!--REPORT caution-->
      <xsl:if test="contains(text()[1], ';')">
         <svrl:successful-report xmlns:svrl="http://purl.oclc.org/dsdl/svrl" test="contains(text()[1], ';')">
            <xsl:attribute name="id">originInfo_03</xsl:attribute>
            <xsl:attribute name="role">caution</xsl:attribute>
            <xsl:attribute name="location">
               <xsl:apply-templates select="." mode="schematron-select-full-path"/>
            </xsl:attribute>
            <svrl:text>Das Element <xsl:text/>mods:place<xsl:text/> enthält im Unterelement <xsl:text/>mods:placeTerm<xsl:text/> ein <xsl:text/>;<xsl:text/> (Semikolon). Dies ist ein Hinweis, dass das Element eine Aufzählung enthält und damit mehrere Orte beschreibt.
Jeder Ort muss in einem eigenen <xsl:text/>mods:place<xsl:text/> mit dem Unterelement <xsl:text/>mods:placeTerm<xsl:text/> beschrieben sein. Ist dies nicht der Fall, kann dies zu Fehldarstellungen in der DDB führen.
Ein Semikolon im <xsl:text/>mods:placeTerm<xsl:text/> verhindert nicht das Einspielen des Datensatzes in die DDB, wir bitten Sie jedoch zu prüfen, ob es sich um eine Aufzählung handelt und ggf. die nötigen Korrekturen bis zur nächsten Datenlieferung vorzunehmen.Weitere Informationen zu diesen Elementen finden Sie im DDB-METS/MODS-Anwendungsprofil auf der Seite mods:originInfo (https://wiki.deutsche-digitale-bibliothek.de/x/DcMeB).</svrl:text>
            <svrl:property id="dmd_id">
               <xsl:value-of xmlns:sch="http://purl.oclc.org/dsdl/schematron"
                             select="ancestor-or-self::mets:dmdSec/@ID"/>
            </svrl:property>
         </svrl:successful-report>
      </xsl:if>
      <xsl:apply-templates select="*" mode="M59"/>
   </xsl:template>
   <xsl:template match="text()" priority="-1" mode="M59"/>
   <xsl:template match="@*|node()" priority="-2" mode="M59">
      <xsl:apply-templates select="*" mode="M59"/>
   </xsl:template>
   <!--PATTERN -->
   <!--RULE -->
   <xsl:template match="mets:xmlData/mods:mods/mods:originInfo/mods:place/mods:placeTerm"
                 priority="1000"
                 mode="M60">
      <svrl:fired-rule xmlns:svrl="http://purl.oclc.org/dsdl/svrl"
                       context="mets:xmlData/mods:mods/mods:originInfo/mods:place/mods:placeTerm"/>
      <!--REPORT caution-->
      <xsl:if test="contains(text()[1], ':')">
         <svrl:successful-report xmlns:svrl="http://purl.oclc.org/dsdl/svrl" test="contains(text()[1], ':')">
            <xsl:attribute name="id">originInfo_04</xsl:attribute>
            <xsl:attribute name="role">caution</xsl:attribute>
            <xsl:attribute name="location">
               <xsl:apply-templates select="." mode="schematron-select-full-path"/>
            </xsl:attribute>
            <svrl:text>Das Element <xsl:text/>mods:place<xsl:text/> enthält im Unterelement <xsl:text/>mods:placeTerm<xsl:text/> einen <xsl:text/>:<xsl:text/> (Doppelpunkt). Dies ist ein Hinweis, dass das Element auch Angaben zu Verlegern enthält.
<xsl:text/>mods:place<xsl:text/> darf nur Angaben zu einem Ort enthalten. Verwenden Sie für Informationen zu einem Verleger das Element <xsl:text/>mods:publisher<xsl:text/> und wiederholen Sie es ggf. für weitere Verleger.
Angaben zu Verlegen in <xsl:text/>mods:placeTerm<xsl:text/> können zu Fehldarstellungen in der DDB führen.
Ein Doppelpunkt im <xsl:text/>mods:placeTerm<xsl:text/> verhindert nicht das Einspielen des Datensatzes in die DDB, wir bitten Sie jedoch zu prüfen, ob es sich um Angaben zu Verlegern handelt und ggf. die nötigen Korrekturen bis zur nächsten Datenlieferung vorzunehmen.Weitere Informationen zu diesen Elementen finden Sie im DDB-METS/MODS-Anwendungsprofil auf der Seite mods:originInfo (https://wiki.deutsche-digitale-bibliothek.de/x/DcMeB).</svrl:text>
            <svrl:property id="dmd_id">
               <xsl:value-of xmlns:sch="http://purl.oclc.org/dsdl/schematron"
                             select="ancestor-or-self::mets:dmdSec/@ID"/>
            </svrl:property>
         </svrl:successful-report>
      </xsl:if>
      <xsl:apply-templates select="*" mode="M60"/>
   </xsl:template>
   <xsl:template match="text()" priority="-1" mode="M60"/>
   <xsl:template match="@*|node()" priority="-2" mode="M60">
      <xsl:apply-templates select="*" mode="M60"/>
   </xsl:template>
   <!--PATTERN -->
   <!--RULE -->
   <xsl:template match="mets:xmlData/mods:mods/mods:originInfo[ not(../mods:originInfo[@eventType='digitization'] or ../mods:originInfo[mods:edition[text()[1] = '[Electronic ed.]']]) and (mods:dateIssued[number(substring(text()[1], 1, 4)) &gt; 1999] or mods:dateCreated[number(substring(text()[1], 1, 4)) &gt; 1999]) ]"
                 priority="1000"
                 mode="M61">
      <svrl:fired-rule xmlns:svrl="http://purl.oclc.org/dsdl/svrl"
                       context="mets:xmlData/mods:mods/mods:originInfo[ not(../mods:originInfo[@eventType='digitization'] or ../mods:originInfo[mods:edition[text()[1] = '[Electronic ed.]']]) and (mods:dateIssued[number(substring(text()[1], 1, 4)) &gt; 1999] or mods:dateCreated[number(substring(text()[1], 1, 4)) &gt; 1999]) ]"/>
      <!--ASSERT caution-->
      <xsl:choose>
         <xsl:when test="mods:edition[text()[1] = '[Electronic ed.]'] or ./@eventType='digitization'"/>
         <xsl:otherwise>
            <svrl:failed-assert xmlns:svrl="http://purl.oclc.org/dsdl/svrl"
                                test="mods:edition[text()[1] = '[Electronic ed.]'] or ./@eventType='digitization'">
               <xsl:attribute name="id">originInfo_05</xsl:attribute>
               <xsl:attribute name="role">caution</xsl:attribute>
               <xsl:attribute name="location">
                  <xsl:apply-templates select="." mode="schematron-select-full-path"/>
               </xsl:attribute>
               <svrl:text>Der Wert im Unterelement <xsl:text/>mods:dateIssued<xsl:text/> bzw. im Unterelement <xsl:text/>mods:dateCreated<xsl:text/> von <xsl:text/>mods:originInfo<xsl:text/> enthält das Jahr 2000 oder später. Dies deutet darauf hin, dass sich <xsl:text/>mods:originInfo<xsl:text/> nicht auf die Veröffentlichung bzw. Entstehung sondern die Digitalisierung des Dokuments bezieht.
Bitte verwenden Sie für die Angaben zur Digitalisierung ein eigenes  <xsl:text/>mods:originInfo<xsl:text/> mit dem Attribut <xsl:text/>eventType<xsl:text/> mit dem Wert <xsl:text/>digitization<xsl:text/> sowie dem Unterelement <xsl:text/>mods:edition<xsl:text/> mit dem Wert <xsl:text/>[Electronic ed.]<xsl:text/>.
Dieser Fehler verhindert nicht das Einspielen des Datensatzes in die DDB, wir bitten Sie jedoch, zu prüfen, ob es sich tatsächlich um die richtige Datumsangabe handelt und ggf. die nötigen Korrekturen bis zur nächsten Datenlieferung vorzunehmen.Weitere Informationen zu diesen Elementen finden Sie im DDB-METS/MODS-Anwendungsprofil auf der Seite mods:originInfo (https://wiki.deutsche-digitale-bibliothek.de/x/DcMeB).</svrl:text>
               <svrl:property id="dmd_id">
                  <xsl:value-of xmlns:sch="http://purl.oclc.org/dsdl/schematron"
                                select="ancestor-or-self::mets:dmdSec/@ID"/>
               </svrl:property>
            </svrl:failed-assert>
         </xsl:otherwise>
      </xsl:choose>
      <xsl:apply-templates select="*" mode="M61"/>
   </xsl:template>
   <xsl:template match="text()" priority="-1" mode="M61"/>
   <xsl:template match="@*|node()" priority="-2" mode="M61">
      <xsl:apply-templates select="*" mode="M61"/>
   </xsl:template>
   <!--PATTERN -->
   <!--RULE -->
   <xsl:template match="mets:xmlData/mods:mods/mods:originInfo/mods:place"
                 priority="1000"
                 mode="M62">
      <svrl:fired-rule xmlns:svrl="http://purl.oclc.org/dsdl/svrl"
                       context="mets:xmlData/mods:mods/mods:originInfo/mods:place"/>
      <!--ASSERT error-->
      <xsl:choose>
         <xsl:when test="mods:placeTerm[@type='text']"/>
         <xsl:otherwise>
            <svrl:failed-assert xmlns:svrl="http://purl.oclc.org/dsdl/svrl"
                                test="mods:placeTerm[@type='text']">
               <xsl:attribute name="id">originInfo_06</xsl:attribute>
               <xsl:attribute name="role">error</xsl:attribute>
               <xsl:attribute name="location">
                  <xsl:apply-templates select="." mode="schematron-select-full-path"/>
               </xsl:attribute>
               <svrl:text>Das Element <xsl:text/>mods:place<xsl:text/> muss das Unterelement <xsl:text/>mods:placeTerm<xsl:text/> mit dem Attribut <xsl:text/>type<xsl:text/> mit dem Wert <xsl:text/>text<xsl:text/> enthalten. Ist dies nicht der Fall, wird <xsl:text/>mods:place<xsl:text/> bei der Transformation des Datensatzes entfernt.Weitere Informationen zu diesen Elementen und dem Attribut finden Sie im DDB-METS/MODS-Anwendungsprofil auf der Seite mods:originInfo (https://wiki.deutsche-digitale-bibliothek.de/x/DcMeB).</svrl:text>
               <svrl:property id="dmd_id">
                  <xsl:value-of xmlns:sch="http://purl.oclc.org/dsdl/schematron"
                                select="ancestor-or-self::mets:dmdSec/@ID"/>
               </svrl:property>
            </svrl:failed-assert>
         </xsl:otherwise>
      </xsl:choose>
      <xsl:apply-templates select="*" mode="M62"/>
   </xsl:template>
   <xsl:template match="text()" priority="-1" mode="M62"/>
   <xsl:template match="@*|node()" priority="-2" mode="M62">
      <xsl:apply-templates select="*" mode="M62"/>
   </xsl:template>
   <!--PATTERN -->
   <!--RULE -->
   <xsl:template match="mets:xmlData/mods:mods/mods:originInfo/mods:*[local-name() = ('dateIssued', 'dateCreated', 'dateOther') and namespace-uri() = 'http://www.loc.gov/mods/v3'][@point]"
                 priority="1000"
                 mode="M63">
      <svrl:fired-rule xmlns:svrl="http://purl.oclc.org/dsdl/svrl"
                       context="mets:xmlData/mods:mods/mods:originInfo/mods:*[local-name() = ('dateIssued', 'dateCreated', 'dateOther') and namespace-uri() = 'http://www.loc.gov/mods/v3'][@point]"/>
      <xsl:variable name="point" select="./@point"/>
      <xsl:variable name="name" select="./local-name()"/>
      <!--REPORT error-->
      <xsl:if test="./preceding-sibling::*[local-name() = $name and namespace-uri() = 'http://www.loc.gov/mods/v3'][@point = $point]">
         <svrl:successful-report xmlns:svrl="http://purl.oclc.org/dsdl/svrl"
                                 test="./preceding-sibling::*[local-name() = $name and namespace-uri() = 'http://www.loc.gov/mods/v3'][@point = $point]">
            <xsl:attribute name="id">originInfo_15</xsl:attribute>
            <xsl:attribute name="role">error</xsl:attribute>
            <xsl:attribute name="location">
               <xsl:apply-templates select="." mode="schematron-select-full-path"/>
            </xsl:attribute>
            <svrl:text>Die Unterelemente <xsl:text/>mods:dateIssued<xsl:text/>, <xsl:text/>mods:dateCreated<xsl:text/> und <xsl:text/>mods:dateOther<xsl:text/> im Element <xsl:text/>mods:originInfo<xsl:text/> dürfen nicht mit dem gleichen Wert im Attribut <xsl:text/>point<xsl:text/> wiederholt werden.
Wird eines der o. g. Elemente mit dem identischen Wert in <xsl:text/>point<xsl:text/> wiederholt, werden alle weiteren Vorkommen (XML-Reihenfolge) bei der Transformation des Datensatzes entfernt.Weitere Informationen zu diesen Elementen und Attributen finden Sie im DDB-METS/MODS-Anwendungsprofil auf der Seite mods:originInfo (https://wiki.deutsche-digitale-bibliothek.de/x/DcMeB).</svrl:text>
            <svrl:property id="dmd_id">
               <xsl:value-of xmlns:sch="http://purl.oclc.org/dsdl/schematron"
                             select="ancestor-or-self::mets:dmdSec/@ID"/>
            </svrl:property>
         </svrl:successful-report>
      </xsl:if>
      <xsl:apply-templates select="*" mode="M63"/>
   </xsl:template>
   <xsl:template match="text()" priority="-1" mode="M63"/>
   <xsl:template match="@*|node()" priority="-2" mode="M63">
      <xsl:apply-templates select="*" mode="M63"/>
   </xsl:template>
   <!--PATTERN -->
   <!--RULE -->
   <xsl:template match="mods:mods/mods:originInfo" priority="1000" mode="M64">
      <svrl:fired-rule xmlns:svrl="http://purl.oclc.org/dsdl/svrl"
                       context="mods:mods/mods:originInfo"/>
      <!--REPORT error-->
      <xsl:if test="mods:displayDate[2]">
         <svrl:successful-report xmlns:svrl="http://purl.oclc.org/dsdl/svrl" test="mods:displayDate[2]">
            <xsl:attribute name="id">originInfo_16</xsl:attribute>
            <xsl:attribute name="role">error</xsl:attribute>
            <xsl:attribute name="location">
               <xsl:apply-templates select="." mode="schematron-select-full-path"/>
            </xsl:attribute>
            <svrl:text>Das Element <xsl:text/>mods:displayDate<xsl:text/> darf innerhalb des Elements <xsl:text/>mods:originInfo<xsl:text/> nicht wiederholt werden. Enthält <xsl:text/>mods:originInfo<xsl:text/> mehr als ein <xsl:text/>mods:displayDate<xsl:text/>, wird das erste Vorkommen (XML-Reihenfolge) übernommen, alle anderen werden bei der Transformation des Datensatzes entfernt.Weitere Informationen zu diesem Element finden Sie im DDB-METS/MODS-Anwendungsprofil auf der Seite mods:originInfo (https://wiki.deutsche-digitale-bibliothek.de/x/DcMeB).</svrl:text>
            <svrl:property id="dmd_id">
               <xsl:value-of xmlns:sch="http://purl.oclc.org/dsdl/schematron"
                             select="ancestor-or-self::mets:dmdSec/@ID"/>
            </svrl:property>
         </svrl:successful-report>
      </xsl:if>
      <xsl:apply-templates select="*" mode="M64"/>
   </xsl:template>
   <xsl:template match="text()" priority="-1" mode="M64"/>
   <xsl:template match="@*|node()" priority="-2" mode="M64">
      <xsl:apply-templates select="*" mode="M64"/>
   </xsl:template>
   <!--PATTERN -->
   <!--RULE -->
   <xsl:template match="mets:xmlData/mods:mods" priority="1000" mode="M65">
      <svrl:fired-rule xmlns:svrl="http://purl.oclc.org/dsdl/svrl"
                       context="mets:xmlData/mods:mods"/>
      <!--REPORT error-->
      <xsl:if test="mods:originInfo[@eventType='digitization' or mods:edition[text()[1] = '[Electronic ed.]']][2]">
         <svrl:successful-report xmlns:svrl="http://purl.oclc.org/dsdl/svrl"
                                 test="mods:originInfo[@eventType='digitization' or mods:edition[text()[1] = '[Electronic ed.]']][2]">
            <xsl:attribute name="id">originInfo_18</xsl:attribute>
            <xsl:attribute name="role">error</xsl:attribute>
            <xsl:attribute name="location">
               <xsl:apply-templates select="." mode="schematron-select-full-path"/>
            </xsl:attribute>
            <svrl:text>Das Element <xsl:text/>mods:originInfo<xsl:text/> innerhalb des Elements <xsl:text/>mets:dmdSec<xsl:text/> darf mit dem Attribut <xsl:text/>eventType<xsl:text/> mit dem Wert <xsl:text/>digitization<xsl:text/> bzw. mit <xsl:text/>mods:edition<xsl:text/> mit dem Wert <xsl:text/>[Electronic ed.]<xsl:text/> nicht wiederholt werden.
Enthält <xsl:text/>mets:dmdSec<xsl:text/> mehr als ein <xsl:text/>mods:originInfo<xsl:text/> mit dem Attribut <xsl:text/>eventType<xsl:text/> mit dem Wert <xsl:text/>digitization<xsl:text/> bzw. mit <xsl:text/>mods:edition<xsl:text/> mit dem Wert <xsl:text/>[Electronic ed.]<xsl:text/>, wird bei der Transformation des Datensatzes das erste entsprechende Vorkommen von <xsl:text/>mods:originInfo<xsl:text/> übernommen, alle anderen <xsl:text/>mods:originInfo<xsl:text/> werden entfernt.Weitere Informationen zu diesen Elementen und Attributen finden Sie im DDB-METS/MODS-Anwendungsprofil auf der Seite mods:originInfo (https://wiki.deutsche-digitale-bibliothek.de/x/DcMeB).</svrl:text>
            <svrl:property id="dmd_id">
               <xsl:value-of xmlns:sch="http://purl.oclc.org/dsdl/schematron"
                             select="ancestor-or-self::mets:dmdSec/@ID"/>
            </svrl:property>
         </svrl:successful-report>
      </xsl:if>
      <xsl:apply-templates select="*" mode="M65"/>
   </xsl:template>
   <xsl:template match="text()" priority="-1" mode="M65"/>
   <xsl:template match="@*|node()" priority="-2" mode="M65">
      <xsl:apply-templates select="*" mode="M65"/>
   </xsl:template>
   <!--PATTERN -->
   <!--RULE -->
   <xsl:template match="mets:dmdSec[@ID=$work_dmdid]/mets:mdWrap/mets:xmlData/mods:mods"
                 priority="1000"
                 mode="M66">
      <svrl:fired-rule xmlns:svrl="http://purl.oclc.org/dsdl/svrl"
                       context="mets:dmdSec[@ID=$work_dmdid]/mets:mdWrap/mets:xmlData/mods:mods"/>
      <!--ASSERT warn-->
      <xsl:choose>
         <xsl:when test="mods:language/mods:languageTerm[text() != 'und'] or ancestor::mets:mets/mets:structMap[@TYPE='LOGICAL']//mets:div[contains(@DMDID, $work_dmdid)][@TYPE = ('image', 'photograph', 'illustration', 'map', 'poster', 'plan')]"/>
         <xsl:otherwise>
            <svrl:failed-assert xmlns:svrl="http://purl.oclc.org/dsdl/svrl"
                                test="mods:language/mods:languageTerm[text() != 'und'] or ancestor::mets:mets/mets:structMap[@TYPE='LOGICAL']//mets:div[contains(@DMDID, $work_dmdid)][@TYPE = ('image', 'photograph', 'illustration', 'map', 'poster', 'plan')]">
               <xsl:attribute name="id">language_01</xsl:attribute>
               <xsl:attribute name="role">warn</xsl:attribute>
               <xsl:attribute name="location">
                  <xsl:apply-templates select="." mode="schematron-select-full-path"/>
               </xsl:attribute>
               <svrl:text>Ein Datensatz zu einem Textdokument muss das Element <xsl:text/>mods:language<xsl:text/> mit dem Unterelement <xsl:text/>mods:languageTerm<xsl:text/> mit einem ISO 639-2b (https://id.loc.gov/vocabulary/iso639-2)-konformen Wert enthalten.
Bitte beachten Sie, dass das Fehlen von <xsl:text/>mods:language<xsl:text/>, die Verwendung eines nicht-ISO 639-2b (https://id.loc.gov/vocabulary/iso639-2)-konformen Wertes in <xsl:text/>mods:languageTerm<xsl:text/> oder die Verwendung des Sprachcode und (https://id.loc.gov/vocabulary/iso639-2/und) (Nicht zu entscheiden (https://id.loc.gov/vocabulary/iso639-2/und)) die Weitergabe des Datensatzes an Europeana verhindert.
Darüber hinaus gelten im Kontext der DDB und Europeana auch Noten als Textdokumente. Falls Ihre Noten keinen Sprachtext enthalten, verwenden Sie bitte den Code zxx (https://id.loc.gov/vocabulary/iso639-2/zxx) (Kein linguistischer Inhalt (https://id.loc.gov/vocabulary/iso639-2/zxx)).Weitere Informationen zu diesem Element finden Sie im DDB-METS/MODS-Anwendungsprofil auf der Seite mods:language (https://wiki.deutsche-digitale-bibliothek.de/x/F8MeB).</svrl:text>
               <svrl:property id="dmd_id">
                  <xsl:value-of xmlns:sch="http://purl.oclc.org/dsdl/schematron"
                                select="ancestor-or-self::mets:dmdSec/@ID"/>
               </svrl:property>
            </svrl:failed-assert>
         </xsl:otherwise>
      </xsl:choose>
      <xsl:apply-templates select="*" mode="M66"/>
   </xsl:template>
   <xsl:template match="text()" priority="-1" mode="M66"/>
   <xsl:template match="@*|node()" priority="-2" mode="M66">
      <xsl:apply-templates select="*" mode="M66"/>
   </xsl:template>
   <!--PATTERN -->
   <!--RULE -->
   <xsl:template match="mets:xmlData/mods:mods/mods:language/mods:languageTerm"
                 priority="1000"
                 mode="M67">
      <svrl:fired-rule xmlns:svrl="http://purl.oclc.org/dsdl/svrl"
                       context="mets:xmlData/mods:mods/mods:language/mods:languageTerm"/>
      <!--ASSERT error-->
      <xsl:choose>
         <xsl:when test="key('iso639-1_codes', text()[1], $iso639-1_codes) or key('iso639-2_codes', text()[1], $iso639-2_codes)"/>
         <xsl:otherwise>
            <svrl:failed-assert xmlns:svrl="http://purl.oclc.org/dsdl/svrl"
                                test="key('iso639-1_codes', text()[1], $iso639-1_codes) or key('iso639-2_codes', text()[1], $iso639-2_codes)">
               <xsl:attribute name="id">language_02</xsl:attribute>
               <xsl:attribute name="role">error</xsl:attribute>
               <xsl:attribute name="location">
                  <xsl:apply-templates select="." mode="schematron-select-full-path"/>
               </xsl:attribute>
               <svrl:text>Das Element <xsl:text/>mods:languageTerm<xsl:text/> im Element <xsl:text/>mods:language<xsl:text/> muss einen ISO 639-2b (https://id.loc.gov/vocabulary/iso639-2)-konformen Wert enthalten.
Bitte beachten Sie, dass wiederholte Sprachangaben innerhalb von <xsl:text/>mods:language<xsl:text/> bzw. im Wert von <xsl:text/>mods:languageTerm<xsl:text/> nicht zulässig sind. Verwenden Sie für jede Sprachangabe jeweils ein <xsl:text/>mods:language<xsl:text/> mit <xsl:text/>mods:languageTerm<xsl:text/>.
Enthält <xsl:text/>mods:language<xsl:text/> kein <xsl:text/>mods:languageTerm<xsl:text/> mit einem ISO 639-2b (https://id.loc.gov/vocabulary/iso639-2)-konformen Wert wird <xsl:text/>mods:language<xsl:text/> bei der Transformation des Datensatzes entfernt.Weitere Informationen zu diesem Element finden Sie im DDB-METS/MODS-Anwendungsprofil auf der Seite mods:language (https://wiki.deutsche-digitale-bibliothek.de/x/F8MeB).</svrl:text>
               <svrl:property id="dmd_id">
                  <xsl:value-of xmlns:sch="http://purl.oclc.org/dsdl/schematron"
                                select="ancestor-or-self::mets:dmdSec/@ID"/>
               </svrl:property>
               <svrl:property id="text">
                  <xsl:value-of xmlns:sch="http://purl.oclc.org/dsdl/schematron" select="text()"/>
               </svrl:property>
            </svrl:failed-assert>
         </xsl:otherwise>
      </xsl:choose>
      <xsl:apply-templates select="*" mode="M67"/>
   </xsl:template>
   <xsl:template match="text()" priority="-1" mode="M67"/>
   <xsl:template match="@*|node()" priority="-2" mode="M67">
      <xsl:apply-templates select="*" mode="M67"/>
   </xsl:template>
   <!--PATTERN -->
   <!--RULE -->
   <xsl:template match="mets:xmlData/mods:mods/mods:physicalDescription/mods:extent"
                 priority="1000"
                 mode="M68">
      <svrl:fired-rule xmlns:svrl="http://purl.oclc.org/dsdl/svrl"
                       context="mets:xmlData/mods:mods/mods:physicalDescription/mods:extent"/>
      <!--REPORT error-->
      <xsl:if test="contains(lower-case(text()), 'online') or contains(lower-case(text()[1]), 'electronic')">
         <svrl:successful-report xmlns:svrl="http://purl.oclc.org/dsdl/svrl"
                                 test="contains(lower-case(text()), 'online') or contains(lower-case(text()[1]), 'electronic')">
            <xsl:attribute name="id">physicalDescription_01</xsl:attribute>
            <xsl:attribute name="role">error</xsl:attribute>
            <xsl:attribute name="location">
               <xsl:apply-templates select="." mode="schematron-select-full-path"/>
            </xsl:attribute>
            <svrl:text>Das Element <xsl:text/>mods:extent<xsl:text/> im Element <xsl:text/>mods:physicalDescription<xsl:text/> enthält die Begriffe <xsl:text/>online<xsl:text/> bzw. <xsl:text/>electronic<xsl:text/> und beschreibt damit das Digitalisat.
Da <xsl:text/>mods:extent<xsl:text/> aber nur zur Beschreibung des originalen Dokuments dient, wird es bei der Transformation des Datensatzes entfernt.Weitere Informationen zu diesem Element finden Sie im DDB-METS/MODS-Anwendungsprofil auf der Seite mods:physicalDescription (https://wiki.deutsche-digitale-bibliothek.de/x/G8MeB).</svrl:text>
            <svrl:property id="dmd_id">
               <xsl:value-of xmlns:sch="http://purl.oclc.org/dsdl/schematron"
                             select="ancestor-or-self::mets:dmdSec/@ID"/>
            </svrl:property>
         </svrl:successful-report>
      </xsl:if>
      <xsl:apply-templates select="*" mode="M68"/>
   </xsl:template>
   <xsl:template match="text()" priority="-1" mode="M68"/>
   <xsl:template match="@*|node()" priority="-2" mode="M68">
      <xsl:apply-templates select="*" mode="M68"/>
   </xsl:template>
   <!--PATTERN -->
   <!--RULE -->
   <xsl:template match="mods:note[parent::mods:mods or parent::mods:physicalDescription][not(@type)]"
                 priority="1001"
                 mode="M69">
      <svrl:fired-rule xmlns:svrl="http://purl.oclc.org/dsdl/svrl"
                       context="mods:note[parent::mods:mods or parent::mods:physicalDescription][not(@type)]"/>
      <!--ASSERT error-->
      <xsl:choose>
         <xsl:when test="@type"/>
         <xsl:otherwise>
            <svrl:failed-assert xmlns:svrl="http://purl.oclc.org/dsdl/svrl" test="@type">
               <xsl:attribute name="id">note_01</xsl:attribute>
               <xsl:attribute name="role">error</xsl:attribute>
               <xsl:attribute name="location">
                  <xsl:apply-templates select="." mode="schematron-select-full-path"/>
               </xsl:attribute>
               <svrl:text>Das Top-Level-Element <xsl:text/>mods:note<xsl:text/> bzw. <xsl:text/>mods:note<xsl:text/> im Element <xsl:text/>mods:physicalDescription<xsl:text/> muss das Attribut <xsl:text/>type<xsl:text/> mit einem Wert aus der Liste der MODS &lt;note&gt; Types (https://www.loc.gov/standards/mods/mods-notes.html) enthalten.
Fehlt <xsl:text/>type<xsl:text/> in <xsl:text/>mods:note<xsl:text/> wird <xsl:text/>mods:note<xsl:text/> bei der Transformation des Datensatzes entfernt.Weitere Informationen zu diesem Element finden Sie im DDB-METS/MODS-Anwendungsprofil auf den Seiten mods:note (https://wiki.deutsche-digitale-bibliothek.de/x/IcMeB) und mods:physicalDescription (https://wiki.deutsche-digitale-bibliothek.de/x/G8MeB).</svrl:text>
               <svrl:property id="dmd_id">
                  <xsl:value-of xmlns:sch="http://purl.oclc.org/dsdl/schematron"
                                select="ancestor-or-self::mets:dmdSec/@ID"/>
               </svrl:property>
            </svrl:failed-assert>
         </xsl:otherwise>
      </xsl:choose>
      <xsl:apply-templates select="*" mode="M69"/>
   </xsl:template>
   <!--RULE -->
   <xsl:template match="mods:note[parent::mods:mods or parent::mods:physicalDescription][@type]"
                 priority="1000"
                 mode="M69">
      <svrl:fired-rule xmlns:svrl="http://purl.oclc.org/dsdl/svrl"
                       context="mods:note[parent::mods:mods or parent::mods:physicalDescription][@type]"/>
      <!--ASSERT error-->
      <xsl:choose>
         <xsl:when test="@type = ( 'accrual method', 'accrual policy', 'acquisition', 'action', 'additional physical form', 'admin', 'bibliographic history', 'bibliography', 'biographical/historical', 'citation/reference', 'conservation history', 'content', 'creation/production credits', 'date', 'exhibitions', 'funding', 'handwritten', 'language', 'numbering', 'date/sequential designation', 'original location', 'original version', 'ownership', 'performers', 'preferred citation', 'publications', 'reproduction', 'restriction', 'source characteristics', 'source dimensions', 'source identifier', 'source note', 'source type', 'statement of responsibility', 'subject completeness', 'system details', 'thesis', 'venue', 'version identification', 'condition', 'marks', 'medium', 'organization', 'physical description', 'physical details', 'presentation', 'script', 'support', 'technique' )"/>
         <xsl:otherwise>
            <svrl:failed-assert xmlns:svrl="http://purl.oclc.org/dsdl/svrl"
                                test="@type = ( 'accrual method', 'accrual policy', 'acquisition', 'action', 'additional physical form', 'admin', 'bibliographic history', 'bibliography', 'biographical/historical', 'citation/reference', 'conservation history', 'content', 'creation/production credits', 'date', 'exhibitions', 'funding', 'handwritten', 'language', 'numbering', 'date/sequential designation', 'original location', 'original version', 'ownership', 'performers', 'preferred citation', 'publications', 'reproduction', 'restriction', 'source characteristics', 'source dimensions', 'source identifier', 'source note', 'source type', 'statement of responsibility', 'subject completeness', 'system details', 'thesis', 'venue', 'version identification', 'condition', 'marks', 'medium', 'organization', 'physical description', 'physical details', 'presentation', 'script', 'support', 'technique' )">
               <xsl:attribute name="id">note_02</xsl:attribute>
               <xsl:attribute name="role">error</xsl:attribute>
               <xsl:attribute name="location">
                  <xsl:apply-templates select="." mode="schematron-select-full-path"/>
               </xsl:attribute>
               <svrl:text>Das Attribut <xsl:text/>type<xsl:text/> im Element <xsl:text/>mods:note<xsl:text/> darf nur einem Wert aus der Liste der MODS &lt;note&gt; Types (https://www.loc.gov/standards/mods/mods-notes.html) enthalten.
Enthält <xsl:text/>type<xsl:text/> einen ungültigen Wert, wird <xsl:text/>mods:note<xsl:text/> bei der Transformation des Datensatzes entfernt.Weitere Informationen zu diesem Element und Attribut finden Sie im DDB-METS/MODS-Anwendungsprofil auf der Seite mods:note (https://wiki.deutsche-digitale-bibliothek.de/x/IcMeB).</svrl:text>
               <svrl:property id="dmd_id">
                  <xsl:value-of xmlns:sch="http://purl.oclc.org/dsdl/schematron"
                                select="ancestor-or-self::mets:dmdSec/@ID"/>
               </svrl:property>
               <svrl:property id="type">
                  <xsl:value-of xmlns:sch="http://purl.oclc.org/dsdl/schematron" select="./@type"/>
               </svrl:property>
            </svrl:failed-assert>
         </xsl:otherwise>
      </xsl:choose>
      <xsl:apply-templates select="*" mode="M69"/>
   </xsl:template>
   <xsl:template match="text()" priority="-1" mode="M69"/>
   <xsl:template match="@*|node()" priority="-2" mode="M69">
      <xsl:apply-templates select="*" mode="M69"/>
   </xsl:template>
   <!--PATTERN -->
   <!--RULE -->
   <xsl:template match="mods:mods/mods:subject[@valueURI]/mods:topic | mods:mods/mods:subject[@valueURI]/mods:genre | mods:mods/mods:subject[@valueURI]/mods:geographic | mods:mods/mods:subject[@valueURI]/mods:name | mods:mods/mods:subject[@valueURI]/mods:titleInfo | mods:mods/mods:subject/mods:topic[@valueURI] | mods:mods/mods:subject/mods:genre[@valueURI] | mods:mods/mods:subject/mods:geographic[@valueURI] | mods:mods/mods:subject/mods:name[@valueURI] | mods:mods/mods:subject/mods:titleInfo[@valueURI]"
                 priority="1000"
                 mode="M70">
      <svrl:fired-rule xmlns:svrl="http://purl.oclc.org/dsdl/svrl"
                       context="mods:mods/mods:subject[@valueURI]/mods:topic | mods:mods/mods:subject[@valueURI]/mods:genre | mods:mods/mods:subject[@valueURI]/mods:geographic | mods:mods/mods:subject[@valueURI]/mods:name | mods:mods/mods:subject[@valueURI]/mods:titleInfo | mods:mods/mods:subject/mods:topic[@valueURI] | mods:mods/mods:subject/mods:genre[@valueURI] | mods:mods/mods:subject/mods:geographic[@valueURI] | mods:mods/mods:subject/mods:name[@valueURI] | mods:mods/mods:subject/mods:titleInfo[@valueURI]"/>
      <!--ASSERT info-->
      <xsl:choose>
         <xsl:when test="starts-with(../@valueURI, 'http://d-nb.info/gnd/') or starts-with(../@valueURI, 'https://d-nb.info/gnd/') or starts-with(../@valueURI, 'http://www.wikidata.org/') or starts-with(../@valueURI, 'https://www.wikidata.org/') or starts-with(../@valueURI, 'http://vocab.getty.edu/aat/') or starts-with(../@valueURI, 'https://vocab.getty.edu/aat/') or starts-with(../@valueURI, 'https://sws.geonames.org/') or starts-with(../@valueURI, 'http://sws.geonames.org/') or starts-with(@valueURI, 'http://d-nb.info/gnd/') or starts-with(@valueURI, 'https://d-nb.info/gnd/') or starts-with(@valueURI, 'http://www.wikidata.org/') or starts-with(@valueURI, 'https://www.wikidata.org/') or starts-with(@valueURI, 'http://vocab.getty.edu/aat/') or starts-with(@valueURI, 'https://vocab.getty.edu/aat/') or starts-with(@valueURI, 'http://sws.geonames.org/') or starts-with(@valueURI, 'https://sws.geonames.org/')"/>
         <xsl:otherwise>
            <svrl:failed-assert xmlns:svrl="http://purl.oclc.org/dsdl/svrl"
                                test="starts-with(../@valueURI, 'http://d-nb.info/gnd/') or starts-with(../@valueURI, 'https://d-nb.info/gnd/') or starts-with(../@valueURI, 'http://www.wikidata.org/') or starts-with(../@valueURI, 'https://www.wikidata.org/') or starts-with(../@valueURI, 'http://vocab.getty.edu/aat/') or starts-with(../@valueURI, 'https://vocab.getty.edu/aat/') or starts-with(../@valueURI, 'https://sws.geonames.org/') or starts-with(../@valueURI, 'http://sws.geonames.org/') or starts-with(@valueURI, 'http://d-nb.info/gnd/') or starts-with(@valueURI, 'https://d-nb.info/gnd/') or starts-with(@valueURI, 'http://www.wikidata.org/') or starts-with(@valueURI, 'https://www.wikidata.org/') or starts-with(@valueURI, 'http://vocab.getty.edu/aat/') or starts-with(@valueURI, 'https://vocab.getty.edu/aat/') or starts-with(@valueURI, 'http://sws.geonames.org/') or starts-with(@valueURI, 'https://sws.geonames.org/')">
               <xsl:attribute name="id">subject_01</xsl:attribute>
               <xsl:attribute name="role">info</xsl:attribute>
               <xsl:attribute name="location">
                  <xsl:apply-templates select="." mode="schematron-select-full-path"/>
               </xsl:attribute>
               <svrl:text>Die DDB berücksichtigt das Element <xsl:text/>mods:subject<xsl:text/> nur, wenn es Schlagworte aus der GND (https://www.dnb.de/DE/Professionell/Standardisierung/GND/gnd_node.html), Wikidata (https://www.wikidata.org), Geonames (https://sws.geonames.org) oder dem AAT (https://www.getty.edu/research/tools/vocabularies/aat/) enthält. Diese müssen in dem Unterelement zu <xsl:text/>mods:subject<xsl:text/> stehen und dort mittels einer entsprechenden URIs der o. g. Normdaten in dem Attribut <xsl:text/>valueURI<xsl:text/> eindeutig identifiziert werden.Weitere Informationen zu diesem Element und Attribut finden Sie im DDB-METS/MODS-Anwendungsprofil auf der Seite mods:subject (https://wiki.deutsche-digitale-bibliothek.de/x/JMMeB).</svrl:text>
               <svrl:property id="dmd_id">
                  <xsl:value-of xmlns:sch="http://purl.oclc.org/dsdl/schematron"
                                select="ancestor-or-self::mets:dmdSec/@ID"/>
               </svrl:property>
            </svrl:failed-assert>
         </xsl:otherwise>
      </xsl:choose>
      <xsl:apply-templates select="*" mode="M70"/>
   </xsl:template>
   <xsl:template match="text()" priority="-1" mode="M70"/>
   <xsl:template match="@*|node()" priority="-2" mode="M70">
      <xsl:apply-templates select="*" mode="M70"/>
   </xsl:template>
   <!--PATTERN -->
   <!--RULE -->
   <xsl:template match="mods:mods/mods:subject/mods:titleInfo"
                 priority="1000"
                 mode="M71">
      <svrl:fired-rule xmlns:svrl="http://purl.oclc.org/dsdl/svrl"
                       context="mods:mods/mods:subject/mods:titleInfo"/>
      <!--ASSERT info-->
      <xsl:choose>
         <xsl:when test="mods:title"/>
         <xsl:otherwise>
            <svrl:failed-assert xmlns:svrl="http://purl.oclc.org/dsdl/svrl" test="mods:title">
               <xsl:attribute name="id">subject_02</xsl:attribute>
               <xsl:attribute name="role">info</xsl:attribute>
               <xsl:attribute name="location">
                  <xsl:apply-templates select="." mode="schematron-select-full-path"/>
               </xsl:attribute>
               <svrl:text>Die DDB berücksichtigt das Element <xsl:text/>mods:titleInfo<xsl:text/> im Element <xsl:text/>mods:subject<xsl:text/> nur, wenn es das Element <xsl:text/>mods:title<xsl:text/> enthält.Weitere Informationen zu diesem Element finden Sie im DDB-METS/MODS-Anwendungsprofil auf der Seite mods:subject (https://wiki.deutsche-digitale-bibliothek.de/x/JMMeB).</svrl:text>
               <svrl:property id="dmd_id">
                  <xsl:value-of xmlns:sch="http://purl.oclc.org/dsdl/schematron"
                                select="ancestor-or-self::mets:dmdSec/@ID"/>
               </svrl:property>
            </svrl:failed-assert>
         </xsl:otherwise>
      </xsl:choose>
      <xsl:apply-templates select="*" mode="M71"/>
   </xsl:template>
   <xsl:template match="text()" priority="-1" mode="M71"/>
   <xsl:template match="@*|node()" priority="-2" mode="M71">
      <xsl:apply-templates select="*" mode="M71"/>
   </xsl:template>
   <!--PATTERN -->
   <!--RULE -->
   <xsl:template match="mods:mods/mods:subject/mods:name" priority="1000" mode="M72">
      <svrl:fired-rule xmlns:svrl="http://purl.oclc.org/dsdl/svrl"
                       context="mods:mods/mods:subject/mods:name"/>
      <!--ASSERT info-->
      <xsl:choose>
         <xsl:when test="mods:displayForm"/>
         <xsl:otherwise>
            <svrl:failed-assert xmlns:svrl="http://purl.oclc.org/dsdl/svrl" test="mods:displayForm">
               <xsl:attribute name="id">subject_03</xsl:attribute>
               <xsl:attribute name="role">info</xsl:attribute>
               <xsl:attribute name="location">
                  <xsl:apply-templates select="." mode="schematron-select-full-path"/>
               </xsl:attribute>
               <svrl:text>Die DDB berücksichtigt das Element <xsl:text/>mods:name<xsl:text/> im Element <xsl:text/>mods:subject<xsl:text/> nur, wenn es das Element <xsl:text/>mods:displayForm<xsl:text/> enthält.Weitere Informationen zu diesem Element finden Sie im DDB-METS/MODS-Anwendungsprofil auf der Seite mods:subject (https://wiki.deutsche-digitale-bibliothek.de/x/JMMeB).</svrl:text>
               <svrl:property id="dmd_id">
                  <xsl:value-of xmlns:sch="http://purl.oclc.org/dsdl/schematron"
                                select="ancestor-or-self::mets:dmdSec/@ID"/>
               </svrl:property>
            </svrl:failed-assert>
         </xsl:otherwise>
      </xsl:choose>
      <xsl:apply-templates select="*" mode="M72"/>
   </xsl:template>
   <xsl:template match="text()" priority="-1" mode="M72"/>
   <xsl:template match="@*|node()" priority="-2" mode="M72">
      <xsl:apply-templates select="*" mode="M72"/>
   </xsl:template>
   <!--PATTERN -->
   <!--RULE -->
   <xsl:template match="mods:mods/mods:subject/mods:cartographic"
                 priority="1000"
                 mode="M73">
      <svrl:fired-rule xmlns:svrl="http://purl.oclc.org/dsdl/svrl"
                       context="mods:mods/mods:subject/mods:cartographic"/>
      <!--ASSERT info-->
      <xsl:choose>
         <xsl:when test="mods:scale or mods:coordinates or mods:projection"/>
         <xsl:otherwise>
            <svrl:failed-assert xmlns:svrl="http://purl.oclc.org/dsdl/svrl"
                                test="mods:scale or mods:coordinates or mods:projection">
               <xsl:attribute name="id">subject_04</xsl:attribute>
               <xsl:attribute name="role">info</xsl:attribute>
               <xsl:attribute name="location">
                  <xsl:apply-templates select="." mode="schematron-select-full-path"/>
               </xsl:attribute>
               <svrl:text>Die DDB berücksichtigt das Element <xsl:text/>mods:cartographic<xsl:text/> im Element <xsl:text/>mods:subject<xsl:text/> nur, wenn es mindesten eines der folgenden Unterelemente enthält:
 * <xsl:text/>mods:scale<xsl:text/>
 * <xsl:text/>mods:coordinates<xsl:text/>
 * <xsl:text/>mods:projection<xsl:text/>Weitere Informationen zu diesem Element finden Sie im DDB-METS/MODS-Anwendungsprofil auf der Seite mods:subject (https://wiki.deutsche-digitale-bibliothek.de/x/JMMeB).</svrl:text>
               <svrl:property id="dmd_id">
                  <xsl:value-of xmlns:sch="http://purl.oclc.org/dsdl/schematron"
                                select="ancestor-or-self::mets:dmdSec/@ID"/>
               </svrl:property>
            </svrl:failed-assert>
         </xsl:otherwise>
      </xsl:choose>
      <xsl:apply-templates select="*" mode="M73"/>
   </xsl:template>
   <xsl:template match="text()" priority="-1" mode="M73"/>
   <xsl:template match="@*|node()" priority="-2" mode="M73">
      <xsl:apply-templates select="*" mode="M73"/>
   </xsl:template>
   <!--PATTERN -->
   <!--RULE -->
   <xsl:template match="mets:dmdSec[@ID=$work_dmdid]/mets:mdWrap/mets:xmlData/mods:mods [ancestor::mets:mets/mets:structMap[@TYPE='LOGICAL']/mets:div/mets:mptr]"
                 priority="1000"
                 mode="M74">
      <svrl:fired-rule xmlns:svrl="http://purl.oclc.org/dsdl/svrl"
                       context="mets:dmdSec[@ID=$work_dmdid]/mets:mdWrap/mets:xmlData/mods:mods [ancestor::mets:mets/mets:structMap[@TYPE='LOGICAL']/mets:div/mets:mptr]"/>
      <!--ASSERT fatal-->
      <xsl:choose>
         <xsl:when test="mods:relatedItem[@type = 'host']"/>
         <xsl:otherwise>
            <svrl:failed-assert xmlns:svrl="http://purl.oclc.org/dsdl/svrl"
                                test="mods:relatedItem[@type = 'host']">
               <xsl:attribute name="id">relatedItem_01</xsl:attribute>
               <xsl:attribute name="role">fatal</xsl:attribute>
               <xsl:attribute name="location">
                  <xsl:apply-templates select="." mode="schematron-select-full-path"/>
               </xsl:attribute>
               <svrl:text>Der Datensatz beschreibt den Teil eines Mehrteiligen Dokuments und muss den Ankersatz des Mehrteiligen Dokuments referenzieren.
Die Referenzierung erfolgt im primären <xsl:text/>mets:dmdSec<xsl:text/>-Element des Teiles des Mehrteiligen Dokuments im Element <xsl:text/>mods:relatedItem[@type='host']<xsl:text/> über einen Identifier im Unterelement <xsl:text/>mods:recordIdentifier<xsl:text/> des Elements <xsl:text/>mods:recordInfo<xsl:text/>. Dieser Identifier muss dem Identifier im Unterelement <xsl:text/>mods:recordIdentifier<xsl:text/> des Top-Level-Elements <xsl:text/>mods:recordInfo<xsl:text/> im Ankersatz des Mehrteiligen Dokuments entsprechen.
Der Identifier muss dazu im Unterelement <xsl:text/>mods:recordIdentifier<xsl:text/> des Elements <xsl:text/>mods:recordInfo<xsl:text/> angebeben sein. <xsl:text/>mods:recordIdentifier<xsl:text/> muss darüber hinaus jeweils das Attribut <xsl:text/>source<xsl:text/> mit identischem Wert besitzen.
Ist dies nicht der Fall besteht keine Referenzierung zwischen den Datensätzen und der Datensatz nicht in die DDB eingespielt, Weitere Informationen zu diesen Elementen und ihrem Kontext finden Sie im DDB-METS/MODS-Anwendungsprofil auf den Seiten mods:relatedItem (https://wiki.deutsche-digitale-bibliothek.de/x/K8MeB), mods:recordInfo (https://wiki.deutsche-digitale-bibliothek.de/x/TMMeB) und  METS/MODS für Mehrteilige Dokumente (https://wiki.deutsche-digitale-bibliothek.de/x/RgGuB).</svrl:text>
               <svrl:property id="dmd_id">
                  <xsl:value-of xmlns:sch="http://purl.oclc.org/dsdl/schematron"
                                select="ancestor-or-self::mets:dmdSec/@ID"/>
               </svrl:property>
            </svrl:failed-assert>
         </xsl:otherwise>
      </xsl:choose>
      <xsl:apply-templates select="*" mode="M74"/>
   </xsl:template>
   <xsl:template match="text()" priority="-1" mode="M74"/>
   <xsl:template match="@*|node()" priority="-2" mode="M74">
      <xsl:apply-templates select="*" mode="M74"/>
   </xsl:template>
   <!--PATTERN -->
   <!--RULE -->
   <xsl:template match="mets:xmlData/mods:mods/mods:relatedItem[@type='host']"
                 priority="1002"
                 mode="M75">
      <svrl:fired-rule xmlns:svrl="http://purl.oclc.org/dsdl/svrl"
                       context="mets:xmlData/mods:mods/mods:relatedItem[@type='host']"/>
      <!--ASSERT fatal-->
      <xsl:choose>
         <xsl:when test="mods:recordInfo/mods:recordIdentifier"/>
         <xsl:otherwise>
            <svrl:failed-assert xmlns:svrl="http://purl.oclc.org/dsdl/svrl"
                                test="mods:recordInfo/mods:recordIdentifier">
               <xsl:attribute name="id">relatedItem_02</xsl:attribute>
               <xsl:attribute name="role">fatal</xsl:attribute>
               <xsl:attribute name="location">
                  <xsl:apply-templates select="." mode="schematron-select-full-path"/>
               </xsl:attribute>
               <svrl:text>Der Datensatz beschreibt den Teil eines Mehrteiligen Dokuments und muss den Ankersatz des Mehrteiligen Dokuments referenzieren.
Die Referenzierung erfolgt im primären <xsl:text/>mets:dmdSec<xsl:text/>-Element des Teiles des Mehrteiligen Dokuments im Element <xsl:text/>mods:relatedItem[@type='host']<xsl:text/> über einen Identifier im Unterelement <xsl:text/>mods:recordIdentifier<xsl:text/> des Elements <xsl:text/>mods:recordInfo<xsl:text/>. Dieser Identifier muss dem Identifier im Unterelement <xsl:text/>mods:recordIdentifier<xsl:text/> des Top-Level-Elements <xsl:text/>mods:recordInfo<xsl:text/> im Ankersatz des Mehrteiligen Dokuments entsprechen.
Der Identifier muss dazu im Unterelement <xsl:text/>mods:recordIdentifier<xsl:text/> des Elements <xsl:text/>mods:recordInfo<xsl:text/> angebeben sein. <xsl:text/>mods:recordIdentifier<xsl:text/> muss darüber hinaus jeweils das Attribut <xsl:text/>source<xsl:text/> mit identischem Wert besitzen.
Ist dies nicht der Fall besteht keine Referenzierung zwischen den Datensätzen und der Datensatz nicht in die DDB eingespielt, Weitere Informationen zu diesen Elementen und ihrem Kontext finden Sie im DDB-METS/MODS-Anwendungsprofil auf den Seiten mods:relatedItem (https://wiki.deutsche-digitale-bibliothek.de/x/K8MeB), mods:recordInfo (https://wiki.deutsche-digitale-bibliothek.de/x/TMMeB) und  METS/MODS für Mehrteilige Dokumente (https://wiki.deutsche-digitale-bibliothek.de/x/RgGuB).</svrl:text>
               <svrl:property id="dmd_id">
                  <xsl:value-of xmlns:sch="http://purl.oclc.org/dsdl/schematron"
                                select="ancestor-or-self::mets:dmdSec/@ID"/>
               </svrl:property>
            </svrl:failed-assert>
         </xsl:otherwise>
      </xsl:choose>
      <xsl:apply-templates select="*" mode="M75"/>
   </xsl:template>
   <!--RULE -->
   <xsl:template match="mets:xmlData/mods:mods/mods:relatedItem[@type='host']/mods:recordInfo/mods:recordIdentifier"
                 priority="1001"
                 mode="M75">
      <svrl:fired-rule xmlns:svrl="http://purl.oclc.org/dsdl/svrl"
                       context="mets:xmlData/mods:mods/mods:relatedItem[@type='host']/mods:recordInfo/mods:recordIdentifier"/>
      <!--ASSERT fatal-->
      <xsl:choose>
         <xsl:when test="@source"/>
         <xsl:otherwise>
            <svrl:failed-assert xmlns:svrl="http://purl.oclc.org/dsdl/svrl" test="@source">
               <xsl:attribute name="id">relatedItem_03</xsl:attribute>
               <xsl:attribute name="role">fatal</xsl:attribute>
               <xsl:attribute name="location">
                  <xsl:apply-templates select="." mode="schematron-select-full-path"/>
               </xsl:attribute>
               <svrl:text>Der Datensatz beschreibt den Teil eines Mehrteiligen Dokuments und muss den Ankersatz des Mehrteiligen Dokuments referenzieren.
Die Referenzierung erfolgt im primären <xsl:text/>mets:dmdSec<xsl:text/>-Element des Teiles des Mehrteiligen Dokuments im Element <xsl:text/>mods:relatedItem[@type='host']<xsl:text/> über einen Identifier im Unterelement <xsl:text/>mods:recordIdentifier<xsl:text/> des Elements <xsl:text/>mods:recordInfo<xsl:text/>. Dieser Identifier muss dem Identifier im Unterelement <xsl:text/>mods:recordIdentifier<xsl:text/> des Top-Level-Elements <xsl:text/>mods:recordInfo<xsl:text/> im Ankersatz des Mehrteiligen Dokuments entsprechen.
Der Identifier muss dazu im Unterelement <xsl:text/>mods:recordIdentifier<xsl:text/> des Elements <xsl:text/>mods:recordInfo<xsl:text/> angebeben sein. <xsl:text/>mods:recordIdentifier<xsl:text/> muss darüber hinaus jeweils das Attribut <xsl:text/>source<xsl:text/> mit identischem Wert besitzen.
Ist dies nicht der Fall besteht keine Referenzierung zwischen den Datensätzen und der Datensatz nicht in die DDB eingespielt, Weitere Informationen zu diesen Elementen und ihrem Kontext finden Sie im DDB-METS/MODS-Anwendungsprofil auf den Seiten mods:relatedItem (https://wiki.deutsche-digitale-bibliothek.de/x/K8MeB), mods:recordInfo (https://wiki.deutsche-digitale-bibliothek.de/x/TMMeB) und  METS/MODS für Mehrteilige Dokumente (https://wiki.deutsche-digitale-bibliothek.de/x/RgGuB).</svrl:text>
               <svrl:property id="dmd_id">
                  <xsl:value-of xmlns:sch="http://purl.oclc.org/dsdl/schematron"
                                select="ancestor-or-self::mets:dmdSec/@ID"/>
               </svrl:property>
            </svrl:failed-assert>
         </xsl:otherwise>
      </xsl:choose>
      <xsl:apply-templates select="*" mode="M75"/>
   </xsl:template>
   <!--RULE -->
   <xsl:template match="mets:xmlData/mods:mods/mods:relatedItem"
                 priority="1000"
                 mode="M75">
      <svrl:fired-rule xmlns:svrl="http://purl.oclc.org/dsdl/svrl"
                       context="mets:xmlData/mods:mods/mods:relatedItem"/>
      <!--ASSERT error-->
      <xsl:choose>
         <xsl:when test="@type = ('enumerated', 'preceding', 'succeeding', 'original', 'host', 'constituent', 'series', 'otherVersion', 'otherFormat', 'isReferencedBy', 'references', 'reviewOf')"/>
         <xsl:otherwise>
            <svrl:failed-assert xmlns:svrl="http://purl.oclc.org/dsdl/svrl"
                                test="@type = ('enumerated', 'preceding', 'succeeding', 'original', 'host', 'constituent', 'series', 'otherVersion', 'otherFormat', 'isReferencedBy', 'references', 'reviewOf')">
               <xsl:attribute name="id">relatedItem_04</xsl:attribute>
               <xsl:attribute name="role">error</xsl:attribute>
               <xsl:attribute name="location">
                  <xsl:apply-templates select="." mode="schematron-select-full-path"/>
               </xsl:attribute>
               <svrl:text>Das Element <xsl:text/>mods:relatedItem<xsl:text/> muss das Attribut <xsl:text/>type<xsl:text/> mit einem der folgenden Werte enthalten:
 * <xsl:text/>host<xsl:text/>
 * <xsl:text/>series<xsl:text/>
Fehlt <xsl:text/>type<xsl:text/> bzw. enthält es einen ungültigen Wert, wird <xsl:text/>mods:relatedItem<xsl:text/> bei der Transformation des Datensatzes entfernt.Weitere Informationen zu diesem Attribut finden Sie im DDB-METS/MODS-Anwendungsprofil auf der Seite mods:relatedItem (https://wiki.deutsche-digitale-bibliothek.de/x/K8MeB).</svrl:text>
               <svrl:property id="dmd_id">
                  <xsl:value-of xmlns:sch="http://purl.oclc.org/dsdl/schematron"
                                select="ancestor-or-self::mets:dmdSec/@ID"/>
               </svrl:property>
            </svrl:failed-assert>
         </xsl:otherwise>
      </xsl:choose>
      <xsl:apply-templates select="*" mode="M75"/>
   </xsl:template>
   <xsl:template match="text()" priority="-1" mode="M75"/>
   <xsl:template match="@*|node()" priority="-2" mode="M75">
      <xsl:apply-templates select="*" mode="M75"/>
   </xsl:template>
   <!--PATTERN -->
   <!--RULE -->
   <xsl:template match="mets:xmlData/mods:mods/mods:relatedItem[@type = 'series']"
                 priority="1000"
                 mode="M76">
      <svrl:fired-rule xmlns:svrl="http://purl.oclc.org/dsdl/svrl"
                       context="mets:xmlData/mods:mods/mods:relatedItem[@type = 'series']"/>
      <!--ASSERT error-->
      <xsl:choose>
         <xsl:when test="mods:titleInfo/mods:title"/>
         <xsl:otherwise>
            <svrl:failed-assert xmlns:svrl="http://purl.oclc.org/dsdl/svrl"
                                test="mods:titleInfo/mods:title">
               <xsl:attribute name="id">relatedItem_05</xsl:attribute>
               <xsl:attribute name="role">error</xsl:attribute>
               <xsl:attribute name="location">
                  <xsl:apply-templates select="." mode="schematron-select-full-path"/>
               </xsl:attribute>
               <svrl:text>Das Element <xsl:text/>mods:relatedItem<xsl:text/> mit dem Wert <xsl:text/>series<xsl:text/> im Attribut <xsl:text/>type<xsl:text/> muss mindestens ein Element <xsl:text/>mods:titleInfo<xsl:text/>-Element mit dem Unterelement <xsl:text/>mods:title<xsl:text/> enthalten.
Ist dies nicht der Fall, wird <xsl:text/>mods:relatedItem<xsl:text/> bei der Transformation des Datensatzes entfernt.Weitere Informationen zu diesem Element finden Sie im DDB-METS/MODS-Anwendungsprofil auf der Seite mods:relatedItem (https://wiki.deutsche-digitale-bibliothek.de/x/K8MeB).</svrl:text>
               <svrl:property id="dmd_id">
                  <xsl:value-of xmlns:sch="http://purl.oclc.org/dsdl/schematron"
                                select="ancestor-or-self::mets:dmdSec/@ID"/>
               </svrl:property>
            </svrl:failed-assert>
         </xsl:otherwise>
      </xsl:choose>
      <xsl:apply-templates select="*" mode="M76"/>
   </xsl:template>
   <xsl:template match="text()" priority="-1" mode="M76"/>
   <xsl:template match="@*|node()" priority="-2" mode="M76">
      <xsl:apply-templates select="*" mode="M76"/>
   </xsl:template>
   <!--PATTERN -->
   <!--RULE -->
   <xsl:template match="mets:xmlData/mods:mods" priority="1000" mode="M77">
      <svrl:fired-rule xmlns:svrl="http://purl.oclc.org/dsdl/svrl"
                       context="mets:xmlData/mods:mods"/>
      <!--REPORT error-->
      <xsl:if test="mods:relatedItem[@type='host'][mods:recordInfo/mods:recordIdentifier][2]">
         <svrl:successful-report xmlns:svrl="http://purl.oclc.org/dsdl/svrl"
                                 test="mods:relatedItem[@type='host'][mods:recordInfo/mods:recordIdentifier][2]">
            <xsl:attribute name="id">relatedItem_12</xsl:attribute>
            <xsl:attribute name="role">error</xsl:attribute>
            <xsl:attribute name="location">
               <xsl:apply-templates select="." mode="schematron-select-full-path"/>
            </xsl:attribute>
            <svrl:text>Das Element <xsl:text/>mods:relatedItem<xsl:text/> innerhalb des Elements <xsl:text/>mets:dmdSec<xsl:text/> darf mit dem Attribut <xsl:text/>type<xsl:text/> mit dem Wert <xsl:text/>host<xsl:text/> nicht wiederholt werden, da die DDB zurzeit keine Polyhierarchie unterstützt.
Enthält <xsl:text/>mets:dmdSec<xsl:text/> mehr als ein <xsl:text/>mods:relatedItem<xsl:text/> mit dem Attribut <xsl:text/>type<xsl:text/> mit dem Wert <xsl:text/>host<xsl:text/>, wird bei der Transformation des Datensatzes das erste Vorkommen von <xsl:text/>mods:relatedItem<xsl:text/> übernommen, alle anderen <xsl:text/>mods:relatedItem<xsl:text/> werden entfernt.Weitere Informationen zu diesem Element finden Sie im DDB-METS/MODS-Anwendungsprofil auf der Seite mods:relatedItem (https://wiki.deutsche-digitale-bibliothek.de/x/K8MeB).</svrl:text>
            <svrl:property id="dmd_id">
               <xsl:value-of xmlns:sch="http://purl.oclc.org/dsdl/schematron"
                             select="ancestor-or-self::mets:dmdSec/@ID"/>
            </svrl:property>
         </svrl:successful-report>
      </xsl:if>
      <xsl:apply-templates select="*" mode="M77"/>
   </xsl:template>
   <xsl:template match="text()" priority="-1" mode="M77"/>
   <xsl:template match="@*|node()" priority="-2" mode="M77">
      <xsl:apply-templates select="*" mode="M77"/>
   </xsl:template>
   <!--PATTERN -->
   <!--RULE -->
   <xsl:template match="mets:xmlData/mods:mods[mods:relatedItem[@type='host']][not(mods:part)]"
                 priority="1002"
                 mode="M78">
      <svrl:fired-rule xmlns:svrl="http://purl.oclc.org/dsdl/svrl"
                       context="mets:xmlData/mods:mods[mods:relatedItem[@type='host']][not(mods:part)]"/>
      <!--ASSERT warn-->
      <xsl:choose>
         <xsl:when test="mods:part"/>
         <xsl:otherwise>
            <svrl:failed-assert xmlns:svrl="http://purl.oclc.org/dsdl/svrl" test="mods:part">
               <xsl:attribute name="id">part_01</xsl:attribute>
               <xsl:attribute name="role">warn</xsl:attribute>
               <xsl:attribute name="location">
                  <xsl:apply-templates select="." mode="schematron-select-full-path"/>
               </xsl:attribute>
               <svrl:text>Der Datensatz enthält das Element <xsl:text/>mods:relatedItem<xsl:text/> mit dem Wert <xsl:text/>host<xsl:text/> im Attribut <xsl:text/>type<xsl:text/> und beschreibt daher den Teil eines Mehrteiligen Dokuments.
Diese müssen im Element <xsl:text/>mods:part<xsl:text/> Informationen zur Bandzählung enthalten. Die textliche Angabe erfolgt im Unterelement <xsl:text/>mods:number<xsl:text/> des Unterelements <xsl:text/>mods:detail<xsl:text/> und die maschienenlesbare Form (als Integer) im Attribut <xsl:text/>order<xsl:text/> von <xsl:text/>mods:part<xsl:text/>.
Das Fehlen von <xsl:text/>mods:part<xsl:text/> verhindert nicht das Einspielen des Datensatzes in die DDB, kann aber zu Darstellungsproblemen führen. Wir bitten Sie daher den Sachverhalt zu prüfen und ggf. die nötigen Korrekturen bis zur nächsten Datenlieferung vorzunehmen.Weitere Informationen zu diesem Element und seinem Kontext finden Sie im DDB-METS/MODS-Anwendungsprofil auf der Seite mods:part (https://wiki.deutsche-digitale-bibliothek.de/x/ScMeB) und der Seite METS/MODS für Mehrteilige Dokumente (https://wiki.deutsche-digitale-bibliothek.de/x/RgGuB).</svrl:text>
               <svrl:property id="dmd_id">
                  <xsl:value-of xmlns:sch="http://purl.oclc.org/dsdl/schematron"
                                select="ancestor-or-self::mets:dmdSec/@ID"/>
               </svrl:property>
            </svrl:failed-assert>
         </xsl:otherwise>
      </xsl:choose>
      <xsl:apply-templates select="*" mode="M78"/>
   </xsl:template>
   <!--RULE -->
   <xsl:template match="mets:xmlData/mods:mods[mods:relatedItem[@type='host']][not(mods:part[@order])]"
                 priority="1001"
                 mode="M78">
      <svrl:fired-rule xmlns:svrl="http://purl.oclc.org/dsdl/svrl"
                       context="mets:xmlData/mods:mods[mods:relatedItem[@type='host']][not(mods:part[@order])]"/>
      <!--ASSERT warn-->
      <xsl:choose>
         <xsl:when test="mods:part[@order]"/>
         <xsl:otherwise>
            <svrl:failed-assert xmlns:svrl="http://purl.oclc.org/dsdl/svrl" test="mods:part[@order]">
               <xsl:attribute name="id">part_02</xsl:attribute>
               <xsl:attribute name="role">warn</xsl:attribute>
               <xsl:attribute name="location">
                  <xsl:apply-templates select="." mode="schematron-select-full-path"/>
               </xsl:attribute>
               <svrl:text>Der Datensatz enthält das Element <xsl:text/>mods:relatedItem<xsl:text/> mit dem Wert <xsl:text/>host<xsl:text/> im Attribut <xsl:text/>type<xsl:text/> und beschreibt daher den Teil eines Mehrteiligen Dokuments.
Das Element <xsl:text/>mods:part<xsl:text/> muss mindestens daher das Attribut <xsl:text/>mods:order<xsl:text/> enthalten. Der Wert dient zur Anzeige von Bänden in der richtigen Reihenfolge uns muss als Zahl in maschinenlesbarer Form (als Integer) vorliegen.
Das Fehlen von <xsl:text/>order<xsl:text/> verhindert nicht das Einspielen des Datensatzes in die DDB, kann aber zu Darstellungsproblemen führen. Wir bitten Sie daher den Sachverhalt zu prüfen und ggf. die nötigen Korrekturen bis zur nächsten Datenlieferung vorzunehmen.Weitere Informationen zu diesem Attribut und seinem Kontext finden Sie im DDB-METS/MODS-Anwendungsprofil auf der Seite mods:part (https://wiki.deutsche-digitale-bibliothek.de/x/ScMeB) und der Seite METS/MODS für Mehrteilige Dokumente (https://wiki.deutsche-digitale-bibliothek.de/x/RgGuB).</svrl:text>
               <svrl:property id="dmd_id">
                  <xsl:value-of xmlns:sch="http://purl.oclc.org/dsdl/schematron"
                                select="ancestor-or-self::mets:dmdSec/@ID"/>
               </svrl:property>
            </svrl:failed-assert>
         </xsl:otherwise>
      </xsl:choose>
      <xsl:apply-templates select="*" mode="M78"/>
   </xsl:template>
   <!--RULE -->
   <xsl:template match="mets:xmlData/mods:mods[mods:relatedItem[@type='host']]/mods:part"
                 priority="1000"
                 mode="M78">
      <svrl:fired-rule xmlns:svrl="http://purl.oclc.org/dsdl/svrl"
                       context="mets:xmlData/mods:mods[mods:relatedItem[@type='host']]/mods:part"/>
      <!--ASSERT warn-->
      <xsl:choose>
         <xsl:when test="matches(@order, '^\d+$')"/>
         <xsl:otherwise>
            <svrl:failed-assert xmlns:svrl="http://purl.oclc.org/dsdl/svrl" test="matches(@order, '^\d+$')">
               <xsl:attribute name="id">part_03</xsl:attribute>
               <xsl:attribute name="role">warn</xsl:attribute>
               <xsl:attribute name="location">
                  <xsl:apply-templates select="." mode="schematron-select-full-path"/>
               </xsl:attribute>
               <svrl:text>Der Datensatz enthält das Element <xsl:text/>mods:relatedItem<xsl:text/> mit dem Wert <xsl:text/>host<xsl:text/> im Attribut <xsl:text/>type<xsl:text/> und beschreibt daher den Teil eines Mehrteiligen Dokuments.
Das Attribut <xsl:text/>mods:order<xsl:text/> im Element <xsl:text/>mods:part<xsl:text/> muss mindestens daher die Bandzählung in maschinenlesbarer Form (als Integer) enthalten.
Das Fehlen der maschinenlesbaren Form der Bandzählung in <xsl:text/>order<xsl:text/> verhindert nicht das Einspielen des Datensatzes in die DDB, kann aber zu Darstellungsproblemen führen. Wir bitten Sie daher den Sachverhalt zu prüfen und ggf. die nötigen Korrekturen bis zur nächsten Datenlieferung vorzunehmen.Weitere Informationen zu diesem Attribut und seinem Kontext finden Sie im DDB-METS/MODS-Anwendungsprofil auf der Seite mods:part (https://wiki.deutsche-digitale-bibliothek.de/x/ScMeB) und der Seite METS/MODS für Mehrteilige Dokumente (https://wiki.deutsche-digitale-bibliothek.de/x/RgGuB).</svrl:text>
               <svrl:property id="dmd_id">
                  <xsl:value-of xmlns:sch="http://purl.oclc.org/dsdl/schematron"
                                select="ancestor-or-self::mets:dmdSec/@ID"/>
               </svrl:property>
            </svrl:failed-assert>
         </xsl:otherwise>
      </xsl:choose>
      <xsl:apply-templates select="*" mode="M78"/>
   </xsl:template>
   <xsl:template match="text()" priority="-1" mode="M78"/>
   <xsl:template match="@*|node()" priority="-2" mode="M78">
      <xsl:apply-templates select="*" mode="M78"/>
   </xsl:template>
   <!--PATTERN -->
   <!--RULE -->
   <xsl:template match="mets:xmlData/mods:mods/mods:part[not(mods:detail/mods:number)]"
                 priority="1001"
                 mode="M79">
      <svrl:fired-rule xmlns:svrl="http://purl.oclc.org/dsdl/svrl"
                       context="mets:xmlData/mods:mods/mods:part[not(mods:detail/mods:number)]"/>
      <!--ASSERT error-->
      <xsl:choose>
         <xsl:when test="mods:detail/mods:number"/>
         <xsl:otherwise>
            <svrl:failed-assert xmlns:svrl="http://purl.oclc.org/dsdl/svrl" test="mods:detail/mods:number">
               <xsl:attribute name="id">part_04</xsl:attribute>
               <xsl:attribute name="role">error</xsl:attribute>
               <xsl:attribute name="location">
                  <xsl:apply-templates select="." mode="schematron-select-full-path"/>
               </xsl:attribute>
               <svrl:text>Das Element <xsl:text/>mods:part<xsl:text/> muss das Element <xsl:text/>mods:detail<xsl:text/> mit dem Unterelement <xsl:text/>mods:number<xsl:text/> enthalten. <xsl:text/>mods:number<xsl:text/> enthält die textliche Zählung des Teils eines Mehrteiligen Dokuments.
Fehlt <xsl:text/>mods:detail<xsl:text/> mit dem Unterelement <xsl:text/>mods:number<xsl:text/> in <xsl:text/>mods:part<xsl:text/>, wird <xsl:text/>mods:part<xsl:text/> bei der Transformation des Datensatzes entfernt.Weitere Informationen zu diesem Element und seinem Kontext finden Sie im DDB-METS/MODS-Anwendungsprofil auf der Seite mods:part (https://wiki.deutsche-digitale-bibliothek.de/x/ScMeB) und der Seite METS/MODS für Mehrteilige Dokumente (https://wiki.deutsche-digitale-bibliothek.de/x/RgGuB).</svrl:text>
               <svrl:property id="dmd_id">
                  <xsl:value-of xmlns:sch="http://purl.oclc.org/dsdl/schematron"
                                select="ancestor-or-self::mets:dmdSec/@ID"/>
               </svrl:property>
            </svrl:failed-assert>
         </xsl:otherwise>
      </xsl:choose>
      <xsl:apply-templates select="*" mode="M79"/>
   </xsl:template>
   <!--RULE -->
   <xsl:template match="mets:xmlData/mods:mods/mods:part/mods:detail"
                 priority="1000"
                 mode="M79">
      <svrl:fired-rule xmlns:svrl="http://purl.oclc.org/dsdl/svrl"
                       context="mets:xmlData/mods:mods/mods:part/mods:detail"/>
      <!--ASSERT warn-->
      <xsl:choose>
         <xsl:when test="@type = ('volume', 'issue')"/>
         <xsl:otherwise>
            <svrl:failed-assert xmlns:svrl="http://purl.oclc.org/dsdl/svrl"
                                test="@type = ('volume', 'issue')">
               <xsl:attribute name="id">part_05</xsl:attribute>
               <xsl:attribute name="role">warn</xsl:attribute>
               <xsl:attribute name="location">
                  <xsl:apply-templates select="." mode="schematron-select-full-path"/>
               </xsl:attribute>
               <svrl:text>Das Element <xsl:text/>mods:detail<xsl:text/> muss das Attribut <xsl:text/>type<xsl:text/> mit einem der folgenden Werte enthalten:
 * <xsl:text/>volume<xsl:text/>
 * <xsl:text/>issue<xsl:text/>
Das Fehlen vom <xsl:text/>type<xsl:text/> verhindert nicht das Einspielen des Datensatzes in die DDB, kann aber zu Darstellungsproblemen führen. Wir bitten Sie daher den Sachverhalt zu prüfen und ggf. die nötigen Korrekturen bis zur nächsten Datenlieferung vorzunehmen.Weitere Informationen zu diesem Attribut und seinem Kontext finden Sie im DDB-METS/MODS-Anwendungsprofil auf der Seite mods:part (https://wiki.deutsche-digitale-bibliothek.de/x/ScMeB) und der Seite METS/MODS für Mehrteilige Dokumente (https://wiki.deutsche-digitale-bibliothek.de/x/RgGuB).</svrl:text>
               <svrl:property id="dmd_id">
                  <xsl:value-of xmlns:sch="http://purl.oclc.org/dsdl/schematron"
                                select="ancestor-or-self::mets:dmdSec/@ID"/>
               </svrl:property>
            </svrl:failed-assert>
         </xsl:otherwise>
      </xsl:choose>
      <xsl:apply-templates select="*" mode="M79"/>
   </xsl:template>
   <xsl:template match="text()" priority="-1" mode="M79"/>
   <xsl:template match="@*|node()" priority="-2" mode="M79">
      <xsl:apply-templates select="*" mode="M79"/>
   </xsl:template>
   <!--PATTERN -->
   <!--RULE -->
   <xsl:template match="mods:mods/mods:part" priority="1000" mode="M80">
      <svrl:fired-rule xmlns:svrl="http://purl.oclc.org/dsdl/svrl" context="mods:mods/mods:part"/>
      <!--REPORT error-->
      <xsl:if test="mods:detail[@type='volume'][mods:number][2] or mods:detail[mods:number[@type='volume']][2]">
         <svrl:successful-report xmlns:svrl="http://purl.oclc.org/dsdl/svrl"
                                 test="mods:detail[@type='volume'][mods:number][2] or mods:detail[mods:number[@type='volume']][2]">
            <xsl:attribute name="id">part_12</xsl:attribute>
            <xsl:attribute name="role">error</xsl:attribute>
            <xsl:attribute name="location">
               <xsl:apply-templates select="." mode="schematron-select-full-path"/>
            </xsl:attribute>
            <svrl:text>Das Element <xsl:text/>mods:part<xsl:text/> darf das Element <xsl:text/>mods:detail<xsl:text/> mit dem Attribut <xsl:text/>type<xsl:text/> mit dem Wert <xsl:text/>volume<xsl:text/> nur einmal enthalten.
Enthält <xsl:text/>mods:part<xsl:text/> mehr als ein entsprechendes <xsl:text/>mods:detail<xsl:text/>, werden bei der Transformation des Datensatzes alle entsprechenden Vorkommen von <xsl:text/>mods:detail<xsl:text/> im ersten Vorkommen von <xsl:text/>mods:detail<xsl:text/> zusammengefasst.Weitere Informationen zu diesem Element finden Sie im DDB-METS/MODS-Anwendungsprofil auf der Seite mods:part (https://wiki.deutsche-digitale-bibliothek.de/x/ScMeB).</svrl:text>
            <svrl:property id="dmd_id">
               <xsl:value-of xmlns:sch="http://purl.oclc.org/dsdl/schematron"
                             select="ancestor-or-self::mets:dmdSec/@ID"/>
            </svrl:property>
         </svrl:successful-report>
      </xsl:if>
      <xsl:apply-templates select="*" mode="M80"/>
   </xsl:template>
   <xsl:template match="text()" priority="-1" mode="M80"/>
   <xsl:template match="@*|node()" priority="-2" mode="M80">
      <xsl:apply-templates select="*" mode="M80"/>
   </xsl:template>
   <!--PATTERN -->
   <!--RULE -->
   <xsl:template match="mets:xmlData/mods:mods/mods:identifier"
                 priority="1000"
                 mode="M81">
      <svrl:fired-rule xmlns:svrl="http://purl.oclc.org/dsdl/svrl"
                       context="mets:xmlData/mods:mods/mods:identifier"/>
      <!--ASSERT info-->
      <xsl:choose>
         <xsl:when test="@type = ('purl', 'urn', 'isbn', 'issn', 'doi', 'handle', 'vd16', 'vd17', 'vd18', 'zdb')"/>
         <xsl:otherwise>
            <svrl:failed-assert xmlns:svrl="http://purl.oclc.org/dsdl/svrl"
                                test="@type = ('purl', 'urn', 'isbn', 'issn', 'doi', 'handle', 'vd16', 'vd17', 'vd18', 'zdb')">
               <xsl:attribute name="id">identifier_01</xsl:attribute>
               <xsl:attribute name="role">info</xsl:attribute>
               <xsl:attribute name="location">
                  <xsl:apply-templates select="." mode="schematron-select-full-path"/>
               </xsl:attribute>
               <svrl:text>Das Element <xsl:text/>mods:identifier<xsl:text/> muss das Attribut <xsl:text/>type<xsl:text/> mit einem der folgenden Werte enthalten:
 * <xsl:text/>doi<xsl:text/>
 * <xsl:text/>handle<xsl:text/>
 * <xsl:text/>isbn<xsl:text/>
 * <xsl:text/>issn<xsl:text/>
 * <xsl:text/>purl<xsl:text/>
 * <xsl:text/>urn<xsl:text/>
 * <xsl:text/>vd16<xsl:text/>
 * <xsl:text/>vd17<xsl:text/>
 * <xsl:text/>vd18<xsl:text/>
 * <xsl:text/>zdb<xsl:text/>
Fehlt <xsl:text/>type<xsl:text/> in <xsl:text/>mods:identifier<xsl:text/> bzw. enthält es einen ungültigen Wert, wird <xsl:text/>mods:identifier<xsl:text/> bei der Transformation des Datensatzes entfernt.Weitere Informationen zu diesem Attribut finden Sie im DDB-METS/MODS-Anwendungsprofil auf der Seite mods:identifier (https://wiki.deutsche-digitale-bibliothek.de/x/N8MeB).</svrl:text>
               <svrl:property id="dmd_id">
                  <xsl:value-of xmlns:sch="http://purl.oclc.org/dsdl/schematron"
                                select="ancestor-or-self::mets:dmdSec/@ID"/>
               </svrl:property>
               <svrl:property id="type">
                  <xsl:value-of xmlns:sch="http://purl.oclc.org/dsdl/schematron" select="./@type"/>
               </svrl:property>
            </svrl:failed-assert>
         </xsl:otherwise>
      </xsl:choose>
      <xsl:apply-templates select="*" mode="M81"/>
   </xsl:template>
   <xsl:template match="text()" priority="-1" mode="M81"/>
   <xsl:template match="@*|node()" priority="-2" mode="M81">
      <xsl:apply-templates select="*" mode="M81"/>
   </xsl:template>
   <!--PATTERN -->
   <!--RULE -->
   <xsl:template match="mets:xmlData/mods:mods/mods:location[not(mods:url)]"
                 priority="1000"
                 mode="M82">
      <svrl:fired-rule xmlns:svrl="http://purl.oclc.org/dsdl/svrl"
                       context="mets:xmlData/mods:mods/mods:location[not(mods:url)]"/>
      <!--ASSERT error-->
      <xsl:choose>
         <xsl:when test="mods:physicalLocation"/>
         <xsl:otherwise>
            <svrl:failed-assert xmlns:svrl="http://purl.oclc.org/dsdl/svrl" test="mods:physicalLocation">
               <xsl:attribute name="id">location_01</xsl:attribute>
               <xsl:attribute name="role">error</xsl:attribute>
               <xsl:attribute name="location">
                  <xsl:apply-templates select="." mode="schematron-select-full-path"/>
               </xsl:attribute>
               <svrl:text>Das Element <xsl:text/>mods:location<xsl:text/> muss das Element <xsl:text/>mods:physicalLocation<xsl:text/> oder das Element <xsl:text/>mods:url<xsl:text/> enthalten.
Ist dies nicht der Fall, wird <xsl:text/>mods:location<xsl:text/> bei der Transformation des Datensatzes entfernt.Weitere Informationen zu diesen Elementen finden Sie im DDB-METS/MODS-Anwendungsprofil auf der Seite mods:location (https://wiki.deutsche-digitale-bibliothek.de/x/PMMeB).</svrl:text>
               <svrl:property id="dmd_id">
                  <xsl:value-of xmlns:sch="http://purl.oclc.org/dsdl/schematron"
                                select="ancestor-or-self::mets:dmdSec/@ID"/>
               </svrl:property>
            </svrl:failed-assert>
         </xsl:otherwise>
      </xsl:choose>
      <xsl:apply-templates select="*" mode="M82"/>
   </xsl:template>
   <xsl:template match="text()" priority="-1" mode="M82"/>
   <xsl:template match="@*|node()" priority="-2" mode="M82">
      <xsl:apply-templates select="*" mode="M82"/>
   </xsl:template>
   <!--PATTERN -->
   <!--RULE -->
   <xsl:template match="mets:dmdSec[@ID=$work_dmdid]/mets:mdWrap/mets:xmlData/mods:mods"
                 priority="1000"
                 mode="M83">
      <svrl:fired-rule xmlns:svrl="http://purl.oclc.org/dsdl/svrl"
                       context="mets:dmdSec[@ID=$work_dmdid]/mets:mdWrap/mets:xmlData/mods:mods"/>
      <!--ASSERT warn-->
      <xsl:choose>
         <xsl:when test="mods:location/mods:physicalLocation"/>
         <xsl:otherwise>
            <svrl:failed-assert xmlns:svrl="http://purl.oclc.org/dsdl/svrl"
                                test="mods:location/mods:physicalLocation">
               <xsl:attribute name="id">location_03</xsl:attribute>
               <xsl:attribute name="role">warn</xsl:attribute>
               <xsl:attribute name="location">
                  <xsl:apply-templates select="." mode="schematron-select-full-path"/>
               </xsl:attribute>
               <svrl:text>Das primäre <xsl:text/>mets:dmdSec<xsl:text/>-Element muss das Element <xsl:text/>mods:location<xsl:text/> mit dem Unterelement <xsl:text/>mods:physicalLocation<xsl:text/> enthalten.
Das Fehlen von <xsl:text/>mods:physicalLocation<xsl:text/> verhindert nicht das Einspielen des Datensatzes in die DDB, Ihr Objekt wird dort aber ohne Standorts angezeigt. Wir bitten Sie daher, den Sachverhalt zu prüfen und die nötigen Korrekturen bis zur nächsten Datenlieferung vorzunehmen.Weitere Informationen zu diesem Element finden Sie im DDB-METS/MODS-Anwendungsprofil auf der Seite mods:location (https://wiki.deutsche-digitale-bibliothek.de/x/PMMeB).</svrl:text>
               <svrl:property id="dmd_id">
                  <xsl:value-of xmlns:sch="http://purl.oclc.org/dsdl/schematron"
                                select="ancestor-or-self::mets:dmdSec/@ID"/>
               </svrl:property>
            </svrl:failed-assert>
         </xsl:otherwise>
      </xsl:choose>
      <xsl:apply-templates select="*" mode="M83"/>
   </xsl:template>
   <xsl:template match="text()" priority="-1" mode="M83"/>
   <xsl:template match="@*|node()" priority="-2" mode="M83">
      <xsl:apply-templates select="*" mode="M83"/>
   </xsl:template>
   <!--PATTERN -->
   <!--RULE -->
   <xsl:template match="mets:xmlData/mods:mods/mods:location/mods:physicalLocation[starts-with(text()[1], 'DE-')]"
                 priority="1000"
                 mode="M84">
      <svrl:fired-rule xmlns:svrl="http://purl.oclc.org/dsdl/svrl"
                       context="mets:xmlData/mods:mods/mods:location/mods:physicalLocation[starts-with(text()[1], 'DE-')]"/>
      <!--ASSERT warn-->
      <xsl:choose>
         <xsl:when test="starts-with(@valueURI, 'http://ld.zdb-services.de/resource/organisations/') or starts-with(@valueURI, 'https://ld.zdb-services.de/resource/organisations/') or starts-with(@valueURI, 'http://lobid.org/organisations/') or starts-with(@valueURI, 'https://lobid.org/organisations/')"/>
         <xsl:otherwise>
            <svrl:failed-assert xmlns:svrl="http://purl.oclc.org/dsdl/svrl"
                                test="starts-with(@valueURI, 'http://ld.zdb-services.de/resource/organisations/') or starts-with(@valueURI, 'https://ld.zdb-services.de/resource/organisations/') or starts-with(@valueURI, 'http://lobid.org/organisations/') or starts-with(@valueURI, 'https://lobid.org/organisations/')">
               <xsl:attribute name="id">location_04</xsl:attribute>
               <xsl:attribute name="role">warn</xsl:attribute>
               <xsl:attribute name="location">
                  <xsl:apply-templates select="." mode="schematron-select-full-path"/>
               </xsl:attribute>
               <svrl:text>Enthält das Element <xsl:text/>mods:physicalLocation<xsl:text/> im Element <xsl:text/>mods:location<xsl:text/> als Wert einen ISIL, muss das Attribut <xsl:text/>valueURI<xsl:text/> von <xsl:text/>mods:physicalLocation<xsl:text/> einen entsprenden URI der ISIL enthalten.
Die DDB unterstützt entsprechende URI der Deutschen ISIL-Agentur und Sigelstelle (https://sigel.staatsbibliothek-berlin.de) (z. B. <xsl:text/>https://ld.zdb-services.de/resource/organisations/DE-7<xsl:text/>) und lobid (http://lobid.org/organisations) (z. B. <xsl:text/>https://lobid.org/organisations/DE-7<xsl:text/>).
Das Fehlen von <xsl:text/>valueURI<xsl:text/> verhindert nicht das Einspielen des Datensatzes in die DDB, wir bitten Sie jedoch, den Sachverhalt zu prüfen und die nötigen Korrekturen bis zur nächsten Datenlieferung vorzunehmen.Weitere Informationen zu diesem Attribut finden Sie im DDB-METS/MODS-Anwendungsprofil auf der Seite mods:location (https://wiki.deutsche-digitale-bibliothek.de/x/PMMeB).</svrl:text>
               <svrl:property id="dmd_id">
                  <xsl:value-of xmlns:sch="http://purl.oclc.org/dsdl/schematron"
                                select="ancestor-or-self::mets:dmdSec/@ID"/>
               </svrl:property>
            </svrl:failed-assert>
         </xsl:otherwise>
      </xsl:choose>
      <xsl:apply-templates select="*" mode="M84"/>
   </xsl:template>
   <xsl:template match="text()" priority="-1" mode="M84"/>
   <xsl:template match="@*|node()" priority="-2" mode="M84">
      <xsl:apply-templates select="*" mode="M84"/>
   </xsl:template>
   <!--PATTERN -->
   <!--RULE -->
   <xsl:template match="mets:xmlData/mods:mods/mods:location[mods:physicalLocation]"
                 priority="1000"
                 mode="M85">
      <svrl:fired-rule xmlns:svrl="http://purl.oclc.org/dsdl/svrl"
                       context="mets:xmlData/mods:mods/mods:location[mods:physicalLocation]"/>
      <!--REPORT error-->
      <xsl:if test="mods:physicalLocation[2]">
         <svrl:successful-report xmlns:svrl="http://purl.oclc.org/dsdl/svrl" test="mods:physicalLocation[2]">
            <xsl:attribute name="id">location_05</xsl:attribute>
            <xsl:attribute name="role">error</xsl:attribute>
            <xsl:attribute name="location">
               <xsl:apply-templates select="." mode="schematron-select-full-path"/>
            </xsl:attribute>
            <svrl:text>Das Element <xsl:text/>mods:location<xsl:text/> darf das Element <xsl:text/>mods:physicalLocation<xsl:text/> nur einmal enthalten. Enthält <xsl:text/>mods:titleInfo<xsl:text/> mehr als ein <xsl:text/>mods:physicalLocation<xsl:text/>, wird bei der Transformation des Datensatzes das erste Vorkommen von <xsl:text/>mods:physicalLocation<xsl:text/> übernommen, alle anderen <xsl:text/>mods:physicalLocation<xsl:text/> werden entfernt.Weitere Informationen zu diesem Element finden Sie im DDB-METS/MODS-Anwendungsprofil auf der Seite mods:location (https://wiki.deutsche-digitale-bibliothek.de/x/PMMeB).</svrl:text>
            <svrl:property id="dmd_id">
               <xsl:value-of xmlns:sch="http://purl.oclc.org/dsdl/schematron"
                             select="ancestor-or-self::mets:dmdSec/@ID"/>
            </svrl:property>
         </svrl:successful-report>
      </xsl:if>
      <xsl:apply-templates select="*" mode="M85"/>
   </xsl:template>
   <xsl:template match="text()" priority="-1" mode="M85"/>
   <xsl:template match="@*|node()" priority="-2" mode="M85">
      <xsl:apply-templates select="*" mode="M85"/>
   </xsl:template>
   <!--PATTERN -->
   <!--RULE -->
   <xsl:template match="mets:xmlData/mods:mods/mods:location/mods:url"
                 priority="1000"
                 mode="M86">
      <svrl:fired-rule xmlns:svrl="http://purl.oclc.org/dsdl/svrl"
                       context="mets:xmlData/mods:mods/mods:location/mods:url"/>
      <!--ASSERT error-->
      <xsl:choose>
         <xsl:when test="./@access = ('preview', 'object in context', 'raw object')"/>
         <xsl:otherwise>
            <svrl:failed-assert xmlns:svrl="http://purl.oclc.org/dsdl/svrl"
                                test="./@access = ('preview', 'object in context', 'raw object')">
               <xsl:attribute name="id">location_06</xsl:attribute>
               <xsl:attribute name="role">error</xsl:attribute>
               <xsl:attribute name="location">
                  <xsl:apply-templates select="." mode="schematron-select-full-path"/>
               </xsl:attribute>
               <svrl:text>Das Element <xsl:text/>mods:url<xsl:text/> wird zurzeit bei der Transformation des Datensatzes entfernt.
Perspektivisch unterstützt die DDB <xsl:text/>mods:url<xsl:text/> nur mit dem Attribut <xsl:text/>access<xsl:text/> mit einem der folgenden Werte:
 * <xsl:text/>preview<xsl:text/>
 * <xsl:text/>object in context<xsl:text/>
 * <xsl:text/>raw object<xsl:text/>Weitere Informationen zu diesem Attribut finden Sie im DDB-METS/MODS-Anwendungsprofil auf der Seite mods:location (https://wiki.deutsche-digitale-bibliothek.de/x/PMMeB).</svrl:text>
               <svrl:property id="dmd_id">
                  <xsl:value-of xmlns:sch="http://purl.oclc.org/dsdl/schematron"
                                select="ancestor-or-self::mets:dmdSec/@ID"/>
               </svrl:property>
            </svrl:failed-assert>
         </xsl:otherwise>
      </xsl:choose>
      <xsl:apply-templates select="*" mode="M86"/>
   </xsl:template>
   <xsl:template match="text()" priority="-1" mode="M86"/>
   <xsl:template match="@*|node()" priority="-2" mode="M86">
      <xsl:apply-templates select="*" mode="M86"/>
   </xsl:template>
   <!--PATTERN -->
   <!--RULE -->
   <xsl:template match="mets:xmlData/mods:mods/mods:location[mods:physicalLocation]"
                 priority="1000"
                 mode="M87">
      <svrl:fired-rule xmlns:svrl="http://purl.oclc.org/dsdl/svrl"
                       context="mets:xmlData/mods:mods/mods:location[mods:physicalLocation]"/>
      <xsl:variable name="current_physicalLocation" select="mods:physicalLocation[1]"/>
      <!--REPORT error-->
      <xsl:if test="./preceding-sibling::mods:location[mods:physicalLocation/text() != $current_physicalLocation]">
         <svrl:successful-report xmlns:svrl="http://purl.oclc.org/dsdl/svrl"
                                 test="./preceding-sibling::mods:location[mods:physicalLocation/text() != $current_physicalLocation]">
            <xsl:attribute name="id">location_07</xsl:attribute>
            <xsl:attribute name="role">error</xsl:attribute>
            <xsl:attribute name="location">
               <xsl:apply-templates select="." mode="schematron-select-full-path"/>
            </xsl:attribute>
            <svrl:text>Der Datensatz enthält <xsl:text/>mods:location<xsl:text/>-Elemente mit unterschiedlichen Werten im Unterelement <xsl:text/>mods:physicalLocation<xsl:text/>.
Ein Dokument kann nicht mehrere Standorte besitzen und daher werden bei der Transformation des Datensatzes die weiteren Vorkommen (XML-Reihenfolge) von <xsl:text/>mods:physicalLocation<xsl:text/> innerhalb von <xsl:text/>mods:location<xsl:text/> bzw. die entsprechenden weiteren Vorkommen von <xsl:text/>mods:location<xsl:text/> entfernt.Weitere Informationen zu diesem Element finden Sie im DDB-METS/MODS-Anwendungsprofil auf der Seite mods:location (https://wiki.deutsche-digitale-bibliothek.de/x/PMMeB).</svrl:text>
            <svrl:property id="dmd_id">
               <xsl:value-of xmlns:sch="http://purl.oclc.org/dsdl/schematron"
                             select="ancestor-or-self::mets:dmdSec/@ID"/>
            </svrl:property>
         </svrl:successful-report>
      </xsl:if>
      <xsl:apply-templates select="*" mode="M87"/>
   </xsl:template>
   <xsl:template match="text()" priority="-1" mode="M87"/>
   <xsl:template match="@*|node()" priority="-2" mode="M87">
      <xsl:apply-templates select="*" mode="M87"/>
   </xsl:template>
   <!--PATTERN -->
   <!--RULE -->
   <xsl:template match="mods:accessCondition[@type='use and reproduction']/@*[local-name()= 'href']"
                 priority="1000"
                 mode="M88">
      <svrl:fired-rule xmlns:svrl="http://purl.oclc.org/dsdl/svrl"
                       context="mods:accessCondition[@type='use and reproduction']/@*[local-name()= 'href']"/>
      <!--ASSERT error-->
      <xsl:choose>
         <xsl:when test="namespace-uri() = 'http://www.w3.org/1999/xlink'"/>
         <xsl:otherwise>
            <svrl:failed-assert xmlns:svrl="http://purl.oclc.org/dsdl/svrl"
                                test="namespace-uri() = 'http://www.w3.org/1999/xlink'">
               <xsl:attribute name="id">accessCondition_02</xsl:attribute>
               <xsl:attribute name="role">error</xsl:attribute>
               <xsl:attribute name="location">
                  <xsl:apply-templates select="." mode="schematron-select-full-path"/>
               </xsl:attribute>
               <svrl:text>Das Attribut <xsl:text/>href<xsl:text/> im Element <xsl:text/>mods:accessCondition<xsl:text/> muss zum Namensraum <xsl:text/>http://www.w3.org/1999/xlink<xsl:text/> gehören.
Ist dies nicht der Fall, wird bei der Transformation das erste Vorkommen des Attributs <xsl:text/>href<xsl:text/> in den Namensraum <xsl:text/>http://www.w3.org/1999/xlink<xsl:text/> gesetzt und entsprechend ausgewertet.Weitere Informationen zu diesem Attribut finden Sie im DDB-METS/MODS-Anwendungsprofil auf der Seite mods:accessCondition (https://wiki.deutsche-digitale-bibliothek.de/x/Q8MeB).</svrl:text>
               <svrl:property id="dmd_id">
                  <xsl:value-of xmlns:sch="http://purl.oclc.org/dsdl/schematron"
                                select="ancestor-or-self::mets:dmdSec/@ID"/>
               </svrl:property>
            </svrl:failed-assert>
         </xsl:otherwise>
      </xsl:choose>
      <xsl:apply-templates select="*" mode="M88"/>
   </xsl:template>
   <xsl:template match="text()" priority="-1" mode="M88"/>
   <xsl:template match="@*|node()" priority="-2" mode="M88">
      <xsl:apply-templates select="*" mode="M88"/>
   </xsl:template>
   <!--PATTERN -->
   <!--RULE -->
   <xsl:template match="mets:dmdSec[@ID=$work_dmdid]/mets:mdWrap/mets:xmlData/mods:mods"
                 priority="1000"
                 mode="M89">
      <svrl:fired-rule xmlns:svrl="http://purl.oclc.org/dsdl/svrl"
                       context="mets:dmdSec[@ID=$work_dmdid]/mets:mdWrap/mets:xmlData/mods:mods"/>
      <!--ASSERT fatal-->
      <xsl:choose>
         <xsl:when test="mods:recordInfo/mods:recordIdentifier"/>
         <xsl:otherwise>
            <svrl:failed-assert xmlns:svrl="http://purl.oclc.org/dsdl/svrl"
                                test="mods:recordInfo/mods:recordIdentifier">
               <xsl:attribute name="id">recordInfo_01</xsl:attribute>
               <xsl:attribute name="role">fatal</xsl:attribute>
               <xsl:attribute name="location">
                  <xsl:apply-templates select="." mode="schematron-select-full-path"/>
               </xsl:attribute>
               <svrl:text>Das primäre <xsl:text/>mets:dmdSec<xsl:text/>-Element muss das Element <xsl:text/>mods:recordInfo<xsl:text/> mit dem Unterelement <xsl:text/>mods:recordIdentifier<xsl:text/> enthalten. <xsl:text/>mods:recordIdentifier<xsl:text/> muss darüber hinaus das Attribut <xsl:text/>source<xsl:text/> enthalten um eindeutig identifizierbar sein.
Fehlt <xsl:text/>mods:recordIdentifier<xsl:text/> mit dem Attribut <xsl:text/>source<xsl:text/>, wird der Datensatz nicht in die DDB eingespielt.Weitere Informationen zu diesem Element finden Sie im DDB-METS/MODS-Anwendungsprofil auf der Seite mods:recordInfo (https://wiki.deutsche-digitale-bibliothek.de/x/TMMeB).</svrl:text>
               <svrl:property id="dmd_id">
                  <xsl:value-of xmlns:sch="http://purl.oclc.org/dsdl/schematron"
                                select="ancestor-or-self::mets:dmdSec/@ID"/>
               </svrl:property>
            </svrl:failed-assert>
         </xsl:otherwise>
      </xsl:choose>
      <xsl:apply-templates select="*" mode="M89"/>
   </xsl:template>
   <xsl:template match="text()" priority="-1" mode="M89"/>
   <xsl:template match="@*|node()" priority="-2" mode="M89">
      <xsl:apply-templates select="*" mode="M89"/>
   </xsl:template>
   <!--PATTERN -->
   <!--RULE -->
   <xsl:template match="mets:xmlData/mods:mods/mods:recordInfo/mods:recordIdentifier"
                 priority="1000"
                 mode="M90">
      <svrl:fired-rule xmlns:svrl="http://purl.oclc.org/dsdl/svrl"
                       context="mets:xmlData/mods:mods/mods:recordInfo/mods:recordIdentifier"/>
      <!--ASSERT fatal-->
      <xsl:choose>
         <xsl:when test="string-length(normalize-space(@source)) &gt; 0"/>
         <xsl:otherwise>
            <svrl:failed-assert xmlns:svrl="http://purl.oclc.org/dsdl/svrl"
                                test="string-length(normalize-space(@source)) &gt; 0">
               <xsl:attribute name="id">recordInfo_02</xsl:attribute>
               <xsl:attribute name="role">fatal</xsl:attribute>
               <xsl:attribute name="location">
                  <xsl:apply-templates select="." mode="schematron-select-full-path"/>
               </xsl:attribute>
               <svrl:text>Das Element <xsl:text/>mods:recordIdentifier<xsl:text/> im Element <xsl:text/>mods:recordInfo<xsl:text/> muss das Attribut <xsl:text/>source<xsl:text/> enthalten, damit der Wert in <xsl:text/>mods:recordIdentifier<xsl:text/> eindeutig identifizierbar ist.
Fehlt <xsl:text/>source<xsl:text/> in <xsl:text/>mods:recordIdentifier<xsl:text/> wird der Datensatz nicht in die DDB eingespielt.Weitere Informationen zu diesem Attribut finden Sie im DDB-METS/MODS-Anwendungsprofil auf der Seite mods:recordInfo (https://wiki.deutsche-digitale-bibliothek.de/x/TMMeB).</svrl:text>
               <svrl:property id="dmd_id">
                  <xsl:value-of xmlns:sch="http://purl.oclc.org/dsdl/schematron"
                                select="ancestor-or-self::mets:dmdSec/@ID"/>
               </svrl:property>
            </svrl:failed-assert>
         </xsl:otherwise>
      </xsl:choose>
      <xsl:apply-templates select="*" mode="M90"/>
   </xsl:template>
   <xsl:template match="text()" priority="-1" mode="M90"/>
   <xsl:template match="@*|node()" priority="-2" mode="M90">
      <xsl:apply-templates select="*" mode="M90"/>
   </xsl:template>
   <!--PATTERN -->
   <!--RULE -->
   <xsl:template match="mets:xmlData/mods:mods" priority="1000" mode="M91">
      <svrl:fired-rule xmlns:svrl="http://purl.oclc.org/dsdl/svrl"
                       context="mets:xmlData/mods:mods"/>
      <!--REPORT error-->
      <xsl:if test="mods:recordInfo/mods:recordIdentifier[2] or mods:recordInfo[mods:recordIdentifier][2]">
         <svrl:successful-report xmlns:svrl="http://purl.oclc.org/dsdl/svrl"
                                 test="mods:recordInfo/mods:recordIdentifier[2] or mods:recordInfo[mods:recordIdentifier][2]">
            <xsl:attribute name="id">recordInfo_03</xsl:attribute>
            <xsl:attribute name="role">error</xsl:attribute>
            <xsl:attribute name="location">
               <xsl:apply-templates select="." mode="schematron-select-full-path"/>
            </xsl:attribute>
            <svrl:text>Das Element <xsl:text/>mods:recordInfo<xsl:text/> darf das Element <xsl:text/>mods:recordIdentifier<xsl:text/> nur einmal enthalten.
Enthält <xsl:text/>mods:recordInfo<xsl:text/> mehr als ein <xsl:text/>mods:recordIdentifier<xsl:text/>, teilen Sie uns bitte mit welches <xsl:text/>mods:recordIdentifier<xsl:text/> in Abhängigkeit des Wertes im Attribut <xsl:text/>source<xsl:text/> bei der Transformation des Datensatzes erhalten bleibt. Alle anderen Vorkommen von <xsl:text/>mods:recordIdentifier<xsl:text/> werden entfernt.Weitere Informationen zu diesem Element finden Sie im DDB-METS/MODS-Anwendungsprofil auf der Seite mods:recordInfo (https://wiki.deutsche-digitale-bibliothek.de/x/TMMeB).</svrl:text>
            <svrl:property id="dmd_id">
               <xsl:value-of xmlns:sch="http://purl.oclc.org/dsdl/schematron"
                             select="ancestor-or-self::mets:dmdSec/@ID"/>
            </svrl:property>
         </svrl:successful-report>
      </xsl:if>
      <xsl:apply-templates select="*" mode="M91"/>
   </xsl:template>
   <xsl:template match="text()" priority="-1" mode="M91"/>
   <xsl:template match="@*|node()" priority="-2" mode="M91">
      <xsl:apply-templates select="*" mode="M91"/>
   </xsl:template>
   <!--PATTERN -->
   <!--RULE -->
   <xsl:template match="mets:xmlData/mods:mods/mods:recordInfo/mods:recordIdentifier"
                 priority="1000"
                 mode="M92">
      <svrl:fired-rule xmlns:svrl="http://purl.oclc.org/dsdl/svrl"
                       context="mets:xmlData/mods:mods/mods:recordInfo/mods:recordIdentifier"/>
      <!--ASSERT fatal-->
      <xsl:choose>
         <xsl:when test="matches(text()[1], '^[^ /]+$')"/>
         <xsl:otherwise>
            <svrl:failed-assert xmlns:svrl="http://purl.oclc.org/dsdl/svrl"
                                test="matches(text()[1], '^[^ /]+$')">
               <xsl:attribute name="id">recordInfo_04</xsl:attribute>
               <xsl:attribute name="role">fatal</xsl:attribute>
               <xsl:attribute name="location">
                  <xsl:apply-templates select="." mode="schematron-select-full-path"/>
               </xsl:attribute>
               <svrl:text>Der Wert im Element <xsl:text/>mods:recordIdentifier<xsl:text/> enthält Leer- und / oder Sonderzeichen. Dies kann zu Problemen bei der Verarbeitung in der DDB führen, daher wird der Datensatz nicht in die DDB eingespielt.Weitere Informationen zu diesem Element finden Sie im DDB-METS/MODS-Anwendungsprofil auf der Seite mods:recordInfo (https://wiki.deutsche-digitale-bibliothek.de/x/TMMeB).</svrl:text>
               <svrl:property id="dmd_id">
                  <xsl:value-of xmlns:sch="http://purl.oclc.org/dsdl/schematron"
                                select="ancestor-or-self::mets:dmdSec/@ID"/>
               </svrl:property>
            </svrl:failed-assert>
         </xsl:otherwise>
      </xsl:choose>
      <xsl:apply-templates select="*" mode="M92"/>
   </xsl:template>
   <xsl:template match="text()" priority="-1" mode="M92"/>
   <xsl:template match="@*|node()" priority="-2" mode="M92">
      <xsl:apply-templates select="*" mode="M92"/>
   </xsl:template>
   <!--PATTERN -->
   <!--RULE -->
   <xsl:template match="mets:xmlData/mods:mods" priority="1000" mode="M93">
      <svrl:fired-rule xmlns:svrl="http://purl.oclc.org/dsdl/svrl"
                       context="mets:xmlData/mods:mods"/>
      <!--REPORT error-->
      <xsl:if test="mods:recordInfo[2]">
         <svrl:successful-report xmlns:svrl="http://purl.oclc.org/dsdl/svrl" test="mods:recordInfo[2]">
            <xsl:attribute name="id">recordInfo_05</xsl:attribute>
            <xsl:attribute name="role">error</xsl:attribute>
            <xsl:attribute name="location">
               <xsl:apply-templates select="." mode="schematron-select-full-path"/>
            </xsl:attribute>
            <svrl:text>Das Element <xsl:text/>mods:mods<xsl:text/> darf das Element <xsl:text/>mods:recordInfo<xsl:text/> nur einmal enthalten. Enthält <xsl:text/>mods:mods<xsl:text/> mehr als ein <xsl:text/>mods:recordInfo<xsl:text/>, werden bei der Bereinigung des Datensatzes die Unterelemente aller <xsl:text/>mods:recordInfo<xsl:text/> im erste Vorkommen zusammengefasst und die weiteren Vorkommen von <xsl:text/>mods:recordInfo<xsl:text/> entfernt.Weitere Informationen zu diesem Element finden Sie im DDB-METS/MODS-Anwendungsprofil auf der Seite mods:recordInfo (https://wiki.deutsche-digitale-bibliothek.de/x/TMMeB).</svrl:text>
            <svrl:property id="dmd_id">
               <xsl:value-of xmlns:sch="http://purl.oclc.org/dsdl/schematron"
                             select="ancestor-or-self::mets:dmdSec/@ID"/>
            </svrl:property>
         </svrl:successful-report>
      </xsl:if>
      <xsl:apply-templates select="*" mode="M93"/>
   </xsl:template>
   <xsl:template match="text()" priority="-1" mode="M93"/>
   <xsl:template match="@*|node()" priority="-2" mode="M93">
      <xsl:apply-templates select="*" mode="M93"/>
   </xsl:template>
   <!--PATTERN -->
   <!--RULE -->
   <xsl:template match="mets:mets" priority="1001" mode="M94">
      <svrl:fired-rule xmlns:svrl="http://purl.oclc.org/dsdl/svrl" context="mets:mets"/>
      <!--ASSERT fatal-->
      <xsl:choose>
         <xsl:when test="mets:dmdSec[@ID=$work_dmdid]/mets:mdWrap/mets:xmlData/mods:mods"/>
         <xsl:otherwise>
            <svrl:failed-assert xmlns:svrl="http://purl.oclc.org/dsdl/svrl"
                                test="mets:dmdSec[@ID=$work_dmdid]/mets:mdWrap/mets:xmlData/mods:mods">
               <xsl:attribute name="id">dmdSec_01</xsl:attribute>
               <xsl:attribute name="role">fatal</xsl:attribute>
               <xsl:attribute name="location">
                  <xsl:apply-templates select="." mode="schematron-select-full-path"/>
               </xsl:attribute>
               <svrl:text>Das primäre <xsl:text/>mets:dmdSec<xsl:text/>-Element beschreibt das gesamte im Datensatz beschriebene Dokument. Es muss das Unterelement <xsl:text/>mets:mdWrap<xsl:text/> mit dem Unterelement <xsl:text/>mets:xmlData<xsl:text/> enthalten, welches ein Unterelement <xsl:text/>mods:mods<xsl:text/> besitzt.
Die Selektion des primären <xsl:text/>mets:dmdSec<xsl:text/> erfolgt über ein im Element <xsl:text/>mets:structMap[@TYPE='LOGICAL']<xsl:text/> enthaltenes <xsl:text/>mets:div<xsl:text/>-Element über die folgenden Kriterien:
 * <xsl:text/>mets:structMap[@TYPE='LOGICAL']<xsl:text/> enthält kein <xsl:text/>mets:div<xsl:text/> mit dem Unterelement <xsl:text/>mets:mptr<xsl:text/>: Das Kind von <xsl:text/>mets:structMap[@TYPE='LOGICAL']<xsl:text/>
 * <xsl:text/>mets:structMap[@TYPE='LOGICAL']<xsl:text/> enthält ein <xsl:text/>mets:div<xsl:text/> mit dem Unterelement <xsl:text/>mets:mptr<xsl:text/>: Das Kind des <xsl:text/>mets:div<xsl:text/> mit dem Unterelement <xsl:text/>mets:mptr<xsl:text/>
Das nach den o. g. Kriterien selektierte <xsl:text/>mets:div<xsl:text/> referenziert über sein Attribut <xsl:text/>DMDID<xsl:text/> das Attribut <xsl:text/>ID<xsl:text/> des primären <xsl:text/>mets:dmdSec<xsl:text/>.
Fehlt das primäre <xsl:text/>mets:dmdSec<xsl:text/> bzw. ist keine eindeutige Selektion möglich, wird der Datensatz nicht in die DDB eingespielt.Weitere Informationen zu diesen Elementen und Ihrem Kontext finden Sie im DDB-METS/MODS-Anwendungsprofil auf den Seiten mets:dmdSec (https://wiki.deutsche-digitale-bibliothek.de/x/mMIeB) und mets:structMap[@TYPE='LOGICAL'] (https://wiki.deutsche-digitale-bibliothek.de/x/o8IeB) sowie im Bereich Aufbau einer METS/MODS-Datei für die DDB (https://wiki.deutsche-digitale-bibliothek.de/x/VcIeB).</svrl:text>
               <svrl:property id="id">
                  <xsl:value-of xmlns:sch="http://purl.oclc.org/dsdl/schematron" select="./@ID"/>
               </svrl:property>
            </svrl:failed-assert>
         </xsl:otherwise>
      </xsl:choose>
      <xsl:apply-templates select="*" mode="M94"/>
   </xsl:template>
   <!--RULE -->
   <xsl:template match="mets:dmdSec[not(@ID=$work_dmdid)]"
                 priority="1000"
                 mode="M94">
      <svrl:fired-rule xmlns:svrl="http://purl.oclc.org/dsdl/svrl"
                       context="mets:dmdSec[not(@ID=$work_dmdid)]"/>
      <!--ASSERT error-->
      <xsl:choose>
         <xsl:when test="mets:mdWrap/mets:xmlData/mods:mods"/>
         <xsl:otherwise>
            <svrl:failed-assert xmlns:svrl="http://purl.oclc.org/dsdl/svrl"
                                test="mets:mdWrap/mets:xmlData/mods:mods">
               <xsl:attribute name="id">dmdSec_02</xsl:attribute>
               <xsl:attribute name="role">error</xsl:attribute>
               <xsl:attribute name="location">
                  <xsl:apply-templates select="." mode="schematron-select-full-path"/>
               </xsl:attribute>
               <svrl:text>Das Element <xsl:text/>mets:dmdSec<xsl:text/> muss das Unterelement <xsl:text/>mets:mdWrap<xsl:text/> mit dem Unterelement <xsl:text/>mets:xmlData<xsl:text/> enthalten, welches ein Unterelement <xsl:text/>mods:mods<xsl:text/> besitzt.
Ist dies nicht der Fall, wird <xsl:text/>mets:dmdSec<xsl:text/> bei der Transformation des Datensatzes entfernt.Weitere Informationen zu diesem Element finden Sie im DDB-METS/MODS-Anwendungsprofil auf der Seite mets:dmdSec (https://wiki.deutsche-digitale-bibliothek.de/x/mMIeB).</svrl:text>
               <svrl:property id="id">
                  <xsl:value-of xmlns:sch="http://purl.oclc.org/dsdl/schematron" select="./@ID"/>
               </svrl:property>
            </svrl:failed-assert>
         </xsl:otherwise>
      </xsl:choose>
      <xsl:apply-templates select="*" mode="M94"/>
   </xsl:template>
   <xsl:template match="text()" priority="-1" mode="M94"/>
   <xsl:template match="@*|node()" priority="-2" mode="M94">
      <xsl:apply-templates select="*" mode="M94"/>
   </xsl:template>
   <!--PATTERN -->
   <!--RULE -->
   <xsl:template match="mets:dmdSec" priority="1000" mode="M95">
      <svrl:fired-rule xmlns:svrl="http://purl.oclc.org/dsdl/svrl" context="mets:dmdSec"/>
      <!--ASSERT fatal-->
      <xsl:choose>
         <xsl:when test="count(key('mets_ids', @ID)) = 1 and matches(@ID, '^[\i-[:]][\c-[:]]*$')"/>
         <xsl:otherwise>
            <svrl:failed-assert xmlns:svrl="http://purl.oclc.org/dsdl/svrl"
                                test="count(key('mets_ids', @ID)) = 1 and matches(@ID, '^[\i-[:]][\c-[:]]*$')">
               <xsl:attribute name="id">dmdSec_03</xsl:attribute>
               <xsl:attribute name="role">fatal</xsl:attribute>
               <xsl:attribute name="location">
                  <xsl:apply-templates select="." mode="schematron-select-full-path"/>
               </xsl:attribute>
               <svrl:text>Das Element <xsl:text/>mets:dmdSec<xsl:text/> muss das Attribut <xsl:text/>ID<xsl:text/> mit einem im Datensatz eindeutigen Identifier enthalten. Dieser darf darüber hinaus keine ungültigen Zeichen enthalten.
Ist dies nicht der Fall, wird der Datensatz nicht in die DDB eingespielt.Weitere Informationen zu diesem Element und Attribut finden Sie im DDB-METS/MODS-Anwendungsprofil auf der Seite mets:dmdSec (https://wiki.deutsche-digitale-bibliothek.de/x/mMIeB).</svrl:text>
            </svrl:failed-assert>
         </xsl:otherwise>
      </xsl:choose>
      <xsl:apply-templates select="*" mode="M95"/>
   </xsl:template>
   <xsl:template match="text()" priority="-1" mode="M95"/>
   <xsl:template match="@*|node()" priority="-2" mode="M95">
      <xsl:apply-templates select="*" mode="M95"/>
   </xsl:template>
   <!--PATTERN -->
   <!--RULE -->
   <xsl:template match="mets:dmdSec" priority="1000" mode="M96">
      <svrl:fired-rule xmlns:svrl="http://purl.oclc.org/dsdl/svrl" context="mets:dmdSec"/>
      <!--ASSERT error-->
      <xsl:choose>
         <xsl:when test="key('structMap_LOGICAL_dmdids', @ID)"/>
         <xsl:otherwise>
            <svrl:failed-assert xmlns:svrl="http://purl.oclc.org/dsdl/svrl"
                                test="key('structMap_LOGICAL_dmdids', @ID)">
               <xsl:attribute name="id">dmdSec_04</xsl:attribute>
               <xsl:attribute name="role">error</xsl:attribute>
               <xsl:attribute name="location">
                  <xsl:apply-templates select="." mode="schematron-select-full-path"/>
               </xsl:attribute>
               <svrl:text>Das Element <xsl:text/>mets:dmdSec<xsl:text/> muss genau einmal von einem <xsl:text/>mets:div<xsl:text/>-Element innerhalb des Elements <xsl:text/>mets:structMap[@TYPE='LOGICAL']<xsl:text/> referenziert werden.
Die Referenzierung erfolgt über einen Wert im Attribut <xsl:text/>DMDID<xsl:text/> von <xsl:text/>mets:div<xsl:text/> auf das Attribut <xsl:text/>ID<xsl:text/> des Elements <xsl:text/>mets:dmdSec<xsl:text/>.
Fehlt diese Referenz, wird <xsl:text/>mets:dmdSec<xsl:text/> bei der Transformation des Datensatzes entfernt.Weitere Informationen zu diesen Elementen und Ihrem Kontext finden Sie im DDB-METS/MODS-Anwendungsprofil auf den Seiten mets:dmdSec (https://wiki.deutsche-digitale-bibliothek.de/x/mMIeB) und mets:structMap[@TYPE='LOGICAL'] (https://wiki.deutsche-digitale-bibliothek.de/x/o8IeB) sowie im Bereich Aufbau einer METS/MODS-Datei für die DDB (https://wiki.deutsche-digitale-bibliothek.de/x/VcIeB).</svrl:text>
               <svrl:property id="id">
                  <xsl:value-of xmlns:sch="http://purl.oclc.org/dsdl/schematron" select="./@ID"/>
               </svrl:property>
            </svrl:failed-assert>
         </xsl:otherwise>
      </xsl:choose>
      <xsl:apply-templates select="*" mode="M96"/>
   </xsl:template>
   <xsl:template match="text()" priority="-1" mode="M96"/>
   <xsl:template match="@*|node()" priority="-2" mode="M96">
      <xsl:apply-templates select="*" mode="M96"/>
   </xsl:template>
   <!--PATTERN -->
   <!--RULE -->
   <xsl:template match="mets:mets" priority="1001" mode="M97">
      <svrl:fired-rule xmlns:svrl="http://purl.oclc.org/dsdl/svrl" context="mets:mets"/>
      <!--ASSERT fatal-->
      <xsl:choose>
         <xsl:when test="mets:amdSec"/>
         <xsl:otherwise>
            <svrl:failed-assert xmlns:svrl="http://purl.oclc.org/dsdl/svrl" test="mets:amdSec">
               <xsl:attribute name="id">amdSec_01</xsl:attribute>
               <xsl:attribute name="role">fatal</xsl:attribute>
               <xsl:attribute name="location">
                  <xsl:apply-templates select="." mode="schematron-select-full-path"/>
               </xsl:attribute>
               <svrl:text>Ein Datensatz muss das Element <xsl:text/>mets:amdSec<xsl:text/> enthalten. Ist dies nicht der Fall, wird der Datensatz nicht in die DDB eingespielt.Weitere Informationen zu diesem Element und seinem Kontext finden Sie im DDB-METS/MODS-Anwendungsprofil auf der Seite mets:amdSec (https://wiki.deutsche-digitale-bibliothek.de/x/r8IeB) und im Bereich Aufbau einer METS/MODS-Datei für die DDB (https://wiki.deutsche-digitale-bibliothek.de/x/VcIeB).</svrl:text>
            </svrl:failed-assert>
         </xsl:otherwise>
      </xsl:choose>
      <xsl:apply-templates select="*" mode="M97"/>
   </xsl:template>
   <!--RULE -->
   <xsl:template match="mets:amdSec" priority="1000" mode="M97">
      <svrl:fired-rule xmlns:svrl="http://purl.oclc.org/dsdl/svrl" context="mets:amdSec"/>
      <!--ASSERT warn-->
      <xsl:choose>
         <xsl:when test="count(key('mets_ids', @ID)) = 1 and matches(@ID, '^[\i-[:]][\c-[:]]*$')"/>
         <xsl:otherwise>
            <svrl:failed-assert xmlns:svrl="http://purl.oclc.org/dsdl/svrl"
                                test="count(key('mets_ids', @ID)) = 1 and matches(@ID, '^[\i-[:]][\c-[:]]*$')">
               <xsl:attribute name="id">amdSec_02</xsl:attribute>
               <xsl:attribute name="role">warn</xsl:attribute>
               <xsl:attribute name="location">
                  <xsl:apply-templates select="." mode="schematron-select-full-path"/>
               </xsl:attribute>
               <svrl:text>Das Element <xsl:text/>mets:amdSec<xsl:text/> muss das Attribut <xsl:text/>ID<xsl:text/> mit einem im Datensatz eindeutigen Identifier enthalten. Dieser darf darüber hinaus keine ungültigen Zeichen enthalten.
Das Fehlen von <xsl:text/>ID<xsl:text/> bzw. ungültige Zeichen in Attribut <xsl:text/>ID<xsl:text/> verhindern nicht das Einspielen Ihrer Daten in die DDB, wir bitten Sie jedoch, den Sachverhalt zu prüfen und die nötigen Korrekturen bis zur nächsten Datenlieferung vorzunehmen.Weitere Informationen zu diesem Element und Attribut finden Sie im DDB-METS/MODS-Anwendungsprofil auf der Seite mets:amdSec (https://wiki.deutsche-digitale-bibliothek.de/x/r8IeB).</svrl:text>
               <svrl:property id="id">
                  <xsl:value-of xmlns:sch="http://purl.oclc.org/dsdl/schematron" select="./@ID"/>
               </svrl:property>
            </svrl:failed-assert>
         </xsl:otherwise>
      </xsl:choose>
      <xsl:apply-templates select="*" mode="M97"/>
   </xsl:template>
   <xsl:template match="text()" priority="-1" mode="M97"/>
   <xsl:template match="@*|node()" priority="-2" mode="M97">
      <xsl:apply-templates select="*" mode="M97"/>
   </xsl:template>
   <!--PATTERN -->
   <!--RULE -->
   <xsl:template match="mets:mets[mets:amdSec[@ID=$work_amdid]][not( mets:amdSec[@ID=$work_amdid]/mets:rightsMD/mets:mdWrap/mets:xmlData/dv:rights/dv:license or mets:dmdSec[@ID=$work_dmdid]/mets:mdWrap/mets:xmlData/mods:mods/mods:accessCondition[@type='use and reproduction'] )] | mets:mets[mets:amdSec[not(@ID=$work_amdid)][1]][not( mets:amdSec[not(@ID=$work_amdid)][1]/mets:rightsMD/mets:mdWrap/mets:xmlData/dv:rights/dv:license or mets:dmdSec[@ID=$work_dmdid]/mets:mdWrap/mets:xmlData/mods:mods/mods:accessCondition[@type='use and reproduction'] )]"
                 priority="1001"
                 mode="M98">
      <svrl:fired-rule xmlns:svrl="http://purl.oclc.org/dsdl/svrl"
                       context="mets:mets[mets:amdSec[@ID=$work_amdid]][not( mets:amdSec[@ID=$work_amdid]/mets:rightsMD/mets:mdWrap/mets:xmlData/dv:rights/dv:license or mets:dmdSec[@ID=$work_dmdid]/mets:mdWrap/mets:xmlData/mods:mods/mods:accessCondition[@type='use and reproduction'] )] | mets:mets[mets:amdSec[not(@ID=$work_amdid)][1]][not( mets:amdSec[not(@ID=$work_amdid)][1]/mets:rightsMD/mets:mdWrap/mets:xmlData/dv:rights/dv:license or mets:dmdSec[@ID=$work_dmdid]/mets:mdWrap/mets:xmlData/mods:mods/mods:accessCondition[@type='use and reproduction'] )]"/>
      <!--ASSERT error-->
      <xsl:choose>
         <xsl:when test="mets:amdSec[@ID=$work_amdid]/mets:rightsMD/mets:mdWrap/mets:xmlData/dv:rights/dv:license or mets:amdSec[not(@ID=$work_amdid)][1]/mets:rightsMD/mets:mdWrap/mets:xmlData/dv:rights/dv:license or mets:dmdSec[@ID=$work_dmdid]/mets:mdWrap/mets:xmlData/mods:mods/mods:accessCondition[@type='use and reproduction']"/>
         <xsl:otherwise>
            <svrl:failed-assert xmlns:svrl="http://purl.oclc.org/dsdl/svrl"
                                test="mets:amdSec[@ID=$work_amdid]/mets:rightsMD/mets:mdWrap/mets:xmlData/dv:rights/dv:license or mets:amdSec[not(@ID=$work_amdid)][1]/mets:rightsMD/mets:mdWrap/mets:xmlData/dv:rights/dv:license or mets:dmdSec[@ID=$work_dmdid]/mets:mdWrap/mets:xmlData/mods:mods/mods:accessCondition[@type='use and reproduction']">
               <xsl:attribute name="id">amdSec_04</xsl:attribute>
               <xsl:attribute name="role">error</xsl:attribute>
               <xsl:attribute name="location">
                  <xsl:apply-templates select="." mode="schematron-select-full-path"/>
               </xsl:attribute>
               <svrl:text>Jeder Datensatz muss das Element <xsl:text/>dv:license<xsl:text/> mit Rechteinformationen zum beschriebenen Digitalisat enthalten.
Alternativ zu diesem Element kann die Rechteinformation auch im Attribut <xsl:text/>xlink:href<xsl:text/> des Elements <xsl:text/>mods:accessCondition<xsl:text/> mit dem Attribut <xsl:text/>type<xsl:text/> und dem Wert <xsl:text/>use and reproduction<xsl:text/> angegeben werden.
Die Rechteinformation muss im o. g. Element bzw. Attribut in Form eines URI gemäß den Rechteangaben in der Deutschen Digitalen Bibliothek (https://pro.deutsche-digitale-bibliothek.de/daten-liefern/teilnahmekriterien/rechtliches/lizenzen-und-rechtehinweise-der-lizenzkorb-der-deutschen-digitalen-bibliothek) vorliegen. Im <xsl:text/>dv:license<xsl:text/> können darüber hinaus die kodierten Werte aus dem METS-Anwendungsprofil (Kapitel 2.7.2.11) (https://dfg-viewer.de/fileadmin/groups/dfgviewer/METS-Anwendungsprofil_2.3.1.pdf) verwendet werden. Bitte beachten Sie hierbei, dass die DDB die CC-Lizenz-Werte als Version 4.0 und den Wert <xsl:text/>reserved<xsl:text/> als Urheberrechtsschutz nicht bewertet (Europeana Rightstatement "CNE") (http://rightsstatements.org/vocab/CNE/1.0/) interpretiert.
Ist im Datensatz keine Rechteinformation wie oben beschrieben vorhanden, kann bei der Transformation des Datensatzes eine von Ihnen bestimmte Standard-URI von der Seite Rechteangaben in der Deutschen Digitalen Bibliothek (https://pro.deutsche-digitale-bibliothek.de/daten-liefern/teilnahmekriterien/rechtliches/lizenzen-und-rechtehinweise-der-lizenzkorb-der-deutschen-digitalen-bibliothek) gesetzt werden, die für alle Ihre Datensätze gilt. Bitte teilen Sie diese der Fachstelle Bibliothek mit.
Liegt der der Fachstelle Bibliothek auch keine Standard-URI vor, wird der Datensatz nicht in die DDB eingespielt.Weitere Informationen zu diesen Elementen finden Sie im DDB-METS/MODS-Anwendungsprofil auf den Seiten mets:rightsMD (https://wiki.deutsche-digitale-bibliothek.de/x/ssIeB) und mods:accessCondition (https://wiki.deutsche-digitale-bibliothek.de/x/Q8MeB). Informationen zu den möglichen Rechte-URI finden Sie auf der Seite Rechteangaben in der Deutschen Digitalen Bibliothek (https://pro.deutsche-digitale-bibliothek.de/daten-liefern/teilnahmekriterien/rechtliches/lizenzen-und-rechtehinweise-der-lizenzkorb-der-deutschen-digitalen-bibliothek).</svrl:text>
            </svrl:failed-assert>
         </xsl:otherwise>
      </xsl:choose>
      <xsl:apply-templates select="*" mode="M98"/>
   </xsl:template>
   <!--RULE -->
   <xsl:template match="mets:mets[mets:amdSec[@ID=$work_amdid] or mets:amdSec[not(@ID=$work_amdid)][1]]"
                 priority="1000"
                 mode="M98">
      <svrl:fired-rule xmlns:svrl="http://purl.oclc.org/dsdl/svrl"
                       context="mets:mets[mets:amdSec[@ID=$work_amdid] or mets:amdSec[not(@ID=$work_amdid)][1]]"/>
      <!--ASSERT error-->
      <xsl:choose>
         <xsl:when test="key('license_uris', mets:amdSec[@ID=$work_amdid]/mets:rightsMD/mets:mdWrap/mets:xmlData/dv:rights/dv:license[1], $license_uris) or key('license_uris', mets:amdSec[not(@ID=$work_amdid)][1]/mets:rightsMD/mets:mdWrap/mets:xmlData/dv:rights/dv:license[1], $license_uris) or key('license_uris', mets:dmdSec[@ID=$work_dmdid]/mets:mdWrap/mets:xmlData/mods:mods/mods:accessCondition[@type='use and reproduction'][string-length(@*[local-name()='href'][1]) &gt; 0][1]/@*[local-name()='href'][1], $license_uris) or key('mets_ap_dv_license_values', mets:amdSec[@ID=$work_amdid]/mets:rightsMD/mets:mdWrap/mets:xmlData/dv:rights/dv:license[1], $mets_ap_dv_license_values) or key('mets_ap_dv_license_values', mets:amdSec[not(@ID=$work_amdid)][1]/mets:rightsMD/mets:mdWrap/mets:xmlData/dv:rights/dv:license[1], $mets_ap_dv_license_values)"/>
         <xsl:otherwise>
            <svrl:failed-assert xmlns:svrl="http://purl.oclc.org/dsdl/svrl"
                                test="key('license_uris', mets:amdSec[@ID=$work_amdid]/mets:rightsMD/mets:mdWrap/mets:xmlData/dv:rights/dv:license[1], $license_uris) or key('license_uris', mets:amdSec[not(@ID=$work_amdid)][1]/mets:rightsMD/mets:mdWrap/mets:xmlData/dv:rights/dv:license[1], $license_uris) or key('license_uris', mets:dmdSec[@ID=$work_dmdid]/mets:mdWrap/mets:xmlData/mods:mods/mods:accessCondition[@type='use and reproduction'][string-length(@*[local-name()='href'][1]) &gt; 0][1]/@*[local-name()='href'][1], $license_uris) or key('mets_ap_dv_license_values', mets:amdSec[@ID=$work_amdid]/mets:rightsMD/mets:mdWrap/mets:xmlData/dv:rights/dv:license[1], $mets_ap_dv_license_values) or key('mets_ap_dv_license_values', mets:amdSec[not(@ID=$work_amdid)][1]/mets:rightsMD/mets:mdWrap/mets:xmlData/dv:rights/dv:license[1], $mets_ap_dv_license_values)">
               <xsl:attribute name="id">amdSec_05</xsl:attribute>
               <xsl:attribute name="role">error</xsl:attribute>
               <xsl:attribute name="location">
                  <xsl:apply-templates select="." mode="schematron-select-full-path"/>
               </xsl:attribute>
               <svrl:text>Jeder Datensatz muss das Element <xsl:text/>dv:license<xsl:text/> mit Rechteinformationen zum beschriebenen Digitalisat enthalten.
Alternativ zu diesem Element kann die Rechteinformation auch im Attribut <xsl:text/>xlink:href<xsl:text/> des Elements <xsl:text/>mods:accessCondition<xsl:text/> mit dem Attribut <xsl:text/>type<xsl:text/> und dem Wert <xsl:text/>use and reproduction<xsl:text/> angegeben werden.
Die Rechteinformation muss im o. g. Element bzw. Attribut in Form eines URI gemäß den Rechteangaben in der Deutschen Digitalen Bibliothek (https://pro.deutsche-digitale-bibliothek.de/daten-liefern/teilnahmekriterien/rechtliches/lizenzen-und-rechtehinweise-der-lizenzkorb-der-deutschen-digitalen-bibliothek) vorliegen. Im <xsl:text/>dv:license<xsl:text/> können darüber hinaus die kodierten Werte aus dem METS-Anwendungsprofil (Kapitel 2.7.2.11) (https://dfg-viewer.de/fileadmin/groups/dfgviewer/METS-Anwendungsprofil_2.3.1.pdf) verwendet werden. Bitte beachten Sie hierbei, dass die DDB die CC-Lizenz-Werte als Version 4.0 und den Wert <xsl:text/>reserved<xsl:text/> als Urheberrechtsschutz nicht bewertet (Europeana Rightstatement "CNE") (http://rightsstatements.org/vocab/CNE/1.0/) interpretiert.
Ist im Datensatz keine Rechteinformation wie oben beschrieben vorhanden, kann bei der Transformation des Datensatzes eine von Ihnen bestimmte Standard-URI von der Seite Rechteangaben in der Deutschen Digitalen Bibliothek (https://pro.deutsche-digitale-bibliothek.de/daten-liefern/teilnahmekriterien/rechtliches/lizenzen-und-rechtehinweise-der-lizenzkorb-der-deutschen-digitalen-bibliothek) gesetzt werden, die für alle Ihre Datensätze gilt. Bitte teilen Sie diese der Fachstelle Bibliothek mit.
Liegt der der Fachstelle Bibliothek auch keine Standard-URI vor, wird der Datensatz nicht in die DDB eingespielt.Weitere Informationen zu diesen Elementen finden Sie im DDB-METS/MODS-Anwendungsprofil auf den Seiten mets:rightsMD (https://wiki.deutsche-digitale-bibliothek.de/x/ssIeB) und mods:accessCondition (https://wiki.deutsche-digitale-bibliothek.de/x/Q8MeB). Informationen zu den möglichen Rechte-URI finden Sie auf der Seite Rechteangaben in der Deutschen Digitalen Bibliothek (https://pro.deutsche-digitale-bibliothek.de/daten-liefern/teilnahmekriterien/rechtliches/lizenzen-und-rechtehinweise-der-lizenzkorb-der-deutschen-digitalen-bibliothek).</svrl:text>
            </svrl:failed-assert>
         </xsl:otherwise>
      </xsl:choose>
      <xsl:apply-templates select="*" mode="M98"/>
   </xsl:template>
   <xsl:template match="text()" priority="-1" mode="M98"/>
   <xsl:template match="@*|node()" priority="-2" mode="M98">
      <xsl:apply-templates select="*" mode="M98"/>
   </xsl:template>
   <!--PATTERN -->
   <!--RULE -->
   <xsl:template match="mets:amdSec[@ID=$work_amdid]/mets:digiprovMD | mets:amdSec[not(@ID=$work_amdid)][1]/mets:digiprovMD"
                 priority="1000"
                 mode="M99">
      <svrl:fired-rule xmlns:svrl="http://purl.oclc.org/dsdl/svrl"
                       context="mets:amdSec[@ID=$work_amdid]/mets:digiprovMD | mets:amdSec[not(@ID=$work_amdid)][1]/mets:digiprovMD"/>
      <!--ASSERT error-->
      <xsl:choose>
         <xsl:when test="mets:mdWrap/mets:xmlData/dv:links/dv:reference[matches(text()[1], '^http[s]?://.+')] or mets:mdWrap/mets:xmlData/dv:links/dv:presentation[matches(text()[1], '^http[s]?://.+')]"/>
         <xsl:otherwise>
            <svrl:failed-assert xmlns:svrl="http://purl.oclc.org/dsdl/svrl"
                                test="mets:mdWrap/mets:xmlData/dv:links/dv:reference[matches(text()[1], '^http[s]?://.+')] or mets:mdWrap/mets:xmlData/dv:links/dv:presentation[matches(text()[1], '^http[s]?://.+')]">
               <xsl:attribute name="id">amdSec_06</xsl:attribute>
               <xsl:attribute name="role">error</xsl:attribute>
               <xsl:attribute name="location">
                  <xsl:apply-templates select="." mode="schematron-select-full-path"/>
               </xsl:attribute>
               <svrl:text>Das Element <xsl:text/>mets:amdSec<xsl:text/>, das über sein Attribut <xsl:text/>ID<xsl:text/> vom primären <xsl:text/>mets:div<xsl:text/> im Element <xsl:text/>mets:structMap[@TYPE='LOGICAL']<xsl:text/> über dessen Attribut <xsl:text/>ADMID<xsl:text/> referenziert wird, muss das Unterelement <xsl:text/>mets:digiprovMD<xsl:text/> enthalten.
Dieses muss auf der untersten Ebene das Element <xsl:text/>dv:presentation<xsl:text/> oder das Element <xsl:text/>dv:reference<xsl:text/> mit einem http- oder https-URI enthalten, der auf die Anzeige des Digitalisats bei Ihrer Institution, bzw. das Katalogisat in Ihrem Katalog referenziert.
Fehlt sowohl <xsl:text/>dv:presentation<xsl:text/> als auch <xsl:text/>dv:reference<xsl:text/> bzw. enthält keines dieser Elemente einen http- bzw. https-URI, wird <xsl:text/>mets:digiprovMD<xsl:text/> bei der Transformation des Datensatzes entfernt.Weitere Informationen zu diesen Elementen finden Sie im DDB-METS/MODS-Anwendungsprofil auf der Seite mets:digiprovMD (https://wiki.deutsche-digitale-bibliothek.de/x/tsIeB).</svrl:text>
               <svrl:property id="id">
                  <xsl:value-of xmlns:sch="http://purl.oclc.org/dsdl/schematron" select="./@ID"/>
               </svrl:property>
            </svrl:failed-assert>
         </xsl:otherwise>
      </xsl:choose>
      <xsl:apply-templates select="*" mode="M99"/>
   </xsl:template>
   <xsl:template match="text()" priority="-1" mode="M99"/>
   <xsl:template match="@*|node()" priority="-2" mode="M99">
      <xsl:apply-templates select="*" mode="M99"/>
   </xsl:template>
   <!--PATTERN -->
   <!--RULE -->
   <xsl:template match="mets:rightsMD/mets:mdWrap/mets:xmlData/dv:rights"
                 priority="1000"
                 mode="M100">
      <svrl:fired-rule xmlns:svrl="http://purl.oclc.org/dsdl/svrl"
                       context="mets:rightsMD/mets:mdWrap/mets:xmlData/dv:rights"/>
      <!--REPORT fatal-->
      <xsl:if test="dv:owner[2]">
         <svrl:successful-report xmlns:svrl="http://purl.oclc.org/dsdl/svrl" test="dv:owner[2]">
            <xsl:attribute name="id">amdSec_07</xsl:attribute>
            <xsl:attribute name="role">fatal</xsl:attribute>
            <xsl:attribute name="location">
               <xsl:apply-templates select="." mode="schematron-select-full-path"/>
            </xsl:attribute>
            <svrl:text>Das Element <xsl:text/>dv:rights<xsl:text/> darf das Element <xsl:text/>dv:owner<xsl:text/> nur einmal enthalten. Enthält <xsl:text/>dv:rights<xsl:text/> mehr als ein <xsl:text/>dv:owner<xsl:text/>, wird der Datensatz nicht in die DDB eingespielt.Weitere Informationen zu diesem Element finden Sie im DDB-METS/MODS-Anwendungsprofil auf der Seite mets:rightsMD (https://wiki.deutsche-digitale-bibliothek.de/x/ssIeB).</svrl:text>
            <svrl:property id="id">
               <xsl:value-of xmlns:sch="http://purl.oclc.org/dsdl/schematron" select="./@ID"/>
            </svrl:property>
         </svrl:successful-report>
      </xsl:if>
      <xsl:apply-templates select="*" mode="M100"/>
   </xsl:template>
   <xsl:template match="text()" priority="-1" mode="M100"/>
   <xsl:template match="@*|node()" priority="-2" mode="M100">
      <xsl:apply-templates select="*" mode="M100"/>
   </xsl:template>
   <!--PATTERN -->
   <!--RULE -->
   <xsl:template match="mets:mets[mets:structMap[@TYPE='LOGICAL']//mets:div[tokenize(@DMDID, ' ') = $work_dmdid][contains(@ADMID, ' ')]]"
                 priority="1002"
                 mode="M101">
      <svrl:fired-rule xmlns:svrl="http://purl.oclc.org/dsdl/svrl"
                       context="mets:mets[mets:structMap[@TYPE='LOGICAL']//mets:div[tokenize(@DMDID, ' ') = $work_dmdid][contains(@ADMID, ' ')]]"/>
      <!--REPORT fatal-->
      <xsl:if test="contains($work_amdid, ' ')">
         <svrl:successful-report xmlns:svrl="http://purl.oclc.org/dsdl/svrl"
                                 test="contains($work_amdid, ' ')">
            <xsl:attribute name="id">amdSec_08</xsl:attribute>
            <xsl:attribute name="role">fatal</xsl:attribute>
            <xsl:attribute name="location">
               <xsl:apply-templates select="." mode="schematron-select-full-path"/>
            </xsl:attribute>
            <svrl:text>Das primäre <xsl:text/>mets:div<xsl:text/>-Element im Element <xsl:text/>mets:structMap[@TYPE='LOGICAL']<xsl:text/> referenziert im Attribut <xsl:text/>ADMID<xsl:text/> mehrere <xsl:text/>mets:amdSec<xsl:text/>-Elemente.
Dadurch ist keine eindeutige Zuordnung der adminstrativen Metadaten für den Datensatz möglich und er wird nicht in die DDB eingespielt.Weitere Informationen zu diesen Elementen finden Sie im DDB-METS/MODS-Anwendungsprofil auf den Seiten mets:amdSec (https://wiki.deutsche-digitale-bibliothek.de/x/r8IeB) und mets:structMap[@TYPE='LOGICAL'] (https://wiki.deutsche-digitale-bibliothek.de/x/o8IeB). Informationen zum Kontext der Elemente finden Sie im Bereich Aufbau einer METS/MODS-Datei für die DDB (https://wiki.deutsche-digitale-bibliothek.de/x/VcIeB).</svrl:text>
         </svrl:successful-report>
      </xsl:if>
      <xsl:apply-templates select="*" mode="M101"/>
   </xsl:template>
   <!--RULE -->
   <xsl:template match="mets:mets[not(mets:amdSec[@ID=$work_amdid]) and mets:amdSec[not(key('structMap_LOGICAL_admids', @AMDID))][2]]"
                 priority="1001"
                 mode="M101">
      <svrl:fired-rule xmlns:svrl="http://purl.oclc.org/dsdl/svrl"
                       context="mets:mets[not(mets:amdSec[@ID=$work_amdid]) and mets:amdSec[not(key('structMap_LOGICAL_admids', @AMDID))][2]]"/>
      <!--REPORT fatal-->
      <xsl:if test="not(mets:amdSec[@ID=$work_amdid]) and mets:amdSec[not(key('structMap_LOGICAL_admids', @AMDID))][2]">
         <svrl:successful-report xmlns:svrl="http://purl.oclc.org/dsdl/svrl"
                                 test="not(mets:amdSec[@ID=$work_amdid]) and mets:amdSec[not(key('structMap_LOGICAL_admids', @AMDID))][2]">
            <xsl:attribute name="id">amdSec_09</xsl:attribute>
            <xsl:attribute name="role">fatal</xsl:attribute>
            <xsl:attribute name="location">
               <xsl:apply-templates select="." mode="schematron-select-full-path"/>
            </xsl:attribute>
            <svrl:text>Das Element <xsl:text/>mets:amdSec<xsl:text/> muss über sein Attribut <xsl:text/>ID<xsl:text/> mit einem <xsl:text/>mets:div<xsl:text/>-Element im Element <xsl:text/>mets:structMap[@TYPE='LOGICAL']<xsl:text/> über dessen Attribut <xsl:text/>ADMID<xsl:text/> referenziert werden.
Enthält ein Datensatz kein <xsl:text/>mets:amdSec<xsl:text/>, das vom primären <xsl:text/>mets:div<xsl:text/> im <xsl:text/>mets:structMap[@TYPE='LOGICAL']<xsl:text/> referenziert wird und darüber hinaus mehrere <xsl:text/>mets:amdSec<xsl:text/> ohne eine Referenzierung, ist keine eindeutige Zuordnung der adminstrativen Metadaten für den Datensatz möglich und er wird nicht in die DDB eingespielt. Weitere Informationen zu diesen Elementen finden Sie im DDB-METS/MODS-Anwendungsprofil auf den Seiten mets:amdSec (https://wiki.deutsche-digitale-bibliothek.de/x/r8IeB) und mets:structMap[@TYPE='LOGICAL'] (https://wiki.deutsche-digitale-bibliothek.de/x/o8IeB). Informationen zum Kontext der Elemente finden Sie im Bereich Aufbau einer METS/MODS-Datei für die DDB (https://wiki.deutsche-digitale-bibliothek.de/x/VcIeB).</svrl:text>
         </svrl:successful-report>
      </xsl:if>
      <xsl:apply-templates select="*" mode="M101"/>
   </xsl:template>
   <!--RULE -->
   <xsl:template match="mets:mets" priority="1000" mode="M101">
      <svrl:fired-rule xmlns:svrl="http://purl.oclc.org/dsdl/svrl" context="mets:mets"/>
      <!--ASSERT error-->
      <xsl:choose>
         <xsl:when test="mets:amdSec[@ID=$work_amdid]"/>
         <xsl:otherwise>
            <svrl:failed-assert xmlns:svrl="http://purl.oclc.org/dsdl/svrl"
                                test="mets:amdSec[@ID=$work_amdid]">
               <xsl:attribute name="id">amdSec_10</xsl:attribute>
               <xsl:attribute name="role">error</xsl:attribute>
               <xsl:attribute name="location">
                  <xsl:apply-templates select="." mode="schematron-select-full-path"/>
               </xsl:attribute>
               <svrl:text>Das primäre <xsl:text/>mets:div<xsl:text/>-Element im Element <xsl:text/>mets:structMap[@TYPE='LOGICAL']<xsl:text/> muss im Attribut <xsl:text/>ADMID<xsl:text/> genau ein <xsl:text/>mets:amdSec<xsl:text/>-Element über dessen Attribut <xsl:text/>ID<xsl:text/> referenzieren.
Ist dies nicht der Fall und der Datensatz verfügt nur über genau ein <xsl:text/>mets:amdSec<xsl:text/>, wird eine Referenz auf dieses bei Transformation des Datensatzes erzeugt.Weitere Informationen zu diesen Elementen finden Sie im DDB-METS/MODS-Anwendungsprofil auf den Seiten mets:amdSec (https://wiki.deutsche-digitale-bibliothek.de/x/r8IeB) und mets:structMap[@TYPE='LOGICAL'] (https://wiki.deutsche-digitale-bibliothek.de/x/o8IeB). Informationen zum Kontext der Elemente finden Sie im Bereich Aufbau einer METS/MODS-Datei für die DDB (https://wiki.deutsche-digitale-bibliothek.de/x/VcIeB).</svrl:text>
            </svrl:failed-assert>
         </xsl:otherwise>
      </xsl:choose>
      <xsl:apply-templates select="*" mode="M101"/>
   </xsl:template>
   <xsl:template match="text()" priority="-1" mode="M101"/>
   <xsl:template match="@*|node()" priority="-2" mode="M101">
      <xsl:apply-templates select="*" mode="M101"/>
   </xsl:template>
   <!--PATTERN -->
   <!--RULE -->
   <xsl:template match="mets:mets/mets:structMap[@TYPE='LOGICAL']//mets:div[not(tokenize(@DMDID, ' ') = $work_dmdid)][@ADMID]"
                 priority="1000"
                 mode="M102">
      <svrl:fired-rule xmlns:svrl="http://purl.oclc.org/dsdl/svrl"
                       context="mets:mets/mets:structMap[@TYPE='LOGICAL']//mets:div[not(tokenize(@DMDID, ' ') = $work_dmdid)][@ADMID]"/>
      <!--REPORT info-->
      <xsl:if test="key('amdsec_ids', @ADMID)">
         <svrl:successful-report xmlns:svrl="http://purl.oclc.org/dsdl/svrl"
                                 test="key('amdsec_ids', @ADMID)">
            <xsl:attribute name="id">amdSec_11</xsl:attribute>
            <xsl:attribute name="role">info</xsl:attribute>
            <xsl:attribute name="location">
               <xsl:apply-templates select="." mode="schematron-select-full-path"/>
            </xsl:attribute>
            <svrl:text>Der Datensatz enthält <xsl:text/>mets:amdSec<xsl:text/>-Elemente, die im Element <xsl:text/>mets:structMap[@TYPE='LOGICAL']<xsl:text/> von dem primären <xsl:text/>mets:div<xsl:text/>-Element untergeordneten <xsl:text/>mets:div<xsl:text/>-Elementen referenziert werden.
Die DDB berücksichtigt zurzeit nur das <xsl:text/>mets:amdSec<xsl:text/>, das von primären <xsl:text/>mets:div<xsl:text/> referenziert wird.Weitere Informationen zu diesem Element und seinem Kontext finden Sie im DDB-METS/MODS-Anwendungsprofil auf der Seite mets:amdSec (https://wiki.deutsche-digitale-bibliothek.de/x/r8IeB) und im Bereich Aufbau einer METS/MODS-Datei für die DDB (https://wiki.deutsche-digitale-bibliothek.de/x/VcIeB).</svrl:text>
         </svrl:successful-report>
      </xsl:if>
      <xsl:apply-templates select="*" mode="M102"/>
   </xsl:template>
   <xsl:template match="text()" priority="-1" mode="M102"/>
   <xsl:template match="@*|node()" priority="-2" mode="M102">
      <xsl:apply-templates select="*" mode="M102"/>
   </xsl:template>
   <!--PATTERN -->
   <!--RULE -->
   <xsl:template match="mets:rightsMD/mets:mdWrap/mets:xmlData/dv:rights"
                 priority="1000"
                 mode="M103">
      <svrl:fired-rule xmlns:svrl="http://purl.oclc.org/dsdl/svrl"
                       context="mets:rightsMD/mets:mdWrap/mets:xmlData/dv:rights"/>
      <!--REPORT error-->
      <xsl:if test="dv:license[2]">
         <svrl:successful-report xmlns:svrl="http://purl.oclc.org/dsdl/svrl" test="dv:license[2]">
            <xsl:attribute name="id">amdSec_12</xsl:attribute>
            <xsl:attribute name="role">error</xsl:attribute>
            <xsl:attribute name="location">
               <xsl:apply-templates select="." mode="schematron-select-full-path"/>
            </xsl:attribute>
            <svrl:text>Das Element <xsl:text/>dv:rights<xsl:text/> darf das Element <xsl:text/>dv:license<xsl:text/> nur einmal enthalten. Enthält <xsl:text/>dv:rights<xsl:text/> mehr als ein <xsl:text/>dv:license<xsl:text/>, wird bei der Bereinigung des Datensatzes das erste Vorkommen von <xsl:text/>dv:license<xsl:text/> mit gültigem Rechte-URI übernommen, alle anderen <xsl:text/>dv:license<xsl:text/> werden entfernt.Weitere Informationen zu diesem Element finden Sie im DDB-METS/MODS-Anwendungsprofil auf der Seite mets:rightsMD (https://wiki.deutsche-digitale-bibliothek.de/x/ssIeB).</svrl:text>
         </svrl:successful-report>
      </xsl:if>
      <xsl:apply-templates select="*" mode="M103"/>
   </xsl:template>
   <xsl:template match="text()" priority="-1" mode="M103"/>
   <xsl:template match="@*|node()" priority="-2" mode="M103">
      <xsl:apply-templates select="*" mode="M103"/>
   </xsl:template>
   <!--PATTERN -->
   <!--RULE -->
   <xsl:template match="mets:mets[ ( key('license_uris', mets:amdSec[@ID=$work_amdid]/mets:rightsMD/mets:mdWrap/mets:xmlData/dv:rights/dv:license[1], $license_uris) and not(mets:amdSec[@ID=$work_amdid]/mets:rightsMD/mets:mdWrap/mets:xmlData/dv:rights/dv:license[1][contains(text(), 'creativecommons.org/publicdomain/mark/1.0/')]) ) or ( key('license_uris', mets:amdSec[not(@ID=$work_amdid)][1]/mets:rightsMD/mets:mdWrap/mets:xmlData/dv:rights/dv:license[1], $license_uris) and not(mets:amdSec[not(@ID=$work_amdid)][1]/mets:rightsMD/mets:mdWrap/mets:xmlData/dv:rights/dv:license[1][contains(text(), 'creativecommons.org/publicdomain/mark/1.0/')]) ) or ( key('license_uris', mets:dmdSec[@ID=$work_dmdid]/mets:mdWrap/mets:xmlData/mods:mods/mods:accessCondition[@type='use and reproduction'][string-length(@*[local-name()='href'][1]) &gt; 0][1]/@*[local-name()='href'][1], $license_uris) and not(mets:dmdSec[@ID=$work_dmdid]/mets:mdWrap/mets:xmlData/mods:mods/mods:accessCondition[@type='use and reproduction'][string-length(@*[local-name()='href'][1]) &gt; 0][1]/@*[local-name()='href'][1][contains(., 'creativecommons.org/publicdomain/mark/1.0/')]) ) or ( key('mets_ap_dv_license_values', mets:amdSec[@ID=$work_amdid]/mets:rightsMD/mets:mdWrap/mets:xmlData/dv:rights/dv:license[1], $mets_ap_dv_license_values) and not(mets:amdSec[@ID=$work_amdid]/mets:rightsMD/mets:mdWrap/mets:xmlData/dv:rights/dv:license[1][text()='pdm']) ) or ( key('mets_ap_dv_license_values', mets:amdSec[not(@ID=$work_amdid)][1]/mets:rightsMD/mets:mdWrap/mets:xmlData/dv:rights/dv:license[1], $mets_ap_dv_license_values) and not(mets:amdSec[not(@ID=$work_amdid)][1]/mets:rightsMD/mets:mdWrap/mets:xmlData/dv:rights/dv:license[1][text()='pdm']) ) ]/mets:dmdSec[@ID=$work_dmdid]/mets:mdWrap/mets:xmlData/mods:mods"
                 priority="1000"
                 mode="M104">
      <svrl:fired-rule xmlns:svrl="http://purl.oclc.org/dsdl/svrl"
                       context="mets:mets[ ( key('license_uris', mets:amdSec[@ID=$work_amdid]/mets:rightsMD/mets:mdWrap/mets:xmlData/dv:rights/dv:license[1], $license_uris) and not(mets:amdSec[@ID=$work_amdid]/mets:rightsMD/mets:mdWrap/mets:xmlData/dv:rights/dv:license[1][contains(text(), 'creativecommons.org/publicdomain/mark/1.0/')]) ) or ( key('license_uris', mets:amdSec[not(@ID=$work_amdid)][1]/mets:rightsMD/mets:mdWrap/mets:xmlData/dv:rights/dv:license[1], $license_uris) and not(mets:amdSec[not(@ID=$work_amdid)][1]/mets:rightsMD/mets:mdWrap/mets:xmlData/dv:rights/dv:license[1][contains(text(), 'creativecommons.org/publicdomain/mark/1.0/')]) ) or ( key('license_uris', mets:dmdSec[@ID=$work_dmdid]/mets:mdWrap/mets:xmlData/mods:mods/mods:accessCondition[@type='use and reproduction'][string-length(@*[local-name()='href'][1]) &gt; 0][1]/@*[local-name()='href'][1], $license_uris) and not(mets:dmdSec[@ID=$work_dmdid]/mets:mdWrap/mets:xmlData/mods:mods/mods:accessCondition[@type='use and reproduction'][string-length(@*[local-name()='href'][1]) &gt; 0][1]/@*[local-name()='href'][1][contains(., 'creativecommons.org/publicdomain/mark/1.0/')]) ) or ( key('mets_ap_dv_license_values', mets:amdSec[@ID=$work_amdid]/mets:rightsMD/mets:mdWrap/mets:xmlData/dv:rights/dv:license[1], $mets_ap_dv_license_values) and not(mets:amdSec[@ID=$work_amdid]/mets:rightsMD/mets:mdWrap/mets:xmlData/dv:rights/dv:license[1][text()='pdm']) ) or ( key('mets_ap_dv_license_values', mets:amdSec[not(@ID=$work_amdid)][1]/mets:rightsMD/mets:mdWrap/mets:xmlData/dv:rights/dv:license[1], $mets_ap_dv_license_values) and not(mets:amdSec[not(@ID=$work_amdid)][1]/mets:rightsMD/mets:mdWrap/mets:xmlData/dv:rights/dv:license[1][text()='pdm']) ) ]/mets:dmdSec[@ID=$work_dmdid]/mets:mdWrap/mets:xmlData/mods:mods"/>
      <!--REPORT caution-->
      <xsl:if test="max( ( mods:originInfo[not(mods:edition[text()='[Electronic ed.]'])]/mods:dateIssued[matches(text()[1], '^((-\d\d\d\d+)|(\d\d\d\d))(-\d\d)?(-\d\d)?$')]/number(tokenize(text(), '-')[1]), mods:originInfo[not(mods:edition[text()='[Electronic ed.]'])]/mods:dateCreated[matches(text()[1], '^((-\d\d\d\d+)|(\d\d\d\d))(-\d\d)?(-\d\d)?$')]/number(tokenize(text(), '-')[1]) ) ) &lt; 1910">
         <svrl:successful-report xmlns:svrl="http://purl.oclc.org/dsdl/svrl"
                                 test="max( ( mods:originInfo[not(mods:edition[text()='[Electronic ed.]'])]/mods:dateIssued[matches(text()[1], '^((-\d\d\d\d+)|(\d\d\d\d))(-\d\d)?(-\d\d)?$')]/number(tokenize(text(), '-')[1]), mods:originInfo[not(mods:edition[text()='[Electronic ed.]'])]/mods:dateCreated[matches(text()[1], '^((-\d\d\d\d+)|(\d\d\d\d))(-\d\d)?(-\d\d)?$')]/number(tokenize(text(), '-')[1]) ) ) &lt; 1910">
            <xsl:attribute name="id">amdSec_13</xsl:attribute>
            <xsl:attribute name="role">caution</xsl:attribute>
            <xsl:attribute name="location">
               <xsl:apply-templates select="." mode="schematron-select-full-path"/>
            </xsl:attribute>
            <svrl:text>Die Lizenzen aus dem Lizenzkorb der DDB (https://pro.deutsche-digitale-bibliothek.de/daten-liefern/teilnahmekriterien/rechtliches/lizenzen-und-rechtehinweise-der-lizenzkorb-der-deutschen-digitalen-bibliothek) können nur für Materialien genutzt werden, an denen Urheberrechte nach § 2 UrhG oder Lichtbildrechte nach § 72 UrhG bestehen.
Der Scan oder die Fotografie von typischen Bibliotheksbeständen (Bücher, Zeitschriften und andere Schriftwerke) lässt solche Rechte in Fällen, in denen eine möglichst originalgetreue Reproduktion erzeugt werden soll, nicht entstehen. Daher kommt bei Scans / Fotos gemeinfreier Vorlagen in aller Regel nur der ebenfalls im "Lizenzkorb" enthaltene Rechtehinweis "Public Domain Mark" in Frage.
Dies ist nur ein Hinweis auf die Rechtslage in Verbindung mit der Bitte um Prüfung, ob Sie – dem entsprechend – in den Rechteangaben zu Ihren Digitalisaten den richtigen Rechtehinweis vergeben haben. Die Rechteangaben bleiben jedoch – wie im Kooperationsvertrag geregelt – in der Verantwortung Ihrer Einrichtung.Weitere Informationen zu Rechteangaben in der DDB finden Sie auf der Seite Rechteangaben in der Deutschen Digitalen Bibliothek (https://pro.deutsche-digitale-bibliothek.de/daten-liefern/teilnahmekriterien/rechtliches/lizenzen-und-rechtehinweise-der-lizenzkorb-der-deutschen-digitalen-bibliothek). Bei Fragen wenden Sie sich bitte an Armin Talke (https://pro.deutsche-digitale-bibliothek.de/ueber-uns/ansprechpartner_innen/armin-talke).</svrl:text>
            <svrl:property id="dmd_id">
               <xsl:value-of xmlns:sch="http://purl.oclc.org/dsdl/schematron"
                             select="ancestor-or-self::mets:dmdSec/@ID"/>
            </svrl:property>
         </svrl:successful-report>
      </xsl:if>
      <xsl:apply-templates select="*" mode="M104"/>
   </xsl:template>
   <xsl:template match="text()" priority="-1" mode="M104"/>
   <xsl:template match="@*|node()" priority="-2" mode="M104">
      <xsl:apply-templates select="*" mode="M104"/>
   </xsl:template>
   <!--PATTERN -->
   <!--RULE -->
   <xsl:template match="mets:mets[not( mets:dmdSec[@ID=$work_dmdid]/mets:mdWrap/mets:xmlData/mods:mods/mods:accessCondition[@type='use and reproduction'][key('license_uris', replace(text(), 'deed\.[a-z][a-z]$', ''), $license_uris)] or mets:dmdSec[@ID=$work_dmdid]/mets:mdWrap/mets:xmlData/mods:mods/mods:accessCondition[@type='use and reproduction'][key('license_uris', replace(@*[local-name()='href'][1], 'deed\.[a-z][a-z]$', ''), $license_uris)] )]"
                 priority="1002"
                 mode="M105">
      <svrl:fired-rule xmlns:svrl="http://purl.oclc.org/dsdl/svrl"
                       context="mets:mets[not( mets:dmdSec[@ID=$work_dmdid]/mets:mdWrap/mets:xmlData/mods:mods/mods:accessCondition[@type='use and reproduction'][key('license_uris', replace(text(), 'deed\.[a-z][a-z]$', ''), $license_uris)] or mets:dmdSec[@ID=$work_dmdid]/mets:mdWrap/mets:xmlData/mods:mods/mods:accessCondition[@type='use and reproduction'][key('license_uris', replace(@*[local-name()='href'][1], 'deed\.[a-z][a-z]$', ''), $license_uris)] )]"/>
      <!--REPORT fatal-->
      <xsl:if test="count(distinct-values(( mets:amdSec[@ID=$work_amdid]/mets:rightsMD/mets:mdWrap/mets:xmlData/dv:rights/dv:license[key('license_uris', replace(text(), 'deed\.[a-z][a-z]$', ''), $license_uris)]/replace(replace(text(), '^https', 'http'), 'deed\.[a-z][a-z]$', ''), mets:amdSec[not(@ID=$work_amdid)][1]/mets:rightsMD/mets:mdWrap/mets:xmlData/dv:rights/dv:license[key('license_uris', replace(text(), 'deed\.[a-z][a-z]$', ''), $license_uris)]/replace(replace(text(), '^https', 'http'), 'deed\.[a-z][a-z]$', ''), key('mets_ap_dv_license_values', mets:amdSec[@ID=$work_amdid]/mets:rightsMD/mets:mdWrap/mets:xmlData/dv:rights/dv:license[key('mets_ap_dv_license_values', text(), $mets_ap_dv_license_values)]/text(), $mets_ap_dv_license_values)/@to ))) &gt; 1">
         <svrl:successful-report xmlns:svrl="http://purl.oclc.org/dsdl/svrl"
                                 test="count(distinct-values(( mets:amdSec[@ID=$work_amdid]/mets:rightsMD/mets:mdWrap/mets:xmlData/dv:rights/dv:license[key('license_uris', replace(text(), 'deed\.[a-z][a-z]$', ''), $license_uris)]/replace(replace(text(), '^https', 'http'), 'deed\.[a-z][a-z]$', ''), mets:amdSec[not(@ID=$work_amdid)][1]/mets:rightsMD/mets:mdWrap/mets:xmlData/dv:rights/dv:license[key('license_uris', replace(text(), 'deed\.[a-z][a-z]$', ''), $license_uris)]/replace(replace(text(), '^https', 'http'), 'deed\.[a-z][a-z]$', ''), key('mets_ap_dv_license_values', mets:amdSec[@ID=$work_amdid]/mets:rightsMD/mets:mdWrap/mets:xmlData/dv:rights/dv:license[key('mets_ap_dv_license_values', text(), $mets_ap_dv_license_values)]/text(), $mets_ap_dv_license_values)/@to ))) &gt; 1">
            <xsl:attribute name="id">amdSec_14</xsl:attribute>
            <xsl:attribute name="role">fatal</xsl:attribute>
            <xsl:attribute name="location">
               <xsl:apply-templates select="." mode="schematron-select-full-path"/>
            </xsl:attribute>
            <svrl:text>Der Datensatz enthält mehrere <xsl:text/>dv:license<xsl:text/>-Elemente mit unterschiedlichen Rechteangaben aus dem Lizenzkorb der DDB (https://pro.deutsche-digitale-bibliothek.de/daten-liefern/teilnahmekriterien/rechtliches/lizenzen-und-rechtehinweise-der-lizenzkorb-der-deutschen-digitalen-bibliothek). Bitte beachten Sie hierbei, dass die DDB die CC-Lizenz-Werte aus dem METS-Anwendungsprofil (Kapitel 2.7.2.11) (https://dfg-viewer.de/fileadmin/groups/dfgviewer/METS-Anwendungsprofil_2.3.1.pdf) als Version 4.0 und den Wert <xsl:text/>reserved<xsl:text/> als Urheberrechtsschutz nicht bewertet (Europeana Rightstatement "CNE") (http://rightsstatements.org/vocab/CNE/1.0/) interpretiert.
Die DDB benötigt eindeutige Rechteangaben, daher wird der Datensatz nicht in die DDB eingespielt.</svrl:text>
            <svrl:property id="dmd_id">
               <xsl:value-of xmlns:sch="http://purl.oclc.org/dsdl/schematron"
                             select="ancestor-or-self::mets:dmdSec/@ID"/>
            </svrl:property>
         </svrl:successful-report>
      </xsl:if>
      <xsl:apply-templates select="*" mode="M105"/>
   </xsl:template>
   <!--RULE -->
   <xsl:template match="mets:mets[ mets:dmdSec[@ID=$work_dmdid]/mets:mdWrap/mets:xmlData/mods:mods[mods:accessCondition[@type='use and reproduction'][ key('license_uris', replace(text(), 'deed\.[a-z][a-z]$', ''), $license_uris) or key('license_uris', replace(@*[local-name()='href'][1], 'deed\.[a-z][a-z]$', ''), $license_uris) ][2]] ]"
                 priority="1001"
                 mode="M105">
      <svrl:fired-rule xmlns:svrl="http://purl.oclc.org/dsdl/svrl"
                       context="mets:mets[ mets:dmdSec[@ID=$work_dmdid]/mets:mdWrap/mets:xmlData/mods:mods[mods:accessCondition[@type='use and reproduction'][ key('license_uris', replace(text(), 'deed\.[a-z][a-z]$', ''), $license_uris) or key('license_uris', replace(@*[local-name()='href'][1], 'deed\.[a-z][a-z]$', ''), $license_uris) ][2]] ]"/>
      <!--REPORT fatal-->
      <xsl:if test="count(distinct-values(( mets:dmdSec[@ID=$work_dmdid]/mets:mdWrap/mets:xmlData/mods:mods/mods:accessCondition[@type='use and reproduction'][key('license_uris', replace(text(), 'deed\.[a-z][a-z]$', ''), $license_uris)]/replace(replace(text(), '^https', 'http'), 'deed\.[a-z][a-z]$', ''), mets:dmdSec[@ID=$work_dmdid]/mets:mdWrap/mets:xmlData/mods:mods/mods:accessCondition[@type='use and reproduction'][key('license_uris', replace(@*[local-name()='href'][1], 'deed\.[a-z][a-z]$', ''), $license_uris)]/replace(replace(@*[local-name()='href'][1], '^https', 'http'), 'deed\.[a-z][a-z]$', '') ))) &gt; 1">
         <svrl:successful-report xmlns:svrl="http://purl.oclc.org/dsdl/svrl"
                                 test="count(distinct-values(( mets:dmdSec[@ID=$work_dmdid]/mets:mdWrap/mets:xmlData/mods:mods/mods:accessCondition[@type='use and reproduction'][key('license_uris', replace(text(), 'deed\.[a-z][a-z]$', ''), $license_uris)]/replace(replace(text(), '^https', 'http'), 'deed\.[a-z][a-z]$', ''), mets:dmdSec[@ID=$work_dmdid]/mets:mdWrap/mets:xmlData/mods:mods/mods:accessCondition[@type='use and reproduction'][key('license_uris', replace(@*[local-name()='href'][1], 'deed\.[a-z][a-z]$', ''), $license_uris)]/replace(replace(@*[local-name()='href'][1], '^https', 'http'), 'deed\.[a-z][a-z]$', '') ))) &gt; 1">
            <xsl:attribute name="id">amdSec_16</xsl:attribute>
            <xsl:attribute name="role">fatal</xsl:attribute>
            <xsl:attribute name="location">
               <xsl:apply-templates select="." mode="schematron-select-full-path"/>
            </xsl:attribute>
            <svrl:text>Der Datensatz enthält mehrere <xsl:text/>mods:accessCondition[@type='use and reproduction']<xsl:text/>-Elemente mit unterschiedlichen Rechteangaben aus dem Lizenzkorb der DDB (https://pro.deutsche-digitale-bibliothek.de/daten-liefern/teilnahmekriterien/rechtliches/lizenzen-und-rechtehinweise-der-lizenzkorb-der-deutschen-digitalen-bibliothek) im Attribut <xsl:text/>xlink:href<xsl:text/>.
Die DDB benötigt eindeutige Rechteangaben, daher wird der Datensatz nicht in die DDB eingespielt.</svrl:text>
            <svrl:property id="dmd_id">
               <xsl:value-of xmlns:sch="http://purl.oclc.org/dsdl/schematron"
                             select="ancestor-or-self::mets:dmdSec/@ID"/>
            </svrl:property>
         </svrl:successful-report>
      </xsl:if>
      <xsl:apply-templates select="*" mode="M105"/>
   </xsl:template>
   <!--RULE -->
   <xsl:template match="mets:mets" priority="1000" mode="M105">
      <svrl:fired-rule xmlns:svrl="http://purl.oclc.org/dsdl/svrl" context="mets:mets"/>
      <!--REPORT error-->
      <xsl:if test="count(distinct-values(( mets:amdSec[@ID=$work_amdid]/mets:rightsMD/mets:mdWrap/mets:xmlData/dv:rights/dv:license[key('license_uris', replace(text(), 'deed\.[a-z][a-z]$', ''), $license_uris)]/replace(replace(text(), '^https', 'http'), 'deed\.[a-z][a-z]$', ''), mets:amdSec[not(@ID=$work_amdid)][1]/mets:rightsMD/mets:mdWrap/mets:xmlData/dv:rights/dv:license[key('license_uris', replace(text(), 'deed\.[a-z][a-z]$', ''), $license_uris)]/replace(replace(text(), '^https', 'http'), 'deed\.[a-z][a-z]$', ''), mets:dmdSec[@ID=$work_dmdid]/mets:mdWrap/mets:xmlData/mods:mods/mods:accessCondition[@type='use and reproduction'][key('license_uris', replace(text(), 'deed\.[a-z][a-z]$', ''), $license_uris)]/replace(replace(text(), '^https', 'http'), 'deed\.[a-z][a-z]$', ''), mets:dmdSec[@ID=$work_dmdid]/mets:mdWrap/mets:xmlData/mods:mods/mods:accessCondition[@type='use and reproduction'][key('license_uris', replace(@*[local-name()='href'][1], 'deed\.[a-z][a-z]$', ''), $license_uris)]/replace(replace(@*[local-name()='href'][1], '^https', 'http'), 'deed\.[a-z][a-z]$', ''), key('mets_ap_dv_license_values', mets:amdSec[@ID=$work_amdid]/mets:rightsMD/mets:mdWrap/mets:xmlData/dv:rights/dv:license[key('mets_ap_dv_license_values', text(), $mets_ap_dv_license_values)]/text(), $mets_ap_dv_license_values)/@to ))) &gt; 1">
         <svrl:successful-report xmlns:svrl="http://purl.oclc.org/dsdl/svrl"
                                 test="count(distinct-values(( mets:amdSec[@ID=$work_amdid]/mets:rightsMD/mets:mdWrap/mets:xmlData/dv:rights/dv:license[key('license_uris', replace(text(), 'deed\.[a-z][a-z]$', ''), $license_uris)]/replace(replace(text(), '^https', 'http'), 'deed\.[a-z][a-z]$', ''), mets:amdSec[not(@ID=$work_amdid)][1]/mets:rightsMD/mets:mdWrap/mets:xmlData/dv:rights/dv:license[key('license_uris', replace(text(), 'deed\.[a-z][a-z]$', ''), $license_uris)]/replace(replace(text(), '^https', 'http'), 'deed\.[a-z][a-z]$', ''), mets:dmdSec[@ID=$work_dmdid]/mets:mdWrap/mets:xmlData/mods:mods/mods:accessCondition[@type='use and reproduction'][key('license_uris', replace(text(), 'deed\.[a-z][a-z]$', ''), $license_uris)]/replace(replace(text(), '^https', 'http'), 'deed\.[a-z][a-z]$', ''), mets:dmdSec[@ID=$work_dmdid]/mets:mdWrap/mets:xmlData/mods:mods/mods:accessCondition[@type='use and reproduction'][key('license_uris', replace(@*[local-name()='href'][1], 'deed\.[a-z][a-z]$', ''), $license_uris)]/replace(replace(@*[local-name()='href'][1], '^https', 'http'), 'deed\.[a-z][a-z]$', ''), key('mets_ap_dv_license_values', mets:amdSec[@ID=$work_amdid]/mets:rightsMD/mets:mdWrap/mets:xmlData/dv:rights/dv:license[key('mets_ap_dv_license_values', text(), $mets_ap_dv_license_values)]/text(), $mets_ap_dv_license_values)/@to ))) &gt; 1">
            <xsl:attribute name="id">amdSec_17</xsl:attribute>
            <xsl:attribute name="role">error</xsl:attribute>
            <xsl:attribute name="location">
               <xsl:apply-templates select="." mode="schematron-select-full-path"/>
            </xsl:attribute>
            <svrl:text>Der Datensatz enthält im Element <xsl:text/>mods:accessCondition[@type='use and reproduction']<xsl:text/> im Attribut <xsl:text/>xlink:href<xsl:text/> und im Element <xsl:text/>dv:license<xsl:text/> sich widersprechende Rechteangaben aus dem Lizenzkorb der DDB (https://pro.deutsche-digitale-bibliothek.de/daten-liefern/teilnahmekriterien/rechtliches/lizenzen-und-rechtehinweise-der-lizenzkorb-der-deutschen-digitalen-bibliothek). Bitte beachten Sie hierbei, dass die DDB die CC-Lizenz-Werte aus dem METS-Anwendungsprofil (Kapitel 2.7.2.11) (https://dfg-viewer.de/fileadmin/groups/dfgviewer/METS-Anwendungsprofil_2.3.1.pdf) als Version 4.0 und den Wert <xsl:text/>reserved<xsl:text/> als Urheberrechtsschutz nicht bewertet (Europeana Rightstatement "CNE") (http://rightsstatements.org/vocab/CNE/1.0/) interpretiert.
Bei der Transformation des Datensatzes übernimmt die DDB in diesem Fall die Rechteangabe aus <xsl:text/>mods:accessCondition[@type='use and reproduction']<xsl:text/>.</svrl:text>
            <svrl:property id="dmd_id">
               <xsl:value-of xmlns:sch="http://purl.oclc.org/dsdl/schematron"
                             select="ancestor-or-self::mets:dmdSec/@ID"/>
            </svrl:property>
         </svrl:successful-report>
      </xsl:if>
      <xsl:apply-templates select="*" mode="M105"/>
   </xsl:template>
   <xsl:template match="text()" priority="-1" mode="M105"/>
   <xsl:template match="@*|node()" priority="-2" mode="M105">
      <xsl:apply-templates select="*" mode="M105"/>
   </xsl:template>
   <!--PATTERN -->
   <!--RULE -->
   <xsl:template match="mets:mets" priority="1000" mode="M106">
      <svrl:fired-rule xmlns:svrl="http://purl.oclc.org/dsdl/svrl" context="mets:mets"/>
      <!--ASSERT warn-->
      <xsl:choose>
         <xsl:when test="mets:amdSec/mets:digiprovMD/mets:mdWrap/mets:xmlData/dv:links/dv:presentation[matches(text(), '^https?://')]"/>
         <xsl:otherwise>
            <svrl:failed-assert xmlns:svrl="http://purl.oclc.org/dsdl/svrl"
                                test="mets:amdSec/mets:digiprovMD/mets:mdWrap/mets:xmlData/dv:links/dv:presentation[matches(text(), '^https?://')]">
               <xsl:attribute name="id">amdSec_15</xsl:attribute>
               <xsl:attribute name="role">warn</xsl:attribute>
               <xsl:attribute name="location">
                  <xsl:apply-templates select="." mode="schematron-select-full-path"/>
               </xsl:attribute>
               <svrl:text>Das Element <xsl:text/>mets:amdSec<xsl:text/> muss das Unterelement <xsl:text/>mets:digiprovMD<xsl:text/> enthalten.
Dieses muss auf der untersten Ebene das Element <xsl:text/>dv:presentation<xsl:text/> enthalten, das einen http- bzw. https-URI enthält, der auf die Anzeige des Digitalisats bei Ihrer Institution referenziert.
Fehlt <xsl:text/>dv:presentation<xsl:text/> im Datensatz wird in der DDB der Button "Objekt anzeigen" ausgeblendet.Weitere Informationen zu diesem Element finden Sie im DDB-METS/MODS-Anwendungsprofil auf der Seite mets:digiprovMD (https://wiki.deutsche-digitale-bibliothek.de/x/tsIeB).</svrl:text>
            </svrl:failed-assert>
         </xsl:otherwise>
      </xsl:choose>
      <xsl:apply-templates select="*" mode="M106"/>
   </xsl:template>
   <xsl:template match="text()" priority="-1" mode="M106"/>
   <xsl:template match="@*|node()" priority="-2" mode="M106">
      <xsl:apply-templates select="*" mode="M106"/>
   </xsl:template>
   <!--PATTERN -->
   <!--RULE -->
   <xsl:template match="mets:mets" priority="1000" mode="M107">
      <svrl:fired-rule xmlns:svrl="http://purl.oclc.org/dsdl/svrl" context="mets:mets"/>
      <!--ASSERT warn-->
      <xsl:choose>
         <xsl:when test="mets:amdSec/mets:rightsMD/mets:mdWrap/mets:xmlData/dv:rights/dv:ownerSiteURL[matches(text(), '^https?://')]"/>
         <xsl:otherwise>
            <svrl:failed-assert xmlns:svrl="http://purl.oclc.org/dsdl/svrl"
                                test="mets:amdSec/mets:rightsMD/mets:mdWrap/mets:xmlData/dv:rights/dv:ownerSiteURL[matches(text(), '^https?://')]">
               <xsl:attribute name="id">amdSec_18</xsl:attribute>
               <xsl:attribute name="role">warn</xsl:attribute>
               <xsl:attribute name="location">
                  <xsl:apply-templates select="." mode="schematron-select-full-path"/>
               </xsl:attribute>
               <svrl:text>Das Element <xsl:text/>mets:amdSec<xsl:text/> muss das Unterelement <xsl:text/>mets:rightsMD<xsl:text/> enthalten.
Dieses muss auf der untersten Ebene das Element <xsl:text/>dv:ownerSiteURL<xsl:text/> enthalten, das einen http- bzw. https-URI enthält, der auf die Webseite Ihrer Institution referenziert.
Fehlt <xsl:text/>dv:ownerSiteURL<xsl:text/> im Datensatz, ist der Datengeber-Link im Buchviewer der DDB ohne Referenz.Weitere Informationen zu diesem Element finden Sie im DDB-METS/MODS-Anwendungsprofil auf der Seite mets:rightsMD (https://wiki.deutsche-digitale-bibliothek.de/x/ssIeB).</svrl:text>
            </svrl:failed-assert>
         </xsl:otherwise>
      </xsl:choose>
      <xsl:apply-templates select="*" mode="M107"/>
   </xsl:template>
   <xsl:template match="text()" priority="-1" mode="M107"/>
   <xsl:template match="@*|node()" priority="-2" mode="M107">
      <xsl:apply-templates select="*" mode="M107"/>
   </xsl:template>
   <!--PATTERN -->
   <!--RULE -->
   <xsl:template match="mets:mets" priority="1002" mode="M108">
      <svrl:fired-rule xmlns:svrl="http://purl.oclc.org/dsdl/svrl" context="mets:mets"/>
      <!--ASSERT fatal-->
      <xsl:choose>
         <xsl:when test="$is_anchor or mets:fileSec"/>
         <xsl:otherwise>
            <svrl:failed-assert xmlns:svrl="http://purl.oclc.org/dsdl/svrl"
                                test="$is_anchor or mets:fileSec">
               <xsl:attribute name="id">fileSec_01</xsl:attribute>
               <xsl:attribute name="role">fatal</xsl:attribute>
               <xsl:attribute name="location">
                  <xsl:apply-templates select="." mode="schematron-select-full-path"/>
               </xsl:attribute>
               <svrl:text>Datensätze, die Einteilige Dokumente oder Teile von Mehrteiligen Dokumenten beschreiben, müssen das Element <xsl:text/>mets:fileSec<xsl:text/> enthalten. Fehlt <xsl:text/>mets:fileSec<xsl:text/>, wird der Datensatz nicht in die DDB eingespielt.Weitere Informationen zu diesem Element und seinem Kontext finden Sie im DDB-METS/MODS-Anwendungsprofil auf der Seite mets:fileSec (https://wiki.deutsche-digitale-bibliothek.de/x/asIeB) und in den entsprechenden Unterseiten der Seite Aufbau einer METS/MODS-Datei für die DDB (https://wiki.deutsche-digitale-bibliothek.de/x/VcIeB).</svrl:text>
            </svrl:failed-assert>
         </xsl:otherwise>
      </xsl:choose>
      <xsl:apply-templates select="*" mode="M108"/>
   </xsl:template>
   <!--RULE -->
   <xsl:template match="mets:mets/mets:fileSec[not(mets:fileGrp[@USE='DEFAULT'])]"
                 priority="1001"
                 mode="M108">
      <svrl:fired-rule xmlns:svrl="http://purl.oclc.org/dsdl/svrl"
                       context="mets:mets/mets:fileSec[not(mets:fileGrp[@USE='DEFAULT'])]"/>
      <!--ASSERT fatal-->
      <xsl:choose>
         <xsl:when test="mets:fileGrp[@USE='DEFAULT'] or $is_anchor"/>
         <xsl:otherwise>
            <svrl:failed-assert xmlns:svrl="http://purl.oclc.org/dsdl/svrl"
                                test="mets:fileGrp[@USE='DEFAULT'] or $is_anchor">
               <xsl:attribute name="id">fileSec_02</xsl:attribute>
               <xsl:attribute name="role">fatal</xsl:attribute>
               <xsl:attribute name="location">
                  <xsl:apply-templates select="." mode="schematron-select-full-path"/>
               </xsl:attribute>
               <svrl:text>Beschreibt der Datensatz Einteilige Dokumente oder Teile von Mehrteiligen Dokumenten, muss das Element <xsl:text/>mets:fileSec<xsl:text/> das Element <xsl:text/>mets:fileGrp<xsl:text/> mit dem Attribut <xsl:text/>USE<xsl:text/> mit dem Wert <xsl:text/>DEFAULT<xsl:text/> enthalten.
Fehlt ein entsprechendes <xsl:text/>mets:fileGrp<xsl:text/>, wird der Datensatz nicht in die DDB eingespielt.Weitere Informationen zu diesem Element finden Sie im DDB-METS/MODS-Anwendungsprofil auf der Seite mets:fileSec (https://wiki.deutsche-digitale-bibliothek.de/x/asIeB).</svrl:text>
            </svrl:failed-assert>
         </xsl:otherwise>
      </xsl:choose>
      <xsl:apply-templates select="*" mode="M108"/>
   </xsl:template>
   <!--RULE -->
   <xsl:template match="mets:mets/mets:fileSec/mets:fileGrp[@USE='DEFAULT']"
                 priority="1000"
                 mode="M108">
      <svrl:fired-rule xmlns:svrl="http://purl.oclc.org/dsdl/svrl"
                       context="mets:mets/mets:fileSec/mets:fileGrp[@USE='DEFAULT']"/>
      <!--REPORT fatal-->
      <xsl:if test="mets:file[mets:FLocat[string-length(@xlink:href) = 0]]">
         <svrl:successful-report xmlns:svrl="http://purl.oclc.org/dsdl/svrl"
                                 test="mets:file[mets:FLocat[string-length(@xlink:href) = 0]]">
            <xsl:attribute name="id">fileSec_03</xsl:attribute>
            <xsl:attribute name="role">fatal</xsl:attribute>
            <xsl:attribute name="location">
               <xsl:apply-templates select="." mode="schematron-select-full-path"/>
            </xsl:attribute>
            <svrl:text>Das Element <xsl:text/>mets:fileGrp<xsl:text/> mit dem Attribut <xsl:text/>USE<xsl:text/> mit dem Wert <xsl:text/>DEFAULT<xsl:text/> muss mindestens ein <xsl:text/>mets:file<xsl:text/>-Element enthalten. Dieses muss das Unterelement <xsl:text/>mets:FLocat<xsl:text/> mit dem Attribut <xsl:text/>xlink:href<xsl:text/> besitzen.
Ist dies nicht der Fall, wird der Datensatz nicht in die DDB eingespielt.Weitere Informationen zu diesem Element finden Sie im DDB-METS/MODS-Anwendungsprofil auf der Seite mets:fileSec (https://wiki.deutsche-digitale-bibliothek.de/x/asIeB).</svrl:text>
         </svrl:successful-report>
      </xsl:if>
      <xsl:apply-templates select="*" mode="M108"/>
   </xsl:template>
   <xsl:template match="text()" priority="-1" mode="M108"/>
   <xsl:template match="@*|node()" priority="-2" mode="M108">
      <xsl:apply-templates select="*" mode="M108"/>
   </xsl:template>
   <!--PATTERN -->
   <!--RULE -->
   <xsl:template match="mets:mets/mets:fileSec/mets:fileGrp/mets:file"
                 priority="1000"
                 mode="M109">
      <svrl:fired-rule xmlns:svrl="http://purl.oclc.org/dsdl/svrl"
                       context="mets:mets/mets:fileSec/mets:fileGrp/mets:file"/>
      <!--ASSERT fatal-->
      <xsl:choose>
         <xsl:when test="count(key('mets_ids', @ID)) = 1 and matches(@ID, '^[\i-[:]][\c-[:]]*$')"/>
         <xsl:otherwise>
            <svrl:failed-assert xmlns:svrl="http://purl.oclc.org/dsdl/svrl"
                                test="count(key('mets_ids', @ID)) = 1 and matches(@ID, '^[\i-[:]][\c-[:]]*$')">
               <xsl:attribute name="id">fileSec_04</xsl:attribute>
               <xsl:attribute name="role">fatal</xsl:attribute>
               <xsl:attribute name="location">
                  <xsl:apply-templates select="." mode="schematron-select-full-path"/>
               </xsl:attribute>
               <svrl:text>Das Element <xsl:text/>mets:file<xsl:text/> muss das Attribut <xsl:text/>ID<xsl:text/> mit einem im Datensatz eindeutigen Identifier enthalten. Dieser darf darüber hinaus keine ungültigen Zeichen enthalten.
Fehlt das Attribut <xsl:text/>ID<xsl:text/> bzw. enthält es ungültige Zeichen, wird der Datensatz nicht in die DDB eingespielt.Weitere Informationen zu diesem Attribut finden Sie im DDB-METS/MODS-Anwendungsprofil auf der Seite mets:fileSec (https://wiki.deutsche-digitale-bibliothek.de/x/asIeB).</svrl:text>
               <svrl:property id="id">
                  <xsl:value-of xmlns:sch="http://purl.oclc.org/dsdl/schematron" select="./@ID"/>
               </svrl:property>
            </svrl:failed-assert>
         </xsl:otherwise>
      </xsl:choose>
      <xsl:apply-templates select="*" mode="M109"/>
   </xsl:template>
   <xsl:template match="text()" priority="-1" mode="M109"/>
   <xsl:template match="@*|node()" priority="-2" mode="M109">
      <xsl:apply-templates select="*" mode="M109"/>
   </xsl:template>
   <!--PATTERN -->
   <!--RULE -->
   <xsl:template match="mets:mets/mets:fileSec" priority="1000" mode="M110">
      <svrl:fired-rule xmlns:svrl="http://purl.oclc.org/dsdl/svrl"
                       context="mets:mets/mets:fileSec"/>
      <!--REPORT warn-->
      <xsl:if test="//mets:file[string-length(@MIMETYPE) = 0]">
         <svrl:successful-report xmlns:svrl="http://purl.oclc.org/dsdl/svrl"
                                 test="//mets:file[string-length(@MIMETYPE) = 0]">
            <xsl:attribute name="id">fileSec_08</xsl:attribute>
            <xsl:attribute name="role">warn</xsl:attribute>
            <xsl:attribute name="location">
               <xsl:apply-templates select="." mode="schematron-select-full-path"/>
            </xsl:attribute>
            <svrl:text>Das Element <xsl:text/>mets:file<xsl:text/> muss das Attribut <xsl:text/>MIMETYPE<xsl:text/> besitzen. Der Datensatz enthält mindestens ein <xsl:text/>mets:file<xsl:text/> ohne das Attribut <xsl:text/>MIMETYPE<xsl:text/>.
Das Fehlen des Attributs <xsl:text/>MIMETYPE<xsl:text/> verhindert nicht das Einspielen des Datensatzes in die DDB, kann aber zu Darstellungsproblemen führen. Wir bitten Sie daher den Sachverhalt zu prüfen und ggf. die nötigen Korrekturen bis zur nächsten Datenlieferung vorzunehmen.Weitere Informationen zu diesem Attribut finden Sie im DDB-METS/MODS-Anwendungsprofil auf der Seite mets:fileSec (https://wiki.deutsche-digitale-bibliothek.de/x/asIeB).</svrl:text>
         </svrl:successful-report>
      </xsl:if>
      <xsl:apply-templates select="*" mode="M110"/>
   </xsl:template>
   <xsl:template match="text()" priority="-1" mode="M110"/>
   <xsl:template match="@*|node()" priority="-2" mode="M110">
      <xsl:apply-templates select="*" mode="M110"/>
   </xsl:template>
   <!--PATTERN -->
   <!--RULE -->
   <xsl:template match="mets:mets/mets:fileSec/mets:fileGrp[@USE=('DEFAULT', 'THUMBS', 'FULLTEXT')]/mets:file"
                 priority="1000"
                 mode="M111">
      <svrl:fired-rule xmlns:svrl="http://purl.oclc.org/dsdl/svrl"
                       context="mets:mets/mets:fileSec/mets:fileGrp[@USE=('DEFAULT', 'THUMBS', 'FULLTEXT')]/mets:file"/>
      <!--ASSERT fatal-->
      <xsl:choose>
         <xsl:when test="key('structMap_PHYSICAL_fptr_FILEID', @ID)"/>
         <xsl:otherwise>
            <svrl:failed-assert xmlns:svrl="http://purl.oclc.org/dsdl/svrl"
                                test="key('structMap_PHYSICAL_fptr_FILEID', @ID)">
               <xsl:attribute name="id">fileSec_09</xsl:attribute>
               <xsl:attribute name="role">fatal</xsl:attribute>
               <xsl:attribute name="location">
                  <xsl:apply-templates select="." mode="schematron-select-full-path"/>
               </xsl:attribute>
               <svrl:text>Das Element <xsl:text/>mets:file<xsl:text/> muss über sein Attribut <xsl:text/>ID<xsl:text/> mit einem <xsl:text/>mets:fptr<xsl:text/>-Element im Element <xsl:text/>mets:structMap[@TYPE='PHYSICAL']<xsl:text/> über dessen Attribut <xsl:text/>FILEID<xsl:text/> referenziert werden.
Fehlt diese Referenzierung, wird der Datensatz nicht in die DDB eingespielt.Weitere Informationen zu diesen Elementen finden Sie im DDB-METS/MODS-Anwendungsprofil auf den Seiten mets:fileSec (https://wiki.deutsche-digitale-bibliothek.de/x/asIeB) und mets:structMap[@TYPE='PHYSICAL'] (https://wiki.deutsche-digitale-bibliothek.de/x/i8IeB).</svrl:text>
               <svrl:property id="id">
                  <xsl:value-of xmlns:sch="http://purl.oclc.org/dsdl/schematron" select="./@ID"/>
               </svrl:property>
            </svrl:failed-assert>
         </xsl:otherwise>
      </xsl:choose>
      <xsl:apply-templates select="*" mode="M111"/>
   </xsl:template>
   <xsl:template match="text()" priority="-1" mode="M111"/>
   <xsl:template match="@*|node()" priority="-2" mode="M111">
      <xsl:apply-templates select="*" mode="M111"/>
   </xsl:template>
   <!--PATTERN -->
   <!--RULE -->
   <xsl:template match="mets:mets" priority="1001" mode="M112">
      <svrl:fired-rule xmlns:svrl="http://purl.oclc.org/dsdl/svrl" context="mets:mets"/>
      <!--ASSERT fatal-->
      <xsl:choose>
         <xsl:when test="mets:structMap[@TYPE='LOGICAL']"/>
         <xsl:otherwise>
            <svrl:failed-assert xmlns:svrl="http://purl.oclc.org/dsdl/svrl"
                                test="mets:structMap[@TYPE='LOGICAL']">
               <xsl:attribute name="id">structMapLogical_01</xsl:attribute>
               <xsl:attribute name="role">fatal</xsl:attribute>
               <xsl:attribute name="location">
                  <xsl:apply-templates select="." mode="schematron-select-full-path"/>
               </xsl:attribute>
               <svrl:text>Der Datensatz muss das Element <xsl:text/>mets:structMap<xsl:text/> mit dem Attribut <xsl:text/>TYPE<xsl:text/> mit dem Wert <xsl:text/>LOGICAL<xsl:text/> enthalten.
Fehlt ein entsprechendes <xsl:text/>mets:structMap<xsl:text/>, wird der Datensatz nicht in die DDB eingespielt.Weitere Informationen zu diesem Element und seinem Kontext finden Sie im DDB-METS/MODS-Anwendungsprofil auf der Seite mets:structMap[@TYPE='LOGICAL'] (https://wiki.deutsche-digitale-bibliothek.de/x/o8IeB) und im Bereich Aufbau einer METS/MODS-Datei für die DDB (https://wiki.deutsche-digitale-bibliothek.de/x/VcIeB).</svrl:text>
            </svrl:failed-assert>
         </xsl:otherwise>
      </xsl:choose>
      <xsl:apply-templates select="*" mode="M112"/>
   </xsl:template>
   <!--RULE -->
   <xsl:template match="mets:mets/mets:structMap[@TYPE='LOGICAL']"
                 priority="1000"
                 mode="M112">
      <svrl:fired-rule xmlns:svrl="http://purl.oclc.org/dsdl/svrl"
                       context="mets:mets/mets:structMap[@TYPE='LOGICAL']"/>
      <!--ASSERT fatal-->
      <xsl:choose>
         <xsl:when test="mets:div"/>
         <xsl:otherwise>
            <svrl:failed-assert xmlns:svrl="http://purl.oclc.org/dsdl/svrl" test="mets:div">
               <xsl:attribute name="id">structMapLogical_02</xsl:attribute>
               <xsl:attribute name="role">fatal</xsl:attribute>
               <xsl:attribute name="location">
                  <xsl:apply-templates select="." mode="schematron-select-full-path"/>
               </xsl:attribute>
               <svrl:text>Das Element <xsl:text/>mets:structMap<xsl:text/> mit dem Attribut <xsl:text/>TYPE<xsl:text/> mit dem Wert <xsl:text/>LOGICAL<xsl:text/> muss mindestens ein <xsl:text/>mets:div<xsl:text/>-Element enthalten. Dieses muss das Unterelement <xsl:text/>mets:FLocat<xsl:text/> mit dem Attribut <xsl:text/>xlink:href<xsl:text/> besitzen.
Ist dies nicht der Fall, wird der Datensatz nicht in die DDB eingespielt.Weitere Informationen zu diesem Element und seinem Kontext finden Sie im DDB-METS/MODS-Anwendungsprofil auf der Seite mets:structMap[@TYPE='LOGICAL'] (https://wiki.deutsche-digitale-bibliothek.de/x/o8IeB) und im Bereich Aufbau einer METS/MODS-Datei für die DDB (https://wiki.deutsche-digitale-bibliothek.de/x/VcIeB).</svrl:text>
            </svrl:failed-assert>
         </xsl:otherwise>
      </xsl:choose>
      <xsl:apply-templates select="*" mode="M112"/>
   </xsl:template>
   <xsl:template match="text()" priority="-1" mode="M112"/>
   <xsl:template match="@*|node()" priority="-2" mode="M112">
      <xsl:apply-templates select="*" mode="M112"/>
   </xsl:template>
   <!--PATTERN -->
   <!--RULE -->
   <xsl:template match="mets:mets/mets:structMap[@TYPE='LOGICAL']//mets:div"
                 priority="1000"
                 mode="M113">
      <svrl:fired-rule xmlns:svrl="http://purl.oclc.org/dsdl/svrl"
                       context="mets:mets/mets:structMap[@TYPE='LOGICAL']//mets:div"/>
      <!--ASSERT fatal-->
      <xsl:choose>
         <xsl:when test="count(key('mets_ids', @ID)) = 1 and matches(@ID, '^[\i-[:]][\c-[:]]*$')"/>
         <xsl:otherwise>
            <svrl:failed-assert xmlns:svrl="http://purl.oclc.org/dsdl/svrl"
                                test="count(key('mets_ids', @ID)) = 1 and matches(@ID, '^[\i-[:]][\c-[:]]*$')">
               <xsl:attribute name="id">structMapLogical_03</xsl:attribute>
               <xsl:attribute name="role">fatal</xsl:attribute>
               <xsl:attribute name="location">
                  <xsl:apply-templates select="." mode="schematron-select-full-path"/>
               </xsl:attribute>
               <svrl:text>Das Element <xsl:text/>mets:div<xsl:text/> im Element <xsl:text/>mets:structMap[@TYPE='LOGICAL']<xsl:text/> muss das Attribut <xsl:text/>ID<xsl:text/> mit einem im Datensatz eindeutigen Identifier enthalten. Dieser darf darüber hinaus keine ungültigen Zeichen enthalten.
Fehlt das Attribut <xsl:text/>ID<xsl:text/> bzw. enthält es ungültige Zeichen, wird der Datensatz nicht in die DDB eingespielt.Weitere Informationen zu diesem Attribut finden Sie im DDB-METS/MODS-Anwendungsprofil auf der Seite mets:structMap[@TYPE='LOGICAL'] (https://wiki.deutsche-digitale-bibliothek.de/x/o8IeB).</svrl:text>
               <svrl:property id="id">
                  <xsl:value-of xmlns:sch="http://purl.oclc.org/dsdl/schematron" select="./@ID"/>
               </svrl:property>
            </svrl:failed-assert>
         </xsl:otherwise>
      </xsl:choose>
      <xsl:apply-templates select="*" mode="M113"/>
   </xsl:template>
   <xsl:template match="text()" priority="-1" mode="M113"/>
   <xsl:template match="@*|node()" priority="-2" mode="M113">
      <xsl:apply-templates select="*" mode="M113"/>
   </xsl:template>
   <!--PATTERN -->
   <!--RULE -->
   <xsl:template match="key('structMap_LOGICAL_dmdids', $work_dmdid)//mets:div[@DMDID] | key('structMap_LOGICAL_dmdids', $work_dmdid)[@DMDID]"
                 priority="1001"
                 mode="M114">
      <svrl:fired-rule xmlns:svrl="http://purl.oclc.org/dsdl/svrl"
                       context="key('structMap_LOGICAL_dmdids', $work_dmdid)//mets:div[@DMDID] | key('structMap_LOGICAL_dmdids', $work_dmdid)[@DMDID]"/>
      <!--ASSERT fatal-->
      <xsl:choose>
         <xsl:when test="$is_anchor or key('structLink_from_ids', @ID)"/>
         <xsl:otherwise>
            <svrl:failed-assert xmlns:svrl="http://purl.oclc.org/dsdl/svrl"
                                test="$is_anchor or key('structLink_from_ids', @ID)">
               <xsl:attribute name="id">structMapLogical_04</xsl:attribute>
               <xsl:attribute name="role">fatal</xsl:attribute>
               <xsl:attribute name="location">
                  <xsl:apply-templates select="." mode="schematron-select-full-path"/>
               </xsl:attribute>
               <svrl:text>Das Element <xsl:text/>mets:div<xsl:text/> im Element <xsl:text/>mets:structMap[@TYPE='LOGICAL']<xsl:text/> mit dem Attribut <xsl:text/>DMDID<xsl:text/> muss über sein Attribut <xsl:text/>ID<xsl:text/> von mindestens einem <xsl:text/>mets:smLink<xsl:text/>-Element im Element <xsl:text/>mets:structLink<xsl:text/> über dessen Attribut <xsl:text/>xlink:from<xsl:text/> referenziert werden.
Fehlt diese Referenzierung, wird der Datensatz nicht in die DDB eingespielt.Weitere Informationen zu diesen Elementen finden Sie im DDB-METS/MODS-Anwendungsprofil auf den Seiten mets:structMap[@TYPE='LOGICAL'] (https://wiki.deutsche-digitale-bibliothek.de/x/o8IeB) und mets:structLink (https://wiki.deutsche-digitale-bibliothek.de/x/q8IeB).</svrl:text>
               <svrl:property id="id">
                  <xsl:value-of xmlns:sch="http://purl.oclc.org/dsdl/schematron" select="./@ID"/>
               </svrl:property>
            </svrl:failed-assert>
         </xsl:otherwise>
      </xsl:choose>
      <xsl:apply-templates select="*" mode="M114"/>
   </xsl:template>
   <!--RULE -->
   <xsl:template match="key('structMap_LOGICAL_dmdids', $work_dmdid)//mets:div | key('structMap_LOGICAL_dmdids', $work_dmdid)"
                 priority="1000"
                 mode="M114">
      <svrl:fired-rule xmlns:svrl="http://purl.oclc.org/dsdl/svrl"
                       context="key('structMap_LOGICAL_dmdids', $work_dmdid)//mets:div | key('structMap_LOGICAL_dmdids', $work_dmdid)"/>
      <!--ASSERT warn-->
      <xsl:choose>
         <xsl:when test="$is_anchor or key('structLink_from_ids', @ID)"/>
         <xsl:otherwise>
            <svrl:failed-assert xmlns:svrl="http://purl.oclc.org/dsdl/svrl"
                                test="$is_anchor or key('structLink_from_ids', @ID)">
               <xsl:attribute name="id">structMapLogical_21</xsl:attribute>
               <xsl:attribute name="role">warn</xsl:attribute>
               <xsl:attribute name="location">
                  <xsl:apply-templates select="." mode="schematron-select-full-path"/>
               </xsl:attribute>
               <svrl:text>Das Element <xsl:text/>mets:div<xsl:text/> im Element <xsl:text/>mets:structMap[@TYPE='LOGICAL']<xsl:text/> muss über sein Attribut <xsl:text/>ID<xsl:text/> von mindestens einem <xsl:text/>mets:smLink<xsl:text/>-Element im Element <xsl:text/>mets:structLink<xsl:text/> über dessen Attribut <xsl:text/>xlink:from<xsl:text/> referenziert werden.
Eine fehlende Referenzierung verhindert nicht das Einspielen des Datensatzes in die DDB, kann aber zu Darstellungsproblemen im Main führen. Wir bitten Sie daher, den Sachverhalt zu prüfen und die nötigen Korrekturen bis zur nächsten Datenlieferung vorzunehmen.Weitere Informationen zu diesen Elementen finden Sie im DDB-METS/MODS-Anwendungsprofil auf den Seiten mets:structMap[@TYPE='LOGICAL'] (https://wiki.deutsche-digitale-bibliothek.de/x/o8IeB) und mets:structLink (https://wiki.deutsche-digitale-bibliothek.de/x/q8IeB).</svrl:text>
               <svrl:property id="id">
                  <xsl:value-of xmlns:sch="http://purl.oclc.org/dsdl/schematron" select="./@ID"/>
               </svrl:property>
            </svrl:failed-assert>
         </xsl:otherwise>
      </xsl:choose>
      <xsl:apply-templates select="*" mode="M114"/>
   </xsl:template>
   <xsl:template match="text()" priority="-1" mode="M114"/>
   <xsl:template match="@*|node()" priority="-2" mode="M114">
      <xsl:apply-templates select="*" mode="M114"/>
   </xsl:template>
   <!--PATTERN -->
   <!--RULE -->
   <xsl:template match="mets:mets/mets:structMap[@TYPE='LOGICAL']//mets:div[not(@TYPE)]"
                 priority="1002"
                 mode="M115">
      <svrl:fired-rule xmlns:svrl="http://purl.oclc.org/dsdl/svrl"
                       context="mets:mets/mets:structMap[@TYPE='LOGICAL']//mets:div[not(@TYPE)]"/>
      <!--ASSERT fatal-->
      <xsl:choose>
         <xsl:when test="@TYPE"/>
         <xsl:otherwise>
            <svrl:failed-assert xmlns:svrl="http://purl.oclc.org/dsdl/svrl" test="@TYPE">
               <xsl:attribute name="id">structMapLogical_05</xsl:attribute>
               <xsl:attribute name="role">fatal</xsl:attribute>
               <xsl:attribute name="location">
                  <xsl:apply-templates select="." mode="schematron-select-full-path"/>
               </xsl:attribute>
               <svrl:text>Das Element <xsl:text/>mets:div<xsl:text/> im Element <xsl:text/>mets:structMap[@TYPE='LOGICAL']<xsl:text/> muss das Attribut <xsl:text/>TYPE<xsl:text/> mit einem Wert aus dem Strukturdatenset des DFG-Viewers (https://dfg-viewer.de/strukturdatenset/) (Spalte "XML") enthalten.
Fehlt das Attribut <xsl:text/>TYPE<xsl:text/> in <xsl:text/>mets:div<xsl:text/>, wird der Datensatz nicht in die DDB eingespielt.Weitere Informationen zu diesem Attribut finden Sie im DDB-METS/MODS-Anwendungsprofil auf der Seite mets:structMap[@TYPE='LOGICAL'] (https://wiki.deutsche-digitale-bibliothek.de/x/o8IeB).</svrl:text>
               <svrl:property id="id">
                  <xsl:value-of xmlns:sch="http://purl.oclc.org/dsdl/schematron" select="./@ID"/>
               </svrl:property>
            </svrl:failed-assert>
         </xsl:otherwise>
      </xsl:choose>
      <xsl:apply-templates select="*" mode="M115"/>
   </xsl:template>
   <!--RULE -->
   <xsl:template match="mets:mets/mets:structMap[@TYPE='LOGICAL']//mets:div[not( @TYPE = ( 'section', 'file', 'album', 'register', 'annotation', 'address', 'article', 'atlas', 'issue', 'bachelor_thesis', 'volume', 'contained_work', 'additional', 'report', 'official_notification', 'provenance', 'inventory', 'image', 'collation', 'ornament', 'letter', 'cover', 'cover_front', 'cover_back', 'diploma_thesis', 'doctoral_thesis', 'document', 'printers_mark', 'printed_archives', 'binding', 'entry', 'corrigenda', 'bookplate', 'fascicle', 'leaflet', 'research_paper', 'photograph', 'fragment', 'land_register', 'ground_plan', 'habilitation_thesis', 'manuscript', 'illustration', 'imprint', 'contents', 'initial_decoration', 'year', 'chapter', 'map', 'cartulary', 'colophon', 'ephemera', 'engraved_titlepage', 'magister_thesis', 'folder', 'master_thesis', 'multivolume_work', 'month', 'monograph', 'musical_notation', 'periodical', 'poster', 'plan', 'privileges', 'index', 'spine', 'scheme', 'edge', 'seal', 'paste_down', 'stamp', 'study', 'table', 'day', 'proceeding', 'text', 'title_page', 'subinventory', 'act', 'judgement', 'verse', 'note', 'preprint', 'dossier', 'lecture', 'endsheet', 'paper', 'preface', 'dedication', 'newspaper' ) )]"
                 priority="1001"
                 mode="M115">
      <svrl:fired-rule xmlns:svrl="http://purl.oclc.org/dsdl/svrl"
                       context="mets:mets/mets:structMap[@TYPE='LOGICAL']//mets:div[not( @TYPE = ( 'section', 'file', 'album', 'register', 'annotation', 'address', 'article', 'atlas', 'issue', 'bachelor_thesis', 'volume', 'contained_work', 'additional', 'report', 'official_notification', 'provenance', 'inventory', 'image', 'collation', 'ornament', 'letter', 'cover', 'cover_front', 'cover_back', 'diploma_thesis', 'doctoral_thesis', 'document', 'printers_mark', 'printed_archives', 'binding', 'entry', 'corrigenda', 'bookplate', 'fascicle', 'leaflet', 'research_paper', 'photograph', 'fragment', 'land_register', 'ground_plan', 'habilitation_thesis', 'manuscript', 'illustration', 'imprint', 'contents', 'initial_decoration', 'year', 'chapter', 'map', 'cartulary', 'colophon', 'ephemera', 'engraved_titlepage', 'magister_thesis', 'folder', 'master_thesis', 'multivolume_work', 'month', 'monograph', 'musical_notation', 'periodical', 'poster', 'plan', 'privileges', 'index', 'spine', 'scheme', 'edge', 'seal', 'paste_down', 'stamp', 'study', 'table', 'day', 'proceeding', 'text', 'title_page', 'subinventory', 'act', 'judgement', 'verse', 'note', 'preprint', 'dossier', 'lecture', 'endsheet', 'paper', 'preface', 'dedication', 'newspaper' ) )]"/>
      <!--ASSERT error-->
      <xsl:choose>
         <xsl:when test="@TYPE = ( 'section', 'file', 'album', 'register', 'annotation', 'address', 'article', 'atlas', 'issue', 'bachelor_thesis', 'volume', 'contained_work', 'additional', 'report', 'official_notification', 'provenance', 'inventory', 'image', 'collation', 'ornament', 'letter', 'cover', 'cover_front', 'cover_back', 'diploma_thesis', 'doctoral_thesis', 'document', 'printers_mark', 'printed_archives', 'binding', 'entry', 'corrigenda', 'bookplate', 'fascicle', 'leaflet', 'research_paper', 'photograph', 'fragment', 'land_register', 'ground_plan', 'habilitation_thesis', 'manuscript', 'illustration', 'imprint', 'contents', 'initial_decoration', 'year', 'chapter', 'map', 'cartulary', 'colophon', 'ephemera', 'engraved_titlepage', 'magister_thesis', 'folder', 'master_thesis', 'multivolume_work', 'month', 'monograph', 'musical_notation', 'periodical', 'poster', 'plan', 'privileges', 'index', 'spine', 'scheme', 'edge', 'seal', 'paste_down', 'stamp', 'study', 'table', 'day', 'proceeding', 'text', 'title_page', 'subinventory', 'act', 'judgement', 'verse', 'note', 'preprint', 'dossier', 'lecture', 'endsheet', 'paper', 'preface', 'dedication', 'newspaper' )"/>
         <xsl:otherwise>
            <svrl:failed-assert xmlns:svrl="http://purl.oclc.org/dsdl/svrl"
                                test="@TYPE = ( 'section', 'file', 'album', 'register', 'annotation', 'address', 'article', 'atlas', 'issue', 'bachelor_thesis', 'volume', 'contained_work', 'additional', 'report', 'official_notification', 'provenance', 'inventory', 'image', 'collation', 'ornament', 'letter', 'cover', 'cover_front', 'cover_back', 'diploma_thesis', 'doctoral_thesis', 'document', 'printers_mark', 'printed_archives', 'binding', 'entry', 'corrigenda', 'bookplate', 'fascicle', 'leaflet', 'research_paper', 'photograph', 'fragment', 'land_register', 'ground_plan', 'habilitation_thesis', 'manuscript', 'illustration', 'imprint', 'contents', 'initial_decoration', 'year', 'chapter', 'map', 'cartulary', 'colophon', 'ephemera', 'engraved_titlepage', 'magister_thesis', 'folder', 'master_thesis', 'multivolume_work', 'month', 'monograph', 'musical_notation', 'periodical', 'poster', 'plan', 'privileges', 'index', 'spine', 'scheme', 'edge', 'seal', 'paste_down', 'stamp', 'study', 'table', 'day', 'proceeding', 'text', 'title_page', 'subinventory', 'act', 'judgement', 'verse', 'note', 'preprint', 'dossier', 'lecture', 'endsheet', 'paper', 'preface', 'dedication', 'newspaper' )">
               <xsl:attribute name="id">structMapLogical_06</xsl:attribute>
               <xsl:attribute name="role">error</xsl:attribute>
               <xsl:attribute name="location">
                  <xsl:apply-templates select="." mode="schematron-select-full-path"/>
               </xsl:attribute>
               <svrl:text>Das Element <xsl:text/>mets:div<xsl:text/> im Element <xsl:text/>mets:structMap[@TYPE='LOGICAL']<xsl:text/> muss im Attribut <xsl:text/>TYPE<xsl:text/> einem Wert aus dem Strukturdatenset des DFG-Viewers (https://dfg-viewer.de/strukturdatenset/) (Spalte "XML") enthalten.
Enthält das Attribut <xsl:text/>TYPE<xsl:text/> von <xsl:text/>mets:div<xsl:text/> einen ungültigen Wert, wird er bei der Transformation des Datensatzes durch den Wert <xsl:text/>section<xsl:text/> ersetzt.Weitere Informationen zu diesem Attribut finden Sie im DDB-METS/MODS-Anwendungsprofil auf der Seite mets:structMap[@TYPE='LOGICAL'] (https://wiki.deutsche-digitale-bibliothek.de/x/o8IeB).</svrl:text>
               <svrl:property id="id">
                  <xsl:value-of xmlns:sch="http://purl.oclc.org/dsdl/schematron" select="./@ID"/>
               </svrl:property>
               <svrl:property id="TYPE">
                  <xsl:value-of xmlns:sch="http://purl.oclc.org/dsdl/schematron" select="./@TYPE"/>
               </svrl:property>
            </svrl:failed-assert>
         </xsl:otherwise>
      </xsl:choose>
      <xsl:apply-templates select="*" mode="M115"/>
   </xsl:template>
   <!--RULE -->
   <xsl:template match="mets:mets/mets:structMap[@TYPE='LOGICAL']//mets:div"
                 priority="1000"
                 mode="M115">
      <svrl:fired-rule xmlns:svrl="http://purl.oclc.org/dsdl/svrl"
                       context="mets:mets/mets:structMap[@TYPE='LOGICAL']//mets:div"/>
      <!--REPORT fatal-->
      <xsl:if test="./@TYPE = ('year', 'month', 'day')">
         <svrl:successful-report xmlns:svrl="http://purl.oclc.org/dsdl/svrl"
                                 test="./@TYPE = ('year', 'month', 'day')">
            <xsl:attribute name="id">structMapLogical_19</xsl:attribute>
            <xsl:attribute name="role">fatal</xsl:attribute>
            <xsl:attribute name="location">
               <xsl:apply-templates select="." mode="schematron-select-full-path"/>
            </xsl:attribute>
            <svrl:text>Der Datensatz enthält im Element <xsl:text/>mets:structMap[@TYPE='LOGICAL']<xsl:text/> mindestens ein <xsl:text/>mets:div<xsl:text/>-Element mit einem der folgenden Werte im Attribut <xsl:text/>TYPE<xsl:text/>:
 * <xsl:text/>year<xsl:text/>
 * <xsl:text/>month<xsl:text/>
 * <xsl:text/>day<xsl:text/>
Diese Werte sind nur in Datensätzen, die für das Zeitungsportal bestimmt sind, gültig und werden daher nicht in die DDB eingespielt.
Wenn Sie den Datensatz in das Zeitungsportal einspielen möchten (https://pro.deutsche-digitale-bibliothek.de/daten-liefern/lieferung-subportale/lieferungen-an-das-deutsche-zeitungsportal), teilen Sie dies bitte der Fachstelle Bibliothek (mailto:bibliothek@deutsche-digitale-bibliothek.de) mit.Weitere Informationen zur Struktur von Datensätzen für das Zeitungsportal finden Sie im DDB-METS/MODS-Anwendungsprofil auf der Seite Aufbau für Zeitungsausgaben (https://wiki.deutsche-digitale-bibliothek.de/x/ugGuB).</svrl:text>
            <svrl:property id="id">
               <xsl:value-of xmlns:sch="http://purl.oclc.org/dsdl/schematron" select="./@ID"/>
            </svrl:property>
         </svrl:successful-report>
      </xsl:if>
      <xsl:apply-templates select="*" mode="M115"/>
   </xsl:template>
   <xsl:template match="text()" priority="-1" mode="M115"/>
   <xsl:template match="@*|node()" priority="-2" mode="M115">
      <xsl:apply-templates select="*" mode="M115"/>
   </xsl:template>
   <!--PATTERN -->
   <!--RULE -->
   <xsl:template match="key('structMap_LOGICAL_dmdids', $work_dmdid) [ancestor::mets:mets/mets:dmdSec[@ID=$work_dmdid]/mets:mdWrap/mets:xmlData/mods:mods/mods:relatedItem[@type='host']]"
                 priority="1000"
                 mode="M116">
      <svrl:fired-rule xmlns:svrl="http://purl.oclc.org/dsdl/svrl"
                       context="key('structMap_LOGICAL_dmdids', $work_dmdid) [ancestor::mets:mets/mets:dmdSec[@ID=$work_dmdid]/mets:mdWrap/mets:xmlData/mods:mods/mods:relatedItem[@type='host']]"/>
      <!--ASSERT warn-->
      <xsl:choose>
         <xsl:when test="@TYPE = ('volume', 'additional', 'illustration', 'map', 'folder', 'musical_notation', 'part')"/>
         <xsl:otherwise>
            <svrl:failed-assert xmlns:svrl="http://purl.oclc.org/dsdl/svrl"
                                test="@TYPE = ('volume', 'additional', 'illustration', 'map', 'folder', 'musical_notation', 'part')">
               <xsl:attribute name="id">structMapLogical_07</xsl:attribute>
               <xsl:attribute name="role">warn</xsl:attribute>
               <xsl:attribute name="location">
                  <xsl:apply-templates select="." mode="schematron-select-full-path"/>
               </xsl:attribute>
               <svrl:text>Das primäre <xsl:text/>mets:div<xsl:text/>-Element im Element <xsl:text/>mets:structMap[@TYPE='LOGICAL']<xsl:text/> enthält im referenzierten <xsl:text/>mets:dmdSec<xsl:text/>-Element das Element <xsl:text/>mods:relatedItem[@type='host']<xsl:text/>. Es beschreibt damit den Teil eines Mehrteiligen Dokuments und muss daher im Attribut <xsl:text/>TYPE<xsl:text/> einen der folgenden Werte enthalten:
 * <xsl:text/>additional<xsl:text/>
 * <xsl:text/>folder<xsl:text/>
 * <xsl:text/>illustration<xsl:text/>
 * <xsl:text/>map<xsl:text/>
 * <xsl:text/>musical_notation<xsl:text/>
 * <xsl:text/>part<xsl:text/>
 * <xsl:text/>volume<xsl:text/>
Die Verwendung eines ungültigen Wertes verhindert nicht das Einspielen des Datensatzes in die DDB, wir bitten Sie jedoch, den Sachverhalt zu prüfen und die nötigen Korrekturen bis zur nächsten Datenlieferung vorzunehmen.Weitere Informationen zu diesem Attribut und seinem Kontext finden Sie im DDB-METS/MODS-Anwendungsprofil auf der Seite mets:structMap[@TYPE='LOGICAL'] (https://wiki.deutsche-digitale-bibliothek.de/x/o8IeB) und der Seite Aufbau für Teile Mehrteiliger Dokumente (https://wiki.deutsche-digitale-bibliothek.de/x/jwGuB).</svrl:text>
               <svrl:property id="id">
                  <xsl:value-of xmlns:sch="http://purl.oclc.org/dsdl/schematron" select="./@ID"/>
               </svrl:property>
               <svrl:property id="type">
                  <xsl:value-of xmlns:sch="http://purl.oclc.org/dsdl/schematron" select="./@type"/>
               </svrl:property>
            </svrl:failed-assert>
         </xsl:otherwise>
      </xsl:choose>
      <xsl:apply-templates select="*" mode="M116"/>
   </xsl:template>
   <xsl:template match="text()" priority="-1" mode="M116"/>
   <xsl:template match="@*|node()" priority="-2" mode="M116">
      <xsl:apply-templates select="*" mode="M116"/>
   </xsl:template>
   <!--PATTERN -->
   <!--RULE -->
   <xsl:template match="key('structMap_LOGICAL_dmdids', $work_dmdid) [ancestor::mets:mets/mets:dmdSec[@ID=$work_dmdid]/mets:mdWrap/mets:xmlData/mods:mods/mods:relatedItem[@type='host']]"
                 priority="1000"
                 mode="M117">
      <svrl:fired-rule xmlns:svrl="http://purl.oclc.org/dsdl/svrl"
                       context="key('structMap_LOGICAL_dmdids', $work_dmdid) [ancestor::mets:mets/mets:dmdSec[@ID=$work_dmdid]/mets:mdWrap/mets:xmlData/mods:mods/mods:relatedItem[@type='host']]"/>
      <!--ASSERT fatal-->
      <xsl:choose>
         <xsl:when test="./parent::mets:div/mets:mptr"/>
         <xsl:otherwise>
            <svrl:failed-assert xmlns:svrl="http://purl.oclc.org/dsdl/svrl"
                                test="./parent::mets:div/mets:mptr">
               <xsl:attribute name="id">structMapLogical_08</xsl:attribute>
               <xsl:attribute name="role">fatal</xsl:attribute>
               <xsl:attribute name="location">
                  <xsl:apply-templates select="." mode="schematron-select-full-path"/>
               </xsl:attribute>
               <svrl:text>Der Datensatz enthält im primären <xsl:text/>mets:dmdSec<xsl:text/>-Element das Element <xsl:text/>mods:relatedItem[@type='host']<xsl:text/> und beschreibt damit den Teil eines Mehrteiligen Dokuments. Daher muss das Elternelement <xsl:text/>mets:div<xsl:text/> des primären <xsl:text/>mets:div<xsl:text/>-Elements im Element <xsl:text/>mets:structMap[@TYPE='LOGICAL']<xsl:text/> das Unterelement <xsl:text/>mets:mptr<xsl:text/> enthalten.
Ist dies nicht der Fall, fehlt dem Datensatz die Referenz auf den Ankersatz des Mehrteiligen Dokuments und er wird nicht in die DDB eingespielt.Weitere Informationen zu diesem Kontext finden Sie im DDB-METS/MODS-Anwendungsprofil im Bereich METS/MODS für Mehrteilige Dokumente (https://wiki.deutsche-digitale-bibliothek.de/x/RgGuB).</svrl:text>
               <svrl:property id="id">
                  <xsl:value-of xmlns:sch="http://purl.oclc.org/dsdl/schematron" select="./@ID"/>
               </svrl:property>
            </svrl:failed-assert>
         </xsl:otherwise>
      </xsl:choose>
      <xsl:apply-templates select="*" mode="M117"/>
   </xsl:template>
   <xsl:template match="text()" priority="-1" mode="M117"/>
   <xsl:template match="@*|node()" priority="-2" mode="M117">
      <xsl:apply-templates select="*" mode="M117"/>
   </xsl:template>
   <!--PATTERN -->
   <!--RULE -->
   <xsl:template match="mets:mets/mets:structMap[@TYPE='LOGICAL']//mets:div[@DMDID]"
                 priority="1000"
                 mode="M118">
      <svrl:fired-rule xmlns:svrl="http://purl.oclc.org/dsdl/svrl"
                       context="mets:mets/mets:structMap[@TYPE='LOGICAL']//mets:div[@DMDID]"/>
      <!--ASSERT error-->
      <xsl:choose>
         <xsl:when test="sum( for $dmdid in tokenize(@DMDID, ' ') return if (key('dmdsec_ids', $dmdid)) then 0 else 1 ) = 0"/>
         <xsl:otherwise>
            <svrl:failed-assert xmlns:svrl="http://purl.oclc.org/dsdl/svrl"
                                test="sum( for $dmdid in tokenize(@DMDID, ' ') return if (key('dmdsec_ids', $dmdid)) then 0 else 1 ) = 0">
               <xsl:attribute name="id">structMapLogical_09</xsl:attribute>
               <xsl:attribute name="role">error</xsl:attribute>
               <xsl:attribute name="location">
                  <xsl:apply-templates select="." mode="schematron-select-full-path"/>
               </xsl:attribute>
               <svrl:text>Das Attribut <xsl:text/>DMDID<xsl:text/> des <xsl:text/>mets:div<xsl:text/>-Elements im Element <xsl:text/>mets:structMap[@TYPE='LOGICAL']<xsl:text/> muss den Wert des Attributs <xsl:text/>ID<xsl:text/> eines <xsl:text/>mets:dmdSec<xsl:text/>-Elements referenzieren.
Fehlt ein <xsl:text/>mets:dmdSec<xsl:text/> mit einem entsprechendem Wert im Attribut <xsl:text/>ID<xsl:text/>, wird das Attribut <xsl:text/>DMDID<xsl:text/> des <xsl:text/>mets:div<xsl:text/> bei der Transformation des Datensatz entfernt.Weitere Informationen zu diesem Attribut finden Sie im DDB-METS/MODS-Anwendungsprofil auf der Seite mets:structMap[@TYPE='LOGICAL'] (https://wiki.deutsche-digitale-bibliothek.de/x/o8IeB).</svrl:text>
               <svrl:property id="id">
                  <xsl:value-of xmlns:sch="http://purl.oclc.org/dsdl/schematron" select="./@ID"/>
               </svrl:property>
            </svrl:failed-assert>
         </xsl:otherwise>
      </xsl:choose>
      <xsl:apply-templates select="*" mode="M118"/>
   </xsl:template>
   <xsl:template match="text()" priority="-1" mode="M118"/>
   <xsl:template match="@*|node()" priority="-2" mode="M118">
      <xsl:apply-templates select="*" mode="M118"/>
   </xsl:template>
   <!--PATTERN -->
   <!--RULE -->
   <xsl:template match="mets:mets/mets:structMap[@TYPE='LOGICAL']//mets:div[mets:mptr]"
                 priority="1000"
                 mode="M119">
      <svrl:fired-rule xmlns:svrl="http://purl.oclc.org/dsdl/svrl"
                       context="mets:mets/mets:structMap[@TYPE='LOGICAL']//mets:div[mets:mptr]"/>
      <!--REPORT fatal-->
      <xsl:if test="./descendant::mets:div[mets:mptr]">
         <svrl:successful-report xmlns:svrl="http://purl.oclc.org/dsdl/svrl"
                                 test="./descendant::mets:div[mets:mptr]">
            <xsl:attribute name="id">structMapLogical_10</xsl:attribute>
            <xsl:attribute name="role">fatal</xsl:attribute>
            <xsl:attribute name="location">
               <xsl:apply-templates select="." mode="schematron-select-full-path"/>
            </xsl:attribute>
            <svrl:text>Im Element <xsl:text/>mets:structMap[@TYPE='LOGICAL']<xsl:text/> beschreibt das Element <xsl:text/>mets:div<xsl:text/>, das das Unterelement <xsl:text/>mets:mptr<xsl:text/> enthält, das Mehrteilige Dokument des im Datensatz beschriebenen Teils eines Mehrteiligen Dokuments. <xsl:text/>mets:mptr<xsl:text/> dient dabei zur Referenzierung des entsprechenden Ankersatzes. Alle Nachkommen dieses <xsl:text/>mets:div<xsl:text/> beschreiben den Teil des Mehrteiligen Dokuments bzw. Unselbständige Dokumente innerhalb desselben und dürfen daher keine <xsl:text/>mets:mptr<xsl:text/> enthalten.
Gibt es <xsl:text/>mets:div<xsl:text/>-Nachkommen eines <xsl:text/>mets:div<xsl:text/> mit <xsl:text/>mets:mptr<xsl:text/>, die <xsl:text/>mets:mptr<xsl:text/> enthalten, wird der Datensatz nicht in die DDB eingespielt.Weitere Informationen zu diesen Elementen und ihrem Kontext finden Sie im DDB-METS/MODS-Anwendungsprofil auf der Seite mets:structMap[@TYPE='LOGICAL'] (https://wiki.deutsche-digitale-bibliothek.de/x/o8IeB) und der Seite Aufbau für Teile Mehrteiliger Dokumente (https://wiki.deutsche-digitale-bibliothek.de/x/jwGuB).</svrl:text>
            <svrl:property id="id">
               <xsl:value-of xmlns:sch="http://purl.oclc.org/dsdl/schematron" select="./@ID"/>
            </svrl:property>
         </svrl:successful-report>
      </xsl:if>
      <xsl:apply-templates select="*" mode="M119"/>
   </xsl:template>
   <xsl:template match="text()" priority="-1" mode="M119"/>
   <xsl:template match="@*|node()" priority="-2" mode="M119">
      <xsl:apply-templates select="*" mode="M119"/>
   </xsl:template>
   <!--PATTERN -->
   <!--RULE -->
   <xsl:template match="mets:mets/mets:structMap[@TYPE='LOGICAL']//mets:div[tokenize(@DMDID, ' ') = $work_dmdid][@TYPE='periodical']"
                 priority="1000"
                 mode="M120">
      <svrl:fired-rule xmlns:svrl="http://purl.oclc.org/dsdl/svrl"
                       context="mets:mets/mets:structMap[@TYPE='LOGICAL']//mets:div[tokenize(@DMDID, ' ') = $work_dmdid][@TYPE='periodical']"/>
      <!--REPORT fatal-->
      <xsl:if test="./ancestor::mets:mets/mets:structLink/mets:smLink or ./ancestor::mets:mets/mets:fileSec/mets:fileGrp[@USE='DEFAULT']">
         <svrl:successful-report xmlns:svrl="http://purl.oclc.org/dsdl/svrl"
                                 test="./ancestor::mets:mets/mets:structLink/mets:smLink or ./ancestor::mets:mets/mets:fileSec/mets:fileGrp[@USE='DEFAULT']">
            <xsl:attribute name="id">structMapLogical_11</xsl:attribute>
            <xsl:attribute name="role">fatal</xsl:attribute>
            <xsl:attribute name="location">
               <xsl:apply-templates select="." mode="schematron-select-full-path"/>
            </xsl:attribute>
            <svrl:text>Der Datensatz enthält das Element <xsl:text/>mets:fileSec<xsl:text/> mit dem Unterelement <xsl:text/>mets:fileGrp[@USE='DEFAULT']<xsl:text/> bzw. das Element <xsl:text/>mets:structLink<xsl:text/> mit mindestens einem Unterelement <xsl:text/>mets:smLink<xsl:text/> und beschreibt damit den Teil eines Mehrteiligen Dokuments.
Das primäre <xsl:text/>mets:div<xsl:text/>-Element enthält im Attribut <xsl:text/>TYPE<xsl:text/> jedoch den Wert <xsl:text/>periodical<xsl:text/>. Dieser Wert darf nur für das primäre <xsl:text/>mets:div<xsl:text/> in Ankersätzen, die das Mehrteilige Dokument beschreiben, verwendet werden. Der Datensatz wird daher nicht in die DDB eingespielt.
Bitte verwenden Sie im Attribut <xsl:text/>TYPE<xsl:text/> des primären <xsl:text/>mets:div<xsl:text/> in Teilen von Mehrteiligen Dokumenten des Typs <xsl:text/>periodical<xsl:text/> die Werte <xsl:text/>volume<xsl:text/> oder <xsl:text/>issue<xsl:text/>.Weitere Informationen zu diesem Kontext finden Sie im DDB-METS/MODS-Anwendungsprofil im Bereich METS/MODS für Mehrteilige Dokumente (https://wiki.deutsche-digitale-bibliothek.de/x/RgGuB).</svrl:text>
            <svrl:property id="id">
               <xsl:value-of xmlns:sch="http://purl.oclc.org/dsdl/schematron" select="./@ID"/>
            </svrl:property>
         </svrl:successful-report>
      </xsl:if>
      <xsl:apply-templates select="*" mode="M120"/>
   </xsl:template>
   <xsl:template match="text()" priority="-1" mode="M120"/>
   <xsl:template match="@*|node()" priority="-2" mode="M120">
      <xsl:apply-templates select="*" mode="M120"/>
   </xsl:template>
   <!--PATTERN -->
   <!--RULE -->
   <xsl:template match="mets:mets/mets:structMap[@TYPE='LOGICAL']//mets:div[tokenize(@DMDID, ' ') = $work_dmdid][@TYPE='multivolume_work']"
                 priority="1000"
                 mode="M121">
      <svrl:fired-rule xmlns:svrl="http://purl.oclc.org/dsdl/svrl"
                       context="mets:mets/mets:structMap[@TYPE='LOGICAL']//mets:div[tokenize(@DMDID, ' ') = $work_dmdid][@TYPE='multivolume_work']"/>
      <!--REPORT fatal-->
      <xsl:if test="./ancestor::mets:mets/mets:structLink/mets:smLink or ./ancestor::mets:mets/mets:fileSec/mets:fileGrp[@USE='DEFAULT']">
         <svrl:successful-report xmlns:svrl="http://purl.oclc.org/dsdl/svrl"
                                 test="./ancestor::mets:mets/mets:structLink/mets:smLink or ./ancestor::mets:mets/mets:fileSec/mets:fileGrp[@USE='DEFAULT']">
            <xsl:attribute name="id">structMapLogical_16</xsl:attribute>
            <xsl:attribute name="role">fatal</xsl:attribute>
            <xsl:attribute name="location">
               <xsl:apply-templates select="." mode="schematron-select-full-path"/>
            </xsl:attribute>
            <svrl:text>Der Datensatz enthält das Element <xsl:text/>mets:fileSec<xsl:text/> mit dem Unterelement <xsl:text/>mets:fileGrp[@USE='DEFAULT']<xsl:text/> bzw. das Element <xsl:text/>mets:structLink<xsl:text/> mit mindestens einem Unterelement <xsl:text/>mets:smLink<xsl:text/> und beschreibt damit den Teil eines Mehrteiligen Dokuments.
Das primäre <xsl:text/>mets:div<xsl:text/>-Element enthält im Attribut <xsl:text/>TYPE<xsl:text/> jedoch den Wert <xsl:text/>multivolume_work<xsl:text/>. Dieser Wert darf nur für das primäre <xsl:text/>mets:div<xsl:text/> in Ankersätzen, die das Mehrteilige Dokument beschreiben, verwendet werden. Der Datensatz wird daher nicht in die DDB eingespielt.
Bitte verwenden Sie im Attribut <xsl:text/>TYPE<xsl:text/> des primären <xsl:text/>mets:div<xsl:text/> in Teilen von Mehrteiligen Dokumenten des Typs <xsl:text/>multivolume_work<xsl:text/> nur den Wert <xsl:text/>volume<xsl:text/>.Weitere Informationen zu diesem Kontext finden Sie im DDB-METS/MODS-Anwendungsprofil im Bereich METS/MODS für Mehrteilige Dokumente (https://wiki.deutsche-digitale-bibliothek.de/x/RgGuB).</svrl:text>
            <svrl:property id="id">
               <xsl:value-of xmlns:sch="http://purl.oclc.org/dsdl/schematron" select="./@ID"/>
            </svrl:property>
         </svrl:successful-report>
      </xsl:if>
      <xsl:apply-templates select="*" mode="M121"/>
   </xsl:template>
   <xsl:template match="text()" priority="-1" mode="M121"/>
   <xsl:template match="@*|node()" priority="-2" mode="M121">
      <xsl:apply-templates select="*" mode="M121"/>
   </xsl:template>
   <!--PATTERN -->
   <!--RULE -->
   <xsl:template match="mets:mets/mets:structMap[@TYPE='LOGICAL']//mets:mptr"
                 priority="1000"
                 mode="M122">
      <svrl:fired-rule xmlns:svrl="http://purl.oclc.org/dsdl/svrl"
                       context="mets:mets/mets:structMap[@TYPE='LOGICAL']//mets:mptr"/>
      <!--ASSERT fatal-->
      <xsl:choose>
         <xsl:when test="matches(./@xlink:href, '^(http|https)://[a-zA-Z0-9\-\.]+\.[a-zA-Z][a-zA-Z]+(:[a-zA-Z0-9]*)?/?([a-zA-Z0-9\-\._\?,/\\\+&amp;%\$#=~:])*$')"/>
         <xsl:otherwise>
            <svrl:failed-assert xmlns:svrl="http://purl.oclc.org/dsdl/svrl"
                                test="matches(./@xlink:href, '^(http|https)://[a-zA-Z0-9\-\.]+\.[a-zA-Z][a-zA-Z]+(:[a-zA-Z0-9]*)?/?([a-zA-Z0-9\-\._\?,/\\\+&amp;%\$#=~:])*$')">
               <xsl:attribute name="id">structMapLogical_17</xsl:attribute>
               <xsl:attribute name="role">fatal</xsl:attribute>
               <xsl:attribute name="location">
                  <xsl:apply-templates select="." mode="schematron-select-full-path"/>
               </xsl:attribute>
               <svrl:text>Das Attribut <xsl:text/>xlink:href<xsl:text/> des <xsl:text/>mets:mptr<xsl:text/>-Elements im Element <xsl:text/>mets:structMap[@TYPE='LOGICAL']<xsl:text/> muss einen validen http-URL enthalten.
Ist dies nicht der Fall, wird der Datensatz nicht in die DDB eingespielt.Weitere Informationen zu diesem Attribut und seinem Kontext finden Sie im DDB-METS/MODS-Anwendungsprofil auf der Seite mets:structMap[@TYPE='LOGICAL'] (https://wiki.deutsche-digitale-bibliothek.de/x/o8IeB) und der Seite Aufbau für Teile Mehrteiliger Dokumente (https://wiki.deutsche-digitale-bibliothek.de/x/jwGuB).</svrl:text>
               <svrl:property id="id">
                  <xsl:value-of xmlns:sch="http://purl.oclc.org/dsdl/schematron" select="./@ID"/>
               </svrl:property>
            </svrl:failed-assert>
         </xsl:otherwise>
      </xsl:choose>
      <xsl:apply-templates select="*" mode="M122"/>
   </xsl:template>
   <xsl:template match="text()" priority="-1" mode="M122"/>
   <xsl:template match="@*|node()" priority="-2" mode="M122">
      <xsl:apply-templates select="*" mode="M122"/>
   </xsl:template>
   <!--PATTERN -->
   <!--RULE -->
   <xsl:template match="mets:mets/mets:structMap[@TYPE='LOGICAL']//mets:div[@DMDID]"
                 priority="1000"
                 mode="M123">
      <svrl:fired-rule xmlns:svrl="http://purl.oclc.org/dsdl/svrl"
                       context="mets:mets/mets:structMap[@TYPE='LOGICAL']//mets:div[@DMDID]"/>
      <!--REPORT fatal-->
      <xsl:if test="sum( for $dmdid in tokenize(@DMDID, ' ') return count(key('structMap_LOGICAL_dmdids', $dmdid)) ) &gt; count(tokenize(@DMDID, ' '))">
         <svrl:successful-report xmlns:svrl="http://purl.oclc.org/dsdl/svrl"
                                 test="sum( for $dmdid in tokenize(@DMDID, ' ') return count(key('structMap_LOGICAL_dmdids', $dmdid)) ) &gt; count(tokenize(@DMDID, ' '))">
            <xsl:attribute name="id">structMapLogical_20</xsl:attribute>
            <xsl:attribute name="role">fatal</xsl:attribute>
            <xsl:attribute name="location">
               <xsl:apply-templates select="." mode="schematron-select-full-path"/>
            </xsl:attribute>
            <svrl:text>Der Datensatz enthält im Element <xsl:text/>mets:structMap[@TYPE='LOGICAL']<xsl:text/>
               <xsl:text/>mets:div<xsl:text/>-Elemente, die über ihr Attribut <xsl:text/>DMDID<xsl:text/> dasselbe <xsl:text/>mets:dmdSec<xsl:text/>-Element über dessen Attribut <xsl:text/>ID<xsl:text/> referenzieren.
Ein <xsl:text/>mets:dmdSec<xsl:text/> darf nur von genau einem <xsl:text/>mets:div<xsl:text/> in <xsl:text/>mets:structMap[@TYPE='LOGICAL']<xsl:text/> referenziert werden, daher wird der Datensatz nicht in die DDB eingespielt.Weitere Informationen zu diesem Element und seinem Kontext finden Sie im DDB-METS/MODS-Anwendungsprofil auf der Seite mets:structMap[@TYPE='LOGICAL'] (https://wiki.deutsche-digitale-bibliothek.de/x/o8IeB) und im Bereich Aufbau einer METS/MODS-Datei für die DDB (https://wiki.deutsche-digitale-bibliothek.de/x/VcIeB).</svrl:text>
            <svrl:property id="id">
               <xsl:value-of xmlns:sch="http://purl.oclc.org/dsdl/schematron" select="./@ID"/>
            </svrl:property>
         </svrl:successful-report>
      </xsl:if>
      <xsl:apply-templates select="*" mode="M123"/>
   </xsl:template>
   <xsl:template match="text()" priority="-1" mode="M123"/>
   <xsl:template match="@*|node()" priority="-2" mode="M123">
      <xsl:apply-templates select="*" mode="M123"/>
   </xsl:template>
   <!--PATTERN -->
   <!--RULE -->
   <xsl:template match="mets:mets/mets:structMap[@TYPE='LOGICAL']//mets:div[tokenize(@DMDID, ' ') = $work_dmdid]//mets:div[@DMDID]"
                 priority="1000"
                 mode="M124">
      <svrl:fired-rule xmlns:svrl="http://purl.oclc.org/dsdl/svrl"
                       context="mets:mets/mets:structMap[@TYPE='LOGICAL']//mets:div[tokenize(@DMDID, ' ') = $work_dmdid]//mets:div[@DMDID]"/>
      <xsl:variable name="logid" select="@ID"/>
      <xsl:variable name="physical_div_id"
                    select="./ancestor::mets:mets/mets:structLink/mets:smLink[@xlink:from = $logid][1]/@xlink:to"/>
      <xsl:variable name="fileids"
                    select="key('structMap_PHYSICAL_ids', $physical_div_id)/descendant-or-self::mets:div[mets:fptr][parent::mets:div][1]/mets:fptr/@FILEID"/>
      <!--ASSERT error-->
      <xsl:choose>
         <xsl:when test="key('fileGrp_DEFAULT_file_ids', $fileids) or $is_anchor"/>
         <xsl:otherwise>
            <svrl:failed-assert xmlns:svrl="http://purl.oclc.org/dsdl/svrl"
                                test="key('fileGrp_DEFAULT_file_ids', $fileids) or $is_anchor">
               <xsl:attribute name="id">structMapLogical_22</xsl:attribute>
               <xsl:attribute name="role">error</xsl:attribute>
               <xsl:attribute name="location">
                  <xsl:apply-templates select="." mode="schematron-select-full-path"/>
               </xsl:attribute>
               <svrl:text>Der Datensatz beschreibt ein Einteiliges Dokument bzw. einen Teil eines Mehrteiligen Dokuments und daher muss das Element <xsl:text/>mets:div<xsl:text/> im Element <xsl:text/>mets:structMap[@TYPE='LOGICAL']<xsl:text/> über die Elemente <xsl:text/>mets:structLink<xsl:text/> und <xsl:text/>mets:structMap[@TYPE='PHYSICAL']<xsl:text/> mindestens ein <xsl:text/>mets:file<xsl:text/>-Element im Element <xsl:text/>mets:fileGrp[@USE='DEFAULT']<xsl:text/> referenzieren.
Fehlt diese Referenz, kann <xsl:text/>mets:div<xsl:text/> für die Anzeige in der DDB kein Bild zugewiesen werden und es wird mit allen anderen Referenzen bei der Transformation des Datensatzes entfernt.Weitere Informationen zu diesem Kontext finden Sie im DDB-METS/MODS-Anwendungsprofil im Bereich Aufbau einer METS/MODS-Datei für die DDB (https://wiki.deutsche-digitale-bibliothek.de/x/VcIeB).</svrl:text>
               <svrl:property id="id">
                  <xsl:value-of xmlns:sch="http://purl.oclc.org/dsdl/schematron" select="./@ID"/>
               </svrl:property>
            </svrl:failed-assert>
         </xsl:otherwise>
      </xsl:choose>
      <xsl:apply-templates select="*" mode="M124"/>
   </xsl:template>
   <xsl:template match="text()" priority="-1" mode="M124"/>
   <xsl:template match="@*|node()" priority="-2" mode="M124">
      <xsl:apply-templates select="*" mode="M124"/>
   </xsl:template>
   <!--PATTERN -->
   <!--RULE -->
   <xsl:template match="mets:mets/mets:structMap[@TYPE='LOGICAL']//mets:div[@DMDID = $work_dmdid]"
                 priority="1000"
                 mode="M125">
      <svrl:fired-rule xmlns:svrl="http://purl.oclc.org/dsdl/svrl"
                       context="mets:mets/mets:structMap[@TYPE='LOGICAL']//mets:div[@DMDID = $work_dmdid]"/>
      <!--REPORT fatal-->
      <xsl:if test="$is_anchor and ./parent::mets:div">
         <svrl:successful-report xmlns:svrl="http://purl.oclc.org/dsdl/svrl"
                                 test="$is_anchor and ./parent::mets:div">
            <xsl:attribute name="id">structMapLogical_23</xsl:attribute>
            <xsl:attribute name="role">fatal</xsl:attribute>
            <xsl:attribute name="location">
               <xsl:apply-templates select="." mode="schematron-select-full-path"/>
            </xsl:attribute>
            <svrl:text>Der Datensatz besitzt kein <xsl:text/>mets:fileSec<xsl:text/>-Element mit dem Unterelement <xsl:text/>mets:fileGrp[@USE='DEFAULT']<xsl:text/> bzw. das Element <xsl:text/>mets:structLink<xsl:text/> und beschreibt daher einen Ankersatz. Dadurch darf das primäre <xsl:text/>mets:div<xsl:text/>-Element im Element <xsl:text/>mets:structMap[@TYPE='LOGICAL']<xsl:text/> kein <xsl:text/>mets:div<xsl:text/>-Elternelement besitzen und muss die oberste logische Ebene darstellen.
Ist dies nicht der Fall, wird der Datensatz nicht in die DDB eingespielt.Weitere Informationen zu diesem Element und seinem Kontext finden Sie im DDB-METS/MODS-Anwendungsprofil auf den Seiten mets:structMap[@TYPE='LOGICAL'] (https://wiki.deutsche-digitale-bibliothek.de/x/o8IeB) und Aufbau eines Ankersatzes (https://wiki.deutsche-digitale-bibliothek.de/x/SgGuB).</svrl:text>
            <svrl:property id="id">
               <xsl:value-of xmlns:sch="http://purl.oclc.org/dsdl/schematron" select="./@ID"/>
            </svrl:property>
         </svrl:successful-report>
      </xsl:if>
      <xsl:apply-templates select="*" mode="M125"/>
   </xsl:template>
   <xsl:template match="text()" priority="-1" mode="M125"/>
   <xsl:template match="@*|node()" priority="-2" mode="M125">
      <xsl:apply-templates select="*" mode="M125"/>
   </xsl:template>
   <!--PATTERN -->
   <!--RULE -->
   <xsl:template match="mets:mets/mets:structMap[@TYPE='LOGICAL']/mets:div"
                 priority="1000"
                 mode="M126">
      <svrl:fired-rule xmlns:svrl="http://purl.oclc.org/dsdl/svrl"
                       context="mets:mets/mets:structMap[@TYPE='LOGICAL']/mets:div"/>
      <!--REPORT warn-->
      <xsl:if test="$is_anchor and not(@TYPE = ('multivolume_work', 'periodical', 'newspaper'))">
         <svrl:successful-report xmlns:svrl="http://purl.oclc.org/dsdl/svrl"
                                 test="$is_anchor and not(@TYPE = ('multivolume_work', 'periodical', 'newspaper'))">
            <xsl:attribute name="id">structMapLogical_24</xsl:attribute>
            <xsl:attribute name="role">warn</xsl:attribute>
            <xsl:attribute name="location">
               <xsl:apply-templates select="." mode="schematron-select-full-path"/>
            </xsl:attribute>
            <svrl:text>Der Datensatz beschreibt ein Mehrteiliges Dokument (Ankersatz) und muss daher im Attribut <xsl:text/>TYPE<xsl:text/> des primären <xsl:text/>mets:div<xsl:text/>-Element im Element <xsl:text/>mets:structMap[@TYPE='LOGICAL']<xsl:text/> einen der folgenden Werte im Attribut <xsl:text/>TYPE<xsl:text/> enthalten:
 * <xsl:text/>multivolume_work<xsl:text/>
 * <xsl:text/>newspaper<xsl:text/>
 * <xsl:text/>periodical<xsl:text/>
Die Verwendung ungültiger Attributwerte verhindert das Einspielen des Datensatzes in die DDB zurzeit noch nicht, eine Verschärfung dieser Anforderungen ist aber perspektivisch geplant. Wir bitten Sie daher, den Sachverhalt zu prüfen und die nötigen Korrekturen bis zur nächsten Datenlieferung vorzunehmen.Weitere Informationen zu diesem Attribut und seinem Kontext finden Sie im DDB-METS/MODS-Anwendungsprofil auf den Seiten mets:structMap[@TYPE='LOGICAL'] (https://wiki.deutsche-digitale-bibliothek.de/x/o8IeB) und Hierarchietypen in METS/MODS (https://wiki.deutsche-digitale-bibliothek.de/x/KAGuB).</svrl:text>
            <svrl:property id="id">
               <xsl:value-of xmlns:sch="http://purl.oclc.org/dsdl/schematron" select="./@ID"/>
            </svrl:property>
         </svrl:successful-report>
      </xsl:if>
      <xsl:apply-templates select="*" mode="M126"/>
   </xsl:template>
   <xsl:template match="text()" priority="-1" mode="M126"/>
   <xsl:template match="@*|node()" priority="-2" mode="M126">
      <xsl:apply-templates select="*" mode="M126"/>
   </xsl:template>
   <!--PATTERN -->
   <!--RULE -->
   <xsl:template match="mets:mets/mets:structMap[@TYPE='LOGICAL']/mets:div/mets:div[tokenize(@DMDID, ' ') = $work_dmdid]"
                 priority="1000"
                 mode="M127">
      <svrl:fired-rule xmlns:svrl="http://purl.oclc.org/dsdl/svrl"
                       context="mets:mets/mets:structMap[@TYPE='LOGICAL']/mets:div/mets:div[tokenize(@DMDID, ' ') = $work_dmdid]"/>
      <!--REPORT warn-->
      <xsl:if test="not($is_anchor) and not(@TYPE = ('volume', 'additional', 'illustration', 'map', 'folder', 'musical_notation', 'part'))">
         <svrl:successful-report xmlns:svrl="http://purl.oclc.org/dsdl/svrl"
                                 test="not($is_anchor) and not(@TYPE = ('volume', 'additional', 'illustration', 'map', 'folder', 'musical_notation', 'part'))">
            <xsl:attribute name="id">structMapLogical_25</xsl:attribute>
            <xsl:attribute name="role">warn</xsl:attribute>
            <xsl:attribute name="location">
               <xsl:apply-templates select="." mode="schematron-select-full-path"/>
            </xsl:attribute>
            <svrl:text>Der Datensatz beschreibt einen Teil eines mehrteiligen Dokuments und muss daher im Attribut <xsl:text/>TYPE<xsl:text/> des primären <xsl:text/>mets:div<xsl:text/>-Element im Element <xsl:text/>mets:structMap[@TYPE='LOGICAL']<xsl:text/> einen der folgenden Werte im Attribut <xsl:text/>TYPE<xsl:text/> enthalten:
 * <xsl:text/>additional<xsl:text/>
 * <xsl:text/>folder<xsl:text/>
 * <xsl:text/>illustration<xsl:text/>
 * <xsl:text/>map<xsl:text/>
 * <xsl:text/>musical_notation<xsl:text/>
 * <xsl:text/>part<xsl:text/>
 * <xsl:text/>volume<xsl:text/>
Die Verwendung ungültiger Attributwerte verhindert das Einspielen des Datensatzes in die DDB zurzeit noch nicht, eine Verschärfung dieser Anforderungen ist aber perspektivisch geplant. Wir bitten Sie daher, den Sachverhalt zu prüfen und die nötigen Korrekturen bis zur nächsten Datenlieferung vorzunehmen.Weitere Informationen zu diesem Attribut und seinem Kontext finden Sie im DDB-METS/MODS-Anwendungsprofil auf den Seiten mets:structMap[@TYPE='LOGICAL'] (https://wiki.deutsche-digitale-bibliothek.de/x/o8IeB) und Hierarchietypen in METS/MODS (https://wiki.deutsche-digitale-bibliothek.de/x/KAGuB).</svrl:text>
            <svrl:property id="id">
               <xsl:value-of xmlns:sch="http://purl.oclc.org/dsdl/schematron" select="./@ID"/>
            </svrl:property>
         </svrl:successful-report>
      </xsl:if>
      <xsl:apply-templates select="*" mode="M127"/>
   </xsl:template>
   <xsl:template match="text()" priority="-1" mode="M127"/>
   <xsl:template match="@*|node()" priority="-2" mode="M127">
      <xsl:apply-templates select="*" mode="M127"/>
   </xsl:template>
   <!--PATTERN -->
   <!--RULE -->
   <xsl:template match="mets:mets/mets:structMap[@TYPE='LOGICAL']/mets:div[tokenize(@DMDID, ' ') = $work_dmdid]"
                 priority="1000"
                 mode="M128">
      <svrl:fired-rule xmlns:svrl="http://purl.oclc.org/dsdl/svrl"
                       context="mets:mets/mets:structMap[@TYPE='LOGICAL']/mets:div[tokenize(@DMDID, ' ') = $work_dmdid]"/>
      <!--REPORT warn-->
      <xsl:if test="not($is_anchor) and not(@TYPE = ( 'letter', 'fascicle', 'fragment', 'manuscript', 'illustration', 'map', 'bundle', 'folder', 'monograph', 'musical_notation', 'privilege', 'text', 'verse' ))">
         <svrl:successful-report xmlns:svrl="http://purl.oclc.org/dsdl/svrl"
                                 test="not($is_anchor) and not(@TYPE = ( 'letter', 'fascicle', 'fragment', 'manuscript', 'illustration', 'map', 'bundle', 'folder', 'monograph', 'musical_notation', 'privilege', 'text', 'verse' ))">
            <xsl:attribute name="id">structMapLogical_26</xsl:attribute>
            <xsl:attribute name="role">warn</xsl:attribute>
            <xsl:attribute name="location">
               <xsl:apply-templates select="." mode="schematron-select-full-path"/>
            </xsl:attribute>
            <svrl:text>Der Datensatz beschreibt ein einteiliges Dokument und muss daher im Attribut <xsl:text/>TYPE<xsl:text/> des primären <xsl:text/>mets:div<xsl:text/>-Element im Element <xsl:text/>mets:structMap[@TYPE='LOGICAL']<xsl:text/> einen entsprechenden Wert aus der Tabelle der Hierarchietypen in METS/MODS (Spalte B) (https://wiki.deutsche-digitale-bibliothek.de/x/KAGuB) enthalten.
Die Verwendung ungültiger Attributwerte verhindert das Einspielen des Datensatzes in die DDB zurzeit noch nicht, eine Verschärfung dieser Anforderungen ist aber perspektivisch geplant. Wir bitten Sie daher, den Sachverhalt zu prüfen und die nötigen Korrekturen bis zur nächsten Datenlieferung vorzunehmen.Weitere Informationen zu diesem Attribut und seinem Kontext finden Sie im DDB-METS/MODS-Anwendungsprofil auf den Seiten mets:structMap[@TYPE='LOGICAL'] (https://wiki.deutsche-digitale-bibliothek.de/x/o8IeB) und Hierarchietypen in METS/MODS (https://wiki.deutsche-digitale-bibliothek.de/x/KAGuB).</svrl:text>
            <svrl:property id="id">
               <xsl:value-of xmlns:sch="http://purl.oclc.org/dsdl/schematron" select="./@ID"/>
            </svrl:property>
         </svrl:successful-report>
      </xsl:if>
      <xsl:apply-templates select="*" mode="M128"/>
   </xsl:template>
   <xsl:template match="text()" priority="-1" mode="M128"/>
   <xsl:template match="@*|node()" priority="-2" mode="M128">
      <xsl:apply-templates select="*" mode="M128"/>
   </xsl:template>
   <!--PATTERN -->
   <!--RULE -->
   <xsl:template match="mets:mets/mets:structMap[@TYPE='LOGICAL']//mets:div[tokenize(@DMDID, ' ') = $work_dmdid]//mets:div"
                 priority="1000"
                 mode="M129">
      <svrl:fired-rule xmlns:svrl="http://purl.oclc.org/dsdl/svrl"
                       context="mets:mets/mets:structMap[@TYPE='LOGICAL']//mets:div[tokenize(@DMDID, ' ') = $work_dmdid]//mets:div"/>
      <!--REPORT warn-->
      <xsl:if test="not($is_anchor) and not(@TYPE = ( 'additional', 'address', 'annotation', 'appendix', 'article', 'binding', 'bookplate', 'chapter', 'contained_work', 'dedication', 'entry', 'illustration', 'index', 'issue', 'letter', 'map', 'musical_notation', 'part', 'preface', 'printers_mark', 'privilege', 'review', 'section', 'stamp', 'contents', 'text', 'title_page', 'verse' ))">
         <svrl:successful-report xmlns:svrl="http://purl.oclc.org/dsdl/svrl"
                                 test="not($is_anchor) and not(@TYPE = ( 'additional', 'address', 'annotation', 'appendix', 'article', 'binding', 'bookplate', 'chapter', 'contained_work', 'dedication', 'entry', 'illustration', 'index', 'issue', 'letter', 'map', 'musical_notation', 'part', 'preface', 'printers_mark', 'privilege', 'review', 'section', 'stamp', 'contents', 'text', 'title_page', 'verse' ))">
            <xsl:attribute name="id">structMapLogical_27</xsl:attribute>
            <xsl:attribute name="role">warn</xsl:attribute>
            <xsl:attribute name="location">
               <xsl:apply-templates select="." mode="schematron-select-full-path"/>
            </xsl:attribute>
            <svrl:text>Ein <xsl:text/>mets:div<xsl:text/>-Element im Element <xsl:text/>mets:structMap[@TYPE='LOGICAL']<xsl:text/>, das ein unselbständiges Dokument beschreibt, muss im Attribut <xsl:text/>TYPE<xsl:text/> entsprechenden Wert aus der Tabelle der Hierarchietypen in METS/MODS (Spalte B) (https://wiki.deutsche-digitale-bibliothek.de/x/KAGuB) enthalten.
Die Verwendung ungültiger Attributwerte verhindert das Einspielen des Datensatzes in die DDB zurzeit noch nicht, eine Verschärfung dieser Anforderungen ist aber perspektivisch geplant. Wir bitten Sie daher, den Sachverhalt zu prüfen und die nötigen Korrekturen bis zur nächsten Datenlieferung vorzunehmen.Weitere Informationen zu diesem Attribut und seinem Kontext finden Sie im DDB-METS/MODS-Anwendungsprofil auf den Seiten mets:structMap[@TYPE='LOGICAL'] (https://wiki.deutsche-digitale-bibliothek.de/x/o8IeB) und Hierarchietypen in METS/MODS (https://wiki.deutsche-digitale-bibliothek.de/x/KAGuB).</svrl:text>
            <svrl:property id="id">
               <xsl:value-of xmlns:sch="http://purl.oclc.org/dsdl/schematron" select="./@ID"/>
            </svrl:property>
         </svrl:successful-report>
      </xsl:if>
      <xsl:apply-templates select="*" mode="M129"/>
   </xsl:template>
   <xsl:template match="text()" priority="-1" mode="M129"/>
   <xsl:template match="@*|node()" priority="-2" mode="M129">
      <xsl:apply-templates select="*" mode="M129"/>
   </xsl:template>
   <!--PATTERN -->
   <!--RULE -->
   <xsl:template match="mets:mets/mets:structMap[@TYPE='LOGICAL']//mets:div"
                 priority="1000"
                 mode="M130">
      <svrl:fired-rule xmlns:svrl="http://purl.oclc.org/dsdl/svrl"
                       context="mets:mets/mets:structMap[@TYPE='LOGICAL']//mets:div"/>
      <!--REPORT fatal-->
      <xsl:if test="mets:mptr[2]">
         <svrl:successful-report xmlns:svrl="http://purl.oclc.org/dsdl/svrl" test="mets:mptr[2]">
            <xsl:attribute name="id">structMapLogical_28</xsl:attribute>
            <xsl:attribute name="role">fatal</xsl:attribute>
            <xsl:attribute name="location">
               <xsl:apply-templates select="." mode="schematron-select-full-path"/>
            </xsl:attribute>
            <svrl:text>Das Element <xsl:text/>mets:mptr<xsl:text/> im Element <xsl:text/>mets:div<xsl:text/> innerhalb des Elements <xsl:text/>mets:structMap[@TYPE='LOGICAL']<xsl:text/> darf nicht wiederholt werden.
Enthält <xsl:text/>mets:div<xsl:text/> mehr als ein <xsl:text/>mets:mptr<xsl:text/> wird der Datensatz nicht in die DDB eingespielt.Weitere Informationen zu diesem Attribut finden Sie im DDB-METS/MODS-Anwendungsprofil auf der Seite mets:structMap[@TYPE='LOGICAL'] (https://wiki.deutsche-digitale-bibliothek.de/x/o8IeB).</svrl:text>
            <svrl:property id="id">
               <xsl:value-of xmlns:sch="http://purl.oclc.org/dsdl/schematron" select="./@ID"/>
            </svrl:property>
         </svrl:successful-report>
      </xsl:if>
      <xsl:apply-templates select="*" mode="M130"/>
   </xsl:template>
   <xsl:template match="text()" priority="-1" mode="M130"/>
   <xsl:template match="@*|node()" priority="-2" mode="M130">
      <xsl:apply-templates select="*" mode="M130"/>
   </xsl:template>
   <!--PATTERN -->
   <!--RULE -->
   <xsl:template match="mets:mets" priority="1002" mode="M131">
      <svrl:fired-rule xmlns:svrl="http://purl.oclc.org/dsdl/svrl" context="mets:mets"/>
      <!--ASSERT fatal-->
      <xsl:choose>
         <xsl:when test="$is_anchor or mets:structMap[@TYPE='PHYSICAL']"/>
         <xsl:otherwise>
            <svrl:failed-assert xmlns:svrl="http://purl.oclc.org/dsdl/svrl"
                                test="$is_anchor or mets:structMap[@TYPE='PHYSICAL']">
               <xsl:attribute name="id">structMapPhysical_01</xsl:attribute>
               <xsl:attribute name="role">fatal</xsl:attribute>
               <xsl:attribute name="location">
                  <xsl:apply-templates select="." mode="schematron-select-full-path"/>
               </xsl:attribute>
               <svrl:text>Der Datensatz beschreibt ein Einteiliges Dokument bzw. einen Teil eines Mehrteiligen Dokuments und muss daher das Element <xsl:text/>mets:structMap<xsl:text/> mit dem Attribut <xsl:text/>TYPE<xsl:text/> mit dem Wert <xsl:text/>PHYSICAL<xsl:text/> enthalten.
Fehlt ein entsprechendes <xsl:text/>mets:structMap<xsl:text/>, wird der Datensatz nicht in die DDB eingespielt.Weitere Informationen zu diesem Element und seinem Kontext finden Sie im DDB-METS/MODS-Anwendungsprofil auf der Seite mets:structMap[@TYPE='PHYSICAL'] (https://wiki.deutsche-digitale-bibliothek.de/x/i8IeB) und im Bereich Aufbau einer METS/MODS-Datei für die DDB (https://wiki.deutsche-digitale-bibliothek.de/x/VcIeB).</svrl:text>
            </svrl:failed-assert>
         </xsl:otherwise>
      </xsl:choose>
      <xsl:apply-templates select="*" mode="M131"/>
   </xsl:template>
   <!--RULE -->
   <xsl:template match="mets:mets/mets:structMap[@TYPE='PHYSICAL'][not(mets:div[@TYPE='physSequence'])]"
                 priority="1001"
                 mode="M131">
      <svrl:fired-rule xmlns:svrl="http://purl.oclc.org/dsdl/svrl"
                       context="mets:mets/mets:structMap[@TYPE='PHYSICAL'][not(mets:div[@TYPE='physSequence'])]"/>
      <!--ASSERT fatal-->
      <xsl:choose>
         <xsl:when test="mets:div[@TYPE='physSequence']"/>
         <xsl:otherwise>
            <svrl:failed-assert xmlns:svrl="http://purl.oclc.org/dsdl/svrl"
                                test="mets:div[@TYPE='physSequence']">
               <xsl:attribute name="id">structMapPhysical_02</xsl:attribute>
               <xsl:attribute name="role">fatal</xsl:attribute>
               <xsl:attribute name="location">
                  <xsl:apply-templates select="." mode="schematron-select-full-path"/>
               </xsl:attribute>
               <svrl:text>Das Element <xsl:text/>mets:structMap<xsl:text/> mit dem Attribut <xsl:text/>TYPE<xsl:text/> mit dem Wert <xsl:text/>PHYSICAL<xsl:text/> muss das Unterelement <xsl:text/>mets:div<xsl:text/> mit dem Attribut <xsl:text/>TYPE<xsl:text/> den Wert <xsl:text/>physSequence<xsl:text/> enthalten.
Ist dies nicht der Fall, wird der Datensatz nicht in die DDB eingespielt.Weitere Informationen zu diesem Element und seinem Kontext finden Sie im DDB-METS/MODS-Anwendungsprofil auf der Seite mets:structMap[@TYPE='PHYSICAL'] (https://wiki.deutsche-digitale-bibliothek.de/x/i8IeB) und im Bereich Aufbau einer METS/MODS-Datei für die DDB (https://wiki.deutsche-digitale-bibliothek.de/x/VcIeB).</svrl:text>
            </svrl:failed-assert>
         </xsl:otherwise>
      </xsl:choose>
      <xsl:apply-templates select="*" mode="M131"/>
   </xsl:template>
   <!--RULE -->
   <xsl:template match="mets:mets/mets:structMap[@TYPE='PHYSICAL']"
                 priority="1000"
                 mode="M131">
      <svrl:fired-rule xmlns:svrl="http://purl.oclc.org/dsdl/svrl"
                       context="mets:mets/mets:structMap[@TYPE='PHYSICAL']"/>
      <!--ASSERT fatal-->
      <xsl:choose>
         <xsl:when test="mets:div[@TYPE='physSequence']/mets:div[@TYPE='page']"/>
         <xsl:otherwise>
            <svrl:failed-assert xmlns:svrl="http://purl.oclc.org/dsdl/svrl"
                                test="mets:div[@TYPE='physSequence']/mets:div[@TYPE='page']">
               <xsl:attribute name="id">structMapPhysical_03</xsl:attribute>
               <xsl:attribute name="role">fatal</xsl:attribute>
               <xsl:attribute name="location">
                  <xsl:apply-templates select="." mode="schematron-select-full-path"/>
               </xsl:attribute>
               <svrl:text>Das Element <xsl:text/>mets:div<xsl:text/> mit dem Attribut <xsl:text/>TYPE<xsl:text/> dem Wert <xsl:text/>physSequence<xsl:text/> im Element <xsl:text/>mets:structMap[@TYPE='PHYSICAL']<xsl:text/> muss mindestens ein <xsl:text/>mets:div<xsl:text/> mit dem Attribut <xsl:text/>TYPE<xsl:text/> mit dem Wert <xsl:text/>page<xsl:text/> enthalten.
Ist dies nicht der Fall, wird der Datensatz nicht in die DDB eingespielt.Weitere Informationen zu diesem Element und seinem Kontext finden Sie im DDB-METS/MODS-Anwendungsprofil auf der Seite mets:structMap[@TYPE='PHYSICAL'] (https://wiki.deutsche-digitale-bibliothek.de/x/i8IeB) und im Bereich Aufbau einer METS/MODS-Datei für die DDB (https://wiki.deutsche-digitale-bibliothek.de/x/VcIeB).</svrl:text>
            </svrl:failed-assert>
         </xsl:otherwise>
      </xsl:choose>
      <xsl:apply-templates select="*" mode="M131"/>
   </xsl:template>
   <xsl:template match="text()" priority="-1" mode="M131"/>
   <xsl:template match="@*|node()" priority="-2" mode="M131">
      <xsl:apply-templates select="*" mode="M131"/>
   </xsl:template>
   <!--PATTERN -->
   <!--RULE -->
   <xsl:template match="mets:mets/mets:structMap[@TYPE='PHYSICAL']//mets:div"
                 priority="1000"
                 mode="M132">
      <svrl:fired-rule xmlns:svrl="http://purl.oclc.org/dsdl/svrl"
                       context="mets:mets/mets:structMap[@TYPE='PHYSICAL']//mets:div"/>
      <!--ASSERT fatal-->
      <xsl:choose>
         <xsl:when test="count(key('mets_ids', @ID)) = 1 and matches(@ID, '^[\i-[:]][\c-[:]]*$')"/>
         <xsl:otherwise>
            <svrl:failed-assert xmlns:svrl="http://purl.oclc.org/dsdl/svrl"
                                test="count(key('mets_ids', @ID)) = 1 and matches(@ID, '^[\i-[:]][\c-[:]]*$')">
               <xsl:attribute name="id">structMapPhysical_04</xsl:attribute>
               <xsl:attribute name="role">fatal</xsl:attribute>
               <xsl:attribute name="location">
                  <xsl:apply-templates select="." mode="schematron-select-full-path"/>
               </xsl:attribute>
               <svrl:text>Das Element <xsl:text/>mets:div<xsl:text/> im Element <xsl:text/>mets:structMap[@TYPE='PHYSICAL']<xsl:text/> muss das Attribut <xsl:text/>ID<xsl:text/> mit einem im Datensatz eindeutigen Identifier enthalten. Dieser darf darüber hinaus keine ungültigen Zeichen enthalten.
Fehlt das Attribut <xsl:text/>ID<xsl:text/> bzw. enthält es ungültige Zeichen, wird der Datensatz nicht in die DDB eingespielt.Weitere Informationen zu diesem Attribut finden Sie im DDB-METS/MODS-Anwendungsprofil auf der Seite mets:structMap[@TYPE='PHYSICAL'] (https://wiki.deutsche-digitale-bibliothek.de/x/i8IeB).</svrl:text>
               <svrl:property id="id">
                  <xsl:value-of xmlns:sch="http://purl.oclc.org/dsdl/schematron" select="./@ID"/>
               </svrl:property>
            </svrl:failed-assert>
         </xsl:otherwise>
      </xsl:choose>
      <xsl:apply-templates select="*" mode="M132"/>
   </xsl:template>
   <xsl:template match="text()" priority="-1" mode="M132"/>
   <xsl:template match="@*|node()" priority="-2" mode="M132">
      <xsl:apply-templates select="*" mode="M132"/>
   </xsl:template>
   <!--PATTERN -->
   <!--RULE -->
   <xsl:template match="mets:mets/mets:structMap[@TYPE='PHYSICAL']//mets:div[@TYPE='page'][not(@ORDER)]"
                 priority="1001"
                 mode="M133">
      <svrl:fired-rule xmlns:svrl="http://purl.oclc.org/dsdl/svrl"
                       context="mets:mets/mets:structMap[@TYPE='PHYSICAL']//mets:div[@TYPE='page'][not(@ORDER)]"/>
      <!--ASSERT warn-->
      <xsl:choose>
         <xsl:when test="@ORDER"/>
         <xsl:otherwise>
            <svrl:failed-assert xmlns:svrl="http://purl.oclc.org/dsdl/svrl" test="@ORDER">
               <xsl:attribute name="id">structMapPhysical_05</xsl:attribute>
               <xsl:attribute name="role">warn</xsl:attribute>
               <xsl:attribute name="location">
                  <xsl:apply-templates select="." mode="schematron-select-full-path"/>
               </xsl:attribute>
               <svrl:text>Das Element <xsl:text/>mets:div<xsl:text/> innerhalb des Elements <xsl:text/>mets:structMap[@TYPE='PHYSICAL']<xsl:text/>, das das Attribut <xsl:text/>TYPE<xsl:text/> mit dem Wert <xsl:text/>page<xsl:text/> besitzt, muss auch das Attribut <xsl:text/>order<xsl:text/> enthalten.
Fehlt das Attribut <xsl:text/>order<xsl:text/> verhindert dies nicht das Einspielen des Datensatzes in die DDB, wir bitten Sie jedoch, den Sachverhalt zu prüfen und die nötigen Korrekturen bis zur nächsten Datenlieferung vorzunehmen.Weitere Informationen zu diesem Attribut finden Sie im DDB-METS/MODS-Anwendungsprofil auf der Seite mets:structMap[@TYPE='PHYSICAL'] (https://wiki.deutsche-digitale-bibliothek.de/x/i8IeB).</svrl:text>
               <svrl:property id="id">
                  <xsl:value-of xmlns:sch="http://purl.oclc.org/dsdl/schematron" select="./@ID"/>
               </svrl:property>
            </svrl:failed-assert>
         </xsl:otherwise>
      </xsl:choose>
      <xsl:apply-templates select="*" mode="M133"/>
   </xsl:template>
   <!--RULE -->
   <xsl:template match="mets:mets/mets:structMap[@TYPE='PHYSICAL']//mets:div[@TYPE='page']"
                 priority="1000"
                 mode="M133">
      <svrl:fired-rule xmlns:svrl="http://purl.oclc.org/dsdl/svrl"
                       context="mets:mets/mets:structMap[@TYPE='PHYSICAL']//mets:div[@TYPE='page']"/>
      <!--ASSERT warn-->
      <xsl:choose>
         <xsl:when test="matches(@ORDER, '^\d+$')"/>
         <xsl:otherwise>
            <svrl:failed-assert xmlns:svrl="http://purl.oclc.org/dsdl/svrl" test="matches(@ORDER, '^\d+$')">
               <xsl:attribute name="id">structMapPhysical_06</xsl:attribute>
               <xsl:attribute name="role">warn</xsl:attribute>
               <xsl:attribute name="location">
                  <xsl:apply-templates select="." mode="schematron-select-full-path"/>
               </xsl:attribute>
               <svrl:text>Das Attribut <xsl:text/>order<xsl:text/> vom Element <xsl:text/>mets:div<xsl:text/> im Element <xsl:text/>mets:structMap[@TYPE='PHYSICAL']<xsl:text/>muss als Wert einen Integer enthalten.
Die Verwendung eines ungültigen Wertes verhindert nicht das Einspielen des Datensatzes in die DDB, kann aber zu Darstellungsproblemen führen. Wir bitten Sie daher den Sachverhalt zu prüfen und ggf. die nötigen Korrekturen bis zur nächsten Datenlieferung vorzunehmen.Weitere Informationen zu diesem Attribut finden Sie im DDB-METS/MODS-Anwendungsprofil auf der Seite mets:structMap[@TYPE='PHYSICAL'] (https://wiki.deutsche-digitale-bibliothek.de/x/i8IeB).</svrl:text>
               <svrl:property id="id">
                  <xsl:value-of xmlns:sch="http://purl.oclc.org/dsdl/schematron" select="./@ID"/>
               </svrl:property>
            </svrl:failed-assert>
         </xsl:otherwise>
      </xsl:choose>
      <xsl:apply-templates select="*" mode="M133"/>
   </xsl:template>
   <xsl:template match="text()" priority="-1" mode="M133"/>
   <xsl:template match="@*|node()" priority="-2" mode="M133">
      <xsl:apply-templates select="*" mode="M133"/>
   </xsl:template>
   <!--PATTERN -->
   <!--RULE -->
   <xsl:template match="mets:mets/mets:structMap[@TYPE='PHYSICAL']//mets:div[@TYPE='page']"
                 priority="1000"
                 mode="M134">
      <svrl:fired-rule xmlns:svrl="http://purl.oclc.org/dsdl/svrl"
                       context="mets:mets/mets:structMap[@TYPE='PHYSICAL']//mets:div[@TYPE='page']"/>
      <!--ASSERT fatal-->
      <xsl:choose>
         <xsl:when test="mets:fptr[string-length(@FILEID) &gt; 0]"/>
         <xsl:otherwise>
            <svrl:failed-assert xmlns:svrl="http://purl.oclc.org/dsdl/svrl"
                                test="mets:fptr[string-length(@FILEID) &gt; 0]">
               <xsl:attribute name="id">structMapPhysical_07</xsl:attribute>
               <xsl:attribute name="role">fatal</xsl:attribute>
               <xsl:attribute name="location">
                  <xsl:apply-templates select="." mode="schematron-select-full-path"/>
               </xsl:attribute>
               <svrl:text>Das Element <xsl:text/>mets:fptr<xsl:text/> im Element <xsl:text/>mets:div[@TYPE='page']<xsl:text/> muss das Attribut <xsl:text/>FILEID<xsl:text/> enthalten.
Fehlt <xsl:text/>FILEID<xsl:text/> in <xsl:text/>mets:div[@TYPE='page']<xsl:text/> wird der Datensatz nicht in die DDB eingespielt.Weitere Informationen zu diesem Attribut finden Sie im DDB-METS/MODS-Anwendungsprofil auf der Seite mets:structMap[@TYPE='PHYSICAL'] (https://wiki.deutsche-digitale-bibliothek.de/x/i8IeB).</svrl:text>
               <svrl:property id="id">
                  <xsl:value-of xmlns:sch="http://purl.oclc.org/dsdl/schematron" select="./@ID"/>
               </svrl:property>
            </svrl:failed-assert>
         </xsl:otherwise>
      </xsl:choose>
      <xsl:apply-templates select="*" mode="M134"/>
   </xsl:template>
   <xsl:template match="text()" priority="-1" mode="M134"/>
   <xsl:template match="@*|node()" priority="-2" mode="M134">
      <xsl:apply-templates select="*" mode="M134"/>
   </xsl:template>
   <!--PATTERN -->
   <!--RULE -->
   <xsl:template match="mets:mets/mets:structMap[@TYPE='PHYSICAL'][//mets:div[@TYPE='page'][starts-with(@CONTENTIDS, 'urn:')]]//mets:div[@TYPE='page']"
                 priority="1000"
                 mode="M135">
      <svrl:fired-rule xmlns:svrl="http://purl.oclc.org/dsdl/svrl"
                       context="mets:mets/mets:structMap[@TYPE='PHYSICAL'][//mets:div[@TYPE='page'][starts-with(@CONTENTIDS, 'urn:')]]//mets:div[@TYPE='page']"/>
      <!--ASSERT fatal-->
      <xsl:choose>
         <xsl:when test="starts-with(@CONTENTIDS, 'urn:')"/>
         <xsl:otherwise>
            <svrl:failed-assert xmlns:svrl="http://purl.oclc.org/dsdl/svrl"
                                test="starts-with(@CONTENTIDS, 'urn:')">
               <xsl:attribute name="id">structMapPhysical_08</xsl:attribute>
               <xsl:attribute name="role">fatal</xsl:attribute>
               <xsl:attribute name="location">
                  <xsl:apply-templates select="." mode="schematron-select-full-path"/>
               </xsl:attribute>
               <svrl:text>Der Datensatz enthält in mindestens einem <xsl:text/>mets:div[@TYPE='page']<xsl:text/>-Element im Element <xsl:text/>mets:structMap[@TYPE='PHYSICAL']<xsl:text/>im Attribut <xsl:text/>CONTENTIDS<xsl:text/> einen URN. Ist dies der Fall, müssen alle <xsl:text/>mets:div[@TYPE='page']<xsl:text/> das Attribut <xsl:text/>CONTENTIDS<xsl:text/> mit einem URN besitzen.
Fehlt <xsl:text/>CONTENTIDS<xsl:text/> mit einem URN in einem <xsl:text/>mets:div[@TYPE='page']<xsl:text/>, wird der Datensatz nicht in die DDB eingespielt.Weitere Informationen zu diesem Attribut finden Sie im DDB-METS/MODS-Anwendungsprofil auf der Seite mets:structMap[@TYPE='PHYSICAL'] (https://wiki.deutsche-digitale-bibliothek.de/x/i8IeB).</svrl:text>
               <svrl:property id="id">
                  <xsl:value-of xmlns:sch="http://purl.oclc.org/dsdl/schematron" select="./@ID"/>
               </svrl:property>
            </svrl:failed-assert>
         </xsl:otherwise>
      </xsl:choose>
      <xsl:apply-templates select="*" mode="M135"/>
   </xsl:template>
   <xsl:template match="text()" priority="-1" mode="M135"/>
   <xsl:template match="@*|node()" priority="-2" mode="M135">
      <xsl:apply-templates select="*" mode="M135"/>
   </xsl:template>
   <!--PATTERN -->
   <!--RULE -->
   <xsl:template match="mets:mets" priority="1003" mode="M136">
      <svrl:fired-rule xmlns:svrl="http://purl.oclc.org/dsdl/svrl" context="mets:mets"/>
      <!--ASSERT fatal-->
      <xsl:choose>
         <xsl:when test="$is_anchor or mets:structLink"/>
         <xsl:otherwise>
            <svrl:failed-assert xmlns:svrl="http://purl.oclc.org/dsdl/svrl"
                                test="$is_anchor or mets:structLink">
               <xsl:attribute name="id">structLink_01</xsl:attribute>
               <xsl:attribute name="role">fatal</xsl:attribute>
               <xsl:attribute name="location">
                  <xsl:apply-templates select="." mode="schematron-select-full-path"/>
               </xsl:attribute>
               <svrl:text>Der Datensatz beschreibt ein Einteiliges Dokument bzw. einen Teil eines Mehrteiligen Dokuments und muss daher das Element <xsl:text/>mets:structLink<xsl:text/> enthalten.
Fehlt <xsl:text/>mets:structLink<xsl:text/>, wird der Datensatz nicht in die DDB eingespielt.Weitere Informationen zu diesem Element und seinem Kontext finden Sie im DDB-METS/MODS-Anwendungsprofil auf der Seite mets:structLink (https://wiki.deutsche-digitale-bibliothek.de/x/q8IeB) und im Bereich Aufbau einer METS/MODS-Datei für die DDB (https://wiki.deutsche-digitale-bibliothek.de/x/VcIeB).</svrl:text>
            </svrl:failed-assert>
         </xsl:otherwise>
      </xsl:choose>
      <xsl:apply-templates select="*" mode="M136"/>
   </xsl:template>
   <!--RULE -->
   <xsl:template match="mets:mets/mets:structLink[not(mets:smLink)]"
                 priority="1002"
                 mode="M136">
      <svrl:fired-rule xmlns:svrl="http://purl.oclc.org/dsdl/svrl"
                       context="mets:mets/mets:structLink[not(mets:smLink)]"/>
      <!--ASSERT fatal-->
      <xsl:choose>
         <xsl:when test="mets:smLink"/>
         <xsl:otherwise>
            <svrl:failed-assert xmlns:svrl="http://purl.oclc.org/dsdl/svrl" test="mets:smLink">
               <xsl:attribute name="id">structLink_02</xsl:attribute>
               <xsl:attribute name="role">fatal</xsl:attribute>
               <xsl:attribute name="location">
                  <xsl:apply-templates select="." mode="schematron-select-full-path"/>
               </xsl:attribute>
               <svrl:text>Das Element <xsl:text/>mets:structLink<xsl:text/> muss mindestens ein <xsl:text/>mets:smLink<xsl:text/>-Element enthalten.
Ist dies nicht der Fall, wird der Datensatz nicht in die DDB eingespielt.Weitere Informationen zu diesem Element und seinem Kontext finden Sie im DDB-METS/MODS-Anwendungsprofil auf der Seite mets:structLink (https://wiki.deutsche-digitale-bibliothek.de/x/q8IeB) und im Bereich Aufbau einer METS/MODS-Datei für die DDB (https://wiki.deutsche-digitale-bibliothek.de/x/VcIeB).</svrl:text>
            </svrl:failed-assert>
         </xsl:otherwise>
      </xsl:choose>
      <xsl:apply-templates select="*" mode="M136"/>
   </xsl:template>
   <!--RULE -->
   <xsl:template match="mets:mets/mets:structLink/mets:smLink[not(string-length(@xlink:from) &gt; 0 and string-length(@xlink:to) &gt; 0)]"
                 priority="1001"
                 mode="M136">
      <svrl:fired-rule xmlns:svrl="http://purl.oclc.org/dsdl/svrl"
                       context="mets:mets/mets:structLink/mets:smLink[not(string-length(@xlink:from) &gt; 0 and string-length(@xlink:to) &gt; 0)]"/>
      <!--ASSERT fatal-->
      <xsl:choose>
         <xsl:when test="string-length(@xlink:from) &gt; 0 and string-length(@xlink:to) &gt; 0"/>
         <xsl:otherwise>
            <svrl:failed-assert xmlns:svrl="http://purl.oclc.org/dsdl/svrl"
                                test="string-length(@xlink:from) &gt; 0 and string-length(@xlink:to) &gt; 0">
               <xsl:attribute name="id">structLink_03</xsl:attribute>
               <xsl:attribute name="role">fatal</xsl:attribute>
               <xsl:attribute name="location">
                  <xsl:apply-templates select="." mode="schematron-select-full-path"/>
               </xsl:attribute>
               <svrl:text>Das Element <xsl:text/>mets:smLink<xsl:text/> muss die Attribute <xsl:text/>xlink:from<xsl:text/> und <xsl:text/>xlink:to<xsl:text/> enthalten.
Fehlen diese Attribute, wird der Datensatz nicht in die DDB eingespielt.Weitere Informationen zu diesem Element, seinen Attributen und seinem Kontext finden Sie im DDB-METS/MODS-Anwendungsprofil auf der Seite mets:structLink (https://wiki.deutsche-digitale-bibliothek.de/x/q8IeB) und im Bereich Aufbau einer METS/MODS-Datei für die DDB (https://wiki.deutsche-digitale-bibliothek.de/x/VcIeB).</svrl:text>
            </svrl:failed-assert>
         </xsl:otherwise>
      </xsl:choose>
      <xsl:apply-templates select="*" mode="M136"/>
   </xsl:template>
   <!--RULE -->
   <xsl:template match="mets:mets/mets:structLink/mets:smLink"
                 priority="1000"
                 mode="M136">
      <svrl:fired-rule xmlns:svrl="http://purl.oclc.org/dsdl/svrl"
                       context="mets:mets/mets:structLink/mets:smLink"/>
      <!--ASSERT fatal-->
      <xsl:choose>
         <xsl:when test="key('structMap_PHYSICAL_ids', @xlink:to)"/>
         <xsl:otherwise>
            <svrl:failed-assert xmlns:svrl="http://purl.oclc.org/dsdl/svrl"
                                test="key('structMap_PHYSICAL_ids', @xlink:to)">
               <xsl:attribute name="id">structLink_04</xsl:attribute>
               <xsl:attribute name="role">fatal</xsl:attribute>
               <xsl:attribute name="location">
                  <xsl:apply-templates select="." mode="schematron-select-full-path"/>
               </xsl:attribute>
               <svrl:text>Das Attribut <xsl:text/>xlink:to<xsl:text/> des Elements <xsl:text/>mets:smLink<xsl:text/> muss den Wert des Attributs <xsl:text/>ID<xsl:text/> eines <xsl:text/>mets:div<xsl:text/>-Elements im Element <xsl:text/>mets:structMap[@TYPE='PHYSICAL']<xsl:text/> referenzieren.
Enthält ein <xsl:text/>xlink:to<xsl:text/> eine ungültige Referenz, wird der Datensatz nicht in die DDB eingespielt.Weitere Informationen zu diesen Elementen, ihren Attributen und ihrem Kontext finden Sie im DDB-METS/MODS-Anwendungsprofil auf den Seiten mets:structLink (https://wiki.deutsche-digitale-bibliothek.de/x/q8IeB) und mets:structMap[@TYPE='PHYSICAL'] (https://wiki.deutsche-digitale-bibliothek.de/x/i8IeB) sowie im Bereich Aufbau einer METS/MODS-Datei für die DDB (https://wiki.deutsche-digitale-bibliothek.de/x/VcIeB).</svrl:text>
            </svrl:failed-assert>
         </xsl:otherwise>
      </xsl:choose>
      <xsl:apply-templates select="*" mode="M136"/>
   </xsl:template>
   <xsl:template match="text()" priority="-1" mode="M136"/>
   <xsl:template match="@*|node()" priority="-2" mode="M136">
      <xsl:apply-templates select="*" mode="M136"/>
   </xsl:template>
   <!--PATTERN -->
   <!--RULE -->
   <xsl:template match="oai:record/oai:metadata/mets:mets/mets:structLink/mets:smLink[1]"
                 priority="1000"
                 mode="M137">
      <svrl:fired-rule xmlns:svrl="http://purl.oclc.org/dsdl/svrl"
                       context="oai:record/oai:metadata/mets:mets/mets:structLink/mets:smLink[1]"/>
      <!--ASSERT fatal-->
      <xsl:choose>
         <xsl:when test="@*[local-name() = 'from'][namespace-uri() = 'http://www.w3.org/1999/xlink']"/>
         <xsl:otherwise>
            <svrl:failed-assert xmlns:svrl="http://purl.oclc.org/dsdl/svrl"
                                test="@*[local-name() = 'from'][namespace-uri() = 'http://www.w3.org/1999/xlink']">
               <xsl:attribute name="id">structLink_05</xsl:attribute>
               <xsl:attribute name="role">fatal</xsl:attribute>
               <xsl:attribute name="location">
                  <xsl:apply-templates select="." mode="schematron-select-full-path"/>
               </xsl:attribute>
               <svrl:text>Die Attribute <xsl:text/>xlink:from<xsl:text/> und <xsl:text/>xlink:to<xsl:text/> des Elements <xsl:text/>mets:smLink<xsl:text/> verwenden einen ungültigen Namensraum. Der korrekte Namensraum für diese XLink-Attribute ist <xsl:text/>http://www.w3.org/1999/xlink<xsl:text/>.
Verwenden die Attribute einen ungültigen Namensraum ist eine Verarbeitung des Datensatzes nicht möglich und er wird nicht in die DDB eingespielt.</svrl:text>
            </svrl:failed-assert>
         </xsl:otherwise>
      </xsl:choose>
      <xsl:apply-templates select="*" mode="M137"/>
   </xsl:template>
   <xsl:template match="text()" priority="-1" mode="M137"/>
   <xsl:template match="@*|node()" priority="-2" mode="M137">
      <xsl:apply-templates select="*" mode="M137"/>
   </xsl:template>
</xsl:stylesheet>
