<?php

declare(strict_types=1);

use S2\AdminYard\Config\AdminConfig;
use S2\AdminYard\Config\EntityConfig;
use S2\AdminYard\Config\FieldConfig;
use S2\AdminYard\Validator\Length;

// Example of admin config for demo and tests

$adminConfig = new AdminConfig();

$postEntity = new EntityConfig('Post', 'posts');

$commentConfig = (new EntityConfig('Comment', 'comments'))
    ->addField(
        (new FieldConfig('id'))
            ->setDataType('int')
            ->markAsPrimaryKey()
            ->setUseOnActions([])
    )
    ->addField(
        (new FieldConfig('post_id'))
            ->setDataType('int')
            ->setControl('select')
            ->manyToOne($postEntity, 'CONCAT("#", id, " ", title)')
            ->setUseOnActions([FieldConfig::ACTION_LIST, FieldConfig::ACTION_SHOW, FieldConfig::ACTION_NEW])
    )
    ->addField(
        (new FieldConfig('name'))
            ->setControl('input')
            ->addValidator(new Length(50))
    )
    ->addField(
        (new FieldConfig('email'))
            ->setControl('input')
            ->addValidator(new Length(80))
    )
    ->addField(
        (new FieldConfig('comment_text'))
            ->setDataType('string')
            ->setControl('textarea')
    )
    ->addField(
        (new FieldConfig('created_at'))
            ->setDataType('timestamp')
            ->setControl('datetime')
    )
    ->addField(
        (new FieldConfig('status'))
            ->setDataType('string')
            ->setControl('radio')
            ->setOptions(['new' => 'Pending', 'approved' => 'Approved', 'rejected' => 'Rejected'])
            ->setDefaultValue('new')
            ->setUseOnActions([FieldConfig::ACTION_LIST, FieldConfig::ACTION_SHOW, FieldConfig::ACTION_EDIT])
    )
;

$adminConfig
    ->addEntity(
        $postEntity
            ->addField(
                (new FieldConfig('id'))
                    ->setLabel('ID')
                    ->setDataType(FieldConfig::DATA_TYPE_INT)
                    ->markAsPrimaryKey()
                    ->markAsSortable()
                    ->markAsFilterable(true)
                    ->setUseOnActions([FieldConfig::ACTION_LIST, FieldConfig::ACTION_SHOW])
            )
            ->addField(
                (new FieldConfig('title'))
                    ->setControl('input')
                    ->markAsSortable()
                    ->markAsFilterable(true)
                    ->addValidator(new Length(80))
            )
            ->addField(
                (new FieldConfig('text'))
                    ->setControl('textarea')
                    ->setUseOnActions([FieldConfig::ACTION_SHOW, FieldConfig::ACTION_EDIT, FieldConfig::ACTION_NEW])
            )
            ->addField(
                (new FieldConfig('is_active'))
                    ->setLabel('Published')
                    ->setDataType(FieldConfig::DATA_TYPE_BOOL)
                    ->setControl('checkbox')
                    ->markAsFilterable(true)
            )
            ->addField(
                (new FieldConfig('created_at'))
                    ->setLabel('Created at')
                    ->setDataType(FieldConfig::DATA_TYPE_TIMESTAMP)
                    ->setControl('datetime')
                    ->markAsFilterable(true)
            )
            ->addField(
                (new FieldConfig('updated_at'))
                    ->setLabel('Modified at')
                    ->setDataType(FieldConfig::DATA_TYPE_UNIXTIME)
                    ->setControl('datetime')
                    ->markAsFilterable(true)
            )
            ->addField(
                (new FieldConfig('comments'))
                    ->oneToMany($commentConfig, 'CASE WHEN COUNT(*) > 0 THEN COUNT(*) ELSE NULL END', 'post_id')
            )
            ->markAsDefault()
    )
    ->addEntity(
        $commentConfig
    )
    ->addEntity(
        (new EntityConfig('Tag', 'tags'))
            ->addField(
                (new FieldConfig('id'))
                    ->setDataType('int')
                    ->markAsPrimaryKey()
                    ->setUseOnActions([])
            )
            ->addField(
                (new FieldConfig('name'))
                    ->setDataType('string')
                    ->setControl('input')
                    ->markAsSortable()
                    ->markAsFilterable(true)
                    ->addValidator(new Length(80))
            )
            ->addField(
                (new FieldConfig('description'))
                    ->setDataType('string')
                    ->setControl('textarea')
                    ->setUseOnActions([FieldConfig::ACTION_SHOW, FieldConfig::ACTION_EDIT, FieldConfig::ACTION_NEW])
            )
    )
    ->addEntity(
        (new EntityConfig('MyTable', 'my_table'))
            ->addField(
                (new FieldConfig('column1'))
                    ->setDataType('int')
                    ->setControl('int_input')
                    ->markAsPrimaryKey()
            )
            ->addField(
                (new FieldConfig('column2'))
                    ->setDataType('string')
                    ->setControl('input')
                    ->markAsPrimaryKey()
            )
            ->addField(
                (new FieldConfig('column3'))
                    ->setDataType('date')
                    ->setControl('date')
                    ->markAsPrimaryKey()
            )
    )
;

return $adminConfig;
