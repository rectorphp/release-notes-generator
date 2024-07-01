<?php

declare(strict_types=1);

namespace Rector\ReleaseNotesGenerator\Configuration;

final class Configuration
{
    public function __construct(
        private string $fromCommit,
        private string $toCommit,
        private string $githubToken
    ) {
    }

    public function getFromCommit(): string
    {
        return $this->fromCommit;
    }

    public function getToCommit(): string
    {
        return $this->toCommit;
    }

    public function getGithubToken(): string
    {
        return $this->githubToken;
    }
}
