<?php

declare(strict_types=1);

use Rector\ReleaseNotesGenerator\Command\GenerateCommand;
use Rector\ReleaseNotesGenerator\GithubApiCaller;
use Symfony\Component\Console\Application;

require __DIR__ . '/../vendor/autoload.php';

$container = new \Illuminate\Container\Container();
$container->when(GithubApiCaller::class)
    ->needs('$githubToken')
    ->give(getenv('GITHUB_TOKEN'));


$generateChangelogCommand = $container->make(GenerateCommand::class);

$application = new Application();
$application->add($generateChangelogCommand);
$application->setDefaultCommand('generate', true);

exit($application->run());
