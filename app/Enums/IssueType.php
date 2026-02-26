<?php

namespace App\Enums;

enum IssueType: string
{
    case DuplicateNamespace = 'duplicate_namespace';
    case DefinitionWithoutValues = 'definition_without_values';
    case ValueWithoutDefinition = 'value_without_definition';
    case EmptyMetafield = 'empty_metafield';
    case UnusedMetafield = 'unused_metafield';
    case LongTextValue = 'long_text_value';
    case SeoDuplicate = 'seo_duplicate';
    case ValidationMissing = 'validation_missing';
}
