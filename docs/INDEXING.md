# INDEXING

## Setup

Create tables:

    ```bash
    # create tables
        ./bin/console corepulse:setup
    # update with option `--update` or `-u`
        ./bin/console corepulse:setup -u
    ```

## Re-Generate

After updating or deteting object or document/page, automatically regenerate sitemap file with config in `corepulse`, see [example config](../config/pimcore/corepulse.yaml)

```bash
corepulse:
    event_listener:
        update_indexing: true # default false
        inspection_index: true # default false
```