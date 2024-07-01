<?php

declare(strict_types=1);

namespace Rector\ReleaseNotesGenerator\Command;

use Rector\ReleaseNotesGenerator\ChangelogContentsFactory;
use Rector\ReleaseNotesGenerator\ChangelogLineFactory;
use Rector\ReleaseNotesGenerator\Configuration\ConfigurationResolver;
use Rector\ReleaseNotesGenerator\Enum\Option;
use Rector\ReleaseNotesGenerator\Exception\InvalidConfigurationException;
use Rector\ReleaseNotesGenerator\GitResolver;
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
            InputOption::VALUE_REQUIRED,
            'Remote repository (use multiple values)'
        );
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
        $this->printToFile($releaseChangelogContents);

        return self::SUCCESS;
    }

    private function printToFile(string $releaseChangelogContents): void
    {
        $filePath = getcwd() . '/generated-release-notes.md';
        file_put_contents($filePath, $releaseChangelogContents);

        $this->symfonyStyle->writeln(sprintf('Release notes dumped into "%s" file', $filePath));
    }
}
