# Remote fixture snapshots

The functional tests need METS documents that are normally fetched from
live servers. Fetching from the internet makes the test suite depend on
external availability and behaviour (downtime, anti-bot protection such
as Anubis, or changed documents). To keep the suite hermetic, copies of
the documents are stored here instead, and the test data sets point at
them through the local test web container (`http://web:8001`, which
serves the repository root).

## Layout

Paths mirror the origin URLs, one directory per remote host and record:

    <remote-host>/<origin-path>/<origin-filename>

e.g. `digital.slub-dresden.de/data/kitodo/10Kepi_476251419/10Kepi_476251419_mets.xml`
is a snapshot of
`https://digital.slub-dresden.de/data/kitodo/10Kepi_476251419/10Kepi_476251419_mets.xml`.

## Current snapshots (SLUB Digital)

| Record | Origin |
| ------ | ------ |
| 476251419 "10 Keyboard pieces - Go. S. 658" | https://digital.slub-dresden.de/data/kitodo/10Kepi_476251419/10Kepi_476251419_mets.xml |
| 476248086 "6 Sacred songs - Go. S. 591" | https://digital.slub-dresden.de/data/kitodo/6Saso_476248086/6Saso_476248086_mets.xml |
| 476251729 "6 Fugues - Go. S. 317" | https://digital.slub-dresden.de/data/kitodo/6FuG_476251729/6FuG_476251729_mets.xml |
| 351357262 "Auf der Suche nach Zukunft: Das Beispiel Pieschen" | https://digital.slub-dresden.de/data/kitodo/aufdesun_351357262/aufdesun_351357262_mets.xml |

Fetched 2026-09-18 (the SLUB server sits behind an Anubis anti-bot
challenge that plain HTTP clients cannot pass, so the files were
retrieved with a browser).

## References

The following data sets / tests use these snapshots:

- `Tests/Fixtures/Common/documents_1.csv`
- `Tests/Fixtures/Common/documents_fulltext.csv`
- `Tests/Fixtures/Hooks/documents.csv`
- `Tests/Fixtures/Controller/documents.csv`
- `Tests/Functional/Repository/DocumentRepositoryTest.php`

## Refreshing a snapshot

1. Fetch the current document from the origin (see table above), e.g.
   with a browser.
2. Replace the local file, keeping the path.
3. Re-run the functional tests
   (`Build/Test/runTests.sh -s functional`); content-sensitive tests
   (page counts, table of contents, titles) encode the document state
   and will fail if it has changed.
