<?php

declare(strict_types=1);

namespace Rector\ReleaseNotesGenerator\Command;

use Rector\ReleaseNotesGenerator\ChangelogContentsFactory;
use Rector\ReleaseNotesGenerator\ChangelogLineFactory;
use Rector\ReleaseNotesGenerator\Configuration\Configuration;
use Rector\ReleaseNotesGenerator\Configuration\ConfigurationResolver;
use Rector\ReleaseNotesGenerator\Enum\Option;
use Rector\ReleaseNotesGenerator\Exception\InvalidConfigurationException;
use Rector\ReleaseNotesGenerator\GithubApiCaller;
use Rector\ReleaseNotesGenerator\GitResolver;
use Rector\ReleaseNotesGenerator\ValueObject\Commit;
use Rector\ReleaseNotesGenerator\ValueObject\ExternalRepositoryChangelog;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

final class GenerateCommand extends Command
{
    private ?SymfonyStyle $symfonyStyle = null;

    public function __construct(
        private readonly GitResolver $gitResolver,
        private readonly ChangelogContentsFactory $changelogContentsFactory,
        private readonly ConfigurationResolver $configurationResolver,
        private readonly ChangelogLineFactory $changelogLineFactory,
        private readonly GithubApiCaller $githubApiCaller,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->setName('generate');
        $this->addOption(Option::FROM_COMMIT, null, InputOption::VALUE_REQUIRED);
        $this->addOption(Option::TO_COMMIT, null, InputOption::VALUE_REQUIRED);
        $this->addOption(Option::GITHUB_TOKEN, null, InputOption::VALUE_REQUIRED);
        $this->addOption(
            Option::REMOTE_REPOSITORY,
            null,
            InputOption::VALUE_REQUIRED | InputOption::VALUE_IS_ARRAY,
            'Remote repository (use multiple values)'
        );

        $this->addOption(Option::REMOTE_ONLY, null, InputOption::VALUE_NONE, 'Show only remote repositories');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->symfonyStyle = new SymfonyStyle($input, $output);

        try {
            $configuration = $this->configurationResolver->resolve($input);
        } catch (InvalidConfigurationException $invalidConfigurationException) {
            $this->symfonyStyle->error($invalidConfigurationException->getMessage());
            return self::FAILURE;
        }

        $commits = $this->gitResolver->resolveCommitLinesFromToHashes(
            $configuration->getFromCommit(),
            $configuration->getToCommit()
        );

        if ($commits === []) {
            $this->symfonyStyle->error(
                'No commits found in the range. Just a reminder: we look into the local git repository history.'
            );
            return self::FAILURE;
        }

        $externalRepositoryChangelogs = $this->resolveExternalRepositoryChangelogs($commits, $configuration);

        if ($configuration->isRemoteOnly()) {
            $releaseChangelogContents = '';
        } else {
            $i = 0;

            $changelogLines = [];
            foreach ($commits as $commit) {
                $changelogLine = $this->changelogLineFactory->create($commit, $configuration);

                // just to show the script is doing something :)
                $this->symfonyStyle->writeln($changelogLine);

                $changelogLines[] = $changelogLine;

                // not to throttle the GitHub API
                if ($i > 0 && $i % 8 === 0) {
                    sleep(60);
                }

                ++$i;
            }

            $releaseChangelogContents = $this->changelogContentsFactory->create($changelogLines);
        }

        foreach ($externalRepositoryChangelogs as $externalRepositoryChangelog) {
            $releaseChangelogContents .= PHP_EOL . PHP_EOL;
            $releaseChangelogContents .= $externalRepositoryChangelog->toString();
        }

        $this->printToFile($releaseChangelogContents);

        return self::SUCCESS;
    }

    private function printToFile(string $releaseChangelogContents): void
    {
        $filePath = getcwd() . '/generated-release-notes.md';
        file_put_contents($filePath, $releaseChangelogContents);

        $this->symfonyStyle->writeln(sprintf('Release notes dumped into "%s" file', $filePath));
    }

    /**
     * @param Commit[] $commits
     * @return ExternalRepositoryChangelog[]
     */
    private function resolveExternalRepositoryChangelogs(array $commits, Configuration $configuration): array
    {
        if (! $configuration->hasRemoteRepositories()) {
            return [];
        }

        $externalRepositoryChangelogs = [];

        // process remote repositories by date first
        $startCommit = $commits[array_key_first($commits)];
        $endCommit = $commits[array_key_last($commits)];

        foreach ($configuration->getRemoteRepositories() as $remoteRepository) {
            $foundPullRequests = $this->githubApiCaller->findRepositoryPullRequestsBetweenDates(
                $remoteRepository,
                $configuration->getGithubToken(),
                $startCommit->getDate(),
                $endCommit->getDate()
            );

            // nothing to process
            if ($foundPullRequests->total_count === 0) {
                continue;
            }

            $externalChangelogLines = $this->changelogLineFactory->createFromPullRequests($foundPullRequests);

            $externalRepositoryChangelogs[] = new ExternalRepositoryChangelog(
                $remoteRepository,
                $externalChangelogLines
            );
        }

        return $externalRepositoryChangelogs;
    }
}
