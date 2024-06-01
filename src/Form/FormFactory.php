<?php
/**
 * @copyright 2024 Roman Parpalak
 * @license   http://opensource.org/licenses/MIT MIT
 * @package   AdminYard
 */

declare(strict_types=1);

namespace S2\AdminYard\Form;

use Random\RandomException;
use S2\AdminYard\Config\EntityConfig;
use S2\AdminYard\Config\FilterLinkTo;
use S2\AdminYard\Database\PdoDataProvider;
use Symfony\Component\HttpFoundation\Request;
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
     */
    public function createEntityForm(EntityConfig $entityConfig, string $action, Request $request): Form
    {
        // NOTE: Pass primary key and generate form action here?
        $form      = new Form($this->translator);
        $csrfToken = $this->generateCsrfToken($entityConfig->getName(), $action, [], $request);
        $form->setCsrfToken($csrfToken);

        foreach ($entityConfig->getFields($action) as $field) {
            $columnName  = $field->name;
            $controlName = $field->control;
            if ($controlName === null) {
                throw new \DomainException(sprintf(
                    'Field "%s" for entity "%s" must have a control configured.',
                    $columnName,
                    $entityConfig->getName()
                ));
            }
            $control = $this->formControlFactory->create($controlName, $columnName);

            // Dealing with options
            if ($field->linkToEntity !== null) {
                $foreignEntity = $field->linkToEntity->foreignEntity;

                if ($control instanceof OptionsInterface) {
                    $options = $this->dataProvider->getLabelsFromTable(
                        $foreignEntity->getTableName(),
                        $foreignEntity->getFieldNamesOfPrimaryKey(),
                        $field->linkToEntity->titleSqlExpression
                    );
                    if ($field->canBeEmpty()) {
                        $options[''] = self::EMPTY_SELECT_LABEL;
                    }
                    $control->setOptions($options);

                } elseif ($control instanceof Autocomplete) {
                    $control->setAutocompleteParams(
                        $foreignEntity->getName(),
                        md5($field->linkToEntity->titleSqlExpression),
                        fn(string $value) => $this->dataProvider->getAutocompleteResults(
                            $foreignEntity->getTableName(),
                            $foreignEntity->getFieldNamesOfPrimaryKey()[0],
                            $field->linkToEntity->titleSqlExpression,
                            '',
                            (int)$value,
                        ),
                        $field->canBeEmpty()
                    );

                } else {
                    throw new \DomainException(sprintf(
                        'Field "%s" for entity "%s" must have a control configured as OptionsInterface or Autocomplete.',
                        $columnName,
                        $entityConfig->getName()
                    ));
                }
            } elseif ($control instanceof OptionsInterface) {
                $options = $field->options;
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

            $control->setValidators(...$field->validators);

            $form->addControl($control, $columnName);
        }

        return $form;
    }

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
                    throw new \DomainException(sprintf(
                        'Filter "%s" for entity "%s" of type "LinkTo" cannot be applied since there is no such field.',
                        $filterName,
                        $entityConfig->getName()
                    ));
                }
                $field = $linkToFields[$filter->name];
                if ($field->linkToEntity->foreignEntity !== $filter->foreignEntity) {
                    throw new \DomainException(sprintf(
                        'Filter "%s" for entity "%s" of type "LinkTo" cannot be applied since it is not pointing to the same entity as corresponding field.',
                        $filterName,
                        $entityConfig->getName()
                    ));
                }

                if ($control instanceof OptionsInterface) {
                    $options = $this->dataProvider->getLabelsFromTable(
                        $field->linkToEntity->foreignEntity->getTableName(),
                        $field->linkToEntity->foreignEntity->getFieldNamesOfPrimaryKey(),
                        $field->linkToEntity->titleSqlExpression
                    );

                    $options[''] = self::EMPTY_SELECT_LABEL;
                    $control->setOptions($options);

                } elseif ($control instanceof Autocomplete) {
                    $control->setAutocompleteParams(
                        $field->linkToEntity->foreignEntity->getName(),
                        md5($field->linkToEntity->titleSqlExpression),
                        fn(string $value) => $this->dataProvider->getAutocompleteResults(
                            $field->linkToEntity->foreignEntity->getTableName(),
                            $field->linkToEntity->foreignEntity->getFieldNamesOfPrimaryKey()[0],
                            $field->linkToEntity->titleSqlExpression,
                            '',
                            (int)$value
                        ),
                        true
                    );

                } else {
                    throw new \LogicException(sprintf(
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

        // 2. Add external references to the filter form as hidden fields if there were no corresponding filters.
        foreach ($linkToFields as $field) {
            $hidden = $this->formControlFactory->create('hidden_input', $field->name);
            $form->addControl($hidden, $field->name);
        }

        return $form;
    }

    public function generateCsrfToken(string $entityName, string $action, array $primaryKey, Request $request): string
    {
        $session = $request->getSession();
        if (!$session->has('main_csrf_token')) {
            try {
                $mainToken = bin2hex(random_bytes(16));
            } catch (RandomException $e) {
                $mainToken = sha1(uniqid((string)mt_rand(), true));
            }
            $session->set('main_csrf_token', $mainToken);
        } else {
            $mainToken = $session->get('main_csrf_token');
        }

        return sha1(serialize([$entityName, $action, $primaryKey, $mainToken]));
    }
}
