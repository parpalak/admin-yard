<?php
/**
 * @copyright 2024 Roman Parpalak
 * @license   http://opensource.org/licenses/MIT MIT
 * @package   AdminYard
 */

declare(strict_types=1);

namespace S2\AdminYard\Form;

use S2\AdminYard\Config\EntityConfig;
use S2\AdminYard\Config\FieldConfig;
use S2\AdminYard\Database\PdoDataProvider;
use Symfony\Contracts\Translation\TranslatorInterface;

readonly class FormFactory
{
    public function __construct(
        private FormControlFactoryInterface $formControlFactory,
        private TranslatorInterface         $translator,
        private PdoDataProvider             $dataProvider
    ) {
    }

    /**
     * @throws \DomainException in case of incorrect entity configuration, may be visible during AdminYard integration.
     * @throws \LogicException if a violation of invariants is detected, may be visible in case of AdminYard bugs.
     */
    public function createEntityForm(EntityConfig $entityConfig, string $action): Form
    {
        $form = new Form($this->translator);

        foreach ($entityConfig->getFields($action) as $field) {
            if ($field->getDataType() === FieldConfig::DATA_TYPE_VIRTUAL) {
                continue;
            }

            $columnName  = $field->getName();
            $controlName = $field->getControl();
            if ($controlName === null) {
                throw new \DomainException(sprintf(
                    'Field "%s" for entity "%s" must have a control configured.',
                    $columnName,
                    $entityConfig->getName()
                ));
            }
            $control = $this->formControlFactory->create($controlName, $columnName);

            // Dealing with options
            $foreignEntity = $field->getForeignEntity();
            if ($foreignEntity !== null) {
                if (!$control instanceof OptionsInterface) {
                    throw new \DomainException(sprintf(
                        'Field "%s" for entity "%s" must have a control configured as OptionsInterface.',
                        $columnName,
                        $entityConfig->getName()
                    ));
                }
                // TODO: Implement some kind of ajax autocomplete for large tables.
                $control->setOptions($this->dataProvider->getLabelsFromTable(
                    $foreignEntity->getTableName(),
                    $foreignEntity->getFieldNamesOfPrimaryKey(),
                    $field->getTitleSqlExpression()
                ));
            } elseif ($control instanceof OptionsInterface) {
                $options = $field->getOptions();
                if ($options === null) {
                    throw new \DomainException(sprintf(
                        'Field "%s" for entity "%s" must have options configured since its control is "%s".',
                        $columnName,
                        $entityConfig->getName(),
                        $controlName
                    ));
                }
                $control->setOptions($options);
            }

            $control->setValidators(...$field->getValidators());

            $form->addControl($control, $columnName);
        }

        return $form;
    }

    public function createFilterForm(EntityConfig $entityConfig): Form
    {
        $form = new Form($this->translator);

        // 1. Add external references to the filter form
        foreach ($entityConfig->getManyToOneFields() as $field) {
            $foreignEntity = $field->getForeignEntity();
            if ($foreignEntity === null) {
                // @codeCoverageIgnoreStart
                throw new \LogicException(sprintf(
                    'Field "%s" for entity "%s" must have a foreign entity since it is a many-to-one association.',
                    $field->getName(),
                    $entityConfig->getName()
                ));
                // @codeCoverageIgnoreEnd
            }
            /** @var Select $select */
            $select = $this->formControlFactory->create('select', $field->getName());

            // TODO: Implement some kind of ajax autocomplete for large tables.
            $options = $this->dataProvider->getLabelsFromTable(
                $foreignEntity->getTableName(),
                $foreignEntity->getFieldNamesOfPrimaryKey(),
                $field->getTitleSqlExpression()
            );

            $options[''] = '–';

            $select->setOptions($options);
            $form->addControl($select, $field->getName());
        }

        // 2. Add filters from configuration.
        foreach ($entityConfig->getFilters() as $filter) {
            $filterName = $filter->name;
            $control    = $this->formControlFactory->create($filter->control, $filterName);
            if ($control instanceof OptionsInterface) {
                $options = $filter->options;
                if ($options === null) {
                    throw new \DomainException(sprintf(
                        'Filter "%s" for entity "%s" must have options configured since its control is "%s".',
                        $filterName,
                        $entityConfig->getName(),
                        $filter->control
                    ));
                }
                $control->setOptions($options);
            }
            $form->addControl($control, $filterName);
        }

        return $form;
    }
}
