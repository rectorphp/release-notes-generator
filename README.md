# Release Notes Generator

The way we generate release notes for Rector repository - https://github.com/rectorphp/rector/releases/

<br>

## Install

```bash
composer require rector/release-notes-generator --dev
```

<br>

## Usage

1. Generate Github token here: https://github.com/settings/tokens/new

2. Run the command:

```bash
vendor/bin/rng --from-commit <commit-hash> --to-commit <commit-hash> --github-token <github_token>
```
