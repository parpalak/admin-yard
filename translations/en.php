<?php

return [
    // Interface
    'Actions'                                           => 'Actions',
    'list'                                              => 'List',
    'show'                                              => 'Show',
    'edit'                                              => 'Edit',
    'delete'                                            => 'Delete',
    'new'                                               => 'New',
    'Save'                                              => 'Save',
    'Filter'                                            => 'Filter',

    // Core errors
    'An error encountered'                              => 'An error encountered',
    'No entity was requested.'                          => 'No entity was requested and no default entity has been configured.',
    'Unknown entity "%s" was requested.'                => 'Unknown entity "%s" was requested.',
    'No action was requested.'                          => 'No action was requested.',
    'Action "%s" is unsupported.'                       => 'Action "%s" is unsupported.',
    'Action "%s" is not allowed for entity "%s".'       => 'Action "%s" is not allowed for entity "%s".',

    // Controller errors
    'Parameter "%s" must be provided.'                  => 'Parameter "%s" must be provided.',
    '%s with %s not found.'                             => '%s with %s not found.',
    'The entity with same parameters already exists.'   => 'The entity with same parameters already exists.',

    // Template errors
    'Cannot create new %s due to the following errors:' => 'Cannot create new %s due to the following errors:',
    'Cannot save %s due to the following errors:'       => 'Cannot save %s due to the following errors:',

    'This value should not be blank.'                                                                                                                => 'This value should not be blank.',
    'The value you selected is not a valid choice.'                                                                                                  => 'The value you selected is not a valid choice.',
    'This value is not a valid date.'                                                                                                                => 'This value is not a valid date.',
    'This value is too long. It should have {{ limit }} character or less.|This value is too long. It should have {{ limit }} characters or less.'   => 'This value is too long. It should have {{ limit }} character or less.|This value is too long. It should have {{ limit }} characters or less.',
    'This value is too short. It should have {{ limit }} character or more.|This value is too short. It should have {{ limit }} characters or more.' => 'This value is too short. It should have {{ limit }} character or more.|This value is too short. It should have {{ limit }} characters or more.',

    'Unable to confirm security token. A likely cause for this is that some time passed between when you first entered the page and when you submitted the form. If that is the case and you would like to continue, submit the form again.' => 'Unable to confirm security token. A likely cause for this is that some time passed between when you first entered the page and when you submitted the form. If that is the case and you would like to continue, submit the form again.',
];
