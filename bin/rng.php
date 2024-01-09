<?php

declare(strict_types=1);

use Illuminate\Container\Container;
use Rector\ReleaseNotesGenerator\Command\GenerateCommand;
use Rector\ReleaseNotesGenerator\GithubApiCaller;
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

$container = new Container();
$container->when(GithubApiCaller::class)
    ->needs('$githubToken')
    ->give(getenv('GITHUB_TOKEN'));


$generateChangelogCommand = $container->make(GenerateCommand::class);

$application = new Application();
$application->add($generateChangelogCommand);
$application->setDefaultCommand('generate', true);

exit($application->run());
