# AdminYard

AdminYard is a lightweight PHP library for building admin panels without heavy dependencies
such as frameworks, templating engines, or ORMs.
It provides a declarative object-oriented configuration for defining entities, fields, and their properties.
With AdminYard, you can quickly set up CRUD (Create, Read, Update, Delete) interfaces
for your database tables and customize them according to your needs.
The library also includes features for generating menus, rendering templates, and handling database setup.

AdminYard simplifies the process of creating typical admin interfaces,
allowing you to focus on developing core functionality.

When developing AdminYard, I had an example of EasyAdmin.
I would use it to develop some of my own project,
but I didn't want to pull major dependencies like Symfony, Doctrine, Twig into it.
So I tried to make a similar product without those dependencies.
It can be useful for embedding into existing legacy projects,
where adding a new framework and ORM is not so easy.
If you are starting a new project from scratch, 
I recommend you to consider Symfony, Doctrine and EasyAdmin.

## Installation
To install AdminYard, you can use Composer:

```bash
composer require s2/adminyard
```

## Usage
Once installed, you can start using AdminYard by creating an instance of AdminConfig
and configuring it with your entity settings.
Then, create an instance of AdminPanel passing the AdminConfig instance.
Use the handleRequest method to handle incoming requests and generate the admin panel HTML.

## Architecture
AdminYard operates with three levels of data representation:

- HTTP: HTML code of forms sent and data received in POST requests.
- Normalized data passed between code components.
- Persisted data in the database.

When transitioning between these levels, data is transformed based on the entity configuration.
To transform between the HTTP level and normalized data, form controls are used.
To transform between normalized data and data persisted in the database, `dataType` is used.
The `dataType` value must be chosen based on the column type in the database
and the meaning of the data stored in it.
For example, if you have a column in the database of type `VARCHAR`, you cannot specify the dataType as `bool`,
since the TypeTransformer will convert all values to integer 0 or 1 when writing to the database,
which is not compatible with string data types.

It should be understood that not all form controls are compatible with normalized data
produced by the TypeTransformer when reading from the database based on dataType.

## Contributing
If you have suggestions for improvement, please submit a pull request.

## License
AdminYard is released under the MIT License. See LICENSE for details.
