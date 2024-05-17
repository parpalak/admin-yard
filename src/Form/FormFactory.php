<?php
/**
 * @copyright 2024 Roman Parpalak
 * @license http://opensource.org/licenses/MIT MIT
 * @package AdminYard
 */

declare(strict_types=1);

namespace S2\AdminYard\Form;

use S2\AdminYard\Config\EntityConfig;
use S2\AdminYard\Database\PdoDataProvider;

readonly class FormFactory
{
    public function __construct(
        private FormControlFactoryInterface $formControlFactory,
        private PdoDataProvider             $dataProvider
    ) {
    }

    public function create(EntityConfig $entityConfig, string $action): Form
    {
        $form = new Form();

        foreach ($entityConfig->getFields($action) as $field) {
            $columnName  = $field->getName();
            $controlName = $field->getControl();
            if ($controlName === null) {
                throw new \LogicException(sprintf(
                    'Field "%s" for entity "%s" must have a control configured.',
                    $columnName,
                    $entityConfig->getName()
                ));
            }
            $control       = $this->formControlFactory->create($controlName, $columnName);
            $foreignEntity = $field->getForeignEntity();
            if ($foreignEntity !== null) {
                if (!$control instanceof OptionsInterface) {
                    throw new \LogicException(sprintf(
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
            }
            $form->addControl($control, $columnName);
        }

        return $form;
    }
}
