<?php

declare(strict_types=1);

use S2\AdminYard\Config\AdminConfig;
use S2\AdminYard\Config\DbColumnFieldType;
use S2\AdminYard\Config\EntityConfig;
use S2\AdminYard\Config\FieldConfig;
use S2\AdminYard\Config\Filter;
use S2\AdminYard\Config\FilterLinkTo;
use S2\AdminYard\Config\LinkedByFieldType;
use S2\AdminYard\Config\LinkTo;
use S2\AdminYard\Config\VirtualFieldType;
use S2\AdminYard\Validator\Length;
use S2\AdminYard\Validator\NotBlank;

// Example of admin config for demo and tests

$adminConfig = new AdminConfig();

$postEntity = new EntityConfig('Post', 'posts');

$commentConfig = (new EntityConfig('Comment', 'comments'))
    ->addField(new FieldConfig(
        name: 'id',
        type: new DbColumnFieldType(FieldConfig::DATA_TYPE_INT, true),
        useOnActions: []
    ))
    ->addField(($postIdField = new FieldConfig(
        name: 'post_id',
        type: new DbColumnFieldType(FieldConfig::DATA_TYPE_INT),
        control: 'select',
        validators: [new NotBlank()],
        sortable: true,
        linkToEntity: new LinkTo($postEntity, 'CONCAT("#", id, " ", title)'),
        useOnActions: [FieldConfig::ACTION_LIST, FieldConfig::ACTION_SHOW, FieldConfig::ACTION_NEW])
    ))
    ->addField(new FieldConfig(
        name: 'name',
        control: 'input',
        validators: [new NotBlank(), new Length(max: 50)]
    ))
    ->addField(new FieldConfig(
        name: 'email',
        control: 'input',
        validators: [new Length(max: 80)]
    ))
    ->addField(new FieldConfig(
        name: 'comment_text',
        control: 'textarea',
    ))
    ->addField(new FieldConfig(
        name: 'created_at',
        type: new DbColumnFieldType(FieldConfig::DATA_TYPE_TIMESTAMP),
        control: 'datetime',
        sortable: true
    ))
    ->addField(new FieldConfig(
        name: 'status_code',
        type: new DbColumnFieldType(FieldConfig::DATA_TYPE_STRING, defaultValue: 'new'),
        control: 'radio',
        options: ['new' => 'Pending', 'approved' => 'Approved', 'rejected' => 'Rejected'],
        useOnActions: [FieldConfig::ACTION_LIST, FieldConfig::ACTION_SHOW, FieldConfig::ACTION_EDIT]
    ))
    ->addFilter(new Filter(
        'search',
        'Fulltext Search',
        'input',
        'name LIKE %1$s OR email LIKE %1$s OR comment_text LIKE %1$s',
        fn(string $value) => $value !== '' ? '%' . $value . '%' : null
    ))
    ->addFilter(new FilterLinkTo(
        $postIdField,
        'Post',
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
            ->addField(new FieldConfig(
                name: 'id',
                label: 'ID',
                type: new DbColumnFieldType(FieldConfig::DATA_TYPE_INT, true),
                sortable: true,
                useOnActions: [FieldConfig::ACTION_LIST, FieldConfig::ACTION_SHOW]
            ))
            ->addField(new FieldConfig(
                name: 'title',
                control: 'input',
                validators: [new Length(max: 80)],
                sortable: true,
                actionOnClick: 'edit'
            ))
//            ->addField(new FieldConfig(
//                name: 'tags',
//                type: new VirtualFieldType('SELECT GROUP_CONCAT(t.name SEPARATOR ", ") FROM tags AS t JOIN posts_tags AS pt ON t.id = pt.tag_id WHERE pt.post_id = entity.id'),
//                control: 'input',
//            ))
            ->addField(new FieldConfig(
                name: 'text',
                control: 'textarea',
                useOnActions: [FieldConfig::ACTION_SHOW, FieldConfig::ACTION_EDIT, FieldConfig::ACTION_NEW]
            ))
            ->addField(new FieldConfig(
                name: 'is_active',
                label: 'Published',
                type: new DbColumnFieldType(FieldConfig::DATA_TYPE_BOOL),
                control: 'checkbox',
            ))
            ->addField(new FieldConfig(
                name: 'created_at',
                type: new DbColumnFieldType(FieldConfig::DATA_TYPE_TIMESTAMP),
                control: 'datetime',
                sortable: true
            ))
            ->addField(new FieldConfig(
                name: 'updated_at',
                type: new DbColumnFieldType(FieldConfig::DATA_TYPE_UNIXTIME),
                control: 'datetime',
                sortable: true
            ))
            ->addField(new FieldConfig(
                name: 'comments',
                type: new LinkedByFieldType($commentConfig, 'CASE WHEN COUNT(*) > 0 THEN COUNT(*) ELSE NULL END', 'post_id'),
                sortable: true
            ))
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
            ->addField(new FieldConfig(
                name: 'id',
                type: new DbColumnFieldType(FieldConfig::DATA_TYPE_INT, true),
                useOnActions: []
            ))
            ->addField(new FieldConfig(
                name: 'name',
                control: 'input',
                validators: [new NotBlank(), new Length(min: 1, max: 80)],
                sortable: true
            ))
            ->addField(new FieldConfig(
                name: 'description',
                control: 'textarea',
                useOnActions: [FieldConfig::ACTION_SHOW, FieldConfig::ACTION_EDIT, FieldConfig::ACTION_NEW]
            ))
    )
    ->addEntity(
        (new EntityConfig('CompositeKey', 'composite_key_table'))
            ->addField(new FieldConfig(
                name: 'column1',
                type: new DbColumnFieldType(FieldConfig::DATA_TYPE_INT, true),
                control: 'int_input',
                actionOnClick: 'edit'
            ))
            ->addField(new FieldConfig(
                name: 'column2',
                type: new DbColumnFieldType(FieldConfig::DATA_TYPE_STRING, true),
                control: 'input',
            ))
            ->addField(new FieldConfig(
                name: 'column3',
                type: new DbColumnFieldType(FieldConfig::DATA_TYPE_DATE, true),
                control: 'date',
            ))
    )
    ->addEntity(
        (new EntityConfig('Config', 'config'))
            ->addField(new FieldConfig(
                name: 'name',
                type: new DbColumnFieldType(FieldConfig::DATA_TYPE_STRING, true),
                control: 'input'  // TODO exception, only link is available
            ))
            ->addField(new FieldConfig(
                name: 'value',
                control: 'input',
            ))
            ->setEnabledActions([FieldConfig::ACTION_LIST])
    )
    ->addEntity(
        (new EntityConfig('Sequence', 'sequence'))
            ->addField(new FieldConfig(
                name: 'id',
                type: new DbColumnFieldType(FieldConfig::DATA_TYPE_INT, true),
                useOnActions: []
            ))
    )
    ->addEntity(
        (new EntityConfig('Empty', 'sequence'))
    )
;

return $adminConfig;
