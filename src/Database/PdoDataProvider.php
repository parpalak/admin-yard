<?php
/**
 * @copyright 2024 Roman Parpalak
 * @license   http://opensource.org/licenses/MIT MIT
 * @package   AdminYard
 */
/** @noinspection UnknownInspectionInspection */
/** @noinspection PhpComposerExtensionStubsInspection */
/** @noinspection SqlDialectInspection */

declare(strict_types=1);

namespace S2\AdminYard\Database;

use S2\AdminYard\Config\FieldConfig;

readonly class PdoDataProvider
{
    public function __construct(
        private \PDO                     $pdo,
        private TypeTransformerInterface $typeTransformer
    ) {
    }

    /**
     * @param string                                 $tableName
     * @param array<string,string>                   $dataTypes  List of data types configured for this entity.
     *                                                           If some fields are missing, they will not be selected.
     * @param array<string,string|LogicalExpression> $labels     List of SQL expressions to be displayed instead of the
     *                                                           field values.
     * @param LogicalExpression[]                    $conditions Conditions for WHERE clause.
     * @param ?string                                $sortField
     * @param string                                 $sortDirection
     * @param ?int                                   $limit
     * @param int                                    $offset
     *
     * @return array
     * @throws DataProviderException
     */
    public function getEntityList(
        string  $tableName,
        array   $dataTypes,
        array   $labels = [],
        array   $conditions = [],
        ?string $sortField = null,
        string  $sortDirection = 'asc',
        ?int    $limit = null,
        int     $offset = 0
    ): array {
        $paramsSet = [];

        $sql = "SELECT " . $this->getAliasesForSelect($dataTypes, $labels, $paramsSet) . " FROM $tableName AS entity";

        $criteria = [];
        foreach ($conditions as $condition) {
            if (!$condition instanceof LogicalExpression) {
                throw new \InvalidArgumentException(\sprintf('Condition must be instance of %s', LogicalExpression::class));
            }
            if ($condition->isTrivialCondition()) {
                continue;
            }

            $paramsSet[] = $condition->getParams();
            $criteria[]  = $condition->getSqlExpression();
        }

        $params = array_merge(...$paramsSet);
        if (\count($criteria) > 0) {
            $sql .= ' WHERE (' . implode(') AND (', $criteria) . ')';
        }

        if ($sortField !== null) {
            if ($this->driverIs('pgsql')) {
                $sortDirection = match (strtolower($sortDirection)) {
                    'asc' => 'ASC NULLS FIRST',
                    'desc' => 'DESC NULLS LAST',
                    default => throw new \InvalidArgumentException(\sprintf('Invalid sort direction "%s".', $sortDirection)),
                };
            }
            $sql .= " ORDER BY $sortField $sortDirection";
        }

        if ($limit !== null) {
            $sql .= " LIMIT $limit OFFSET $offset";
        }
        $stmt = $this->pdo->prepare($sql);
        try {
            $stmt->execute($params);
            $rows = $stmt->fetchAll(\PDO::FETCH_ASSOC);
        } catch (\PDOException $e) {
            throw new DataProviderException($e->getMessage(), 0, $e);
        }

        foreach ($rows as &$row) {
            foreach ($dataTypes as $columnName => $type) {
                $key       = 'column_' . $columnName;
                $row[$key] = $this->typeTransformer->normalizedFromDb($row[$key], $type);
            }
        }
        unset($row);

        return $rows;
    }

    /**
     * @param string              $tableName
     * @param LogicalExpression[] $conditions Conditions for WHERE clause.
     *
     * @return int
     * @throws DataProviderException
     */
    public function getEntityCount(string $tableName, array $conditions): int
    {
        $sql = "SELECT COUNT(*) FROM $tableName AS entity";

        $criteria  = [];
        $paramsSet = [];
        foreach ($conditions as $condition) {
            if (!$condition instanceof LogicalExpression) {
                throw new \InvalidArgumentException(\sprintf('Condition must be instance of %s', LogicalExpression::class));
            }
            if ($condition->isTrivialCondition()) {
                continue;
            }
            $criteria[]  = $condition->getSqlExpression();
            $paramsSet[] = $condition->getParams();
        }

        $params = array_merge(...$paramsSet);
        if (\count($criteria) > 0) {
            $sql .= ' WHERE (' . implode(') AND (', $criteria) . ')';
        }

        $stmt = $this->pdo->prepare($sql);
        try {
            $stmt->execute($params);
        } catch (\PDOException $e) {
            throw new DataProviderException($e->getMessage(), 0, $e);
        }
        $row = $stmt->fetch(\PDO::FETCH_NUM);
        if ($row === false) {
            return 0;
        }

        return (int)$row[0];
    }

    /**
     * @param LogicalExpression[] $conditions
     *
     * @throws DataProviderException
     */
    public function getEntity(string $tableName, array $dataTypes, array $labels, array $conditions, Key $primaryKey): ?array
    {
        $paramsSet = [];

        $criteria = [];
        foreach ($conditions as $condition) {
            if ($condition->isTrivialCondition()) {
                continue;
            }

            $paramsSet[] = $condition->getParams();
            $criteria[]  = $condition->getSqlExpression();
        }

        foreach ($primaryKey->getColumnNames() as $key) {
            $criteria[] = sprintf('%1$s %2$s :%1$s', $key, $this->eqOp());
        }

        $sql = "SELECT " . $this->getAliasesForSelect($dataTypes, $labels, $paramsSet) . " FROM $tableName AS entity WHERE (" . implode(') AND (', $criteria) . ")";

        $paramsSet[] = $this->getTransformedKeyParams($primaryKey, $dataTypes);

        $params = array_merge(...$paramsSet);
        try {
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute($params);
        } catch (\PDOException $e) {
            throw new DataProviderException($e->getMessage(), 0, $e);
        }
        $row = $stmt->fetch(\PDO::FETCH_ASSOC);

        if ($row === false) {
            return null;
        }

        foreach ($dataTypes as $columnName => $type) {
            $key       = 'column_' . $columnName;
            $row[$key] = $this->typeTransformer->normalizedFromDb($row[$key], $type);
        }
        return $row;
    }

    /**
     * @param LogicalExpression[] $conditions
     *
     * @throws DataProviderException
     * @throws SafeDataProviderException
     */
    public function updateEntity(string $tableName, array $dataTypes, array $conditions, Key $primaryKey, array $data): void
    {
        if (\count($data) < 1) {
            return;
        }

        $paramsSet = [];

        $criteria = [];
        foreach ($conditions as $condition) {
            if ($condition->isTrivialCondition()) {
                continue;
            }

            $condition = $condition->withNamePrefix('cond_');

            $paramsSet[] = $condition->getParams();
            $criteria[]  = $condition->getSqlExpression();
        }

        foreach ($primaryKey->getColumnNames() as $key) {
            $criteria[] = sprintf('%1$s %2$s :pk_%1$s', $key, $this->eqOp());
        }

        foreach ($data as $key => $value) {
            if (isset($dataTypes[$key])) {
                $data[$key] = $this->typeTransformer->dbFromNormalized($value, $dataTypes[$key]);
            }
        }

        $sql = "UPDATE $tableName AS entity SET " . implode(', ', array_map(
                static fn($key) => "$key = :$key", array_keys($data)
            )) . " WHERE (" . implode(') AND (', $criteria) . ")";

        $keyParams = $this->getTransformedKeyParams($primaryKey, $dataTypes);
        $keyParams = (new Key($keyParams))->prependColumnNames('pk_')->toArray();
        $params    = array_merge($data, $keyParams, ...$paramsSet);

        $stmt = $this->pdo->prepare($sql);
        try {
            $stmt->execute($params);
        } catch (\PDOException $e) {
            if ($this->isUniqueConstraintViolation($e)) {
                throw new SafeDataProviderException('The entity with same parameters already exists.', 422, $e);
            }
            throw new SafeDataProviderException('Cannot save entity to database', 500, $e);
        }
    }

    /**
     * @throws SafeDataProviderException
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
            if ($this->isUniqueConstraintViolation($e)) {
                throw new SafeDataProviderException('The entity with same parameters already exists.', 0, $e);
            }
            throw new SafeDataProviderException('Cannot save entity to database', 0, $e);
        }
    }

    public function lastInsertId(): ?string
    {
        try {
            return $this->pdo->lastInsertId() ?: null;
        } catch (\PDOException) {
            return null;
        }
    }

    /**
     * @param string              $tableName
     * @param array               $dataTypes
     * @param Key                 $keyCondition Identifier of entity to be deleted.
     *                                          It is possible to pass a part of composite primary key. Then several
     *                                          entities will be deleted. This behavior is not compatible with
     *                                          $conditions in current realization.
     * @param LogicalExpression[] $conditions
     *
     * @return int
     * @throws DataProviderException
     */
    public function deleteEntity(string $tableName, array $dataTypes, Key $keyCondition, array $conditions): int
    {
        $deleteParams = $this->getTransformedKeyParams($keyCondition, $dataTypes);
        $paramsSet    = [$deleteParams];

        $selectCriteria = $deleteCriteria = array_map(
            fn($key) => \sprintf('%1$s %2$s :%1$s', $key, $this->eqOp()), $keyCondition->getColumnNames()
        );

        foreach ($conditions as $condition) {
            if ($condition->isTrivialCondition()) {
                continue;
            }

            $paramsSet[]      = $condition->getParams();
            $selectCriteria[] = $condition->getSqlExpression();
        }

        $selectParams = array_merge(...$paramsSet);
        $selectWhere  = '(' . implode(') AND (', $selectCriteria) . ')';
        $deleteWhere  = '(' . implode(') AND (', $deleteCriteria) . ')';

        /**
         * Now access control conditions are checked in SELECT, not in DELETE.
         * It's due to MariaDB does not support aliases in DELETE
         */
        $selectSql = "SELECT COUNT(*) FROM $tableName AS entity WHERE $selectWhere";
        $stmt      = $this->pdo->prepare($selectSql);
        try {
            $stmt->execute($selectParams);
        } catch (\PDOException $e) {
            throw new SafeDataProviderException('Cannot delete entity from database', 0, $e);
        }
        $oldCount = $stmt->fetchColumn();

        if ($oldCount === 0) {
            // If there is no access to the deleting entity, we exit here.
            return 0;
        }

        $deleteSql = "DELETE FROM $tableName WHERE $deleteWhere";
        $stmt      = $this->pdo->prepare($deleteSql);
        try {
            $stmt->execute($deleteParams);
        } catch (\PDOException $e) {
            if (
                ($this->driverIs('mysql') && $e->errorInfo[1] === 1451)
                || ($this->driverIs('pgsql') && $e->errorInfo[0] === '23503')
                || ($this->driverIs('sqlite') && $e->errorInfo[1] === 19)
            ) {
                throw new SafeDataProviderException('Cannot delete entity because it is used in other entities.', 0, $e);
            }
            throw new SafeDataProviderException('Cannot delete entity from database', 0, $e);
        }

        $reportedCount = $stmt->rowCount();
        if ($reportedCount > 0) {
            return $reportedCount;
        }

        $stmt = $this->pdo->prepare($selectSql);
        try {
            $stmt->execute($selectParams);
        } catch (\PDOException $e) {
            throw new SafeDataProviderException('Cannot delete entity from database', 0, $e);
        }
        $newCount = $stmt->fetchColumn();

        return $newCount - $oldCount;
    }

    /**
     * @param array<string,LogicalExpression> $conditions
     *
     * @return array<string,string>
     * @throws DataProviderException
     */
    public function getLabelsFromTable(
        string $tableName,
        string $idColumn,
        string $titleSqlExpression,
        array  $conditions,
    ): array {
        $paramsSet = [];
        $criteria  = ['1=1'];
        foreach ($conditions as $condition) {
            if ($condition->isTrivialCondition()) {
                continue;
            }

            $paramsSet[] = $condition->getParams();
            $criteria[]  = $condition->getSqlExpression();
        }

        $sql = "SELECT $idColumn, $titleSqlExpression AS label FROM $tableName WHERE (" . implode(') AND (', $criteria) . ")";

        $stmt = $this->pdo->prepare($sql);
        try {
            $stmt->execute(array_merge(...$paramsSet));
            return $stmt->fetchAll(\PDO::FETCH_KEY_PAIR);
        } catch (\PDOException $e) {
            throw new DataProviderException($e->getMessage(), 0, $e);
        }
    }

    /**
     * @param array<string,LogicalExpression> $conditions
     *
     * @throws DataProviderException
     */
    public function getAutocompleteResults(
        string $tableName,
        string $idColumn,
        string $autocompleteSqlExpression,
        array  $conditions,
        string $query,
        int    $limit,
        ?int   $additionalId,
    ): array {
        $paramsSet = [];

        $criteria = [];
        foreach ($conditions as $condition) {
            if ($condition->isTrivialCondition()) {
                continue;
            }

            $paramsSet[] = $condition->getParams();
            $criteria[]  = $condition->getSqlExpression();
        }

        $queryWhere      = '(' . implode(') AND (', array_merge($criteria, ["LOWER($autocompleteSqlExpression) LIKE :query"])) . ')';
        $paramsSet[]     = [
            'query' => '%' . mb_strtolower($query) . '%',
        ];
        $additionalWhere = '(' . implode(') AND (', array_merge($criteria, ["$idColumn = :additionalId"])) . ')';
        $paramsSet[]     = [
            'additionalId' => $additionalId,
        ];

        // NOTE: Maybe it's worth to search over each column in expression separately to improve performance?
        // And to control over '%foo%' or 'foo%' to be searched?
        $sql  = <<<SQL
SELECT * FROM (SELECT
    $idColumn AS value,
    $autocompleteSqlExpression AS text
FROM $tableName
WHERE $queryWhere
ORDER BY $autocompleteSqlExpression
LIMIT $limit) AS tmp
UNION
SELECT
    $idColumn AS value,
    $autocompleteSqlExpression AS text
FROM $tableName
WHERE $additionalWhere
ORDER BY text
SQL;
        $stmt = $this->pdo->prepare($sql);
        try {
            $stmt->execute(array_merge(...$paramsSet));
        } catch (\PDOException $e) {
            throw new DataProviderException($e->getMessage(), 0, $e);
        }

        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }

    /**
     * @param array<string,string|LogicalExpression> $labels
     */
    private function getAliasesForSelect(array $dataTypes, array $labels, array &$paramsSet): string
    {
        $aliases = array_map(
            static fn(string $columnName) => $dataTypes[$columnName] === FieldConfig::DATA_TYPE_PASSWORD ? "'***' AS column_$columnName" : "$columnName AS column_$columnName",
            array_keys($dataTypes)
        );
        foreach ($labels as $columnName => $label) {
            if ($label instanceof LogicalExpression) {
                $paramsSet[] = $label->getParams();
                $aliases[]   = $label->getSqlExpression() . ' AS virtual_' . $columnName;
            } else {
                $aliases[] = "$label AS virtual_$columnName";
            }
        }

        if (\count($aliases) === 0) {
            return '0 as _dummy';
            // TODO figure out how to process entities without fields. Allow or not?
            // throw new \LogicException('No fields are configured to be selected.');
        }

        return implode(', ', $aliases);
    }

    private function driverIs(string $driverName): bool
    {
        $supportedDrivers = ['mysql', 'pgsql', 'sqlite'];
        if (!\in_array($driverName, $supportedDrivers, true)) {
            throw new \InvalidArgumentException(sprintf("Unsupported driver: %s. Supported drivers: [%s].", $driverName, implode(', ', $supportedDrivers)));
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

    private function isUniqueConstraintViolation(\PDOException $e): bool
    {
        return ($e->errorInfo[1] === 1062 && $this->driverIs('mysql'))
            || ($e->errorInfo[0] === '23505' && $this->driverIs('pgsql'))
            || ($e->errorInfo[1] === 19 && $this->driverIs('sqlite') && str_contains($e->getMessage(), 'UNIQUE'));
    }
}
