<?php

declare(strict_types=1);

namespace Rector\ReleaseNotesGenerator\Tests\ChangelogContentsFactory;

use PHPUnit\Framework\TestCase;
use Rector\ReleaseNotesGenerator\ChangelogContentsFactory;

final class ChangelogContentsFactoryTest extends TestCase
{
    private ChangelogContentsFactory $changelogContentsFactory;

    protected function setUp(): void
    {
        $this->changelogContentsFactory = new ChangelogContentsFactory();
    }

    public function test(): void
    {
        $changelogLines = [
            '* Fix bug',
            '* Add new rule',
            '* Fixed another bug',
            '* Removed old rule',
            '* Enable PHPStan on tests as well + add "unused public" ([#3238](https://github.com/rectorphp/rector-src/pull/3238))',
        ];

        $generatedChangelogContents = $this->changelogContentsFactory->create($changelogLines);

        $this->assertStringEqualsFile(__DIR__ . '/Fixture/generated_changelog.md', $generatedChangelogContents);
    }
}
