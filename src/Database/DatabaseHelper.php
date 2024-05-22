<?php
/**
 * @copyright 2024 Roman Parpalak
 * @license   http://opensource.org/licenses/MIT MIT
 * @package   AdminYard
 */

declare(strict_types=1);

namespace S2\AdminYard\Database;

use S2\AdminYard\Config\EntityConfig;
use S2\AdminYard\Config\FieldConfig;

class DatabaseHelper
{
    public static function getSqlExpressionsForAssociations(EntityConfig $entityConfig): array
    {
        // TODO: not all fields may be required for displaying
        return array_merge(
            array_map(static fn(FieldConfig $field) => sprintf(
            // One-To-Many, aggregated info about "children"
                '(SELECT %s FROM %s WHERE %s = entity.%s)',
                $field->linkedBy->titleSqlExpression,
                $field->linkedBy->foreignEntity->getTableName(),
                $field->linkedBy->inverseFieldName,
                $entityConfig->getFieldNamesOfPrimaryKey()[0]
            ), $entityConfig->getOneToManyFields()),

            array_map(static fn(FieldConfig $field) => sprintf(
            // Many-To-One, info about "parent"
                '(SELECT %s FROM %s WHERE %s = entity.%s)',
                $field->linkToEntity->titleSqlExpression,
                $field->linkToEntity->foreignEntity->getTableName(),
                $field->linkToEntity->foreignEntity->getFieldNamesOfPrimaryKey()[0],
                $field->name
            ), $entityConfig->getManyToOneFields())
        );
    }
}
