<?php

declare(strict_types=1);

namespace Rector\ReleaseNotesGenerator;

use Rector\ReleaseNotesGenerator\ValueObject\Commit;
use Symfony\Component\Process\Process;

final class GitResolver
{
    /**
     * @return Commit[]
     */
    public function resolveCommitLinesFromToHashes(string $fromCommit, string $toCommit): array
    {
        $commitHashRange = sprintf('%s..%s', $fromCommit, $toCommit);

        $output = $this->exec(['git', 'log', $commitHashRange, '--reverse', '--pretty=%H %s %cd', '--date=format:%Y-%m-%d']);
        $commitLines = explode("\n", $output);

        // remove empty values
        $commitLines = array_filter($commitLines);
        return $this->mapCommitLinesToCommits($commitLines);
    }

    /**
     * @param string[] $commitLines
     * @return Commit[]
     */
    private function mapCommitLinesToCommits(array $commitLines): array
    {
        return array_map(static function (string $line): Commit {
            preg_match('#(?<hash>\w+) (?<message>.*?) (?<date>\d+\-\d+\-\d+)#', $line, $matches);
            return new Commit($matches['hash'], $matches['message'], $matches['date']);
        }, $commitLines);
    }

    /**
     * @param string[] $commandParts
     */
    private function exec(array $commandParts): string
    {
        $process = new Process($commandParts);
        $process->run();

        return $process->getOutput();
    }
}
