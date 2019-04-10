## 2.4.1 (not released yet)
  * [backport] Update Logger.php to support SF 4.x
  * [backport] Fix autowiring deprecation on Symfony 3.3

## 2.4.0 (2017-08-17)

 * Added symfony4 to allowed versions
 * Fixed symfony3.2 WebProfiler compatibility issues

## 2.3.0 (2016-12-04)
  * Added support for configuring solarium plugins
  * Fixed the data collector templates for the new symfony profiler

## 2.2.1 (2016-03-29)

  * Fixed Symfony3 support (still requires solarium/solarium `^3.5@dev` until they release 3.5.2 or 3.6.0)
  * Fixed creating a new EventDispatcher for nothing, we now reuse the Symfony one

## 2.2.0 (2015-07-21)

  * Added scheme config option for every endpoint
  * Bumped Solarium requirement to 3.3.0

## 2.1.0 (2014-10-21)

  * Added client load balance config support
  * Added a ClientRegistry class
  * Fixed DataCollector / Profiler support

## 2.0.4 (2013-06-26)

  * Fixed issue when a solr request was issued after one that failed
  * Fixed links in the Symfony Profiler

## 2.0.3 (2013-05-22)

  * Fixed bug in Symfony Profiler support

## 2.0.2 (2013-05-03)

  * Fixed bug in Symfony 2.1 / 2.2 support
  * Added support for user:pass@host in dsn configuration

## 2.0.1 (2013-04-07)

  * Fix bug in Monolog support with Symfony 2.1

## 2.0.0 (2013-03-27)

  * Migrated to Solarium 3.x, use the 1.x version of this bundle if you want to keep using Solarium 2.x
  * Added support for defining multiple endpoints
  * Added Monolog support
  * Added Stopwatch data for the Symfony Profiler

## 1.1.0 (2013-01-07)

  * Added data collector for the Symfony Profiler and debug toolbar
  * Added support for defining multiple clients
  * Added dsn option to configure clients in one line

## 1.0.0 (2011-07-31)

  * Initial release
