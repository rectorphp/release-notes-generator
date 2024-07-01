<?php

declare(strict_types=1);

namespace Rector\ReleaseNotesGenerator;

use Httpful\Request;
use Httpful\Response;
use Rector\ReleaseNotesGenerator\Enum\RectorRepositoryName;
use Rector\ReleaseNotesGenerator\Exception\GithubRequestException;
use Rector\ReleaseNotesGenerator\ValueObject\Commit;
use stdClass;

final readonly class GithubApiCaller
{
    public function searchIssues(Commit $commit, string $githubToken): stdClass
    {
        $requestUri = sprintf(
            'https://api.github.com/search/issues?q=repo:%s+%s+is:issue',
            RectorRepositoryName::DEPLOY,
            $commit->getHash()
        );

        return $this->sendRequest($requestUri, $githubToken);
    }

    public function findRepositoryPullRequestsBetweenDates(string $repositoryName, string $githubToken, string $startDate, string $endDate): stdClass
    {
        $requestUri = sprintf(
            'https://api.github.com/search/issues?q=repo:%s+is:pull-request+merged:%s..%s',
            $repositoryName,
            $startDate,
            $endDate
        );

        return $this->sendRequest($requestUri, $githubToken);
    }

    public function searchPullRequests(Commit $commit, string $githubToken): stdClass
    {
        $requestUri = sprintf(
            'https://api.github.com/search/issues?q=repo:%s+%s+is:pull-request',
            RectorRepositoryName::DEVELOPMENT,
            $commit->getHash()
        );

        return $this->sendRequest($requestUri, $githubToken);
    }

    private function sendRequest(string $requestUri, string $githubToken): stdClass
    {
        /** @var Response $response */
        $response = Request::get($requestUri)
            ->sendsAndExpectsType('application/json')
            ->basicAuth('tomasvotruba', $githubToken)
            ->send();

        if ($response->code !== 200) {
            throw new GithubRequestException($response->body->message, (int) $response->code);
        }

        return $response->body;
    }
}
