<?php

declare(strict_types=1);

return [
    // Same filenames do not mean the same responsibility. These are module facades/data.
    'facade_basenames' => ['Constants', 'Helper', 'Utility'],

    // Current same-name candidates which were reviewed and intentionally retained.
    'decisions' => [
        'article:text'        => ['status' => 'retained', 'reason' => 'Article body persistence entity, not the ModuleTools text service.'],
        'myalbum:Text'       => ['status' => 'retained', 'reason' => 'Module domain entity, not the ModuleTools text service.'],
        'myreviews:Text'     => ['status' => 'retained', 'reason' => 'Module domain entity with permission/form behavior.'],
        'newbb:ObjectTree'   => ['status' => 'retained', 'reason' => 'Forum-specific tree API (forum parents, tagged arrays, object trees).'],
        'newbb:Text'         => ['status' => 'retained', 'reason' => 'Forum text persistence entity.'],
        'queries:migrate'    => ['status' => 'retained', 'reason' => 'Module-specific schema migration subclass.'],
        'recette:ObjectTree' => ['status' => 'retained', 'reason' => 'Legacy SQL-tree contract; not compatible with object-array ObjectTree.'],
        'surnames:migrate'   => ['status' => 'retained', 'reason' => 'Module-specific schema migration subclass.'],
        'teambios:Breadcrumb' => ['status' => 'retained', 'reason' => 'Compatibility no-op renderer; changing it would enable output unexpectedly.'],
        'wggallery:Resizer'  => ['status' => 'delegated', 'reason' => 'Standard resize/crop delegates to ModuleTools; local grid merge/rotation remains.'],
        'wgteams:Resizer'    => ['status' => 'delegated', 'reason' => 'Standard resize/crop delegates to ModuleTools; local grid composition remains.'],
        'xcontent:Text'      => ['status' => 'retained', 'reason' => 'Content text persistence entity.'],
    ],

    // Candidates removed after review; the audit verifies that they stay removed.
    'resolved' => [
        'adslight:ObjectTree'     => 'Empty pass-through replaced by Xoops\\ModuleTools\\Common\\ObjectTree.',
        'news:ObjectTree'         => 'Duplicate object tree replaced by Xoops\\ModuleTools\\Common\\ObjectTree.',
        'publisher:Resizer'       => 'Single resize caller converted to ResizeRequest/Resizer.',
        'team:TestdataButtons'    => 'Unused copied helper removed; shared implementation owns the behavior.',
        'xnewsletter:Breadcrumb'  => 'Duplicate renderer replaced by Xoops\\ModuleTools\\Common\\Breadcrumb.',
    ],
];
