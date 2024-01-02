<?php

declare(strict_types=1);

namespace Rector\ReleaseNotesGenerator\Command;

use Nette\Utils\FileSystem;
use Nette\Utils\Strings;
use Rector\ReleaseNotesGenerator\ChangelogContentsFactory;
use Rector\ReleaseNotesGenerator\Enum\Option;
use Rector\ReleaseNotesGenerator\Enum\RectorRepositoryName;
use Rector\ReleaseNotesGenerator\GithubApiCaller;
use Rector\ReleaseNotesGenerator\GitResolver;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Webmozart\Assert\Assert;

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

    public function __construct(
        private readonly GitResolver $gitResolver,
        private readonly GithubApiCaller $githubApiCaller,
        private readonly ChangelogContentsFactory $changelogContentsFactory,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->setName('generate');
        $this->addOption(Option::FROM_COMMIT, null, InputOption::VALUE_REQUIRED);
        $this->addOption(Option::TO_COMMIT, null, InputOption::VALUE_REQUIRED);
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $fromCommit = (string) $input->getOption(Option::FROM_COMMIT);
        Assert::notEmpty($fromCommit);

        $toCommit = (string) $input->getOption(Option::TO_COMMIT);
        Assert::notEmpty($toCommit);

        $commits = $this->gitResolver->resolveCommitLinesFromToHashes($fromCommit, $toCommit);

        $i = 0;

        $changelogLines = [];

        foreach ($commits as $commit) {
            $searchPullRequestsResponse = $this->githubApiCaller->searchPullRequests($commit);
            $searchIssuesResponse = $this->githubApiCaller->searchIssues($commit);

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
            $commitMatch = Strings::match($commit->getMessage(), self::ISSUE_NAME_REGEX);

            $commit = $commitMatch[1] ?? $commit->getMessage();

            $changelogLine = sprintf(
                '* %s (%s)%s%s',
                (string) $commit,
                $parenthesis,
                $issuesToReference !== [] ? ', ' . implode(', ', $issuesToReference) : '',
                $this->createThanks($thanks)
            );

            // just to show off :)
            $output->writeln($changelogLine);

            $changelogLines[] = $changelogLine;

            // not to throttle the GitHub API
            if ($i > 0 && $i % 8 === 0) {
                sleep(60);
            }

            ++$i;
        }

        $releaseChangelogContents = $this->changelogContentsFactory->create($changelogLines);

        $filePath = getcwd() . '/generated-release-notes.md';

        FileSystem::write($filePath, $releaseChangelogContents);
        $output->write(sprintf('Release notes dumped into "%s" file', $filePath));

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
}
