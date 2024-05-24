<?php
/**
 * @copyright 2024 Roman Parpalak
 * @license   http://opensource.org/licenses/MIT MIT
 * @package   AdminYard
 */

declare(strict_types=1);

namespace S2\AdminYard\Database;

use S2\AdminYard\Config\EntityConfig;
use S2\AdminYard\Config\LinkedByFieldType;
use S2\AdminYard\Config\VirtualFieldType;

class DatabaseHelper
{
    public static function getSqlExpressionsForAssociations(EntityConfig $entityConfig, string $action): array
    {
        $result = [];
        foreach ($entityConfig->getFields($action) as $field) {
            if ($field->type instanceof LinkedByFieldType) {
                // One-To-Many, aggregated info about "children"
                $result[$field->name] = sprintf(
                    '(SELECT %s FROM %s WHERE %s = entity.%s)',
                    $field->type->titleSqlExpression,
                    $field->type->foreignEntity->getTableName(),
                    $field->type->inverseFieldName,
                    $entityConfig->getFieldNamesOfPrimaryKey()[0]
                );
            }

            if ($field->type instanceof VirtualFieldType) {
                // NOTE: should virtual fields have a data type other than string?
                $result[$field->name] = 'COALESCE((' . $field->type->titleSqlExpression . '), "")';
            }

            if ($field->linkToEntity !== null) {
                // Many-To-One, info about "parent"
                $result[$field->name] = sprintf(
                    '(SELECT %s FROM %s WHERE %s = entity.%s)',
                    $field->linkToEntity->titleSqlExpression,
                    $field->linkToEntity->foreignEntity->getTableName(),
                    $field->linkToEntity->foreignEntity->getFieldNamesOfPrimaryKey()[0],
                    $field->name
                );
            }
        }

        return $result;
    }
}
