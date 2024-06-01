<?php
/**
 * @copyright 2024 Roman Parpalak
 * @license   http://opensource.org/licenses/MIT MIT
 * @package   AdminYard
 */

declare(strict_types=1);

namespace S2\AdminYard\Config;

readonly class FilterLinkTo extends Filter
{
    public EntityConfig $foreignEntity;

    public function __construct(
        FieldConfig $fieldConfig,
        ?string     $label,
    ) {
        if ($fieldConfig->linkToEntity === null) {
            throw new \InvalidArgumentException(sprintf(
                'Filter "LinkTo" can only be used for a field with linkToEntity set. Field "%s" has no linkToEntity configured.',
                $fieldConfig->name,
            ));
        }

        $this->foreignEntity = $fieldConfig->linkToEntity->foreignEntity;

        parent::__construct(
            $fieldConfig->name,
            $label ?? $fieldConfig->getLabel(),
            $fieldConfig->control === 'autocomplete' ? 'autocomplete' : 'select',
            $fieldConfig->name . ' = %1$s'
        );
    }
}
