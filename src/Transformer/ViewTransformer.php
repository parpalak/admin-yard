<?php
/**
 * @copyright 2024 Roman Parpalak
 * @license   http://opensource.org/licenses/MIT MIT
 * @package   AdminYard
 */

declare(strict_types=1);

namespace S2\AdminYard\Transformer;

use InvalidArgumentException;
use S2\AdminYard\Config\FieldConfig;

class ViewTransformer
{
    /**
     * One-directional transform, as its result is used for displaying,
     * not for editing.
     */
    public function viewFromNormalized(mixed $value, string $dataType, ?array $options): ?string
    {
        if ($options !== null && is_scalar($value) && isset($options[$value])) {
            return $options[$value];
        }

        return match ($dataType) {
            FieldConfig::DATA_TYPE_STRING,
            FieldConfig::DATA_TYPE_DATE,
            FieldConfig::DATA_TYPE_PASSWORD => $value,
            FieldConfig::DATA_TYPE_INT => $value !== null ? number_format((int)$value, 0, '.', ' ') : null,
            FieldConfig::DATA_TYPE_FLOAT => (string)$value,
            FieldConfig::DATA_TYPE_BOOL => $value ? '✓' : '✗',
            FieldConfig::DATA_TYPE_TIMESTAMP,
            FieldConfig::DATA_TYPE_UNIXTIME => $value?->format('Y-m-d H:i:s'),
            default => throw new InvalidArgumentException(sprintf('Unknown data type "%s".', $dataType)),
        };
    }
}
