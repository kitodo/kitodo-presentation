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
siteSet: kitodo/presentation-demo-minimal
basePath: /demo-minimal/

root:
  title: 'Kitodo.Presentation Minimal Demo'
  backend_layout_next_level: pagets__Default

pages:
  storage:
    title: 'Kitodo Storage'
    doktype: 254
  search:
    title: 'Search'
    content:
      - CType: dlf_search
        settings:
          solrcore: '{solrCore}'
          targetPid: '{page:search}'
```

### Top-level keys

| Key        | Required | Description                                                                         |
|------------|----------|-------------------------------------------------------------------------------------|
| `label`    | no       | Label shown in the scenario selection. Defaults to the scenario name.               |
| `siteSet`  | yes      | Site set added as dependency to the site configuration, see `Configuration/Sets/`.  |
| `basePath` | yes      | Base path of the site, e.g. `/demo-minimal/`. Prefixed with `--base-url` if given.  |
| `root`     | no       | Fields of the root page, see [Page fields](#page-fields).                           |
| `pages`    | yes      | Subpages of the root page, see [Pages](#pages).                                     |

### Pages

`pages` (and `children`, see below) is a map of pages. The key of each page is used to reference the page in
[placeholders](#placeholders) and must be unique within the scenario. The key `root` is reserved for the root page.

A page with the key `storage` is required. It is used as storage folder for the tenant records (formats, structures,
metadata and Solr core) and should therefore be a folder (`doktype: 254`).

Pages are created in the order they are listed.

#### Page fields

Any field of the table `pages` can be set, e.g.:

| Field                       | Description                                                              |
|-----------------------------|--------------------------------------------------------------------------|
| `title`                     | Title of the page.                                                       |
| `doktype`                   | Page type, e.g. `254` for a folder. Defaults to `1` (standard page).     |
| `backend_layout`            | Backend layout of this page, e.g. `pagets__Default`.                     |
| `backend_layout_next_level` | Backend layout of all subpages, e.g. `pagets__Default`.                  |

Pages are visible (`hidden: 0`) unless specified otherwise. The root page is always marked as site root.

In addition, a page supports the following keys:

| Key        | Description                                                                 |
|------------|-----------------------------------------------------------------------------|
| `children` | Subpages of this page, in the same format as `pages`.                       |
| `content`  | List of content elements of this page, see [Content elements](#content-elements). |

### Content elements

Each entry of `content` is a content element. Content elements are created in the order they are listed within
each column.

Any field of the table `tt_content` can be set, e.g.:

| Field    | Description                                                                  |
|----------|------------------------------------------------------------------------------|
| `CType`  | Type of the content element, e.g. `dlf_search` for the Kitodo search plugin. |
| `colPos` | Column of the backend layout, e.g. `0` (Main) or `1` (Sidebar).               |
| `header` | Header shown above the content element.                                      |

In addition, a content element supports the following key:

| Key        | Description                                                                                                                                                                                  |
|------------|----------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------|
| `settings` | FlexForm values of the plugin without the `settings.` prefix, e.g. `targetPid` for `settings.targetPid`. Values not given are set to the defaults of the plugin's FlexForm, as when adding the plugin in the backend. |

The available settings of each plugin are defined in `Configuration/FlexForms/`.

### Placeholders

Field and setting values may contain placeholders, which are replaced after all pages and tenant records have been
created:

| Placeholder    | Replaced with                                              |
|----------------|------------------------------------------------------------|
| `{page:<key>}` | UID of the page with the given key, e.g. `{page:search}`.  |
| `{page:root}`  | UID of the root page.                                      |
| `{solrCore}`   | UID of the Solr core record in the storage folder.         |

## Site settings

The following site settings are written to the site configuration and are available as TypoScript constants:

| Setting                                | Value                              |
|----------------------------------------|------------------------------------|
| `plugin.tx_dlf.persistence.storagePid`  | UID of the page with key `storage` |
| `plugin.tx_dlf.persistence.solrCoreUid` | UID of the Solr core record        |
