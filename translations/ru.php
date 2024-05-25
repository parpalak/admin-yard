<?php

return [
    // Interface
    'Actions'                                           => 'Действия',
    'list'                                              => 'Список',
    'show'                                              => 'Просмотр',
    'edit'                                              => 'Редактировать',
    'delete'                                            => 'Удалить',
    'new'                                               => 'Создать',
    'Save'                                              => 'Сохранить',
    'Filter'                                            => 'Фильтровать',
    'Confirm deletion'                                  => 'Подтвердить удаление',
    'Cancel'                                            => 'Отменить',

    // Core errors
    'An error encountered'                              => 'Произошла ошибка',
    'No entity was requested.'                          => 'В запросе сущность не указана, и в конфигурации не задана сущность по умолчанию.',
    'Unknown entity "%s" was requested.'                => 'Была запрошена неизвестная сущность "%s".',
    'No action was requested.'                          => 'Никакое действие не было запрошено.',
    'Action "%s" is unsupported.'                       => 'Действие "%s" не поддерживается.',
    'Action "%s" is not allowed for entity "%s".'       => 'Действие "%s" запрещено для записей "%s".',

    // Controller errors
    'Parameter "%s" must be provided.'                  => 'Параметр "%s" должен быть предоставлен.',
    '%s with %s not found.'                             => 'Не найдена запись %s с %s.',
    'The entity with same parameters already exists.'   => 'Запись с такими параметрами уже существует.',

    // Template errors
    'Cannot create new %s due to the following errors:' => 'Невозможно создать %s из-за следующих ошибок:',
    'Cannot save %s due to the following errors:'       => 'Невозможно сохранить %s из-за следующих ошибок:',

    'Are you sure you want to delete?'                           => 'Вы уверены, что хотите удалить эту запись?',
    '%s was not deleted.'                                        => 'Запись "%s" не была удален.',
    '%s deleted successfully.'                                   => 'Запись "%s" была успешно удалена.',
    'Cannot delete entity because it is used in other entities.' => 'Невозможно удалить запись, так как она используется в других записях.',

    'This value should not be blank.'                                                                                                                => 'Поле не может быть пустым.',
    'The value you selected is not a valid choice.'                                                                                                  => 'Выбранное значение неверно.',
    'This value is not a valid date.'                                                                                                                => 'Значение не является корректной датой.',
    'This value is too long. It should have {{ limit }} character or less.|This value is too long. It should have {{ limit }} characters or less.'   => 'Значение слишком длинное. Его длина должна быть не более {{ limit }} символа.|Значение слишком длинное. Ешл длина должна быть не более {{ limit }} символов.',
    'This value is too short. It should have {{ limit }} character or more.|This value is too short. It should have {{ limit }} characters or more.' => 'Значение слишком короткое. Его длина должна быть не менее {{ limit }} символа.|Значение слишком короткое. Его длина должна быть не менее {{ limit }} символов.',

    'Unable to confirm security token. A likely cause for this is that some time passed between when you first entered the page and when you submitted the form. If that is the case and you would like to continue, submit the form again.' => 'Не получилось проверить, что эти данные отправили именно вы. Возможно, между открытием страницы и отправкой данных прошло много времени. Если так и было, и вы хотите сохранить эти данные, отправьте их еще раз.',
];
