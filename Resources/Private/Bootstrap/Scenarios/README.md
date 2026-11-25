# Demo Scenarios

Each `*.yaml` file in this directory describes a demo site that can be created by the bootstrap mechanism of
Kitodo.Presentation. The file name (without `.yaml`) is the name of the scenario.

Create or replace the demo site of a scenario from the command line:

```bash
vendor/bin/typo3 kitodo:bootstrap <scenario> [--base-url=<scheme and host>]
```

Example: `vendor/bin/typo3 kitodo:bootstrap minimal --base-url=http://localhost:1380`

`--base-url` is required if another site already uses the same host (e.g. `http://localhost:1380/`), since TYPO3
always prefers sites with a matching host over sites without a host.

Running a scenario again deletes the previously created demo site (page tree, records and site configuration) and
creates it from scratch. The site configuration is stored with the identifier `kitodo-demo-<scenario>`.

## File format

```yaml
label: 'Minimal demo'
siteSet: kitodo/presentation-demo
basePath: /demo-minimal/

pluginSettings:
  solrcore: '{solrCore}'

root:
  title: 'Kitodo.Presentation Minimal Demo'
  backend_layout: Home
  content:
    - CType: dlf_search
      settings:
        targetPid: '{page:results}'

pages:
  storage:
    title: 'Kitodo Storage'
    doktype: 254
  results:
    title: 'Search Results'
    backend_layout: SearchResultList
    content:
      - CType: dlf_listview
        slot: main
```

### Top-level keys

| Key                         | Required | Description                                                                                  |
|-----------------------------|----------|----------------------------------------------------------------------------------------------|
| `label`                     | no       | Label shown in the scenario selection. Defaults to the scenario name.                        |
| `siteSet`                   | yes      | Site set added as dependency to the site configuration, see `Configuration/Sets/`.           |
| `basePath`                  | yes      | Base path of the site, e.g. `/demo-minimal/`. Prefixed with `--base-url` if given.           |
| `pluginSettings`            | no       | Settings applied to all plugins, see [Content elements](#content-elements).                  |
| `root`                      | no       | Fields of the root page, see [Page fields](#page-fields).                                    |
| `pages`                     | yes      | Subpages of the root page, see [Pages](#pages).                                              |
| `records`                   | no       | Additional records, e.g. frontend users, see [Records](#records).                            |
| `siteSettings`              | no       | Additional site settings, see [Site settings](#site-settings).                               |
| `documents`                 | no       | Demo documents of the scenario, see [Documents](#documents).                                 |
| `generatePlaceholderImages` | no       | Generate the page images of the documents, see [Documents](#documents). Defaults to `false`. |

### Pages

`pages` (and `children`, see below) is a map of pages. The key of each page is used to reference the page in
[placeholders](#placeholders) and must be unique within the scenario. The key `root` is reserved for the root page.

A page with the key `storage` is required. It is used as storage folder for the tenant records (formats, structures,
metadata and Solr core) and should therefore be a folder (`doktype: 254`).

Pages are created in the order they are listed.

#### Page fields

Any field of the table `pages` can be set, e.g.:

| Field            | Description                                                              |
|------------------|--------------------------------------------------------------------------|
| `title`          | Title of the page.                                                       |
| `doktype`        | Page type, e.g. `254` for a folder. Defaults to `1` (standard page).     |
| `backend_layout` | Backend layout of this page, see below.                                  |
| `nav_hide`       | `1` hides the page in the menu, e.g. for a viewer page.                  |

Pages are visible (`hidden: 0`) unless specified otherwise. The root page is always marked as site root.

The demo site set (`Configuration/Sets/Demo/page.tsconfig`) provides the following backend layouts, which can be given
by their name (e.g. `backend_layout: Home` for `pagets__Home`):

| Backend layout     | Slots                                                                       |
|--------------------|-----------------------------------------------------------------------------|
| `Viewer`           | `header` at the top, below it `sidebar` on the left and `main` on the right; `calendar` replaces `main` for newspapers and years, `media` replaces it for audio, video and 3D objects; `pages` in a sidebar on the right |
| `SearchResultList` | `header` at the top, below it `main` on the left and `sidebar` on the right |
| `Home`             | `main` with horizontally centered content                                   |
| `Feeds`            | `main` only, the page renders the RSS feed (XML) of its feeds plugin         |
| `OaiPmh`           | `main` only, the page renders the OAI-PMH response (XML) of its OAI plugin   |

OAI-PMH requests (e.g. `?verb=Identify`) have no cHash, so the TYPO3 setting `FE.pageNotFoundOnCHashError` has to be
disabled, see the configuration chapter of the documentation.

In addition, a page supports the following keys:

| Key        | Description                                                                       |
|------------|-----------------------------------------------------------------------------------|
| `children` | Subpages of this page, in the same format as `pages`.                             |
| `content`  | List of content elements of this page, see [Content elements](#content-elements). |

### Content elements

Each entry of `content` is a content element. Content elements are created in the order they are listed within
each slot.

Any field of the table `tt_content` can be set, e.g.:

| Field    | Description                                                                  |
|----------|------------------------------------------------------------------------------|
| `CType`  | Type of the content element, e.g. `dlf_search` for the Kitodo search plugin. |
| `header` | Header shown above the content element.                                      |

In addition, a content element supports the following keys:

| Key        | Description                                                                                                                                                                                  |
|------------|----------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------|
| `slot`     | Slot of the backend layout the content element is shown in: `main` (default), `sidebar`, `header`, `calendar`, `pages` or `media`. Alternatively, the column can be set by `colPos` (`0` to `5`).                    |
| `settings` | FlexForm values of the plugin without the `settings.` prefix, e.g. `targetPid` for `settings.targetPid`. Values not given are set to the defaults of the plugin's FlexForm, as when adding the plugin in the backend. |

The top-level `pluginSettings` are applied to every plugin whose FlexForm contains a setting with the same name, e.g.
`solrcore` to the search, list view and collection plugins. The `settings` of a content element take precedence.
Settings whose meaning differs between plugins should be set per content element, e.g. `targetPid`, which refers to
the list view for the search plugin, but to the page view for the table of contents.

The available settings of each plugin are defined in `Configuration/FlexForms/`.

### Records

`records` is a map of tables, each containing a map of records to create, e.g. the frontend users of the demo:

```yaml
records:
  fe_groups:
    demoGroup:
      page: users
      title: 'Demo users'
  fe_users:
    demoUser:
      page: users
      username: demo
      password: 'Pass123!'
      usergroup: '{record:demoGroup}'
```

The key of each record is used to reference it in other records (see [Placeholders](#placeholders)) and must be unique
within the scenario. Any field of the table can be set. In addition, a record supports the following key:

| Key    | Description                                                                         |
|--------|-------------------------------------------------------------------------------------|
| `page` | Key of the page the record is stored on, see [Pages](#pages). Defaults to `storage`. |

The records are created via DataHandler after the content elements, so the configuration of the table (TCA) applies,
e.g. passwords of frontend users are hashed and have to fulfill the password policy of the frontend.

### Placeholders

Field and setting values of content elements and records may contain placeholders, which are replaced after all
pages, tenant records and documents have been created:

| Placeholder      | Replaced with                                                                       |
|------------------|-------------------------------------------------------------------------------------|
| `{page:<key>}`   | UID of the page with the given key, e.g. `{page:search}`.                           |
| `{page:root}`    | UID of the root page.                                                               |
| `{solrCore}`     | UID of the Solr core record in the storage folder.                                  |
| `{library}`      | UID of the library owning the documents (created by the ingest, `0` without documents). |
| `{record:<key>}` | UID of the record with the given key (only in [records](#records), e.g. the group of a user). |

### Documents

Each entry of `documents` refers to a directory in `Resources/Private/Bootstrap/Documents/`, which contains the METS
file of the document (`mets.xml`) and optionally its files (e.g. images or ALTO full texts). The directory is copied to
`fileadmin/kitodo-demo/<scenario>/<name>/`, and the placeholder `{documentUrl}` in its XML files is replaced with the
public URL of the copied directory. Since the METS files are loaded via HTTP, a base URL (`--base-url`) is required.

A directory may contain several METS files referencing each other via `mets:mptr` and `{documentUrl}`, e.g. the title
record, years and issues of a newspaper (`newspaper/`) or the title record and volumes of a multi-volume work
(`encyclopedia/`). Only the files listed in `index` are indexed, their parent documents are added by the ingest.
After indexing, the search suggestions of the search plugin are built (like `kitodo:suggestBuild`).

| Key        | Required | Description                                                                                        |
|------------|----------|----------------------------------------------------------------------------------------------------|
| `name`     | yes      | Name of the document's directory.                                                                  |
| `index`    | no       | METS files of the directory to index, e.g. the issues of a newspaper. Defaults to `[mets.xml]`.    |
| `pageSize` | no       | Width and height of generated placeholder images, e.g. `[1400, 1000]`. Defaults to `[1000, 1400]`. |

With `generatePlaceholderImages: true`, a SVG image labeled with the page number (`ORDER`), the document's title and the page label (`ORDERLABEL`) is generated
for every SVG file (`MIMETYPE="image/svg+xml"`) referenced by a page of a METS file with a URL starting with
`{documentUrl}/`. Files of the file group `THUMBS` are scaled down to a maximum size of 150 pixels. If the page has an
ALTO full text (file group `FULLTEXT`), its words are drawn at their positions, so that the full text, its search
highlighting and the image match.

## Site settings

The following site settings are written to the site configuration and are available as TypoScript constants:

| Setting                                | Value                              |
|----------------------------------------|------------------------------------|
| `plugin.tx_dlf.persistence.storagePid`  | UID of the page with key `storage` |
| `plugin.tx_dlf.persistence.solrCoreUid` | UID of the Solr core record        |

In addition, the site settings given by `siteSettings` are written, indexed by their name as defined in the
`settings.definitions.yaml` of a site set, e.g. the login page of the demo site set:

```yaml
siteSettings:
  demo.loginPid: '{page:login}'
```

Values may contain the placeholders `{page:<key>}` and `{solrCore}` (see [Placeholders](#placeholders)), but not
`{library}`, since the site settings are written before the documents are ingested.

The demo site set (`Configuration/Sets/Demo/settings.definitions.yaml`) provides the following settings:

| Setting         | Description                                                                                          |
|-----------------|------------------------------------------------------------------------------------------------------|
| `demo.loginPid` | Page with the login form of frontend users. If set, the menu shows "Not logged in" to visitors who are not logged in (a logged-in user is always shown with a logout link). |
