<?php

declare(strict_types=1);

/** @var callable $trans */
/** @var string $title */
/** @var string $entityName */
/** @var array $header */
/** @var array $hint */
/** @var array $errorMessages */
/** @var \S2\AdminYard\Form\Form $form */
/** @var array $primaryKey */
/** @var array $actions */

$formQueryParams = http_build_query([
    'entity' => $entityName,
    'action' => 'new'
]);

$errorMessages = array_merge($errorMessages, $form->getGlobalFormErrors());

?>
<h1><?= htmlspecialchars($title, ENT_QUOTES, 'UTF-8') ?></h1>
<section class="new-content">
    <?php if (!empty($errorMessages)): ?>
        <div class="error-message-box">
            <p><?php printf($trans('Cannot create new %s due to the following errors:'), $entityName) ?></p>
            <ul class="error-messages">
                <?php foreach ($errorMessages as $message): ?>
                    <li><?= htmlspecialchars($message, ENT_QUOTES, 'UTF-8') ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
    <?php endif; ?>
    <form method="POST" action="?<?= $formQueryParams ?>">
        <?php foreach ($form->getHiddenControls() as $control): ?>
            <?= $control->getHtml() ?>
        <?php endforeach; ?>
        <div class="form-wrapper">
            <?php foreach ($form->getVisibleControls() as $fieldName => $control): ?>
                <label class="field-name"
                       title="<?= htmlspecialchars($hint[$fieldName] ?? '', ENT_QUOTES, 'UTF-8') ?>"
                       for="id-<?= $fieldName ?>"><?= htmlspecialchars($header[$fieldName], ENT_QUOTES, 'UTF-8') ?></label>
                <div
                    class="form-control-<?= strtolower(basename(strtr(get_class($control), ['\\' => '/']))) ?> field-<?= $entityName ?>-<?= $fieldName ?>">
                    <?= $control->getHtml('id-' . $fieldName) ?>
                    <?php foreach ($control->getValidationErrors() as $error): ?>
                        <span class="validation-error"><?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?></span>
                    <?php endforeach; ?>
                </div>
            <?php endforeach; ?>
            <div class="form-buttons">
                <button type="submit"><?= $trans('Save') ?></button>
                <span>
                        <?php
                        foreach ($actions as $action) {
                            $queryParams = http_build_query([
                                'entity' => $entityName,
                                'action' => $action['name'],
                            ]);
                            ?>
                            <a class="link-as-button new-action-link new-action-link-<?= $action['name'] ?>"
                               title="<?= $trans($action['name']) ?>"
                               href="?<?= $queryParams ?>"><span><?= $trans($action['name']) ?></span></a>
                            <?php
                        }
                        ?>
                </span>
            </div>
        </div>
    </form>
</section>
