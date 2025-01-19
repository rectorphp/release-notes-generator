<?php

declare(strict_types=1);

namespace Rector\ReleaseNotesGenerator\ValueObject;

final readonly class ExternalRepositoryChangelog
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
        $changelogContents = '## ' . $this->title . PHP_EOL . PHP_EOL;
        $changelogContents .= implode(PHP_EOL, $this->lines);

        return $changelogContents . (PHP_EOL . PHP_EOL);
    }
}
