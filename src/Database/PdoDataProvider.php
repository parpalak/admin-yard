<?php
/**
 * @copyright 2024 Roman Parpalak
 * @license http://opensource.org/licenses/MIT MIT
 * @package AdminYard
 */

declare(strict_types=1);

namespace S2\AdminYard\Database;

readonly class PdoDataProvider
{
    public function __construct(
        private \PDO                     $pdo,
        private TypeTransformerInterface $typeTransformer
    ) {
    }

    public function getEntityList(string $tableName, array $dataTypes, array $labels, ?int $limit, int $offset): array
    {
        $sql = "SELECT " . $this->getAliasesForSelect($dataTypes, $labels) . " FROM $tableName AS entity";
        if ($limit !== null) {
            $sql .= " LIMIT $limit OFFSET $offset";
        }
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute();
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
