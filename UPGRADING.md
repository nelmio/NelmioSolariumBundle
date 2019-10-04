# Upgrading

## Upgrading from v3.x to v4.x
From version 4 on this bundle requires Solarium 5. 
In case you were using a custom `path` option for endpoints you need to adjust it. 

See https://solarium.readthedocs.io/en/stable/getting-started/#pitfall-when-upgrading-from-earlier-versions-to-5x

## Upgrading from v2.x to v3.x

### Endpoint configuration changes (v3.0.0)
The `dsn` option has been removed from the `endpoints` configuration.
Here an example of the accepted options for the `endpoints` parameter:


```yaml
nelmio_solarium:
    endpoints:
        default:
            scheme: http
            host: localhost
            port: 8983
            path: /solr
            core: active
            timeout: 5
```
