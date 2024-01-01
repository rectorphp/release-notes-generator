<?php

declare(strict_types=1);

use Rector\ReleaseNotesGenerator\Changelog\ChangelogContentsFactory;
use Rector\ReleaseNotesGenerator\Command\GenerateChangelogCommand;
use Rector\ReleaseNotesGenerator\GithubApiCaller;
use Rector\ReleaseNotesGenerator\GitResolver;
use Symfony\Component\Console\Application;

require __DIR__ . '/../vendor/autoload.php';

$githubToken = getenv('GITHUB_TOKEN');
$githubApiCaller = new GithubApiCaller($githubToken);

// @todo

$generateChangelogCommand = new GenerateChangelogCommand(
    new GitResolver(),
    $githubApiCaller,
    new ChangelogContentsFactory()
);

$application = new Application();
$application->add($generateChangelogCommand);
$application->setDefaultCommand('generate-changelog', true);
$application->run();
