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
use S2\AdminYard\Database\PdoDataProvider;
use Symfony\Component\HttpFoundation\Request;
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
                    $field->linkToEntity->titleSqlExpression
                ));
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

        // 1. Add external references to the filter form
        // TODO move to config
        foreach ($entityConfig->getManyToOneFields() as $field) {
            if ($field->linkToEntity === null) {
                // @codeCoverageIgnoreStart
                throw new \LogicException(sprintf(
                    'Field "%s" for entity "%s" must have a foreign entity since it is a many-to-one association.',
                    $field->name,
                    $entityConfig->getName()
                ));
                // @codeCoverageIgnoreEnd
            }
            /** @var Select $select */
            $select = $this->formControlFactory->create('select', $field->name);

            // TODO: Implement some kind of ajax autocomplete for large tables.
            $options = $this->dataProvider->getLabelsFromTable(
                $field->linkToEntity->foreignEntity->getTableName(),
                $field->linkToEntity->foreignEntity->getFieldNamesOfPrimaryKey(),
                $field->linkToEntity->titleSqlExpression
            );

            $options[''] = '–';

            $select->setOptions($options);
            $form->addControl($select, $field->name);
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
