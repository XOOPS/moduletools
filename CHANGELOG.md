# Changelog

All notable changes to this project will be documented in this file.

The format is based on Keep a Changelog and this project follows Semantic Versioning where practical.

## [Unreleased]

### Changed
- **Requires PHP 8.4+** (was 8.2). Rector PHP 8.4 + `xoops/rector-xoops` sets applied across `src/`
  (readonly value objects, constructor promotion, first-class callables, typed class constants).
- `xoops/xmf` constraint widened to `^1.3` — every XMF API the library calls exists in 1.3.1;
  XOOPS 2.8 sites still receive their bundled 1.5.
- `Common\LetterChoice` disables Smarty caching via `setCaching(\Smarty\Smarty::CACHING_OFF)`
  instead of writing the `$caching` property (Smarty 5).
- `Common\UpdateChecker::checkVerModule()` takes an optional `$repository` ("owner/repo") and
  honours a `github_repo` module-manifest entry before falling back to `XoopsModules25x/{dirname}`.
- `Common\ModuleFeedback` labels resolve `_CO_<MODULE>_*` consumer constants before the package
  catalog; `Common\ServerStats` loads the package catalog via `PackageLanguage` instead of the
  mTools module.
- `Common\Cloner::clone()` now throws `RuntimeException` on any unreadable/uncopyable entry
  instead of silently returning a partial clone.

### Fixed
- `Xmf\Module\Helper::getDirname()` does not exist; every call site now uses `dirname()`
  (Blocksadmin, Cloner, Confirm, LetterChoice, TestdataButtons, TestdataSample).
- `Object\DynamicObject::setErrors()` called a non-existent `setError()`.
- `Common\Blocksadmin`: `$newid` was undefined when `store()` failed; `render()` output was echoed twice.
- `Common\Cloner`: `sprintf()` on language constants that carry no placeholder.
- `Common\Db::cloneRecord()` passed a second argument to `fetchArray()`.
- `Admin\Export` passes `escape: ''` to `fputcsv()` (PHP 8.4 deprecation).
- `Common\VersionChecks`: by-reference assignment from a function call, dead constant fallbacks.
- Strict-type mismatches in `Confirm`, `Output` and `ObjectFormBuilder` form-element constructors.

### Added
- `Admin\Table` and `Common\Highlighter` restored from the 1.4.0 path-repository build.
- Standalone package tooling: `tests/bootstrap.php` and the package scripts resolve the local
  `vendor/autoload.php` (falling back to Core's `xoops_lib/vendor/`), `composer qa` gate
  (PHPCS, PHPStan level 6 with generated XOOPS core stubs, Rector dry-run, package checks, PHPUnit),
  CI on PHP 8.4/8.5.
- `resources/language/english/common.php` shipped (required by `PackageLanguage`).

## [0.0.1] — 2026-07-22

First internal pre-release. 

