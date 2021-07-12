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

Kitodo was formerly known as Goobi. Older releases can be found on [Launchpad](https://launchpad.net/goobi-presentation).

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

Application level dependencies are handled by [Composer](https://getcomposer.org) (see [composer.json](./composer.json)).

Kitodo. Digital Library Modules
-------------------------------

[Kitodo](https://github.com/kitodo) is an open source software suite intended to support the digitisation of cultural assets for libraries, archives, museums, and documentation centres of all sizes. A range of modules with open interfaces support the production, presentation, and archiving of digital assets. The software can be flexibly used for a multitude of digitisation strategies and scalable business models – for in-house projects, purely corporate services, or hybrid endeavours. Kitodo is backed and continually updated by a dynamic user and developer community and the non-profit association Kitodo e. V.

Information | Communication | Support
-------------------------------------

For general information and news, please visit our [website](https://www.kitodo.org) and follow us on [Twitter](https://twitter.com/kitodo_org).

As a system that has to meet the diverse requirements of a wide variety of institutions and the materials they want to digitise, Kitodo is a rather complex software solution, the installation and configuration of which can be challenging, especially for users with limited IT capacities and know-how.

To ensure it can best advise and assist users on technical and organisational issues, the Kitodo community has established support structures for the following typical scenarios.

1. Users who have clearly defined questions relating to the use and development of Kitodo or Kitodo modules are well-served by the [Kitodo mailing list](https://maillist.slub-dresden.de/cgi-bin/mailman/listinfo/kitodo-community). They will typically receive helpful answers from the community or the Kitodo release managers within a short period of time. If this should be unsuccessful for any reason, the Kitodo association office will address your matter to an experienced member institution. You do not need to be a member of the association to use the mailing list. The [list archive](https://maillist.slub-dresden.de/pipermail/kitodo-community/) provides an impression of the topics and conversations.
2. For users who occasionally need more extensive advice and possibly also on-site practical assistance for Kitodo installation, workflow modelling, etc., the Kitodo office maintains a list of voluntary mentors. Requests can be directed to these proven experts from various libraries by the association office. More information is available from the [association office](contact@kitodo.org).
3. For institutions that would like an initial and extensive introduction to Kitodo in the form of a product presentation or ongoing support, in particular on-site, we are happy to provide a list of companies that to the best of our knowledge have already worked in these fields. To obtain the company list, please also use the [association office address](contact@kitodo.org). Please bear in mind that the association cannot provide further assistance in selecting service providers.

[![Join the Gitter chat!](https://badges.gitter.im/Kitodo/Presentation.svg)](https://gitter.im/Kitodo/Presentation)

Getting started
---------------

* [Installation Guides](https://github.com/kitodo/kitodo-production/wiki/Installationsanleitung)
* [User documentation](https://github.com/kitodo/kitodo-production/wiki/)
* [Developer documentation](https://kitodo-production.readthedocs.io/en/latest/)
* [Demo server](https://presentation-demo.kitodo.org/)
