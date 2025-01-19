<?php

declare(strict_types=1);

namespace Rector\ReleaseNotesGenerator\Configuration;

use Webmozart\Assert\Assert;

final readonly class Configuration
{
    /**
     * @var string[]
     */
    public const EXCLUDED_THANKS_NAMES = ['TomasVotruba', 'samsonasik'];

    /**
     * @param string[] $remoteRepositories
     */
    public function __construct(
        private string $fromCommit,
        private string $toCommit,
        private string $githubToken,
        private array $remoteRepositories,
        private bool $isRemoteOnly
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

    public function hasRemoteRepositories(): bool
    {
        return $this->remoteRepositories !== [];
    }

    /**
     * @return string[]
     */
    public function getRemoteRepositories(): array
    {
        return $this->remoteRepositories;
    }

    public function isRemoteOnly(): bool
    {
        return $this->isRemoteOnly;
    }
}
