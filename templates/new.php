<?php

declare(strict_types=1);

/** @var callable $trans */
/** @var string $title */
/** @var string $entityName */
/** @var array $header */
/** @var array $fields */
/** @var array $primaryKey */
/** @var array $actions */

$formQueryParams = http_build_query([
    'entity' => $entityName,
    'action' => 'new'
]);

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
        <table>
            <tbody>
            <?php foreach ($fields as $fieldName => $control): ?>
                <tr>
                    <td class="field-name"><?= htmlspecialchars($header[$fieldName], ENT_QUOTES, 'UTF-8') ?></td>
                    <td class="field-<?= $entityName ?>-<?= $fieldName ?>">
                        <?= $control->getHtml() ?>
                        <?php foreach ($control->getValidationErrors() as $error): ?>
                            <span class="validation-error"><?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?></span>
                        <?php endforeach; ?>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
        <div class="form-buttons">
            <button type="submit"><?= $trans('Save') ?></button>
        </div>
    </form>
</section>
<section class="new-actions">
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
</section>
