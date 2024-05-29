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
     * @param ?string               $sortField
     * @param string                $sortDirection
     * @param ?int                  $limit
     * @param int                   $offset
     *
     * @return array
     * @throws \PDOException
     */
    public function getEntityList(
        string  $tableName,
        array   $dataTypes,
        array   $labels = [],
        array   $filters = [],
        array   $filterData = [],
        ?string $sortField = null,
        string  $sortDirection = 'asc',
        ?int    $limit = null,
        int     $offset = 0
    ): array {
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
                if (isset($filters[$filterName]) && $filters[$filterName]->whereSqlExprPattern !== null) {
                    $format = $filters[$filterName]->whereSqlExprPattern;
                }
                $arrayParamNames = [];
                foreach (array_values($filterValue) as $i => $value) {
                    $paramName          = strtr($filterName, '()', '__') . '_' . $i;
                    $arrayParamNames[]  = ':' . $paramName;
                    $params[$paramName] = $value;
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

        if ($sortField !== null) {
            if ($this->driverIs('pgsql')) {
                $sortDirection = match (strtolower($sortDirection)) {
                    'asc' => 'ASC NULLS FIRST',
                    'desc' => 'DESC NULLS LAST',
                    default => throw new \InvalidArgumentException(sprintf('Invalid sort direction "%s".', $sortDirection)),
                };
            }
            $sql .= " ORDER BY $sortField $sortDirection";
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

    /**
     * @throws DataProviderException
     * @throws \PDOException
     */
    public function getEntity(string $tableName, array $dataTypes, array $labels, Key $primaryKey): ?array
    {
        $sql = "SELECT " . $this->getAliasesForSelect($dataTypes, $labels) . " FROM $tableName AS entity WHERE " . implode(' AND ', array_map(
                fn($key) => sprintf('%1$s %2$s :%1$s', $key, $this->eqOp()), $primaryKey->getColumnNames()
            ));

        $stmt   = $this->pdo->prepare($sql);
        $params = $this->getTransformedKeyParams($primaryKey, $dataTypes);
        $stmt->execute($params);
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

    /**
     * @throws DataProviderException
     */
    public function updateEntity(string $tableName, array $dataTypes, Key $condition, array $data): void
    {
        if (\count($data) < 1) {
            return;
        }

        foreach ($data as $key => $value) {
            if (isset($dataTypes[$key])) {
                $data[$key] = $this->typeTransformer->dbFromNormalized($value, $dataTypes[$key]);
            }
        }

        $sql = "UPDATE $tableName SET " . implode(', ', array_map(
                static fn($key) => "$key = :$key", array_keys($data)
            )) . " WHERE " . implode(' AND ', array_map(
                fn($key) => sprintf('%1$s %2$s :pk_%1$s', $key, $this->eqOp()), $condition->getColumnNames()
            ));

        $keyParams = $this->getTransformedKeyParams($condition, $dataTypes);
        $keyParams = (new Key($keyParams))->prependColumnNames('pk_')->toArray();
        $params    = array_merge($data, $keyParams);

        $stmt = $this->pdo->prepare($sql);
        try {
            $stmt->execute($params);
        } catch (\PDOException $e) {
            if (
                ($e->errorInfo[1] === 1062 && $this->driverIs('mysql'))
                || ($e->errorInfo[0] === '23505' && $this->driverIs('pgsql'))
                || ($e->errorInfo[1] === 19 && $this->driverIs('sqlite'))
            ) {
                throw new DataProviderException('The entity with same parameters already exists.', 0, $e);
            }
            throw new DataProviderException('Cannot save entity to database', 0, $e);
        }
    }

    /**
     * @throws DataProviderException
     */
    public function createEntity(string $tableName, array $dataTypes, array $data): void
    {
        foreach ($dataTypes as $key => $type) {
            $data[$key] = $this->typeTransformer->dbFromNormalized($data[$key], $type);
        }

        $sql  = "INSERT INTO $tableName (" . implode(', ', array_keys($dataTypes)) . ") VALUES (" . implode(', ', array_map(
                static fn($key) => isset($data[$key]) ? ":$key" : "NULL", array_keys($dataTypes)
            )) . ")";
        $stmt = $this->pdo->prepare($sql);
        try {
            $stmt->execute(array_filter($data, static fn($value) => $value !== null));
        } catch (\PDOException $e) {
            if (
                ($e->errorInfo[1] === 1062 && $this->driverIs('mysql'))
                || ($e->errorInfo[0] === '23505' && $this->driverIs('pgsql'))
                || ($e->errorInfo[1] === 19 && $this->driverIs('sqlite'))
            ) {
                throw new DataProviderException('The entity with same parameters already exists.', 0, $e);
            }
            throw new DataProviderException('Cannot save entity to database', 0, $e);
        }
    }

    public function lastInsertId(): ?string
    {
        try {
            return $this->pdo->lastInsertId() ?: null;
        } catch (\PDOException $e) {
            return null;
        }
    }

    /**
     * @throws DataProviderException
     * @throws \PDOException
     */
    public function deleteEntity(string $tableName, array $dataTypes, Key $condition): int
    {
        $criteria  = implode(' AND ', array_map(
            fn($key) => sprintf('%1$s %2$s :%1$s', $key, $this->eqOp()), $condition->getColumnNames()
        ));
        $selectSql = "SELECT COUNT(*) FROM $tableName WHERE " . $criteria;
        $stmt      = $this->pdo->prepare($selectSql);
        $params    = $this->getTransformedKeyParams($condition, $dataTypes);

        $stmt->execute($params);
        $oldCount = $stmt->fetchColumn();

        $deleteSql = "DELETE FROM $tableName WHERE " . $criteria;
        $stmt      = $this->pdo->prepare($deleteSql);
        try {
            $stmt->execute($params);
        } catch (\PDOException $e) {
            if (
                ($this->driverIs('mysql') && $e->errorInfo[1] === 1451)
                || ($this->driverIs('pgsql') && $e->errorInfo[0] === '23503')
                || ($this->driverIs('sqlite') && $e->errorInfo[1] === 19)
            ) {
                throw new DataProviderException('Cannot delete entity because it is used in other entities.', 0, $e);
            }
            throw new DataProviderException('Cannot delete entity from database', 0, $e);
        }

        $reportedCount = $stmt->rowCount();
        if ($reportedCount > 0) {
            return $reportedCount;
        }

        $stmt = $this->pdo->prepare($selectSql);
        $stmt->execute($params);
        $newCount = $stmt->fetchColumn();

        return $newCount - $oldCount;
    }

    private function getAliasesForSelect(array $dataTypes, array $labels): string
    {
        $aliases = array_map(static fn(string $columnName) => "$columnName AS field_$columnName", array_keys($dataTypes));
        foreach ($labels as $columnName => $sqlExpression) {
            $aliases[] = "$sqlExpression AS label_$columnName";
        }

        if (\count($aliases) === 0) {
            return '0 as _dummy';
            // TODO figure out how to process entities without fields. Allow or not?
            // throw new \LogicException('No fields are configured to be selected.');
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

    /**
     * @throws DataProviderException
     */
    private function driverIs(string $driverName): bool
    {
        $supportedDrivers = ['mysql', 'pgsql', 'sqlite'];
        if (!\in_array($driverName, $supportedDrivers, true)) {
            throw new DataProviderException(sprintf("Unsupported driver: %s. Supported drivers: [%s].", $driverName, implode(', ', $supportedDrivers)));
        }
        return $this->pdo->getAttribute(\PDO::ATTR_DRIVER_NAME) === $driverName;
    }

    private function getTransformedKeyParams(Key $key, array $dataTypes): array
    {
        $result = [];
        foreach ($key->toArray() as $columnName => $value) {
            if (!isset($dataTypes[$columnName])) {
                throw new \LogicException(sprintf('Data type of key field "%s" must be specified', $columnName));
            }
            $result[$columnName] = $this->typeTransformer->dbFromNormalized($value, $dataTypes[$columnName]);
        }

        return $result;
    }

    /**
     * @throws DataProviderException
     */
    private function eqOp(): string
    {
        if ($this->driverIs('mysql')) {
            return '<=>';
        }
        if ($this->driverIs('pgsql')) {
            return 'IS NOT DISTINCT FROM';
        }
        if ($this->driverIs('sqlite')) {
            return 'IS';
        }

        throw new DataProviderException('Unsupported driver.');
    }
}
