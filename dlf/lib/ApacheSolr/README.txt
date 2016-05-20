Apache Solr for Kitodo.Presentation
==================================

This is just a pre-configured version of Apache Solr. Some files have to be
patched in order to add some configuration and security constraints for up
to 15 different Lucene cores to be used by Kitodo.Presentation. If you need
more cores, you have to add more security constraints by yourself.
Additionally there are ready-to-use configuration files for the Apache Solr
application in the conf/ directoy.


Installation instructions
-------------------------

1. Make sure you have Apache Tomcat 6 up and running. Download Solr 3.6.1
	from http://lucene.apache.org/solr/. Using later versions may be
	possible, but is not tested.

2. Apply the patches in patches/* to the respective files and build Solr.

3. Copy solr.xml and conf/* to /home/solr and confirm overwriting the
	existing files. Then move the Solr WAR file to /home/solr and rename it
	to "apache-solr-for-kitodo.war" or change the "docBase" value in
	conf/context.xml accordingly.

4. Add the roles "dlfSolrUpdate" and "dlfSolrAdmin" and at least one user
	with both roles to Tomcat's tomcat-users.xml file.

5. Load the application by using conf/context.xml as Tomcat's context file.

6. Restart Tomcat and go for it!


Update instructions
-------------------

When updating an existing Solr instance for Kitodo.Presentation follow the
above steps but DO NOT overwrite solr.xml! Kitodo.Presentation dynamically
adds new cores to this file, so overwriting it would result in a loss of
these indexes.
