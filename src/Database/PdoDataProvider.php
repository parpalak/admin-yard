<?php
/**
 * @copyright 2024 Roman Parpalak
 * @license   http://opensource.org/licenses/MIT MIT
 * @package   AdminYard
 */

declare(strict_types=1);

namespace S2\AdminYard\Database;

use S2\AdminYard\Config\Filter;

readonly class PdoDataProvider
{
    public function __construct(
        private \PDO                     $pdo,
        private TypeTransformerInterface $typeTransformer
    ) {
    }

    /**
     * @param string                $tableName
     * @param array<string,string>  $dataTypes  List of data types configured for this entity.
     *                                          If some fields are missing, they will be skipped in the query.
     * @param array<string,string>  $labels     List of SQL expressions to be displayed instead of the field values.
     * @param array<string, Filter> $filters    List of Filters configured for this entity
     * @param array<string,mixed>   $filterData Content of the filter form
     * @param int|null              $limit
     * @param int                   $offset
     *
     * @return array
     */
    public function getEntityList(string $tableName, array $dataTypes, array $labels, array $filters, array $filterData, ?int $limit, int $offset): array
    {
        $sql = "SELECT " . $this->getAliasesForSelect($dataTypes, $labels) . " FROM $tableName AS entity";

        $params = [];

        $criteria = [];
        foreach ($filterData as $filterName => $filterValue) {
            if (isset($filters[$filterName])) {
                $filterValue = $filters[$filterName]->transformParamValue($filterValue);
            }
            if ($filterValue === null || $filterValue === '' || $filterValue === []) {
                // NOTE: maybe '' and [] must be excluded somewhere else, not for all filters here.
                // It can be useful in case of different semantics for null and [].
                continue;
            }

            if (\is_array($filterValue)) {
                $format = "$filterName IN (%s)";
                if (isset($filters[$filterName]) && $filters[$filterName]->sqlExprPattern !== null) {
                    $format = $filters[$filterName]->sqlExprPattern;
                }
                $arrayParamNames = [];
                foreach (array_values($filterValue) as $i => $value) {
                    $arrayParamNames[]              = ':' . $filterName . '_' . $i;
                    $params[$filterName . '_' . $i] = $value;
                }
                $criteria[] = sprintf($format, implode(', ', $arrayParamNames));
                continue;
            }

            $params[$filterName] = $filterValue;
            if (isset($filters[$filterName])) {
                $criteria[] = $filters[$filterName]->getSqlExpressionWithSubstitutions();
            } else {
                $criteria[] = "$filterName = :$filterName";
            }
        }

        if (\count($criteria) > 0) {
            $sql .= ' WHERE (' . implode(') AND (', $criteria) . ')';
        }

        if ($limit !== null) {
            $sql .= " LIMIT $limit OFFSET $offset";
        }
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        $rows = $stmt->fetchAll(\PDO::FETCH_ASSOC);

        foreach ($rows as &$row) {
            foreach ($dataTypes as $columnName => $type) {
                $key       = 'field_' . $columnName;
                $row[$key] = $this->typeTransformer->normalizedFromDb($row[$key], $type);
            }
        }
        unset($row);

        return $rows;
    }

    public function getEntity(string $tableName, array $dataTypes, array $labels, PrimaryKey $primaryKey): ?array
    {
        $sql = "SELECT " . $this->getAliasesForSelect($dataTypes, $labels) . " FROM $tableName AS entity WHERE " . implode(' AND ', array_map(
                static fn($key) => "$key = :$key", $primaryKey->getColumnNames()
            ));

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($primaryKey->toArray());
        $row = $stmt->fetch(\PDO::FETCH_ASSOC);

        if ($row === false) {
            return null;
        }

        foreach ($dataTypes as $columnName => $type) {
            $key       = 'field_' . $columnName;
            $row[$key] = $this->typeTransformer->normalizedFromDb($row[$key], $type);
        }
        return $row;
    }

    public function updateEntity(string $tableName, array $dataTypes, PrimaryKey $primaryKey, array $data): void
    {
        if (\count($data) < 1) {
            return;
        }

        foreach ($dataTypes as $key => $type) {
            $data[$key] = $this->typeTransformer->dbFromNormalized($data[$key], $type);
        }

        $sql = "UPDATE $tableName SET " . implode(', ', array_map(
                static fn($key) => "$key = :$key", array_keys($data)
            )) . " WHERE " . implode(' AND ', array_map(
                static fn($key) => "$key = :pk_$key", $primaryKey->getColumnNames()
            ));

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute(array_merge($data, $primaryKey->prepend('pk_')->toArray()));
    }

    public function createEntity(string $tableName, array $dataTypes, array $data): ?string
    {
        foreach ($dataTypes as $key => $type) {
            $data[$key] = $this->typeTransformer->dbFromNormalized($data[$key], $type);
        }

        $sql  = "INSERT INTO $tableName (" . implode(', ', array_keys($dataTypes)) . ") VALUES (" . implode(', ', array_map(
                static fn($key) => isset($data[$key]) ? ":$key" : "NULL", array_keys($dataTypes)
            )) . ")";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute(array_filter($data, static fn($value) => $value !== null));

        try {
            return $this->pdo->lastInsertId() ?: null;
        } catch (\PDOException) {
            return null;
        }
    }

    public function deleteEntity(string $tableName, PrimaryKey $primaryKey): void
    {
        $sql  = "DELETE FROM $tableName WHERE " . implode(' AND ', array_map(
                static fn($key) => "$key = :$key", $primaryKey->getColumnNames()
            ));
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($primaryKey->toArray());
    }

    private function getAliasesForSelect(array $dataTypes, array $labels): string
    {
        $aliases = array_map(static fn(string $columnName) => "$columnName AS field_$columnName", array_keys($dataTypes));
        foreach ($labels as $columnName => $sqlExpression) {
            $aliases[] = "$sqlExpression AS label_$columnName";
        }

        return implode(', ', $aliases);
    }

    /**
     * @return array<string, string>
     */
    public function getLabelsFromTable(string $tableName, array $primaryKeyColumnNames, string $titleSqlExpression): array
    {
        $sql = "SELECT $primaryKeyColumnNames[0], $titleSqlExpression AS label FROM $tableName";
        return $this->pdo->query($sql)->fetchAll(\PDO::FETCH_KEY_PAIR);
    }
}
