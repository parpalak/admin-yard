<?php
/**
 * @copyright 2024 Roman Parpalak
 * @license   http://opensource.org/licenses/MIT MIT
 * @package   AdminYard
 */

declare(strict_types=1);

namespace S2\AdminYard\Form;

use S2\AdminYard\Config\EntityConfig;
use S2\AdminYard\Config\FilterLinkTo;
use S2\AdminYard\Database\DatabaseHelper;
use S2\AdminYard\Database\DataProviderException;
use S2\AdminYard\Database\PdoDataProvider;
use Symfony\Contracts\Translation\TranslatorInterface;

readonly class FormFactory
{
    public const EMPTY_SELECT_LABEL = '–';

    public function __construct(
        private FormControlFactoryInterface $formControlFactory,
        private TranslatorInterface         $translator,
        private PdoDataProvider             $dataProvider
    ) {
    }

    /**
     * @throws \DomainException if entities are not configured correctly, may appear during AdminYard integration.
     * @throws \LogicException if a violation of invariants is detected, may appear in case of AdminYard bugs.
     * @throws DataProviderException
     */
    public function createEntityForm(FormParams $formParams): Form
    {
        $form = new Form($this->translator);
        $form->setCsrfToken($formParams->getCsrfToken());

        $entityName = $formParams->entityName;

        foreach ($formParams->fields as $field) {
            $columnName  = $field->name;
            $controlName = $field->control;
            if ($controlName === null) {
                throw new \DomainException(\sprintf(
                    'Field "%s" for entity "%s" must have a control configured.',
                    $columnName,
                    $entityName
                ));
            }
            $control = $this->formControlFactory->create($controlName, $columnName);

            // Dealing with options
            if ($field->linkToEntity !== null) {
                $foreignEntity = $field->linkToEntity->foreignEntity;

                if ($control instanceof OptionsInterface) {
                    $options = $this->dataProvider->getLabelsFromTable(
                        $foreignEntity->getTableName(),
                        $foreignEntity->getFieldNamesOfPrimaryKey()[0],
                        $field->linkToEntity->titleSqlExpression,
                        array_merge(DatabaseHelper::getReadAccessControlConditions($foreignEntity), $field->linkToEntity->getConditions()),
                    );
                    if ($field->canBeEmpty()) {
                        $options[''] = self::EMPTY_SELECT_LABEL;
                    }
                    $control->setOptions($options);

                } elseif ($control instanceof Autocomplete) {
                    $control->setAutocompleteParams(
                        $foreignEntity->getName(),
                        $field->linkToEntity->getHash(),
                        fn(string $value, int $limit = 20) => $this->dataProvider->getAutocompleteResults(
                            $foreignEntity->getTableName(),
                            $foreignEntity->getFieldNamesOfPrimaryKey()[0],
                            $field->linkToEntity->titleSqlExpression,
                            array_merge(DatabaseHelper::getReadAccessControlConditions($foreignEntity), $field->linkToEntity->getConditions()),
                            '',
                            $limit,
                            (int)$value,
                        ),
                        $field->canBeEmpty()
                    );

                } else {
                    throw new \DomainException(\sprintf(
                        'Field "%s" for entity "%s" must have a control configured as OptionsInterface or Autocomplete.',
                        $columnName,
                        $entityName
                    ));
                }
            } elseif ($control instanceof OptionsInterface) {
                $options = $field->options;
                if ($options === null) {
                    throw new \DomainException(\sprintf(
                        'Field "%s" for entity "%s" must have options configured since its control is "%s".',
                        $columnName,
                        $entityName,
                        $controlName
                    ));
                }
                $control->setOptions($options);
            } elseif ($control instanceof ColorInput && $field->options !== null) {
                $control->setOptions($field->options);
            }

            $control->setValidators(...$field->validators);

            $form->addControl($control, $columnName);
        }

        return $form;
    }

    /**
     * @throws DataProviderException
     */
    public function createFilterForm(EntityConfig $entityConfig): Form
    {
        $form = new Form($this->translator);

        $linkToFields = $entityConfig->getManyToOneFields();

        // 1. Add filters from configuration.
        foreach ($entityConfig->getFilters() as $filter) {
            $filterName = $filter->name;
            $control    = $this->formControlFactory->create($filter->control, $filterName);
            if ($filter instanceof FilterLinkTo) {
                if (!isset($linkToFields[$filterName])) {
                    throw new \DomainException(\sprintf(
                        'Filter "%s" for entity "%s" of type "LinkTo" cannot be applied since there is no such field.',
                        $filterName,
                        $entityConfig->getName()
                    ));
                }
                $field              = $linkToFields[$filter->name];
                $fieldForeignEntity = $field->linkToEntity->foreignEntity;
                if ($fieldForeignEntity !== $filter->foreignEntity) {
                    throw new \DomainException(\sprintf(
                        'Filter "%s" for entity "%s" of type "LinkTo" cannot be applied since it is not pointing to the same entity as corresponding field.',
                        $filterName,
                        $entityConfig->getName()
                    ));
                }

                if ($control instanceof OptionsInterface) {
                    $options = $this->dataProvider->getLabelsFromTable(
                        $fieldForeignEntity->getTableName(),
                        $fieldForeignEntity->getFieldNamesOfPrimaryKey()[0],
                        $field->linkToEntity->titleSqlExpression,
                        array_merge(DatabaseHelper::getReadAccessControlConditions($fieldForeignEntity), $field->linkToEntity->getConditions()),
                    );

                    $options[''] = self::EMPTY_SELECT_LABEL;
                    $control->setOptions($options);

                } elseif ($control instanceof Autocomplete) {
                    $control->setAutocompleteParams(
                        $fieldForeignEntity->getName(),
                        $field->linkToEntity->getHash(),
                        fn(string $value, int $limit = 20) => $this->dataProvider->getAutocompleteResults(
                            $field->linkToEntity->foreignEntity->getTableName(),
                            $field->linkToEntity->foreignEntity->getFieldNamesOfPrimaryKey()[0],
                            $field->linkToEntity->titleSqlExpression,
                            array_merge(DatabaseHelper::getReadAccessControlConditions($fieldForeignEntity), $field->linkToEntity->getConditions()),
                            '',
                            $limit,
                            (int)$value
                        ),
                        true
                    );

                } else {
                    throw new \LogicException(\sprintf(
                        'Filter "%s" for entity "%s" of type "LinkTo" must have a control configured as OptionsInterface or Autocomplete, "%s" given.',
                        $filterName,
                        $entityConfig->getName(),
                        $filter->control
                    ));
                }

                unset($linkToFields[$filterName]);

            } elseif ($control instanceof OptionsInterface) {
                $options = $filter->options;
                if ($options === null) {
                    throw new \DomainException(\sprintf(
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

        // 2. Add external references to the filter form as hidden fields if there were no corresponding filters.
        foreach ($linkToFields as $field) {
            $hidden = $this->formControlFactory->create('hidden_input', $field->name);
            $form->addControl($hidden, $field->name);
        }

        return $form;
    }
}
