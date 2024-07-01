<?php

declare(strict_types=1);

namespace Rector\ReleaseNotesGenerator\Configuration;

use Webmozart\Assert\Assert;

final class Configuration
{
    /**
     * @param string[] $remoteRepositories
     */
    public function __construct(
        private string $fromCommit,
        private string $toCommit,
        private string $githubToken,
        private array $remoteRepositories
    ) {
        Assert::allString($remoteRepositories);
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

    /**
     * @return string[]
     */
    public function getRemoteRepositories(): array
    {
        return $this->remoteRepositories;
    }
}
