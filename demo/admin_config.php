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
use S2\AdminYard\Database\Key;
use S2\AdminYard\Event\AfterSaveEvent;
use S2\AdminYard\Event\BeforeSaveEvent;
use S2\AdminYard\Validator\Length;
use S2\AdminYard\Validator\NotBlank;
use S2\AdminYard\Validator\Regex;

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

/**
 * @throws \S2\AdminYard\Database\DataProviderException
 * @throws PDOException
 */
function tagIdsFromTags(\S2\AdminYard\Database\PdoDataProvider $dataProvider, array $tags): array
{
    $existingTags = $dataProvider->getEntityList('tags', [
        'name' => FieldConfig::DATA_TYPE_STRING,
        'id'   => FieldConfig::DATA_TYPE_INT,
    ], filterData: ['name' => $tags]);

    $existingTagsMap = array_column($existingTags, 'field_name', 'field_id');
    $existingTagsMap = array_map(static fn(string $tag) => mb_strtolower($tag), $existingTagsMap);
    $existingTagsMap = array_flip($existingTagsMap);

    $tagIds = [];
    foreach ($tags as $tag) {
        if (!isset($existingTagsMap[mb_strtolower($tag)])) {
            $newTagId = $dataProvider->createEntity('tags', ['name' => FieldConfig::DATA_TYPE_STRING], ['name' => $tag]);
            // TODO: check if lastInsertId fails
        } else {
            $newTagId = $existingTagsMap[mb_strtolower($tag)];
        }
        $tagIds[] = $newTagId;
    }

    return $tagIds;
}

$adminConfig
    ->addEntity(
        entity: $postEntity
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
            ->addField(new FieldConfig(
                name: 'tags',
                type: new VirtualFieldType('SELECT GROUP_CONCAT(t.name SEPARATOR ", ") FROM tags AS t JOIN posts_tags AS pt ON t.id = pt.tag_id WHERE pt.post_id = entity.id'),
                control: 'input',
                validators: [
                    (static function () {
                        $validator          = new Regex('#^[\p{L}\p{N}_\- ,\.!]*$#u');
                        $validator->message = 'Tags must contain only letters, numbers and spaces.';
                        return $validator;
                    })(),
                ]
            ))
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
            ->addListener([EntityConfig::EVENT_BEFORE_UPDATE, EntityConfig::EVENT_BEFORE_CREATE], function (BeforeSaveEvent $event) {
                $event->context['tags'] = $event->data['tags'];
                unset($event->data['tags']);
            })
            ->addListener([EntityConfig::EVENT_AFTER_UPDATE, EntityConfig::EVENT_AFTER_CREATE], function (AfterSaveEvent $event) {
                $tagStr = $event->context['tags'];
                $tags   = array_map(static fn(string $tag) => trim($tag), explode(',', $tagStr));
                $tags   = array_filter($tags, static fn(string $tag) => $tag !== '');

                $newTagIds = tagIdsFromTags($event->dataProvider, $tags);

                $existingLinks = $event->dataProvider->getEntityList('posts_tags', [
                    'post_id' => FieldConfig::DATA_TYPE_INT,
                    'tag_id'  => FieldConfig::DATA_TYPE_INT,
                ], filterData: ['post_id' => $event->primaryKey->toArray()['id']]);

                $existingTagIds = array_column($existingLinks, 'field_tag_id');
                if (implode(',', $existingTagIds) !== implode(',', $newTagIds)) {
                    $event->dataProvider->deleteEntity('posts_tags', new Key(['post_id' => $event->primaryKey->toArray()['id']]));
                    foreach ($newTagIds as $tagId) {
                        $event->dataProvider->createEntity('posts_tags', [
                            'post_id' => FieldConfig::DATA_TYPE_INT,
                            'tag_id'  => FieldConfig::DATA_TYPE_INT,
                        ], ['post_id' => $event->primaryKey->toArray()['id'], 'tag_id' => $tagId]);
                    }
                }
            })
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
                validators: [
                    new NotBlank(),
                    new Length(min: 1, max: 80),
                    (static function () {
                        $r          = new Regex('#^[\p{L}\p{N}_\- !\.]*$#u');
                        $r->message = 'Tag name must contain only letters, numbers and spaces';
                        return $r;
                    })(),
                ],
                sortable: true
            ))
            ->addField(new FieldConfig(
                name: 'used_in_posts',
                type: new VirtualFieldType('SELECT COUNT(*) FROM posts_tags AS pt WHERE pt.tag_id = entity.id'),
                useOnActions: [FieldConfig::ACTION_LIST]
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
