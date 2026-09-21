# ModuleTools promotion backlog

> Generated from unresolved type-level destination records.

## Xoops\ModuleTools\Admin\Export

- Target: `none` / `none` (not-applicable).
- Blocker: Frozen XOOPS 2.x compatibility surface.
- Required evidence: behavioral parity corpus plus an explicit stable owner.

## Xoops\ModuleTools\Admin\ObjectColumn

- Target: `xoops/xmf` / `Xmf\Presentation\DataTable` (experimental).
- Blocker: Keep frozen until the XMF presentation contract is stable.
- Required evidence: behavioral parity corpus plus an explicit stable owner.

## Xoops\ModuleTools\Admin\ObjectController

- Target: `xoops/xmf` / `Xmf\Presentation\DataTable` (experimental).
- Blocker: Keep frozen until the XMF presentation contract is stable.
- Required evidence: behavioral parity corpus plus an explicit stable owner.

## Xoops\ModuleTools\Admin\ObjectRow

- Target: `xoops/xmf` / `Xmf\Presentation\DataTable` (experimental).
- Blocker: Keep frozen until the XMF presentation contract is stable.
- Required evidence: behavioral parity corpus plus an explicit stable owner.

## Xoops\ModuleTools\Admin\ObjectTable

- Target: `xoops/xmf` / `Xmf\Presentation\DataTable` (experimental).
- Blocker: Keep frozen until the XMF presentation contract is stable.
- Required evidence: behavioral parity corpus plus an explicit stable owner.

## Xoops\ModuleTools\Admin\SingleView

- Target: `xoops/xmf` / `Xmf\Presentation\DataTable` (experimental).
- Blocker: Keep frozen until the XMF presentation contract is stable.
- Required evidence: behavioral parity corpus plus an explicit stable owner.

## Xoops\ModuleTools\Admin\Table

- Target: `none` / `none` (not-applicable).
- Blocker: Frozen XOOPS 2.x compatibility surface.
- Required evidence: behavioral parity corpus plus an explicit stable owner.

## Xoops\ModuleTools\Admin\TreeTable

- Target: `xoops/xmf` / `Xmf\Presentation\DataTable` (experimental).
- Blocker: Keep frozen until the XMF presentation contract is stable.
- Required evidence: behavioral parity corpus plus an explicit stable owner.

## Xoops\ModuleTools\Bootstrap

- Target: `none` / `none` (not-applicable).
- Blocker: Frozen XOOPS 2.x compatibility surface.
- Required evidence: behavioral parity corpus plus an explicit stable owner.

## Xoops\ModuleTools\Common\Blocksadmin

- Target: `none` / `none` (not-applicable).
- Blocker: Frozen XOOPS 2.x compatibility surface.
- Required evidence: behavioral parity corpus plus an explicit stable owner.

## Xoops\ModuleTools\Common\Breadcrumb

- Target: `xoops/smartyextensions` / `none` (missing).
- Blocker: Installed presentation helpers do not expose an exact equivalent API.
- Required evidence: behavioral parity corpus plus an explicit stable owner.

## Xoops\ModuleTools\Common\Cloner

- Target: `none` / `none` (not-applicable).
- Blocker: Frozen XOOPS 2.x compatibility surface.
- Required evidence: behavioral parity corpus plus an explicit stable owner.

## Xoops\ModuleTools\Common\Configurator

- Target: `none` / `none` (not-applicable).
- Blocker: Frozen XOOPS 2.x compatibility surface.
- Required evidence: behavioral parity corpus plus an explicit stable owner.

## Xoops\ModuleTools\Common\Confirm

- Target: `none` / `none` (not-applicable).
- Blocker: Frozen XOOPS 2.x compatibility surface.
- Required evidence: behavioral parity corpus plus an explicit stable owner.

## Xoops\ModuleTools\Common\Db

- Target: `xoops/xmf` / `Xmf\Repository\RepositoryInterface` (stable).
- Blocker: Static database helpers remain compatibility-only; repositories own new persistence.
- Required evidence: behavioral parity corpus plus an explicit stable owner.

## Xoops\ModuleTools\Common\DbInterface

- Target: `xoops/xmf` / `Xmf\Repository\RepositoryInterface` (stable).
- Blocker: Static database helpers remain compatibility-only; repositories own new persistence.
- Required evidence: behavioral parity corpus plus an explicit stable owner.

## Xoops\ModuleTools\Common\Highlighter

- Target: `none` / `none` (not-applicable).
- Blocker: Frozen XOOPS 2.x compatibility surface.
- Required evidence: behavioral parity corpus plus an explicit stable owner.

## Xoops\ModuleTools\Common\ImageResizerInterface

- Target: `xoops/xmf` / `Xmf\Media\Storage` (missing).
- Blocker: No proven stable XMF media/storage contract currently matches this surface.
- Required evidence: behavioral parity corpus plus an explicit stable owner.

## Xoops\ModuleTools\Common\LetterChoice

- Target: `xoops/smartyextensions` / `none` (missing).
- Blocker: Installed presentation helpers do not expose an exact equivalent API.
- Required evidence: behavioral parity corpus plus an explicit stable owner.

## Xoops\ModuleTools\Common\Media\MediaUploadRequest

- Target: `xoops/xmf` / `Xmf\Media\Storage` (missing).
- Blocker: No proven stable XMF media/storage contract currently matches this surface.
- Required evidence: behavioral parity corpus plus an explicit stable owner.

## Xoops\ModuleTools\Common\ModuleConfig

- Target: `none` / `none` (not-applicable).
- Blocker: Frozen XOOPS 2.x compatibility surface.
- Required evidence: behavioral parity corpus plus an explicit stable owner.

## Xoops\ModuleTools\Common\ModuleFeedback

- Target: `xoops/xmf` / `Xmf\Bridge\HandlerToRepositoryBridge` (stable).
- Blocker: XOOPS 2.x object compatibility; consumers use the 4.0 bridge sequence.
- Required evidence: behavioral parity corpus plus an explicit stable owner.

## Xoops\ModuleTools\Common\ModuleStats

- Target: `none` / `none` (not-applicable).
- Blocker: Retained until an exact stable owner and parity corpus exist.
- Required evidence: behavioral parity corpus plus an explicit stable owner.

## Xoops\ModuleTools\Common\ObjectTree

- Target: `xoops/xmf` / `Xmf\Bridge\HandlerToRepositoryBridge` (stable).
- Blocker: XOOPS 2.x object compatibility; consumers use the 4.0 bridge sequence.
- Required evidence: behavioral parity corpus plus an explicit stable owner.

## Xoops\ModuleTools\Common\Output

- Target: `none` / `none` (not-applicable).
- Blocker: Frozen XOOPS 2.x compatibility surface.
- Required evidence: behavioral parity corpus plus an explicit stable owner.

## Xoops\ModuleTools\Common\PaginationState

- Target: `xoops/xmf` / `Xmf\Pagination\PaginatedResult` (stable).
- Blocker: The parity corpus documents non-equivalence (zero pages, bands, SQL and rendering); no shim yet.
- Required evidence: behavioral parity corpus plus an explicit stable owner.

## Xoops\ModuleTools\Common\PaginationStateInterface

- Target: `xoops/xmf` / `Xmf\Pagination\PaginatedResult` (stable).
- Blocker: The parity corpus documents non-equivalence (zero pages, bands, SQL and rendering); no shim yet.
- Required evidence: behavioral parity corpus plus an explicit stable owner.

## Xoops\ModuleTools\Common\Paginator

- Target: `xoops/xmf` / `Xmf\Pagination\PaginatedResult` (stable).
- Blocker: The parity corpus documents non-equivalence (zero pages, bands, SQL and rendering); no shim yet.
- Required evidence: behavioral parity corpus plus an explicit stable owner.

## Xoops\ModuleTools\Common\ResizeRequest

- Target: `xoops/xmf` / `Xmf\Media\Storage` (missing).
- Blocker: No proven stable XMF media/storage contract currently matches this surface.
- Required evidence: behavioral parity corpus plus an explicit stable owner.

## Xoops\ModuleTools\Common\ResizeResult

- Target: `xoops/xmf` / `Xmf\Media\Storage` (missing).
- Blocker: No proven stable XMF media/storage contract currently matches this surface.
- Required evidence: behavioral parity corpus plus an explicit stable owner.

## Xoops\ModuleTools\Common\Resizer

- Target: `xoops/xmf` / `Xmf\Media\Storage` (missing).
- Blocker: No proven stable XMF media/storage contract currently matches this surface.
- Required evidence: behavioral parity corpus plus an explicit stable owner.

## Xoops\ModuleTools\Common\ServerStats

- Target: `none` / `none` (not-applicable).
- Blocker: Retained until an exact stable owner and parity corpus exist.
- Required evidence: behavioral parity corpus plus an explicit stable owner.

## Xoops\ModuleTools\Common\SocialBookmarks

- Target: `xoops/smartyextensions` / `none` (missing).
- Blocker: Installed presentation helpers do not expose an exact equivalent API.
- Required evidence: behavioral parity corpus plus an explicit stable owner.

## Xoops\ModuleTools\Common\StandardConfig

- Target: `none` / `none` (not-applicable).
- Blocker: Frozen XOOPS 2.x compatibility surface.
- Required evidence: behavioral parity corpus plus an explicit stable owner.

## Xoops\ModuleTools\Common\SysUtility

- Target: `none` / `none` (not-applicable).
- Blocker: Frozen XOOPS 2.x compatibility surface.
- Required evidence: behavioral parity corpus plus an explicit stable owner.

## Xoops\ModuleTools\Common\TestdataButtons

- Target: `xoops/xmf` / `Xmf\Module\Testdata` (provisional).
- Blocker: The target is absent from XMF 1.5 and must be proven in the PHP 8.4 tree.
- Required evidence: behavioral parity corpus plus an explicit stable owner.

## Xoops\ModuleTools\Common\TestdataSample

- Target: `xoops/xmf` / `Xmf\Module\Testdata` (provisional).
- Blocker: The target is absent from XMF 1.5 and must be proven in the PHP 8.4 tree.
- Required evidence: behavioral parity corpus plus an explicit stable owner.

## Xoops\ModuleTools\Common\Text

- Target: `none` / `none` (not-applicable).
- Blocker: Retained until an exact stable owner and parity corpus exist.
- Required evidence: behavioral parity corpus plus an explicit stable owner.

## Xoops\ModuleTools\Common\TextInterface

- Target: `none` / `none` (not-applicable).
- Blocker: Retained until an exact stable owner and parity corpus exist.
- Required evidence: behavioral parity corpus plus an explicit stable owner.

## Xoops\ModuleTools\Common\UpdateChecker

- Target: `none` / `none` (not-applicable).
- Blocker: Frozen XOOPS 2.x compatibility surface.
- Required evidence: behavioral parity corpus plus an explicit stable owner.

## Xoops\ModuleTools\Common\VersionChecks

- Target: `none` / `none` (not-applicable).
- Blocker: Frozen XOOPS 2.x compatibility surface.
- Required evidence: behavioral parity corpus plus an explicit stable owner.

## Xoops\ModuleTools\Constants

- Target: `none` / `none` (not-applicable).
- Blocker: Frozen XOOPS 2.x compatibility surface.
- Required evidence: behavioral parity corpus plus an explicit stable owner.

## Xoops\ModuleTools\Form\ObjectFormBuilder

- Target: `xoops/xmf` / `Xmf\Presentation\DataTable` (experimental).
- Blocker: Keep frozen until the XMF presentation contract is stable.
- Required evidence: behavioral parity corpus plus an explicit stable owner.

## Xoops\ModuleTools\Module\ConsumerRuntime

- Target: `none` / `none` (not-applicable).
- Blocker: Frozen XOOPS 2.x compatibility surface.
- Required evidence: behavioral parity corpus plus an explicit stable owner.

## Xoops\ModuleTools\Module\Dependency

- Target: `none` / `none` (not-applicable).
- Blocker: Frozen XOOPS 2.x compatibility surface.
- Required evidence: behavioral parity corpus plus an explicit stable owner.

## Xoops\ModuleTools\Module\Installer

- Target: `none` / `none` (not-applicable).
- Blocker: Frozen XOOPS 2.x compatibility surface.
- Required evidence: behavioral parity corpus plus an explicit stable owner.

## Xoops\ModuleTools\Object\DynamicObject

- Target: `xoops/xmf` / `Xmf\Bridge\HandlerToRepositoryBridge` (stable).
- Blocker: XOOPS 2.x object compatibility; consumers use the 4.0 bridge sequence.
- Required evidence: behavioral parity corpus plus an explicit stable owner.

## Xoops\ModuleTools\Object\SeoObject

- Target: `xoops/xmf` / `Xmf\Bridge\HandlerToRepositoryBridge` (stable).
- Blocker: XOOPS 2.x object compatibility; consumers use the 4.0 bridge sequence.
- Required evidence: behavioral parity corpus plus an explicit stable owner.

## Xoops\ModuleTools\Permission\ItemPermission

- Target: `xoops/xmf` / `Xmf\Permissions\CoreGroupPermissionGateway` (provisional).
- Blocker: The local adapter is retained until upstream BC and write semantics are proven.
- Required evidence: behavioral parity corpus plus an explicit stable owner.

## Xoops\ModuleTools\Permission\PermissionGatewayInterface

- Target: `xoops/xmf` / `Xmf\Permissions\CoreGroupPermissionGateway` (provisional).
- Blocker: The local adapter is retained until upstream BC and write semantics are proven.
- Required evidence: behavioral parity corpus plus an explicit stable owner.

## Xoops\ModuleTools\Permission\XoopsPermissionGateway

- Target: `xoops/xmf` / `Xmf\Permissions\CoreGroupPermissionGateway` (provisional).
- Blocker: The local adapter is retained until upstream BC and write semantics are proven.
- Required evidence: behavioral parity corpus plus an explicit stable owner.

## Xoops\ModuleTools\Persistence\PersistableHandler

- Target: `xoops/xmf` / `Xmf\Bridge\HandlerToRepositoryBridge` (stable).
- Blocker: XOOPS 2.x object compatibility; consumers use the 4.0 bridge sequence.
- Required evidence: behavioral parity corpus plus an explicit stable owner.

## Xoops\ModuleTools\Persistence\PersistableHandler2

- Target: `xoops/xmf` / `Xmf\Bridge\HandlerToRepositoryBridge` (stable).
- Blocker: XOOPS 2.x object compatibility; consumers use the 4.0 bridge sequence.
- Required evidence: behavioral parity corpus plus an explicit stable owner.

## Xoops\ModuleTools\Utility

- Target: `none` / `none` (not-applicable).
- Blocker: Frozen XOOPS 2.x compatibility surface.
- Required evidence: behavioral parity corpus plus an explicit stable owner.

