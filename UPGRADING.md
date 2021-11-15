# Upgrading

## Upgrading from v4.0 to v4.1
If you were using the endpoint `timeout` options without customizing the used adapter like 

```
nelmio_solarium
    endpoints:
        default:
            timeout: 10
```

Then you need to use the new `adapter_timeout` option instead:

```
nelmio_solarium
    endpoints:
        default: ~
    clients:
        default:
            adapter_timeout: 10
```

If you were using the `adapter_class` option like

```
nelmio_solarium
    clients:
        default:
            adapter_class: 'SomeCustomAdapterClass'
```

Then you need to register your custom adapter as a service and use the new `adapter_service` option:

```
nelmio_solarium
    clients:
        default:
            adapter_service: 'my.custom.adapter.service'
```

**Both options `timeout` and `adapter_class` are not supported anymore when using Solarium 6.**

Note: using `adapter_timeout` together with `adapter_service` does not work. You need to configure the timeout accordingly on your adapter service then.

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
