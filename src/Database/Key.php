<?php
/**
 * @copyright 2024 Roman Parpalak
 * @license   http://opensource.org/licenses/MIT MIT
 * @package   AdminYard
 */

declare(strict_types=1);

namespace S2\AdminYard\Database;

/**
 * Contains a set of column names and values.
 * Can be used as a primary key (composite primary key) or a condition wrapper.
 */
readonly class Key
{
    public function __construct(protected array $columns)
    {
        if (\count($this->columns) < 1) {
            // @codeCoverageIgnoreStart
            throw new \InvalidArgumentException('Primary key must contain at least one column.');
            // @codeCoverageIgnoreEnd
        }
    }

    /**
     * @return string[]
     */
    public function getColumnNames(): array
    {
        return array_keys($this->columns);
    }

    public function prependColumnNames(string $keyPrefix): static
    {
        return new static(array_combine(array_map(static fn($key) => $keyPrefix . $key, $this->getColumnNames()), array_values($this->columns)));
    }

    public function getIntId(): int
    {
        if (array_keys($this->columns) !== ['id']) {
            // @codeCoverageIgnoreStart
            throw new \LogicException('Key does not contain "id" column.');
            // @codeCoverageIgnoreEnd
        }

        if (!is_numeric($this->columns['id'])) {
            // @codeCoverageIgnoreStart
            throw new \LogicException('Key does not contain a numeric identifier.');
            // @codeCoverageIgnoreEnd
        }

        return (int)$this->columns['id'];
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

    public function toString(): string
    {
        $result = [];
        foreach ($this->columns as $key => $value) {
            $result[] = $key . '=' . var_export($value, true);
        }
        return implode('&', $result);
    }
}
