Kitodo.Presentation
===================

Kitodo.Presentation is a feature-rich framework for building a METS- or IIIF-based digital library. It is part of the Kitodo Digital Library Suite.

Kitodo.Presentation is highly customizable through a user-friendly backend and flexible design templates. Since it is based on the great free and open source Content Management System [TYPO3](https://typo3.org), it integrates perfectly with your website and can easily be managed by editors. Kitodo.Presentation provides a comprehensive toolset covering all requirements for presenting digitized media. It implements international standards such as [IIIF Image API](https://iiif.io/api/image), [IIIF Presentation API](https://iiif.io/api/presentation), [OAI Protocol for Metadata Harvesting](http://www.openarchives.org/OAI/openarchivesprotocol.html), [METS](http://www.loc.gov/standards/mets), [MODS](http://www.loc.gov/standards/mods), [TEI](http://www.tei-c.org), [ALTO](http://www.loc.gov/standards/alto), and can be configured to support any other descriptive XML format using simple XPath expressions. With Kitodo.Presentation you can publish digitized books, manuscripts, periodicals, newspapers, archival materials, audio and video.

For a complete overview of all features, visit the [Kitodo homepage](https://www.kitodo.org/software/kitodopresentation/features).

[![Maintainability Rating](https://sonarcloud.io/api/project_badges/measure?project=kitodo-presentation&metric=sqale_rating)](https://sonarcloud.io/dashboard?id=kitodo-presentation)
[![Reliability Rating](https://sonarcloud.io/api/project_badges/measure?project=kitodo-presentation&metric=reliability_rating)](https://sonarcloud.io/dashboard?id=kitodo-presentation)
[![Security Rating](https://sonarcloud.io/api/project_badges/measure?project=kitodo-presentation&metric=security_rating)](https://sonarcloud.io/dashboard?id=kitodo-presentation)
[![Quality Gate Status](https://sonarcloud.io/api/project_badges/measure?project=kitodo-presentation&metric=alert_status)](https://sonarcloud.io/dashboard?id=kitodo-presentation)

[![Codacy Grade](https://api.codacy.com/project/badge/Grade/b2a7bd8e42ef405d95ca503e4fe95320)](https://app.codacy.com/gh/kitodo/kitodo-presentation)
[![LGTM Grade](https://img.shields.io/lgtm/grade/javascript/g/kitodo/kitodo-presentation.svg?logo=lgtm)](https://lgtm.com/projects/g/kitodo/kitodo-presentation/context:javascript)
[![LGTM Alerts](https://img.shields.io/lgtm/alerts/g/kitodo/kitodo-presentation.svg?logo=lgtm)](https://lgtm.com/projects/g/kitodo/kitodo-presentation/alerts/)
[![Scrutinizer Code Quality](https://scrutinizer-ci.com/g/kitodo/kitodo-presentation/badges/quality-score.png?b=master)](https://scrutinizer-ci.com/g/kitodo/kitodo-presentation/?branch=master)

Requirements
------------

Kitodo.Presentation requires [TYPO3](https://get.typo3.org) with [PHP](https://secure.php.net). It uses [MySQL](https://www.mysql.com) or [MariaDB](https://mariadb.com) as database and [Apache Solr](https://lucene.apache.org/solr) via [Solarium](http://www.solarium-project.org/) as search engine backend.

Currently **TYPO3 9.5 LTS** is supported with the following system requirements:

| Component   | Constraints for 9 LTS |
| ----------- | --------------------- |
| TYPO3       | 9.5.x                 |
| PHP         | 7.3.x - 7.4.x         |
| MySQL       | 5.7.x                 |
| MariaDB     | 10.2.x - 10.3.x       |
| Apache Solr | 7.x - 8.x             |
| OCR Highlighting Plugin | 0.7.1     |

Application level dependencies are handled by [Composer](https://getcomposer.org) (see [composer.json](./composer.json)).

Kitodo. Key to digital objects
------------------------------

[Kitodo](https://github.com/kitodo) is an open source software suite intended to support mass digitization projects for cultural heritage institutions. Kitodo is widely-used and cooperatively maintained by major German libraries and digitization service providers. The software implements international standards such as IIIF, METS, MODS, ALTO, TEI, and other formats. Kitodo consists of several independent modules serving different purposes such as controlling the digitization workflow, enriching descriptive and structural metadata, and presenting the results to the public in a modern and convenient way.

To get more information, visit the [Kitodo homepage](https://www.kitodo.org). You can also follow Kitodo News on [Twitter](https://twitter.com/kitodo_org).

---

Kitodo was formerly known as Goobi. Older releases can be found on [Launchpad](https://launchpad.net/goobi-presentation).
