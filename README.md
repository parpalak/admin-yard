# AdminYard

AdminYard is a lightweight PHP library for building admin panels without heavy dependencies
such as frameworks, templating engines, or ORMs.
It provides a declarative configuration in object style for defining entities, fields, and their properties.
With AdminYard, you can quickly set up CRUD (Create, Read, Update, Delete) interfaces
for your database tables and customize them according to your needs.

AdminYard simplifies the process of creating typical admin interfaces, allowing you to focus
on developing core functionality. It does not attempt to create its own abstraction
with as many features as possible. Instead, it addresses common admin tasks
while providing enough extension points to customize it for your specific project.

When developing AdminYard, I took inspiration from EasyAdmin.
I wanted to use it for one of my own projects,
but I didn't want to pull in major dependencies like Symfony, Doctrine, or Twig.
So, I tried to make a similar product without those dependencies.
It can be useful for embedding into existing legacy projects,
where adding a new framework and ORM is not so easy.
If you are starting a new project from scratch,
I recommend that you consider using Symfony, Doctrine and EasyAdmin first.

## Installation

To install AdminYard, you can use Composer:

```bash
composer require s2/admin-yard
```

## Usage

Here are configuration examples with explanations. You can also see a more complete example of
working [demo application](demo).

### Integration

Once installed, you can start using AdminYard by creating an instance of AdminConfig
and configuring it with your entity settings.
Then, create an instance of AdminPanel passing the AdminConfig instance.
Use the handleRequest method to handle incoming requests and generate the admin panel HTML.

```php
<?php

use S2\AdminYard\DefaultAdminFactory;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Session\Session;

// Config for admin panel, see below
$adminConfig = require 'admin_config.php';

// Typical AdminYard services initialization.
// You can use DI instead and override services if required.
$pdo = new PDO('mysql:host=localhost;dbname=adminyard', 'username', 'passwd');
$adminPanel = DefaultAdminFactory::createAdminPanel($adminConfig, $pdo, require 'translations/en.php', 'en');

// AdminYard uses Symfony HTTP Foundation component.
// Sessions are required to store CSRF tokens and filters.
// new Session() stands for native PHP sessions. You can provide an alternative session storage.
$request = Request::createFromGlobals();
$request->setSession(new Session());
$response = $adminPanel->handleRequest($request);
$response->send();
```

### Basic config example for fields, filters, many-to-one and one-to-many associations

```php
<?php

declare(strict_types=1);

use S2\AdminYard\Config\AdminConfig;
use S2\AdminYard\Config\DbColumnFieldType;
use S2\AdminYard\Config\EntityConfig;
use S2\AdminYard\Config\FieldConfig;
use S2\AdminYard\Config\Filter;
use S2\AdminYard\Config\FilterLinkTo;
use S2\AdminYard\Config\LinkTo;
use S2\AdminYard\Database\PdoDataProvider;
use S2\AdminYard\Event\AfterSaveEvent;
use S2\AdminYard\Event\BeforeDeleteEvent;
use S2\AdminYard\Event\BeforeSaveEvent;
use S2\AdminYard\Validator\NotBlank;
use S2\AdminYard\Validator\Length;

$adminConfig = new AdminConfig();

$commentConfig = new EntityConfig(
    'Comment', // Entity name in interface
    'comments' // Database table name
);

$postEntity = (new EntityConfig('Post', 'posts'))
    ->addField(new FieldConfig(
        name: 'id',
        type: new DbColumnFieldType(FieldConfig::DATA_TYPE_INT, true), // Primary key
        useOnActions: [FieldConfig::ACTION_LIST, FieldConfig::ACTION_SHOW] // Show ID only on list and show screens
    ))
    ->addField(new FieldConfig(
        name: 'title',
        // type: new DbColumnFieldType(FieldConfig::DATA_TYPE_STRING), // May be omitted as it is default
        // Form control must be defined since new and edit screens are not excluded in useOnActions 
        control: 'input', // Input field for title
        validators: [new Length(max: 80)], // Form validators may be supplied
        sortable: true, // Allow sorting on the list screen
        actionOnClick: 'edit' // Link from cell on the list screen
    ))
    ->addField(new FieldConfig(
        name: 'text',
        control: 'textarea', // Textarea for post content
        // All screens except list
        useOnActions: [FieldConfig::ACTION_SHOW, FieldConfig::ACTION_EDIT, FieldConfig::ACTION_NEW]
    ))
    ->addField(new FieldConfig(
        name: 'created_at',
        type: new DbColumnFieldType(FieldConfig::DATA_TYPE_TIMESTAMP), // Timestamp field
        control: 'datetime', // Date and time picker
        sortable: true // Allow sorting by creation date
    ))
    ->addField(new FieldConfig(
        name: 'comments',
        // Special config for one-to-many association. Will be displayed on the list and show screens
        // as a link to the comments list screen with a filter on posts applied. COUNT(*) is used as a link text.
        type: new LinkedByFieldType($commentConfig, 'CASE WHEN COUNT(*) > 0 THEN COUNT(*) ELSE NULL END', 'post_id'),
        sortable: true
    ))
    ->addFilter(new Filter(
        'search',
        'Fulltext Search',
        'search_input',
        'title LIKE %1$s OR text LIKE %1$s',
        fn(string $value) => $value !== '' ? '%' . $value . '%' : null // Transformer for PDO parameter
    ))
;

// Fields and filters configuration for "Comment"
$commentConfig
    ->addField(new FieldConfig(
        name: 'id',
        type: new DbColumnFieldType(FieldConfig::DATA_TYPE_INT, true), // Primary key
        useOnActions: [] // Do not show on any screen
    ))
    ->addField($postIdField = new FieldConfig(
        name: 'post_id',
        type: new DbColumnFieldType(FieldConfig::DATA_TYPE_INT), // Foreign key to post
        control: 'autocomplete', // Autocomplete control for selecting post
        validators: [new NotBlank()], // Ensure post_id is not blank
        // Special config for one-to-many association. Will be displayed on the list and show screens
        // as a link to the post. "CONCAT('#', id, ' ', title)" is used as a link text.
        linkToEntity: new LinkTo($postEntity, "CONCAT('#', id, ' ', title)"),
        // Disallow on edit screen, post may be chosen on comment creation only.
        useOnActions: [FieldConfig::ACTION_LIST, FieldConfig::ACTION_SHOW, FieldConfig::ACTION_NEW]
    ))
    ->addField(new FieldConfig(
        name: 'name',
        control: 'input', // Input field for commenter's name
        validators: [new NotBlank(), new Length(max: 50)]
    ))
    ->addField(new FieldConfig(
        name: 'email',
        control: 'email_input',
        validators: [new Length(max: 80)] // Max length validator
    ))
    ->addField(new FieldConfig(
        name: 'comment_text',
        control: 'textarea'
    ))
    ->addField(new FieldConfig(
        name: 'created_at',
        type: new DbColumnFieldType(FieldConfig::DATA_TYPE_TIMESTAMP), // Timestamp field
        control: 'datetime', // Date and time picker
        sortable: true // Allow sorting by creation date
    ))
    ->addField(new FieldConfig(
        name: 'status_code',
        type: new DbColumnFieldType(FieldConfig::DATA_TYPE_STRING, defaultValue: 'new'), // For default value
        control: 'radio', // Radio buttons for status selection
        options: ['new' => 'Pending', 'approved' => 'Approved', 'rejected' => 'Rejected'], // Status options
        // Disallow on new screen
        useOnActions: [FieldConfig::ACTION_LIST, FieldConfig::ACTION_SHOW, FieldConfig::ACTION_EDIT]
    ))
    ->addFilter(new Filter(
        'search',
        'Fulltext Search',
        'search_input',
        'name LIKE %1$s OR email LIKE %1$s OR comment_text LIKE %1$s',
        fn(string $value) => $value !== '' ? '%' . $value . '%' : null
    ))
    ->addFilter(new FilterLinkTo(
        $postIdField, // Filter comments by a post on the list screen
        'Post'
    ))
    ->addFilter(new Filter(
        'created_from',
        'Created after',
        'date',
        'created_at >= %1$s' // Filter comments created after a certain date
    ))
    ->addFilter(new Filter(
        'created_to',
        'Created before',
        'date',
        'created_at < %1$s' // Filter comments created before a certain date
    ))
    ->addFilter(new Filter(
        'statuses', 
        'Status',
        'checkbox_array', // Several statuses can be chosen at once
        'status_code IN (%1$s)', // Filter comments by status
        options: ['new' => 'Pending', 'approved' => 'Approved', 'rejected' => 'Rejected']
    ));

// Add entities to admin config
$adminConfig
    ->addEntity($postEntity)
    ->addEntity($commentConfig);

return $adminConfig;

```

### Advanced example with virtual fields and many-to-many associations

An entity field can either directly map to a column in the corresponding table
or be virtual, calculated on-the-fly based on certain rules.

Continuing with the previous example, suppose posts have a many-to-many relationship
with tags in the posts_tags table.
If we want to display the number of related posts in the list of tags,
we can use the following construction:

```php
<?php

use S2\AdminYard\Config\EntityConfig;
use S2\AdminYard\Config\Filter;

$tagConfig = new EntityConfig('Tag', 'tags');
$tagConfig
    ->addField(new FieldConfig(
        name: 'name',
        control: 'input',
    ))
    ->addField(new FieldConfig(
        name: 'used_in_posts', // Arbitrary field name
        type: new VirtualFieldType(
            // Query evaluates the content of the virtual field
            'SELECT CAST(COUNT(*) AS CHAR) FROM posts_tags AS pt WHERE pt.tag_id = entity.id',
            // We can define a link to the post list.
            // To make this work, a filter on tags must be set up for posts, see below
            new LinkToEntityParams('Post', ['tags'], ['name' /* Tag property name, i.e. tags.name */])
        ),
        // Read-only field, new and edit actions are disabled.
        useOnActions: [FieldConfig::ACTION_LIST, FieldConfig::ACTION_SHOW]
    ))
;

$postConfig
    ->addFilter(
        new Filter(
            name: 'tags',
            label: 'Tags',
            control: 'search_input',
            whereSqlExprPattern:  'id IN (SELECT pt.post_id FROM posts_tags AS pt JOIN tags AS t ON t.id = pt.tag_id WHERE t.name LIKE %1$s)',
            fn(string $value) => $value !== '' ? '%' . $value . '%' : null
        )
    )
;
```

In this example, the virtual field `used_in_posts` is declared as read-only.
We cannot edit the relationships in the `posts_tags` table through it.

## Architecture

AdminYard operates with three levels of data representation:

- HTTP: HTML code of forms sent and data received in POST requests.
- Normalized data passed between code components in a controller.
- Persisted data in the database.

When transitioning between these levels, data is transformed based on the entity configuration.
To transform between the HTTP level and normalized data, form controls are used.
To transform between normalized data and data persisted in the database, `dataType` is used.
The `dataType` value must be chosen based on the column type in the database
and the meaning of the data stored in it.
For example, if you have a column in the database of type `VARCHAR`, you cannot specify the dataType as `bool`,
since the TypeTransformer will convert all values to integer 0 or 1 when writing to the database,
which is not compatible with string data types.

![](https://i.upmath.me/svg/%5Cusetikzlibrary%7Bdecorations.pathreplacing%7D%0A%5Ctikzstyle%7Bblock%7D%20%3D%20%5Brectangle%2C%20text%20width%3D7.5em%2C%20text%20centered%2C%20minimum%20height%3D3em%5D%0A%5Cbegin%7Btikzpicture%7D%5Bnode%20distance%3D4.5cm%5D%0A%20%20%20%5Cnode%20%5Bblock%2Cfill%3Dred!15%5D%20(browser)%20%7BHTML%5C%5CBrowser%7D%3B%0A%20%20%20%5Cnode%20%5Bblock%2Cfill%3Dred!15%2C%20right%20of%3Dbrowser%5D%20(control1)%20%7B%7D%3B%0A%20%20%20%5Cnode%20%5Bblock%2Cfill%3Dgreen!15%2C%20right%20of%3Dbrowser%2C%20text%20width%3D3.6em%2C%20node%20distance%3D5.3cm%5D%20(control2)%20%7B%7D%3B%0A%20%20%20%5Cnode%20%5Bblock%2C%20right%20of%3Dbrowser%5D%20(control)%20%7BFormControl%7D%3B%0A%20%20%20%5Cnode%20%5Bblock%2Cfill%3Dgreen!15%2C%20right%20of%3Dcontrol%5D%20(internal)%20%7BController%7D%3B%0A%20%20%20%5Cnode%20%5Bblock%2Cfill%3Dgreen!15%2C%20right%20of%3Dinternal%5D%20(provider1)%20%7B%7D%3B%0A%20%20%20%5Cnode%20%5Bblock%2Cfill%3Dblue!15%2C%20right%20of%3Dinternal%2C%20text%20width%3D3.6em%2C%20node%20distance%3D5.3cm%5D%20(provider2)%20%7B%7D%3B%0A%20%20%20%5Cnode%20%5Bblock%2Cright%20of%3Dinternal%5D%20(provider)%20%7BPdoDataProvider%7D%3B%0A%20%20%20%5Cnode%20%5Bblock%2Cfill%3Dblue!15%2C%20right%20of%3Dprovider%5D%20(db)%20%7BDB%7D%3B%0A%20%20%20%5Cdraw%20%5B-%3E%5D%20(browser)%20to%5Bout%3D-30%2C%20in%3D210%5D%20node%5Bbelow%20right%2Cpos%3D0.4%5D%20%7B%5Ctext%7B%5Csmall%20setPostValue()%7D%7D%20(control)%3B%0A%20%20%20%5Cdraw%20%5B-%3E%5D%20(control)%20to%5Bout%3D150%2C%20in%3D30%5D%20node%5Babove%20right%2Cpos%3D0.4%5D%20%7B%5Ctext%7B%5Csmall%20getHtml()%7D%7D%20(browser)%3B%0A%20%20%20%5Cdraw%20%5B-%3E%5D%20(control)%20to%5Bout%3D-30%2C%20in%3D210%5D%20node%5Bbelow%20left%2Cpos%3D0.7%5D%20%7B%5Ctext%7B%5Csmall%20getValue()%7D%7D%20(internal)%3B%0A%20%20%20%5Cdraw%20%5B-%3E%5D%20(internal)%20to%5Bout%3D150%2C%20in%3D30%5D%20node%5Babove%20left%2Cpos%3D0.4%5D%20%7B%5Ctext%7B%5Csmall%20setValue()%7D%7D%20(control)%3B%0A%20%20%20%5Cdraw%20%5B-%3E%5D%20(internal)%20to%5Bout%3D-30%2C%20in%3D210%5D%20node%5Bbelow%5D%20%7B%5Cshortstack%7B%5Ctext%7B%5Csmall%20createEntity()%7D%5C%5C%20%5Ctext%7B%5Csmall%20updateEntity()%7D%7D%7D%20(provider)%3B%0A%20%20%20%5Cdraw%20%5B-%3E%5D%20(provider)%20to%5Bout%3D150%2C%20in%3D30%5D%20node%5Babove%5D%20%7B%5Cshortstack%7B%5Ctext%7B%5Csmall%20getEntityList()%7D%5C%5C%20%5Ctext%7B%5Csmall%20getEntity()%7D%7D%7D%20(internal)%3B%0A%20%20%20%5Cdraw%20%5B-%3E%5D%20(provider)%20to%5Bin%3D-60%2C%20out%3D240%2Clooseness%3D9.5%5D%20node%5Bbelow%5D%20%7B%5Ctext%7B%5Csmall%20TypeTransformer%3A%3AdbFromNormalized()%7D%7D%20(provider)%3B%0A%20%20%20%5Cdraw%20%5B-%3E%5D%20(provider)%20to%5Bin%3D120%2C%20out%3D60%2Clooseness%3D9.5%5D%20node%5Babove%5D%20%7B%5Ctext%7B%5Csmall%20TypeTransformer%3A%3AdbFromNormalized()%7D%7D%20(provider)%3B%0A%20%20%20%5Cdraw%20%5B-%3E%5D%20(internal)%20to%5Bout%3D135%2C%20in%3D45%5D%20node%5Babove%5D%20%7B%5Ctext%7B%5Csmall%20ViewTransformer%3A%3AviewFromNormalized()%7D%7D%20(browser)%3B%0A%20%20%20%5Cdraw%20%5B-%3E%5D%20(provider)%20to%5Bout%3D-30%2C%20in%3D210%5D%20node%5Bbelow%20right%5D%20%7B%5Cshortstack%7B%5Ctext%7B%5Csmall%20INSERT%7D%5C%5C%20%5Ctext%7B%5Csmall%20UPDATE%7D%7D%7D%20(db)%3B%0A%20%20%20%5Cdraw%20%5B-%3E%5D%20(db)%20to%5Bout%3D150%2C%20in%3D30%5D%20node%5Babove%20right%5D%20%7B%5Ctext%7B%5Csmall%20SELECT%7D%7D%20(provider)%3B%0Amodels%7D%3B%0A%5Cend%7Btikzpicture%7D)

It should be understood that not all form controls are compatible with normalized data
produced by the TypeTransformer when reading from the database based on `dataType`.

### Choosing controls and dataTypes

Here are some recommendations for choosing dataTypes based on the database column types and desired form control:

| DataType  | Control                                                                  | Normalized type in PHP | Database column types |
|-----------|--------------------------------------------------------------------------|------------------------|-----------------------|
| string    | input<br/>textarea<br/>search_input<br/>email_input<br/>select<br/>radio | string                 | TEXT (incl. VARCHAR)  |
| int       | int_input<br/>select<br/>radio                                           | string ('' -> NULL)    | INT, TEXT             |
| float     | float_input                                                              | string ('' -> NULL)    | TEXT, DECIMAL         |
| bool      | checkbox                                                                 | bool                   | INT, BOOLEAN          |
| date      | date                                                                     | ?string                | DATE, TEXT            |
| timestamp | datetime                                                                 | ?DateTimeImmutable     | TIMESTAMP, DATETIME   |
| unixtime  | datetime                                                                 | ?DateTimeImmutable     | INT                   |

### Note on Normalized Types in PHP

As you might have noticed, among the normalized data types, strings are often used instead of specialized types,
particularly for int and float. This is done for two reasons.
First, the control used for entering numbers is a regular input,
and the data entered into it is transmitted from the browser to the server as a string.
Therefore, the intermediate values are chosen to be strings.
Second, transmitting data as a string without intermediate conversion to float
avoids potential precision loss when working with floating-point numbers.

### Column fields and virtual fields

All fields in the configuration definition are divided into two major types: column fields and virtual fields.
They are described by the DbColumnFieldType and VirtualFieldType classes.
Column fields directly correspond to columns in database tables.
Many-to-one associations are also considered column fields,
as they are usually represented by references like `entity_id`.
AdminYard supports all CRUD operations with column fields.
Additionally, AdminYard supports one-to-many associations through the LinkedByFieldType,
which is a subclass of VirtualFieldType.

To use VirtualFieldType, you need to write an SQL query that evaluates the content displayed in the virtual field.

When executing SELECT queries to the database, one need to retrieve both column field values and virtual
field values.
To avoid conflicts, column field names are prefixed with `field_` since this represents the actual field value.
Virtual field names are prefixed with `label_` as their values are displayed as content on the list and show screens.
Without this separation, many-to-one associations using `new LinkTo($postEntity, "CONCAT('#', id, ' ', title)")`
would not work, as both the content for the link and the entity identifier for the link address
need to be retrieved simultaneously.
These prefixes are not added to form control names or to keys in arrays when passing data
from POST requests to modifying database queries.

### Config example: editable virtual fields via event listeners

To assign tags to posts, let's create a virtual field `tags` in the posts, which can accept tags separated by commas.
AdminYard doesn't have built-in functionality for this, but it has events at various points in the data flow
that allow this functionality to be implemented manually.

```php
<?php

use S2\AdminYard\Config\EntityConfig;
use S2\AdminYard\Config\FieldConfig;
use S2\AdminYard\Config\VirtualFieldType;
use S2\AdminYard\Database\Key;
use S2\AdminYard\Database\PdoDataProvider;
use S2\AdminYard\Event\AfterSaveEvent;
use S2\AdminYard\Event\BeforeDeleteEvent;
use S2\AdminYard\Event\BeforeEditEvent;
use S2\AdminYard\Event\BeforeSaveEvent;

$postConfig
    ->addField(new FieldConfig(
        name: 'tags',
        // Virtual field, SQL query evaluates the content for list and show screens 
        type: new VirtualFieldType('SELECT GROUP_CONCAT(t.name SEPARATOR ", ") FROM tags AS t JOIN posts_tags AS pt ON t.id = pt.tag_id WHERE pt.post_id = entity.id'),
        // Form control for new and edit forms
        control: 'input',
    ))
    ->addListener([EntityConfig::EVENT_BEFORE_EDIT], function (BeforeEditEvent $event) {
        if (\is_array($event->data)) {
            // Convert NULL to an empty string when the edit form is filled with current data.
            // It is required since TypeTransformer is not applied to virtual fields (no dataType).
            // 'label_' prefix is used for virtual fields as explained earlier.
            $event->data['label_tags'] = (string)$event->data['label_tags'];
        }
    })
    ->addListener([EntityConfig::EVENT_BEFORE_UPDATE, EntityConfig::EVENT_BEFORE_CREATE], function (BeforeSaveEvent $event) {
        // Save the tags to context for later use and remove before updating and inserting.
        $event->context['tags'] = $event->data['tags'];
        unset($event->data['tags']);
    })
    ->addListener([EntityConfig::EVENT_AFTER_UPDATE, EntityConfig::EVENT_AFTER_CREATE], function (AfterSaveEvent $event) {
        // Process the saved tags. Convert the comma-separated string to an array to store in the many-to-many relation.
        $tagStr = $event->context['tags'];
        $tags   = array_map(static fn(string $tag) => trim($tag), explode(',', $tagStr));
        $tags   = array_filter($tags, static fn(string $tag) => $tag !== '');

        // Fetching tag IDs, creating new tags if required
        $newTagIds = tagIdsFromTags($event->dataProvider, $tags);

        // Fetching old links
        $existingLinks = $event->dataProvider->getEntityList('posts_tags', [
            'post_id' => FieldConfig::DATA_TYPE_INT,
            'tag_id'  => FieldConfig::DATA_TYPE_INT,
        ], filterData: ['post_id' => $event->primaryKey->toArray()['id']]);
        $existingTagIds = array_column($existingLinks, 'field_tag_id');
        
        // Check if the new tag list differs from the old one
        if (implode(',', $existingTagIds) !== implode(',', $newTagIds)) {
            // Remove all old links
            $event->dataProvider->deleteEntity(
                'posts_tags',
                ['post_id' => FieldConfig::DATA_TYPE_INT],
                new Key(['post_id' => $event->primaryKey->toArray()['id']])
            );
            // And create new ones
            foreach ($newTagIds as $tagId) {
                $event->dataProvider->createEntity('posts_tags', [
                    'post_id' => FieldConfig::DATA_TYPE_INT,
                    'tag_id'  => FieldConfig::DATA_TYPE_INT,
                ], ['post_id' => $event->primaryKey->toArray()['id'], 'tag_id' => $tagId]);
            }
        }
    })
    ->addListener(EntityConfig::EVENT_BEFORE_DELETE, function (BeforeDeleteEvent $event) {
        $event->dataProvider->deleteEntity(
            'posts_tags',
            ['post_id' => FieldConfig::DATA_TYPE_INT], 
            new Key(['post_id' => $event->primaryKey->toArray()['id']])
        );
    })
;

// Fetching tag IDs, creating new tags if required
function tagIdsFromTags(PdoDataProvider $dataProvider, array $tags): array
{
    $existingTags = $dataProvider->getEntityList('tags', [
        'name' => FieldConfig::DATA_TYPE_STRING,
        'id'   => FieldConfig::DATA_TYPE_INT,
    ], filterData: ['LOWER(name)' => array_map(static fn(string $tag) => mb_strtolower($tag), $tags)]);

    $existingTagsMap = array_column($existingTags, 'field_name', 'field_id');
    $existingTagsMap = array_map(static fn(string $tag) => mb_strtolower($tag), $existingTagsMap);
    $existingTagsMap = array_flip($existingTagsMap);

    $tagIds = [];
    foreach ($tags as $tag) {
        if (!isset($existingTagsMap[mb_strtolower($tag)])) {
            $dataProvider->createEntity('tags', ['name' => FieldConfig::DATA_TYPE_STRING], ['name' => $tag]);
            $newTagId = $dataProvider->lastInsertId();
        } else {
            $newTagId = $existingTagsMap[mb_strtolower($tag)];
        }
        $tagIds[] = $newTagId;
    }

    return $tagIds;
}
```

## Contributing

If you have suggestions for improvement, please submit a pull request.

## License

AdminYard is released under the MIT License. See LICENSE for details.
