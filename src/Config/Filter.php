<?php
/**
 * @copyright 2024 Roman Parpalak
 * @license   http://opensource.org/licenses/MIT MIT
 * @package   AdminYard
 */

declare(strict_types=1);

namespace S2\AdminYard\Config;

use S2\AdminYard\Database\LogicalExpression;

readonly class Filter
{
    /**
     * @param string    $name                 A name of the query parameter to be used in the filter form
     * @param string    $label                A label of the field in the filter form
     * @param string    $control              A control to be used in the filter form
     * @param string    $whereSqlExprPattern  An SQL expression to be used in WHERE clause.
     *                                        Supposed to include table columns and %1$s placeholder
     *                                        for the filter value.
     * @param ?\Closure $paramTransformer     A function to be applied to the filter value before its binding
     *                                        to the query. Necessary for converting types to match the column contents
     *                                        in the table, as filters do not know the corresponding data types.
     *                                        It can also be used to add special characters, e.g. % for LIKE operator.
     * @param ?array    $options              An array of options if the control requires it.
     */
    public function __construct(
        public string    $name,
        public string    $label,
        public string    $control,
        public string    $whereSqlExprPattern,
        public ?\Closure $paramTransformer = null,
        public ?array    $options = null,
    ) {
    }

    public function getCondition(mixed $value): LogicalExpression
    {
        return new LogicalExpression(
            $this->name,
            $this->transformParamValue($value),
            $this->whereSqlExprPattern
        );
    }

    private function transformParamValue(mixed $value): mixed
    {
        return $this->paramTransformer !== null ? ($this->paramTransformer)($value) : $value;
    }
}
