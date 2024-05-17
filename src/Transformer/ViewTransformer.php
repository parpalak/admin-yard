<?php
/**
 * @copyright 2024 Roman Parpalak
 * @license http://opensource.org/licenses/MIT MIT
 * @package AdminYard
 */

declare(strict_types=1);

namespace S2\AdminYard\Transformer;

use InvalidArgumentException;
use S2\AdminYard\Config\FieldConfig;

class ViewTransformer
{
    /**
     * One-directional, as it is used for displaying, but not for editing.
     */
    public function viewFromDb(mixed $value, string $dataType): string
    {
        return match ($dataType) {
            FieldConfig::DATA_TYPE_STRING,
            FieldConfig::DATA_TYPE_DATE => $value,
            FieldConfig::DATA_TYPE_INT => number_format((int)$value, 0, '.', ' '),
            FieldConfig::DATA_TYPE_FLOAT => (string)$value,
            FieldConfig::DATA_TYPE_BOOL => $value ? 'TRUE' : 'FALSE',
            FieldConfig::DATA_TYPE_TIMESTAMP => $value->format('Y-m-d H:i:s'),
            FieldConfig::DATA_TYPE_UNIXTIME => $value !== null ? $value->format('Y-m-d H:i:s') : '-',
            default => throw new InvalidArgumentException(sprintf('Unknown data type "%s".', $dataType)),
        };
    }
}
