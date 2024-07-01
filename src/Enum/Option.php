<?php

declare(strict_types=1);

namespace Rector\ReleaseNotesGenerator\Enum;

final class Option
{
    /**
     * @var string
     */
    public const FROM_COMMIT = 'from-commit';

    /**
     * @var string
     */
    public const TO_COMMIT = 'to-commit';

    /**
     * @var string
     */
    public const GITHUB_TOKEN = 'github-token';

    /**
     * @var string
     */
    public const REMOTE_REPOSITORY = 'remote-repository';

    /**
     * @var string
     */
    public const REMOTE_ONLY = 'remote-only';
}
