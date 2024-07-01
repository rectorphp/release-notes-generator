<?php

declare(strict_types=1);

namespace Rector\ReleaseNotesGenerator\ValueObject;

final class ExternalRepositoryChangelog
{
    /**
     * @param string[] $lines
     */
    public function __construct(
        private string $title,
        private array $lines
    ) {

    }

    public function getTitle(): string
    {
        return $this->title;
    }

    /**
     * @return string[]
     */
    public function getLines(): array
    {
        return $this->lines;
    }

    public function toString(): string
    {
        $changelogContents = '## ' . $this->title;
        $changelogContents .= implode(PHP_EOL, $this->lines);
        $changelogContents .= PHP_EOL . PHP_EOL;

        return $changelogContents;
    }
}
