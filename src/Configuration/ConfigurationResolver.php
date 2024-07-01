<?php

declare(strict_types=1);

namespace Rector\ReleaseNotesGenerator\Configuration;

use Rector\ReleaseNotesGenerator\Enum\Option;
use Rector\ReleaseNotesGenerator\Exception\InvalidConfigurationException;
use Symfony\Component\Console\Input\InputInterface;

final class ConfigurationResolver
{
    public function resolve(InputInterface $input): Configuration
    {
        $fromCommit = (string) $input->getOption(Option::FROM_COMMIT);
        if ($fromCommit === '') {
            throw new InvalidConfigurationException('Option "--from-commit" is required');
        }

        $toCommit = (string) $input->getOption(Option::TO_COMMIT);
        if ($toCommit === '') {
            throw new InvalidConfigurationException('Option "--to-commit" is required');
        }

        $githubToken = (string) $input->getOption(Option::GITHUB_TOKEN);
        if ($githubToken === '') {
            throw new InvalidConfigurationException(
                'Option "--github-token" is required. Get your token here: https://github.com/settings/tokens/new'
            );
        }

        $isRemoteOnly = (bool) $input->getOption(Option::REMOTE_ONLY);

        $remoteRepositories = (array) $input->getOption(Option::REMOTE_REPOSITORY);
        return new Configuration($fromCommit, $toCommit, $githubToken, $remoteRepositories, $isRemoteOnly);
    }
}
