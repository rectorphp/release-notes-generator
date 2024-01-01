<?php

declare (strict_types=1);
namespace ECSPrefix202312;

use PhpCsFixer\Fixer\ArrayNotation\ArraySyntaxFixer;
use PhpCsFixer\Fixer\ArrayNotation\NoWhitespaceBeforeCommaInArrayFixer;
use PhpCsFixer\Fixer\ArrayNotation\TrimArraySpacesFixer;
use PhpCsFixer\Fixer\ArrayNotation\WhitespaceAfterCommaInArrayFixer;
use PhpCsFixer\Fixer\Basic\NoTrailingCommaInSinglelineFixer;
use PhpCsFixer\Fixer\ControlStructure\TrailingCommaInMultilineFixer;
use PhpCsFixer\Fixer\Whitespace\ArrayIndentationFixer;
use Symplify\CodingStandard\Fixer\ArrayNotation\ArrayListItemNewlineFixer;
use Symplify\CodingStandard\Fixer\ArrayNotation\ArrayOpenerAndCloserNewlineFixer;
use Symplify\CodingStandard\Fixer\ArrayNotation\StandaloneLineInMultilineArrayFixer;
use Symplify\EasyCodingStandard\Config\ECSConfig;
return static function (ECSConfig $ecsConfig) : void {
    $ecsConfig->rules([NoWhitespaceBeforeCommaInArrayFixer::class, ArrayOpenerAndCloserNewlineFixer::class, ArrayIndentationFixer::class, TrimArraySpacesFixer::class, WhitespaceAfterCommaInArrayFixer::class, ArrayListItemNewlineFixer::class, StandaloneLineInMultilineArrayFixer::class]);
    // commas
    $ecsConfig->ruleWithConfiguration(NoTrailingCommaInSinglelineFixer::class, ['elements' => ['arguments', 'array_destructuring', 'array', 'group_import']]);
    $ecsConfig->ruleWithConfiguration(TrailingCommaInMultilineFixer::class, ['elements' => [TrailingCommaInMultilineFixer::ELEMENTS_ARRAYS]]);
    $ecsConfig->ruleWithConfiguration(ArraySyntaxFixer::class, ['syntax' => 'short']);
};
