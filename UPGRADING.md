# Upgrading

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
