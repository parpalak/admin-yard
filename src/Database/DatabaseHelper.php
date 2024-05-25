<?php
/**
 * @copyright 2024 Roman Parpalak
 * @license   http://opensource.org/licenses/MIT MIT
 * @package   AdminYard
 */

declare(strict_types=1);

namespace S2\AdminYard\Database;

use S2\AdminYard\Config\EntityConfig;
use S2\AdminYard\Config\VirtualFieldType;

class DatabaseHelper
{
    public static function getSqlExpressionsForAssociations(EntityConfig $entityConfig, string $action): array
    {
        $result = [];
        foreach ($entityConfig->getFields($action) as $field) {
            if ($field->type instanceof VirtualFieldType) {
                $result[$field->name] = "(" . $field->type->getTitleSqlSubQuery() . ")";
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
