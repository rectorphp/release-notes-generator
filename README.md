# Release Notes Generator


## Install

```bash
composer install rector/release-notes-generator --dev
```

## Usage

```bash
GITHUB_TOKEN=<github_token> php bin/generate-changelog.php <from-commit> <to-commit> >> <file_to_dump.md>
```

```bash
GITHUB_TOKEN=ghp_... php bin/generate-changelog.php 07736c1 cb74bb6 >> CHANGELOG_dumped.md
```

* Generate the Composer token here: https://github.com/settings/tokens/new
