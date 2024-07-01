<?php

declare(strict_types=1);

namespace Rector\ReleaseNotesGenerator\Command;

use Rector\ReleaseNotesGenerator\ChangelogContentsFactory;
use Rector\ReleaseNotesGenerator\Configuration\Configuration;
use Rector\ReleaseNotesGenerator\Configuration\ConfigurationResolver;
use Rector\ReleaseNotesGenerator\Enum\Option;
use Rector\ReleaseNotesGenerator\Enum\RectorRepositoryName;
use Rector\ReleaseNotesGenerator\Exception\InvalidConfigurationException;
use Rector\ReleaseNotesGenerator\GithubApiCaller;
use Rector\ReleaseNotesGenerator\GitResolver;
use Rector\ReleaseNotesGenerator\ValueObject\Commit;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

final class GenerateCommand extends Command
{
    /**
     * @var string[]
     */
    private const EXCLUDED_THANKS_NAMES = ['TomasVotruba', 'samsonasik'];

    /**
     * @see https://regex101.com/r/jdT01W/1
     * @var string
     */
    private const ISSUE_NAME_REGEX = '#(.*?)( \(\#\d+\))?$#ms';

    private ?SymfonyStyle $symfonyStyle = null;

    public function __construct(
        private readonly GitResolver $gitResolver,
        private readonly GithubApiCaller $githubApiCaller,
        private readonly ChangelogContentsFactory $changelogContentsFactory,
        private readonly ConfigurationResolver $configurationResolver,
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

        $i = 0;

        $changelogLines = [];

        foreach ($commits as $commit) {
            $changelogLine = $this->createChangelogLing($commit, $configuration);

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

    private function createThanks(string|null $thanks): string
    {
        if ($thanks === null) {
            return '';
        }

        if (in_array($thanks, self::EXCLUDED_THANKS_NAMES, true)) {
            return '';
        }

        return sprintf(', Thanks @%s!', $thanks);
    }

    private function createChangelogLing(Commit $commit, Configuration $configuration): string
    {
        $searchPullRequestsResponse = $this->githubApiCaller->searchPullRequests(
            $commit,
            $configuration->getGithubToken()
        );
        $searchIssuesResponse = $this->githubApiCaller->searchIssues($commit, $configuration->getGithubToken());

        $items = array_merge($searchPullRequestsResponse->items, $searchIssuesResponse->items);
        $parenthesis = 'https://github.com/' . RectorRepositoryName::DEVELOPMENT . '/commit/' . $commit->getHash();

        $thanks = null;
        $issuesToReference = [];

        foreach ($items as $item) {
            if (property_exists($item, 'pull_request') && $item->pull_request !== null) {
                $parenthesis = sprintf(
                    '[#%d](%s)',
                    (int) $item->number,
                    'https://github.com/' . RectorRepositoryName::DEVELOPMENT . '/pull/' . $item->number
                );
                $thanks = $item->user->login;
                break;
            }

            $issuesToReference[] = '#' . $item->number;
        }

        // clean commit from duplicating issue number
        preg_match(self::ISSUE_NAME_REGEX, $commit->getMessage(), $commitMatch);

        $commit = $commitMatch[1] ?? $commit->getMessage();

        return sprintf(
            '* %s (%s)%s%s',
            (string) $commit,
            $parenthesis,
            $issuesToReference !== [] ? ', ' . implode(', ', $issuesToReference) : '',
            $this->createThanks($thanks)
        );

    }

    private function printToFile(string $releaseChangelogContents): void
    {
        $filePath = getcwd() . '/generated-release-notes.md';
        file_put_contents($filePath, $releaseChangelogContents);

        $this->symfonyStyle->writeln(sprintf('Release notes dumped into "%s" file', $filePath));
    }
}
