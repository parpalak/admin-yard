<?php
/**
 * @copyright 2024 Roman Parpalak
 * @license http://opensource.org/licenses/MIT MIT
 * @package AdminYard
 */

declare(strict_types=1);

namespace S2\AdminYard\Database;

use Symfony\Component\HttpFoundation\Request;

readonly class PrimaryKey
{
    public function __construct(protected array $columns)
    {
        if (\count($this->columns) < 1) {
            throw new \InvalidArgumentException('Primary key must contain at least one column.');
        }
    }

    public static function fromRequestQueryParams(Request $request, array $columnNames): static
    {
        $values = array_map(static fn(string $field) => $request->query->get($field), $columnNames);
        return new static(array_combine($columnNames, $values));
    }

    /**
     * @return string[]
     */
    public function getColumnNames(): array
    {
        return array_keys($this->columns);
    }

    public function prepend(string $keyPrefix): static
    {
        return new static(array_combine(array_map(static fn($key) => $keyPrefix . $key, $this->getColumnNames()), array_values($this->columns)));
    }

    public function toArray(): array
    {
        return $this->columns;
    }

    public function withColumnValues(array $data): static
    {
        $primaryKey = $this->columns;
        foreach ($primaryKey as $key => $value) {
            if (isset($data[$key]) && $value !== $data[$key]) {
                $primaryKey[$key] = $data[$key];
            }
        }

        return new static($primaryKey);
    }
}
