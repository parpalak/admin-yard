<?php
/**
 * @copyright 2024 Roman Parpalak
 * @license   http://opensource.org/licenses/MIT MIT
 * @package   AdminYard
 */

declare(strict_types=1);

namespace S2\AdminYard\Database;

use DateTimeImmutable;
use InvalidArgumentException;
use S2\AdminYard\Config\FieldConfig;

class TypeTransformer implements TypeTransformerInterface
{
    /**
     * @throws \DateMalformedStringException
     * @throws \JsonException
     */
    public function normalizedFromDb(mixed $value, string $dataType): mixed
    {
        return match ($dataType) {
            FieldConfig::DATA_TYPE_STRING,
            FieldConfig::DATA_TYPE_FLOAT => (string)$value,
            FieldConfig::DATA_TYPE_INT,
            FieldConfig::DATA_TYPE_DATE => $value !== null ? (string)$value : null,
            FieldConfig::DATA_TYPE_BOOL => $value === 1 || $value === '1' || $value === true,
            FieldConfig::DATA_TYPE_TIMESTAMP => $value !== null ? new DateTimeImmutable($value) : null,
            FieldConfig::DATA_TYPE_UNIXTIME => $value === 0 ? null : (new DateTimeImmutable())->setTimestamp($value),
            FieldConfig::DATA_TYPE_PASSWORD => '***',
            FieldConfig::DATA_TYPE_JSON_ROWS => $value !== null ? json_decode($value, true, 512, JSON_THROW_ON_ERROR) : null,
            default => throw new InvalidArgumentException(\sprintf('Unknown data type "%s".', $dataType)),
        };
    }

    /**
     * @throws \JsonException
     * @noinspection PhpDuplicateMatchArmBodyInspection
     */
    public function dbFromNormalized(mixed $value, string $dataType): mixed
    {
        return match ($dataType) {
            FieldConfig::DATA_TYPE_STRING => (string)$value,
            FieldConfig::DATA_TYPE_FLOAT => $value !== null && $value !== '' ? (string)$value : null,
            FieldConfig::DATA_TYPE_INT => $value !== null && $value !== '' ? (int)$value : null,
            FieldConfig::DATA_TYPE_DATE => $value !== null && $value !== '' ? (string)$value : null,
            FieldConfig::DATA_TYPE_BOOL => $value ? 1 : 0,
            FieldConfig::DATA_TYPE_TIMESTAMP => $value?->format('Y-m-d H:i:s'),
            FieldConfig::DATA_TYPE_UNIXTIME => $value?->getTimestamp() ?? 0, // TODO how to configure default value?
            FieldConfig::DATA_TYPE_PASSWORD => (string)$value,
            FieldConfig::DATA_TYPE_JSON_ROWS => json_encode($value, JSON_THROW_ON_ERROR),
            default => throw new InvalidArgumentException(\sprintf('Unknown data type "%s".', $dataType)),
        };
    }
}
