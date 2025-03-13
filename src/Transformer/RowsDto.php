<?php
/**
 * @copyright 2025 Roman Parpalak
 * @license   https://opensource.org/license/mit MIT
 * @package   AdminYard
 */

declare(strict_types=1);

namespace S2\AdminYard\Transformer;

readonly class RowsDto implements \Stringable
{
    /**
     * @var int[]|string[]
     */
    private array $allKeys;

    public function __construct(public ?array $data, public array $attributeNames)
    {
        $tempArrayWithALlKeys = $this->attributeNames;
        if ($this->data !== null) {
            foreach ($this->data as $row) {
                foreach ($row as $key => $value) {
                    $tempArrayWithALlKeys[$key] = true;
                }
            }
        }
        $this->allKeys = array_keys($tempArrayWithALlKeys);
    }

    /**
     * @throws \JsonException
     */
    public function __toString(): string
    {
        return json_encode($this->data, JSON_THROW_ON_ERROR);
    }

    /**
     * @return string[]
     */
    public function getLabels(): array
    {
        $result = [];
        foreach ($this->allKeys as $key) {
            $result[] = $this->attributeNames[$key] ?? $key;
        }

        return $result;
    }

    /**
     * @return string[][]|null
     */
    public function getRows(): ?array
    {
        if ($this->data === null) {
            return null;
        }

        $result = [];
        foreach ($this->data as $dataRow) {
            $row = [];
            foreach ($this->allKeys as $key) {
                $row[] = isset($dataRow[$key]) ? (string)$dataRow[$key] :  null;
            }
            $result[] = $row;
        }

        return $result;
    }
}
