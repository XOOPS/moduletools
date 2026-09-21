<?php

declare(strict_types=1);

$compat = static fn (array $overrides = []): array => array_replace([
    'disposition'       => 'retain-compat',
    'qualifier'         => '',
    'coupling'          => ['xoops-runtime'],
    'target_package'    => 'none',
    'target_symbol'     => 'none',
    'target_min_version' => 'none',
    'target_owner'      => 'none',
    'target_status'     => 'not-applicable',
    'availability'      => 'not-applicable',
    'migration'         => ['none-yet'],
    'rector_rules'      => [],
    'rector_config_template' => '',
    'migration_safety'  => 'manual',
    'capability_profile' => '',
    'parity_test'       => 'missing',
    'upstream_issue'    => '',
    'introduced_version' => '',
    'not_before_release' => '',
    'next_review_release' => '',
    'notes'             => 'Frozen XOOPS 2.x compatibility surface.',
], $overrides);

$decisions = [];

$decisions['Admin\\Utility'] = $compat([
    'qualifier'          => 'legacy-collapsible-adapter',
    'coupling'           => ['xoops-runtime', 'presentation-html'],
    'target_package'     => 'xoops/smartyextensions',
    'target_symbol'      => 'none',
    'target_min_version' => 'none',
    'target_owner'       => 'XOOPS admin presentation maintainers',
    'target_status'      => 'missing',
    'availability'       => 'not-applicable',
    'migration'          => ['none-yet'],
    'parity_test'        => 'tests/Admin/UtilityTest.php',
    'notes'              => 'Escaped compatibility output for legacy Smart-module collapsible admin sections.',
]);

foreach ([
    'Admin\\ObjectColumn', 'Admin\\ObjectController', 'Admin\\ObjectRow', 'Admin\\ObjectTable',
    'Admin\\SingleView', 'Admin\\TreeTable', 'Form\\ObjectFormBuilder',
] as $symbol) {
    $decisions[$symbol] = $compat([
        'qualifier'          => 'then-migrate',
        'coupling'           => ['xoops-runtime', 'xoopsobject-handler', 'request-global', 'presentation-html'],
        'target_package'     => 'xoops/xmf',
        'target_symbol'      => 'Xmf\\Presentation\\DataTable',
        'target_min_version' => '2.0',
        'target_owner'       => 'XMF presentation and repository maintainers',
        'target_status'      => 'experimental',
        'availability'       => 'migrate-on-4.0',
        'migration'          => ['adapter', 'manual-recipe'],
        'notes'              => 'Keep frozen until the XMF presentation contract is stable.',
    ]);
}

foreach ([
    'Object\\DynamicObject', 'Object\\SeoObject', 'Persistence\\PersistableHandler',
    'Persistence\\PersistableHandler2', 'Common\\ObjectTree', 'Common\\ModuleFeedback',
] as $symbol) {
    $decisions[$symbol] = $compat([
        'coupling'           => ['xoops-runtime', 'xoopsobject-handler'],
        'target_package'     => 'xoops/xmf',
        'target_symbol'      => 'Xmf\\Bridge\\HandlerToRepositoryBridge',
        'target_min_version' => '2.0',
        'target_owner'       => 'XMF bridge and repository maintainers',
        'target_status'      => 'stable',
        'availability'       => 'migrate-on-4.0',
        'migration'          => ['adapter', 'manual-recipe'],
        'notes'              => 'XOOPS 2.x object compatibility; consumers use the 4.0 bridge sequence.',
    ]);
}

foreach (['Common\\Db', 'Common\\DbInterface'] as $symbol) {
    $decisions[$symbol] = $compat([
        'coupling'           => ['xoops-runtime', 'xoopsobject-handler'],
        'target_package'     => 'xoops/xmf',
        'target_symbol'      => 'Xmf\\Repository\\RepositoryInterface',
        'target_min_version' => '2.0',
        'target_owner'       => 'XMF repository maintainers and module domain owners',
        'target_status'      => 'stable',
        'availability'       => 'migrate-on-4.0',
        'migration'          => ['adapter', 'manual-recipe'],
        'notes'              => 'Static database helpers remain compatibility-only; repositories own new persistence.',
    ]);
}

foreach (['Common\\Text', 'Common\\TextInterface', 'Common\\ServerStats', 'Common\\ModuleStats'] as $symbol) {
    $decisions[$symbol] = $compat([
        'qualifier' => 'find-owner',
        'coupling'  => str_contains($symbol, 'Text') ? ['pure-php'] : ['xoops-runtime'],
        'notes'     => 'Retained until an exact stable owner and parity corpus exist.',
    ]);
}

foreach (['Common\\Paginator', 'Common\\PaginationState', 'Common\\PaginationStateInterface'] as $symbol) {
    $decisions[$symbol] = $compat([
        'disposition'        => 'migrate-to-target',
        'qualifier'          => 'parity-blocked',
        'coupling'           => 'Common\\Paginator' === $symbol
            ? ['request-global', 'presentation-html'] : ['pure-php'],
        'target_package'     => 'xoops/xmf',
        'target_symbol'      => 'Xmf\\Pagination\\PaginatedResult',
        'target_min_version' => '1.5',
        'target_owner'       => 'XMF pagination maintainers',
        'target_status'      => 'stable',
        'availability'       => 'delegate-now',
        'migration'          => ['manual-recipe'],
        'parity_test'        => 'missing',
        'notes'              => 'The parity corpus documents non-equivalence (zero pages, bands, SQL and rendering); no shim yet.',
    ]);
}

foreach ([
    'Common\\Resizer', 'Common\\ImageResizerInterface', 'Common\\ResizeRequest',
    'Common\\ResizeResult', 'Common\\Media\\MediaUploadRequest',
] as $symbol) {
    $decisions[$symbol] = $compat([
        'disposition'        => 'blocked-on-upstream',
        'qualifier'          => 'provisional-adapter',
        'coupling'           => ['xoops-runtime'],
        'target_package'     => 'xoops/xmf',
        'target_symbol'      => 'Xmf\\Media\\Storage',
        'target_min_version' => '2.0',
        'target_owner'       => 'XMF media and storage maintainers',
        'target_status'      => 'missing',
        'availability'       => 'migrate-on-4.0',
        'migration'          => ['none-yet'],
        'notes'              => 'No proven stable XMF media/storage contract currently matches this surface.',
    ]);
}

foreach ([
    'Permission\\PermissionGatewayInterface', 'Permission\\XoopsPermissionGateway',
    'Permission\\ItemPermission',
] as $symbol) {
    $decisions[$symbol] = $compat([
        'disposition'        => 'blocked-on-upstream',
        'qualifier'          => 'provisional-adapter',
        'coupling'           => ['xoops-runtime'],
        'target_package'     => 'xoops/xmf',
        'target_symbol'      => 'Xmf\\Permissions\\CoreGroupPermissionGateway',
        'target_min_version' => '1.5',
        'target_owner'       => 'XMF permissions maintainers',
        'target_status'      => 'provisional',
        'availability'       => 'delegate-now',
        'migration'          => ['adapter', 'manual-recipe', 'rector-rule'],
        'rector_rules'       => ['Xoops\\Rector\\Rules\\ConfiguredPermissionGatewayRector'],
        'rector_config_template' => 'docs/XMF-MODERNIZATION.md#permission-families',
        'migration_safety'   => 'configured',
        'capability_profile' => '1.5',
        'parity_test'        => 'tests/Permission/ItemPermissionTest.php',
        'notes'              => 'The local adapter is retained until upstream BC and write semantics are proven.',
    ]);
}

$decisions['Module\\ModuleContext'] = $compat([
    'disposition'        => 'promote',
    'qualifier'          => 'deprecated-facade',
    'coupling'           => ['xoops-runtime'],
    'target_package'     => 'xoops/helpers',
    'target_symbol'      => 'Xoops\\Helpers\\Service\\Path',
    'target_min_version' => '1.0',
    'target_owner'       => 'XOOPS Helpers maintainers',
    'target_status'      => 'stable',
    'availability'       => 'delegate-now',
    'migration'          => ['delegation', 'manual-recipe', 'rector-rule'],
    'rector_rules'       => [
        'Xoops\\Rector\\Rules\\ModuleConfigToHelperRector',
        'Xoops\\Rector\\Rules\\ModulePathUrlToHelperRector',
    ],
    'rector_config_template' => 'bin/xoops-module-profile.php',
    'migration_safety'   => 'configured',
    'capability_profile' => '1.5',
    'parity_test'        => 'tests/Migration/ModuleContextDelegationTest.php',
    'notes'              => 'Facade delegates path, URL and config operations to Helpers services.',
]);

$decisions['Module\\NamespaceAutoloader'] = $compat([
    'qualifier'   => 'transitional-autoloader',
    'coupling'    => ['pure-php'],
    'parity_test' => 'tests/Module/NamespaceAutoloaderTest.php',
    'notes'       => 'XOOPS 2.x bridge for namespaced modules that still retain legacy class filenames; Composer-native module packaging supersedes it after migration.',
]);

foreach (['Common\\DirectoryChecker', 'Common\\FileChecker', 'Common\\FilesManagement'] as $symbol) {
    $decisions[$symbol] = $compat([
        // DirectoryChecker also renders admin action forms and redirects (handleRequest), so it stays compat.
        'disposition'        => 'Common\\DirectoryChecker' === $symbol ? 'retain-compat' : 'promote',
        'qualifier'          => 'delegating-shim',
        'coupling'           => ['xoops-runtime'],
        'target_package'     => 'xoops/helpers',
        'target_symbol'      => 'Xoops\\Helpers\\Utility\\Filesystem',
        'target_min_version' => '1.0',
        'target_owner'       => 'XOOPS Helpers maintainers',
        'target_status'      => 'stable',
        'availability'       => 'delegate-now',
        'migration'          => ['delegation', 'manual-recipe'],
        'parity_test'        => 'tests/Migration/FilesystemDelegationTest.php',
        'notes'              => 'Heavy filesystem operations already delegate; XOOPS guards stay in the compatibility shim.',
    ]);
}

$filesystemRetainedMethods = [
    'Common\\DirectoryChecker::getDirectoryStatus()',
    'Common\\DirectoryChecker::setDirectoryPermissions()',
    'Common\\DirectoryChecker::dirExists()',
    'Common\\FileChecker::getFileStatus()',
    'Common\\FileChecker::compareFiles()',
    'Common\\FileChecker::fileExists()',
    'Common\\FileChecker::setFilePermissions()',
];
foreach ($filesystemRetainedMethods as $symbol) {
    $decisions[$symbol] = $compat([
        'qualifier' => 'method-specific-residue',
        'coupling'  => str_contains($symbol, 'Status') ? ['xoops-runtime', 'presentation-html'] : ['pure-php'],
        'notes'     => 'This member has no exact Filesystem equivalent and remains frozen compatibility residue.',
    ]);
}

$decisions['Common\\Migrate'] = $compat([
    'disposition'        => 'promote',
    'qualifier'          => 'delegating-shim',
    'coupling'           => ['xoops-runtime'],
    'target_package'     => 'xoops/xmf',
    'target_symbol'      => 'Xmf\\Database\\Migrate',
    'target_min_version' => '1.5',
    'target_owner'       => 'XMF database maintainers',
    'target_status'      => 'stable',
    'availability'       => 'delegate-now',
    'migration'          => ['delegation', 'manual-recipe'],
    'rector_config_template' => 'bin/xoops-schema-plan.php',
    'migration_safety'   => 'plan-only',
    'capability_profile' => '1.5',
    'parity_test'        => 'tests/Migration/MigrateDelegationTest.php',
    'notes'              => 'ModuleTools subclasses XMF only to preserve configurator-driven table renames.',
]);

foreach (['Common\\Breadcrumb', 'Common\\SocialBookmarks', 'Common\\LetterChoice'] as $symbol) {
    $decisions[$symbol] = $compat([
        'disposition'        => 'blocked-on-upstream',
        'qualifier'          => 'find-exact-presentation-owner',
        'coupling'           => ['presentation-html'],
        'target_package'     => 'xoops/smartyextensions',
        'target_symbol'      => 'none',
        'target_min_version' => 'none',
        'target_owner'       => 'SmartyExtensions navigation maintainers',
        'target_status'      => 'missing',
        'availability'       => 'not-applicable',
        'migration'          => ['none-yet'],
        'notes'              => 'Installed presentation helpers do not expose an exact equivalent API.',
    ]);
}

foreach (['Common\\TestdataButtons', 'Common\\TestdataSample'] as $symbol) {
    $decisions[$symbol] = $compat([
        'disposition'        => 'migrate-to-target',
        'qualifier'          => 'await-4.0',
        'coupling'           => ['xoops-runtime', 'presentation-html'],
        'target_package'     => 'xoops/xmf',
        'target_symbol'      => 'Xmf\\Module\\Testdata',
        'target_min_version' => '2.0',
        'target_owner'       => 'XMF module tooling maintainers',
        'target_status'      => 'provisional',
        'availability'       => 'migrate-on-4.0',
        'migration'          => ['manual-recipe'],
        'notes'              => 'The target is absent from XMF 1.5 and must be proven in the PHP 8.4 tree.',
    ]);
}

$simpleCompat = [
    'Module\\Installer'       => ['then-migrate', ['xoops-runtime', 'request-global', 'presentation-html']],
    'Module\\Dependency'      => ['reassess', ['xoops-runtime']],
    'Module\\ConsumerRuntime' => ['reassess', ['xoops-runtime', 'presentation-html']],
    'Bootstrap'                => ['reassess', ['xoops-runtime']],
    'Common\\Blocksadmin'     => ['', ['xoops-runtime', 'request-global', 'presentation-html']],
    'Common\\VersionChecks'   => ['', ['xoops-runtime']],
    'Common\\ModuleConfig'    => ['reassess-ownership', ['xoops-runtime']],
    'Common\\StandardConfig'  => ['reassess-ownership', ['xoops-runtime']],
    'Common\\Configurator'    => ['reassess-ownership', ['xoops-runtime']],
    'Common\\Output'          => ['until-output-modeled', ['xoops-runtime', 'presentation-html']],
    'Common\\Confirm'         => ['until-output-modeled', ['xoops-runtime', 'presentation-html']],
    'Common\\UpdateChecker'   => ['reassess', ['xoops-runtime', 'presentation-html']],
    'Common\\SysUtility'      => ['split-first-per-method', ['xoops-runtime', 'request-global', 'presentation-html']],
    'Utility'                  => ['split-first-per-method', ['xoops-runtime', 'request-global', 'presentation-html']],
    'Constants'                => ['', ['pure-php']],
    // CSV download and module cloning emit HTTP headers/redirects by design.
    'Admin\\Export'            => ['', ['xoops-runtime', 'xoopsobject-handler', 'presentation-html']],
    'Common\\Cloner'           => ['', ['xoops-runtime', 'request-global', 'presentation-html']],
    'Admin\\Table'             => ['legacy-alias', ['xoops-runtime', 'presentation-html']],
    'Common\\Highlighter'      => ['', ['pure-php']],
];
foreach ($simpleCompat as $symbol => [$qualifier, $coupling]) {
    $decisions[$symbol] = $compat(['qualifier' => $qualifier, 'coupling' => $coupling]);
}

return $decisions;
