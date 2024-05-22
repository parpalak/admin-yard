<?php

declare(strict_types=1);

use S2\AdminYard\Config\AdminConfig;
use S2\AdminYard\Config\EntityConfig;
use S2\AdminYard\Config\FieldConfig;
use S2\AdminYard\Config\Filter;
use S2\AdminYard\Validator\Length;
use S2\AdminYard\Validator\NotBlank;

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
            ->markAsSortable()
            ->setControl('select')
            ->addValidator(new NotBlank())
            ->manyToOne($postEntity, 'CONCAT("#", id, " ", title)')
            ->setUseOnActions([FieldConfig::ACTION_LIST, FieldConfig::ACTION_SHOW, FieldConfig::ACTION_NEW])
    )
    ->addField(
        (new FieldConfig('name'))
            ->setControl('input')
            ->addValidator(new NotBlank())
            ->addValidator(new Length(max: 50))
    )
    ->addField(
        (new FieldConfig('email'))
            ->setControl('input')
            ->addValidator(new Length(max: 80))
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
            ->markAsSortable()
    )
    ->addField(
        (new FieldConfig('status_code'))
            ->setLabel('Status')
            ->setDataType('string')
            ->setControl('radio')
            ->setOptions(['new' => 'Pending', 'approved' => 'Approved', 'rejected' => 'Rejected'])
            ->setDefaultValue('new')
            ->setUseOnActions([FieldConfig::ACTION_LIST, FieldConfig::ACTION_SHOW, FieldConfig::ACTION_EDIT])
    )
    ->addFilter(new Filter(
        'search',
        'Fulltext Search',
        'input',
        'name LIKE %1$s OR email LIKE %1$s OR comment_text LIKE %1$s',
        fn(string $value) => $value !== '' ? '%' . $value . '%' : null
    ))
    ->addFilter(new Filter(
        'created_from',
        'Created after',
        'date',
        'created_at >= %1$s'
    ))
    ->addFilter(new Filter(
        'created_to',
        'Created before',
        'date',
        'created_at < %1$s'
    ))
    ->addFilter(new Filter(
        'statuses',
        'Status',
        'checkbox_array',
        'status_code IN (%1$s)',
        options: ['new' => 'Pending', 'approved' => 'Approved', 'rejected' => 'Rejected']
    ))
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
                    ->setLinkToAction('edit')
                    ->markAsSortable()
                    ->markAsFilterable(true)
                    ->addValidator(new Length(max: 80))
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
                    ->markAsSortable()
            )
            ->addField(
                (new FieldConfig('updated_at'))
                    ->setLabel('Modified at')
                    ->setDataType(FieldConfig::DATA_TYPE_UNIXTIME)
                    ->setControl('datetime')
                    ->markAsFilterable(true)
                    ->markAsSortable()
            )
            ->addField(
                (new FieldConfig('comments'))
                    ->oneToMany($commentConfig, 'CASE WHEN COUNT(*) > 0 THEN COUNT(*) ELSE NULL END', 'post_id')
                    ->markAsSortable()
            )
            ->markAsDefault()
            ->addFilter(
                new Filter(
                    'search',
                    'Fulltext Search',
                    'input',
                    'title LIKE %1$s OR text LIKE %1$s',
                    fn(string $value) => $value !== '' ? '%' . $value . '%' : null
                )
            )
            ->addFilter(
                new Filter(
                    'is_active',
                    'Published',
                    'radio',
                    'is_active = %1$s',
                    options: ['' => 'All', 1 => 'Yes', 0 => 'No']
                )
            )
            ->addFilter(
                new Filter(
                    'modified_from',
                    'Modified after',
                    'date',
                    'updated_at >= %1$s',
                    fn(?string $value) => $value !== null ? strtotime($value) : null
                )
            )
            ->addFilter(
                new Filter(
                    'modified_to',
                    'Modified before',
                    'date',
                    'updated_at < %1$s',
                    fn(?string $value) => $value !== null ? strtotime($value) : null
                )
            )
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
                    ->addValidator(new Length(1, 80))
                    ->addValidator(new NotBlank())
            )
            ->addField(
                (new FieldConfig('description'))
                    ->setDataType('string')
                    ->setControl('textarea')
                    ->setUseOnActions([FieldConfig::ACTION_SHOW, FieldConfig::ACTION_EDIT, FieldConfig::ACTION_NEW])
            )
    )
    ->addEntity(
        (new EntityConfig('CompositeKey', 'composite_key_table'))
            ->addField(
                (new FieldConfig('column1'))
                    ->setDataType('int')
                    ->setControl('int_input')
                    ->markAsPrimaryKey()
                    ->setLinkToAction('edit')
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
    ->addEntity(
        (new EntityConfig('Config', 'config'))
            ->addField(
                (new FieldConfig('name'))
                    ->setDataType('string')
                    ->setControl('input')
                    ->markAsPrimaryKey()
            )
            ->addField(
                (new FieldConfig('value'))
                    ->setDataType('string')
                    ->setControl('input')
            )
            ->setEnabledActions([FieldConfig::ACTION_LIST])
    )
;

return $adminConfig;
