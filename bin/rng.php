<?php

declare(strict_types=1);

use Rector\ReleaseNotesGenerator\ChangelogContentsFactory;
use Rector\ReleaseNotesGenerator\ChangelogLineFactory;
use Rector\ReleaseNotesGenerator\Command\GenerateCommand;
use Rector\ReleaseNotesGenerator\Configuration\ConfigurationResolver;
use Rector\ReleaseNotesGenerator\GithubApiCaller;
use Rector\ReleaseNotesGenerator\GitResolver;
use Symfony\Component\Console\Application;

$possibleAutoloadPaths = [
    // dependency
    __DIR__ . '/../../../autoload.php',
    // after split package
    __DIR__ . '/../vendor/autoload.php',
];

foreach ($possibleAutoloadPaths as $possibleAutoloadPath) {
    if (file_exists($possibleAutoloadPath)) {
        require_once $possibleAutoloadPath;
        break;
    }
}

$gitResolver = new GitResolver();
$githubApiCaller = new GithubApiCaller();
$changelogContentsFactory = new ChangelogContentsFactory();
$configurationResolver = new ConfigurationResolver();

$changelogLineFactory = new ChangelogLineFactory($githubApiCaller);

$generateCommand = new GenerateCommand(
    $gitResolver,
    $changelogContentsFactory,
    $configurationResolver,
    $changelogLineFactory,
    $githubApiCaller
);

$application = new Application();
$application->add($generateCommand);
$application->setDefaultCommand('generate', true);

exit($application->run());
