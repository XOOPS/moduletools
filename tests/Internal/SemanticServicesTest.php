<?php

declare(strict_types=1);

namespace Xoops\ModuleTools\Tests\Internal;

use PHPUnit\Framework\TestCase;
use Xoops\ModuleTools\Internal\Confirmation\ConfirmationResolver;
use Xoops\ModuleTools\Internal\Installation\InstallationPlan;
use Xoops\ModuleTools\Internal\Presentation\SortControlBuilder;
use Xoops\ModuleTools\Internal\Update\VersionUpdatePolicy;

final class SemanticServicesTest extends TestCase
{
    public function testConfirmationResolverReturnsDataWithoutRendering(): void
    {
        $model = new ConfirmationResolver()->resolve(
            hiddens: ['id' => 42],
            action: '',
            requestUri: '/delete.php?id=42',
            object: 'Example',
            title: 'Confirm',
            label: 'Delete?',
            moduleDirName: null,
        );

        self::assertSame(['id' => 42], $model->hiddens);
        self::assertSame('/delete.php?id=42', $model->action);
        self::assertSame('Confirm', $model->title);
        self::assertSame('Delete?', $model->label);
    }

    public function testSortBuilderReturnsSemanticLinksAndSelection(): void
    {
        $model = new SortControlBuilder()->build('/items', '/items.php', 20, 'asc', 'title', 'title');

        self::assertSame('/items', $model->formAction);
        self::assertSame('/items.php?start=20&sort=title&order=asc', $model->ascendingUrl);
        self::assertSame('/items.php?start=20&sort=title&order=desc', $model->descendingUrl);
        self::assertSame('selasc.png', $model->ascendingIcon);
        self::assertSame('desc.png', $model->descendingIcon);
    }

    public function testVersionPolicyPreservesFinalAndPrereleaseRules(): void
    {
        $policy = new VersionUpdatePolicy();

        self::assertTrue($policy->hasStableUpdate('1.0.0_Final', '1.1.0', false));
        self::assertFalse($policy->hasStableUpdate('1.0.0_Final', '1.1.0-beta', true));
        self::assertSame('1.0.0', $policy->normalize('1.0.0_Final'));
        self::assertTrue($policy->hasStableUpdate('1.0.0', 'v1.1.0', false));
        self::assertSame('1.1.0', $policy->normalize('V1.1.0'));
        self::assertFalse($policy->hasStableUpdate('1.1.0', 'v1.1.0', false));
        self::assertSame('vv1.1.0', $policy->normalize('vv1.1.0'));
    }

    public function testInstallationPlanIsTransportFreeData(): void
    {
        $plan = new InstallationPlan(['/uploads/example'], ['/uploads/example'], [], '/module/blank.png');

        self::assertSame(['/uploads/example'], $plan->uploadFolders);
        self::assertSame('/module/blank.png', $plan->blankFileSource);
    }
}
