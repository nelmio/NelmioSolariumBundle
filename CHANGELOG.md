# Changelog
All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](http://keepachangelog.com/en/1.0.0/)
and this project adheres to [Semantic Versioning](http://semver.org/spec/v2.0.0.html).

## [Unreleased]
### Added
### Changed
### Deprecated
### Fixed
 - Fixed deprecations from Solarium 4.2
### Security

## [v3.0.0-beta.2](https://github.com/nelmio/NelmioSolariumBundle/releases/tag/v3.0.0-beta.2) - 2018-08-13
### Added
 - Addded dupport for `solariumphp/solarium` v4.x

## [v3.0.0-beta](https://github.com/nelmio/NelmioSolariumBundle/releases/tag/v3.0.0-beta) - 2018-01-12
### Changed
 - Updated changelog to follow the "Keep a Changelog" format
 - Updated tests: use `::class` instead of FQDN, minor fixes
 - Removed support of `dsn` option for the `endpoint` parameter
### Fixed
 - Removed usage of deprecated Solarium\Core\Plugin\Plugin
 - Fixed compatibility with Symfony4 DataCollectorInterface
 - Fixed auto-wiring deprecation on Symfony 3.4

## [v2.4.0](https://github.com/nelmio/NelmioSolariumBundle/releases/tag/v2.4.0) - 2017-08-08
### Added
 - Added symfony4 to allowed versions
### Fixed
 - Fixed symfony3.2 WebProfiler compatibility issues

## [v2.3.0](https://github.com/nelmio/NelmioSolariumBundle/releases/tag/v2.3.0) - 2016-12-04
### Added
 - Added support for configuring solarium plugins
### Fixed
 - Fixed the data collector templates for the new symfony profiler

## [v2.2.1](https://github.com/nelmio/NelmioSolariumBundle/releases/tag/v2.2.1) - 2016-03-29
### Fixed
 - Fixed Symfony3 support (still requires solarium/solarium `^3.5@dev` until they release 3.5.2 or 3.6.0)
 - Fixed creating a new EventDispatcher for nothing, we now reuse the Symfony one

## [v2.2.0](https://github.com/nelmio/NelmioSolariumBundle/releases/tag/v2.2.0) - 2015-07-21
### Added
 - Added scheme config option for every endpoint
 - Bumped Solarium requirement to 3.3.0

## [v2.1.0](https://github.com/nelmio/NelmioSolariumBundle/releases/tag/v2.1.0) - 2014-10-21
### Added
 - Added client load balance config support
 - Added a ClientRegistry class
### Fixed
 - Fixed DataCollector / Profiler support

## [v2.0.4](https://github.com/nelmio/NelmioSolariumBundle/releases/tag/v2.0.4) - 2013-06-26
### Fixed
 - Fixed issue when a solr request was issued after one that failed
 - Fixed links in the Symfony Profiler

## [v2.0.3](https://github.com/nelmio/NelmioSolariumBundle/releases/tag/v2.0.3) - 2013-05-22
### Fixed
 - Fixed bug in Symfony Profiler support

## [v2.0.2](https://github.com/nelmio/NelmioSolariumBundle/releases/tag/v2.0.2) - 2013-05-03
### Added
 - Added support for user:pass@host in dsn configuration
### Fixed
 - Fixed bug in Symfony 2.1 / 2.2 support

## [v2.0.1](https://github.com/nelmio/NelmioSolariumBundle/releases/tag/v2.0.1) - 2013-04-07
### Fixed
 - Fix bug in Monolog support with Symfony 2.1

## [v2.0.0](https://github.com/nelmio/NelmioSolariumBundle/releases/tag/v2.0.0) - 2013-03-27
### Added
 - Added support for defining multiple endpoints
 - Added Monolog support
 - Added Stopwatch data for the Symfony Profiler
### Changed
 - Migrated to Solarium 3.x, use the 1.x version of this bundle if you want to keep using Solarium 2.x

## [v1.1.0](https://github.com/nelmio/NelmioSolariumBundle/releases/tag/v1.1.0) - 2013-01-07
### Added
 - Added data collector for the Symfony Profiler and debug toolbar
 - Added support for defining multiple clients
 - Added dsn option to configure clients in one line

## [v1.0.0](https://github.com/nelmio/NelmioSolariumBundle/releases/tag/v1.0.0) - 2011-07-31
 - Initial release
