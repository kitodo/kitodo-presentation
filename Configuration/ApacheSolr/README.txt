Apache Solr for Kitodo.Presentation
==================================

This is just a configset for Apache Solr.

Installation instructions
-------------------------

See also: https://wiki.apache.org/solr/SolrTomcat

1. Make sure you have Apache Solr 7.4 up and running. Download Solr
    from http://lucene.apache.org/solr/. Other versions since 5.0 should be possible but are not tested.

2. Copy the Configuration/ApacheSolr/configsets/dlf to $SOLR_HOME/configsets/dlf.

3. Using basic authentication is optional but recommended. The documentation is available here:
    https://lucene.apache.org/solr/guide/7_4/basic-authentication-plugin.html

Update instructions
-------------------

When updating an existing Solr instance for Kitodo.Presentation follow the
above steps.
