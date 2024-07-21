<?php
/**
 * @copyright 2024 Roman Parpalak
 * @license   http://opensource.org/licenses/MIT MIT
 * @package   AdminYard
 */

declare(strict_types=1);

namespace S2\AdminYard\Database;

/**
 * Wraps logical (boolean) expressions for SQL.
 * Contains parameters and SQL expression.
 * Can be used in WHERE and SELECT clauses.
 */
class LogicalExpression
{
    private ?array $params = null;
    private ?string $sqlExpression = null;

    public function __construct(
        private readonly string                      $name,
        private readonly array|string|int|float|null $value,
        private ?string                              $sqlExpressionPattern = null
    ) {
        if ($this->sqlExpressionPattern === null) {
            if (\is_array($this->value)) {
                $this->sqlExpressionPattern = "{$this->name} IN (%s)";
            } else {
                $this->sqlExpressionPattern = "{$this->name} = %s";
            }
        }
    }

    public static function true(): self
    {
        return new self('', '', 'true');
    }

    /**
     * @return array<string,string>
     */
    public function getParams(): array
    {
        if ($this->params === null) {
            $this->prepare();
        }

        return $this->params;
    }

    public function getSqlExpression(): string
    {
        if ($this->sqlExpression === null) {
            $this->prepare();
        }

        return $this->sqlExpression;
    }

    public function isTrivialCondition(): bool
    {
        // NOTE: maybe '' and [] must be excluded somewhere else, not for all filters here.
        // It can be useful in case of different semantics for `null` (universal set) and `[]` (empty set).
        return $this->value === null || $this->value === '' || $this->value === [];
    }

    private function prepare(): void
    {
        if (\is_array($this->value)) {
            $arrayParamNames = [];
            foreach (array_values($this->value) as $i => $value) {
                $paramName                = strtr($this->name, '()', '__') . '_' . $i;
                $arrayParamNames[]        = ':' . $paramName;
                $this->params[$paramName] = $value;
            }
            $this->sqlExpression = sprintf($this->sqlExpressionPattern, implode(', ', $arrayParamNames));
            return;
        }

        $this->sqlExpression = sprintf($this->sqlExpressionPattern, ":{$this->name}");
        if ($this->sqlExpression === $this->sqlExpressionPattern) {
            // No substitution was done
            $this->params = [];
        } else {
            $this->params[$this->name] = $this->value;
        }
    }

    public function withNamePrefix(string $prefix): static
    {
        return new static($prefix . $this->name, $this->value, $this->sqlExpressionPattern);
    }

    public function wrap(string $newName, string $newSqlExpressionPattern): static
    {
        return new static($newName, $this->value, sprintf($newSqlExpressionPattern, $this->sqlExpressionPattern));
    }
}
