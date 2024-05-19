<?php

return [
    'Actions'                                                            => 'Действия',
    'list'                                                               => 'Список',
    'show'                                                               => 'Просмотр',
    'edit'                                                               => 'Редактировать',
    'delete'                                                             => 'Удалить',
    'new'                                                                => 'Создать',
    'Save'                                                               => 'Сохранить',
    'Filter'                                                             => 'Фильтровать',

    // Core errors
    'An error encountered'                                               => 'Произошла ошибка',
    'No entity was requested and no default entity has been configured.' => 'В запросе сущность не указана, и в конфигурации не задана сущность по умолчанию.',
    'Unknown entity "%s" was requested.'                                 => 'Была запрошена неизвестная сущность "%s".',
    'No action was requested.'                                           => 'Никакое действие не было запрошено.',
    'Action "%s" is unsupported.'                                        => 'Действие "%s" не поддерживается.',
    'Action "%s" is not allowed for entity "%s".'                        => 'Действие "%s" запрещено для записей "%s".',

    // Controller errors
    'Parameter "%s" must be provided.'                                   => 'Параметр "%s" должен быть предоставлен.',
    '%s with %s not found.'                                              => 'Не найдена запись %s с %s.',
    'The entity with same parameters already exists.'                    => 'Запись с такими параметрами уже существует.',

    // Template errors
    'Cannot create new %s due to the following errors:'                  => 'Невозможно создать %s из-за следующих ошибок:',
    'Cannot save %s due to the following errors:'                        => 'Невозможно сохранить %s из-за следующих ошибок:',
];
