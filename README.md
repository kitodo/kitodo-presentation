Kitodo.Presentation
===================

Kitodo.Presentation is a feature-rich framework for building a METS- or IIIF-based digital library. It is part of the Kitodo Digital Library Suite.

Kitodo.Presentation is highly customizable through a user-friendly backend and flexible design templates. Since it is based on the great free and open source Content Management System [TYPO3](https://typo3.org), it integrates perfectly with your website and can easily be managed by editors. Kitodo.Presentation provides a comprehensive toolset covering all requirements for presenting digitized media. It implements international standards such as [IIIF Image API](https://iiif.io/api/image), [IIIF Presentation API](https://iiif.io/api/presentation), [OAI Protocol for Metadata Harvesting](http://www.openarchives.org/OAI/openarchivesprotocol.html), [METS](http://www.loc.gov/standards/mets), [MODS](http://www.loc.gov/standards/mods), [TEI](http://www.tei-c.org), [ALTO](http://www.loc.gov/standards/alto), and can be configured to support any other descriptive XML format using simple XPath expressions. With Kitodo.Presentation you can publish digitized books, manuscripts, periodicals, newspapers, archival materials, audio and video.

<a href="https://scrutinizer-ci.com/g/kitodo/kitodo-presentation/?branch=master">
  <img alt="Scrutinizer Code Quality" src="https://scrutinizer-ci.com/g/kitodo/kitodo-presentation/badges/quality-score.png?b=master"/>
</a>
<a href="https://app.codacy.com/gh/kitodo/kitodo-presentation">
  <img alt="Codacy Grade" src="https://api.codacy.com/project/badge/Grade/b2a7bd8e42ef405d95ca503e4fe95320"/>
</a>
<a href="https://lgtm.com/projects/g/kitodo/kitodo-presentation/context:javascript">
  <img alt="LGTM Grade" src="https://img.shields.io/lgtm/grade/javascript/g/kitodo/kitodo-presentation.svg?logo=lgtm"/>
</a>
<a href="https://lgtm.com/projects/g/kitodo/kitodo-presentation/alerts/">
  <img alt="LGTM Alerts" src="https://img.shields.io/lgtm/alerts/g/kitodo/kitodo-presentation.svg?logo=lgtm"/>
</a>
<a href="https://gitter.im/Kitodo/Presentation">
  <img alt="Join the Gitter chat!" src="https://badges.gitter.im/Kitodo/Presentation.svg"/>
</a>

Requirements
------------

Kitodo.Presentation requires [TYPO3](https://get.typo3.org) with [PHP](https://secure.php.net). It uses [MySQL](https://www.mysql.com) or [MariaDB](https://mariadb.com) as database and [Apache Solr](https://lucene.apache.org/solr) via [Solarium](http://www.solarium-project.org/) as search engine backend.

Currently **TYPO3 8 ELTS** and **TYPO3 9 LTS** are supported with the following system requirements:

| Component   | Constraints for 8 ELTS | Constraints for 9 LTS |
| ----------- | ---------------------- | --------------------- |
| TYPO3       | 8.7.x                  | 9.5.x                 |
| PHP         | 7.2.x - 7.4.x          | 7.2.x - 7.4.x         |
| MySQL       | 5.7.x                  | 5.7.x                 |
| MariaDB     | 10.2.x - 10.2.26       | 10.2.x - 10.3.x       |
| Apache Solr | 7.x                    | 7.x                   |

Application level dependencies are handled by [Composer](https://getcomposer.org) (see [composer.json](./composer.json)).

Kitodo. Key to digital objects
------------------------------

[Kitodo](https://github.com/kitodo) is an open source software suite intended to support mass digitization projects for cultural heritage institutions. Kitodo is widely-used and cooperatively maintained by major German libraries and digitization service providers. The software implements international standards such as IIIF, METS, MODS, ALTO, TEI, and other formats. Kitodo consists of several independent modules serving different purposes such as controlling the digitization workflow, enriching descriptive and structural metadata, and presenting the results to the public in a modern and convenient way.

To get more information, visit the [Kitodo homepage](https://www.kitodo.org). You can also follow Kitodo News on [Twitter](https://twitter.com/kitodo_org).

Kitodo was formerly known as Goobi. Older releases can be found on [Launchpad](https://launchpad.net/goobi-presentation).
