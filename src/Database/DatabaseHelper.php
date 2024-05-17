<?php
/**
 * @copyright 2024 Roman Parpalak
 * @license http://opensource.org/licenses/MIT MIT
 * @package AdminYard
 */

declare(strict_types=1);

namespace S2\AdminYard\Database;

use S2\AdminYard\Config\EntityConfig;
use S2\AdminYard\Config\FieldConfig;

class DatabaseHelper
{
    public static function getSqlExpressionsForManyToOne(EntityConfig $entityConfig): array
    {
        // TODO: not all fields may be required for displaying
        return array_map(static fn(FieldConfig $field) => sprintf(
            '(SELECT %s FROM %s WHERE %s = entity.%s)',
            $field->getTitleSqlExpression(),
            $field->getForeignEntity()->getTableName(),
            $field->getForeignEntity()->getFieldNamesOfPrimaryKey()[0],
            $field->getName()
        ), $entityConfig->getFieldsWithForeignEntities());
    }
}
