<?php

declare(strict_types=1);

namespace Rector\ReleaseNotesGenerator;

use Httpful\Request;
use Httpful\Response;
use InvalidArgumentException;
use Rector\ReleaseNotesGenerator\Enum\RectorRepositoryName;
use Rector\ReleaseNotesGenerator\Exception\GithubRequestException;
use Rector\ReleaseNotesGenerator\ValueObject\Commit;
use stdClass;

final class GithubApiCaller
{
    public function __construct(
        private readonly string|false $githubToken
    ) {
        if ($githubToken === false) {
            throw new InvalidArgumentException(
                'Provide GitHub token via: "GITHUB_TOKEN=*** bin/generate-changelog.php ..."'
            );
        }
    }

    public function searchIssues(Commit $commit): stdClass
    {
        $requestUri = sprintf(
            'https://api.github.com/search/issues?q=repo:%s+%s+is:issue',
            RectorRepositoryName::DEPLOY,
            $commit->getHash()
        );

        return $this->sendRequest($requestUri);
    }

    public function searchPullRequests(Commit $commit): stdClass
    {
        $requestUri = sprintf(
            'https://api.github.com/search/issues?q=repo:%s+%s+is:pull-request',
            RectorRepositoryName::DEVELOPMENT,
            $commit->getHash()
        );

        return $this->sendRequest($requestUri);
    }

    private function sendRequest(string $requestUri): stdClass
    {
        /** @var Response $response */
        $response = Request::get($requestUri)
            ->sendsAndExpectsType('application/json')
            ->basicAuth('tomasvotruba', $this->githubToken)
            ->send();

        if ($response->code !== 200) {
            throw new GithubRequestException($response->body->message, (int) $response->code);
        }

        return $response->body;
    }
}
