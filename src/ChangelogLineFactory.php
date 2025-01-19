<?php

declare(strict_types=1);

namespace Rector\ReleaseNotesGenerator;

use Rector\ReleaseNotesGenerator\Configuration\Configuration;
use Rector\ReleaseNotesGenerator\Enum\RectorRepositoryName;
use Rector\ReleaseNotesGenerator\ValueObject\Commit;
use stdClass;

final readonly class ChangelogLineFactory
{
    /**
     * @see https://regex101.com/r/jdT01W/1
     * @var string
     */
    private const ISSUE_NAME_REGEX = '#(.*?)( \(\#\d+\))?$#ms';

    public function __construct(
        private GithubApiCaller $githubApiCaller
    ) {
    }

    /**
     * @return string[]
     */
    public function createFromPullRequests(stdClass $pullRequests): array
    {
        $changelogLines = [];
        foreach ($pullRequests->items as $foundPullRequest) {
            $changelogLines[] = $this->createFromPullRequest($foundPullRequest);
        }

        return $changelogLines;
    }

    public function create(Commit $commit, Configuration $configuration): string
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
            $commit,
            $parenthesis,
            $issuesToReference !== [] ? ', ' . implode(', ', $issuesToReference) : '',
            $this->createThanks($thanks)
        );
    }

    private function createFromPullRequest(stdClass $pullRequest): string
    {
        $changelogLine = sprintf(
            '* %s ([#%s](%s))',
            $pullRequest->title,
            $pullRequest->number,
            $pullRequest->pull_request->html_url
        );

        $username = $pullRequest->user->login;
        if (! in_array($username, Configuration::EXCLUDED_THANKS_NAMES, true)) {
            $changelogLine .= ', Thanks @' . $username;
        }

        return $changelogLine;
    }

    private function createThanks(string|null $thanks): string
    {
        if ($thanks === null) {
            return '';
        }

        if (in_array($thanks, Configuration::EXCLUDED_THANKS_NAMES, true)) {
            return '';
        }

        return sprintf(', Thanks @%s!', $thanks);
    }
}
