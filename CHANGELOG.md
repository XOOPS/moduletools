# Changelog

All notable changes to this project will be documented in this file.

The format is based on Keep a Changelog and this project follows Semantic Versioning where practical.

## [Unreleased]

### Changed
- `Common\ObjectTree::makeSelBox()` preserves its 1.x options-array result and delegates to
  `makeOptionsArray()`. Use core's `XoopsObjectTree` for HTML select rendering.
- `Form\ObjectFormBuilder::build()` / `Object\DynamicObject::getForm()`: `$cancelAction` is now a
  local URL (or `history.back()`), emitted as a fixed `location.href = "..."` assignment. It is no
  longer executed as a JavaScript statement; a scheme, host or control character falls back to
  the current script.
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
- `Common\Cloner::isValidDirname()` accepts letters, digits and underscore only: a hyphen became a
  `namespace XoopsModules\My-news;` in the clone, which no file could parse.
- `Admin\ObjectController::storeFromDefaultForm()`, `handleObjectDeletion()` and
  `handleObjectDeletionFromUserSide()` take an optional `$authorize` callback (ownership /
  permission of the posted id). A user-side deletion without one falls back to
  `DynamicObject::accessGranted()` for the operation and otherwise refuses.
- `Common\Paginator::$queryStr` is URL-encoded (RFC 3986) instead of entity-encoded; page hrefs
  are HTML-escaped once at emission, so `q=a%26b` survives the round trip.
- `Common\Blocksadmin`: every write path (`orderBlock()`, `updateBlock()`, `isBlockCloned()`,
  `deleteBlock()`) validates the XOOPS token itself and fails closed; the delete link carries a
  token. A dispatcher must not call `$xoopsSecurity->check()` before delegating.

### Fixed
- HTML truncation preserves short entity-encoded input and bounds the ending for short limits.
- ModuleTools 1.5.0 advertises API 1.1.0 so consumers can require its added public surface.
- API compatibility checks reject stale approvals; accepting a baseline clears approvals only after saving succeeds.
- CSV export neutralizes line-feed-prefixed cells; dependency review uses the verified v5.0.0 action commit.
- Row cloning preserves SQL NULL; enum metadata is scoped to the active database.
- HTML truncation recognizes complete entities and counts multibyte offsets correctly.
- Update checks use GitHub's latest stable release and compare tags with a leading v correctly.
- `Common\Blocksadmin::isBlockCloned()` casts the request-supplied module and group ids before
  building the INSERT statements; `updateBlock()` deletes only the block's own `block_read`
  permission rows instead of every row sharing the item id.
- `Common\Highlighter` matches on the raw text and escapes each piece afterwards, so terms
  containing `&`, quotes or `<` are found, and a term such as `amp` can no longer split an entity.
- `Common\Blocksadmin::deleteBlock()` was reachable from a plain GET link with no token (CSRF);
  it now also refuses module/system (`M`/`S`) blocks server-side and removes the block's
  `block_read` permission rows and template rows instead of leaving them orphaned.
- `Admin\ObjectTable`: a `filter_<key>` value went into `Criteria` unescaped (SQL injection on
  user-side tables); only a value the handler's option method offered is accepted, escaped.
- `Form\ObjectFormBuilder`: values were taken with `getVar('e')`, which is raw for URL, EMAIL,
  OTHER, FLOAT, ENUM and time fields, and `XoopsFormText` emits its value verbatim (stored XSS in
  edit forms). Text controls now receive the stored value escaped once; option controls receive
  the raw value so an option key containing `&`, quotes or `<` is still pre-selected.
- `Admin\ObjectController::postDataToObject()` no longer writes fields hidden from the form or
  marked read-only (mass assignment), skips an array posted for a scalar field (`strlen(array)`
  TypeError in core), and validates `redirect_page` / the referer as on-site targets.
- `Admin\ObjectTable`, `Admin\SingleView`, `Admin\Export` and `DynamicObject::getValueFor()`
  double-encoded TXTBOX values and showed TXTAREA HTML literally while leaving URL/EMAIL/OTHER
  values unescaped; one rule now applies (`Internal\Presentation\ObjectValuePresenter`).
  A column/row value method returns presentation HTML in both the table and the single view.
- `Common\Confirm` emitted hidden-field values unescaped.
- `Common\Breadcrumb::render()` used `require` on `class/template.php`, redeclaring `XoopsTpl`.
- `Common\VersionChecks` threw `Undefined constant` on the failure path: the consumer's
  `_AM_<MODULE>_ERROR_BAD_*` / `_CO_<MODULE>_ERROR_BAD_*` constant is used first, then the package
  catalog (now loaded here), then a literal.
- `Common\DirectoryChecker` / `Common\FileChecker`: with an explicit base path a `..` segment in a
  not-yet-existing tail passed the containment check; `setDirectoryPermissions()` chmod-ed files and
  `setFilePermissions()` directories; `handleRequest()` did not require an administrator; a `0777`
  argument was compared raw while the button applied `0755`, so the page kept offering the button.
- `Common\Cloner::handleAdminRequest()` requires an administrator and exits after every redirect.
- `Admin\Export::csvCell()` exempted every numeric string from the formula prefix, including `+1`;
  only a negative number keeps its sign.
- `Permission\ItemPermission::grantedItems()` / `isGranted()` with an empty or invalid group list
  reached core with no group predicate (fail open); they now return `[]` / `false`.
- `Common\TestdataSample` looked for `<language>/` inside the library folder, so non-English sample
  data never loaded; a skipped column left `INSERT INTO t (\`a\`, , \`c\`)`.
- `Common\LetterChoice::render()` overwrote the last letter with the "Other" entry (Z vanished),
  hard-coded `?init=Other`, and ignored the `$alphabet` constructor argument.
- `Internal\Confirmation\ConfirmationResolver` threw when a module defined `_DELETE_CONFIRM` but
  not `_DELETE_LABEL`.
- `Object\DynamicObject`: core's `XOBJ_DTYPE_FLOAT` was remapped to OTHER (dropping the float cast);
  `setErrors()` flattened an array of upload errors to "Array".
- `Common\Output::selectSorting()` threw a TypeError when the page kept `$start` local.
- `Internal\Tools\ConsumerBridgeGenerator`: a table closed by `) DEFAULT CHARSET=… ENGINE=…;` or
  `) AUTO_INCREMENT=1;` never closed and swallowed the next table; `INDEX`/`SPATIAL`/`CHECK` lines
  became columns; a primary key without explicit `NOT NULL` became `?int $id = null` (so `isNew()`
  disagreed with `save()`); an inline `PRIMARY KEY` was not detected; the module dirname and
  namespace are validated as identifiers before being written into generated PHP; a column named
  `this` and a table starting with a digit produced uncompilable code; the smoke script defined the
  `XOBJ_DTYPE_*` constants with the wrong values.
- `Common\UpdateChecker` cached the answer without the installed version, so the "new version"
  banner survived an update for up to an hour.
- `Module\Installer::dropModuleTables()` validates and quotes the table names; `Module\ModuleContext`
  refuses an empty dirname (it defined bare `_URL`/`_PATH`/`_ADMIN` constants);
  `Common\FilesManagement::xcopy()` no longer recreates symlinks with an unchecked target.
- `Common\Highlighter` marks the longest matching term (`abc` over `ab`).
- `composer.json` declares `ext-ctype` and `ext-tokenizer`; the gate generators write LF regardless
  of platform and the `--check` comparison ignores CRLF.
- `Common\Blocksadmin::cloneBlock()` casts the request-supplied module and group ids before
  building the INSERT statements.
- `Common\Highlighter` escapes each search term the same way as the text before matching, so
  terms containing `&`, quotes or `<` are found and marked intact.
- `Common\TestdataSample::loadData()` / `saveData()` / `clearData()` require an administrator
  session and a valid XOOPS token (`TestdataButtons::isAuthorizedRequest()`); a consumer's
  `testdata/index.php` can no longer run them unguarded.
- `Common\Paginator` HTML-escapes the generated page links; `PHP_SELF` and `urlOther` could
  break out of the `href` attribute.
- `Common\Db::enumerate()` validates the table name like its siblings and parses ENUM/SET
  values correctly (`Db::enumValues()`); it previously returned only a fragment of the first value.
- `Common\VersionChecks` returns `false` when the module cannot be resolved instead of
  dereferencing the `false` from `XoopsModule::getByDirname()`.
- `Common\VersionChecks::checkVerXoops()` / `checkVerPhp()` accept the legacy `false` from
  `XoopsModule::getByDirname()` again instead of throwing a `TypeError`.
- `Common\ObjectTree` no longer installs a placeholder class when loaded before XOOPS is
  bootstrapped; a placeholder loaded once would have shadowed the real class for the whole process.
- `Persistence\PersistableHandler::setGrantedObjectsCriteria()` always adds a key restriction: with
  no grants or no module context the criteria now match nothing instead of every row. Conditions
  already in the criteria are grouped first so an OR among them cannot bypass the restriction.
- `Admin\ObjectController::storeFromDefaultForm()` no longer persists or redirects after a
  rejected upload, and returns false when a permission update fails.
- `Common\DirectoryChecker` form action and redirect accept only local paths (`javascript:` and
  `data:` schemes were passing). The shared check is `Common\Output::localPath()`.
- `Common\Cloner::clone()` removes its partial target when copying fails, and skips symlinks.
- `Internal\Tools\ConsumerBridgeGenerator`: generated repositories allow only `ASC`/`DESC` as
  the ORDER BY direction.
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
