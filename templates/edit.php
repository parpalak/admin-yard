<?php

declare(strict_types=1);

/** @var string $title */
/** @var string $entityName */
/** @var array $header */
/** @var array $fields */
/** @var array $primaryKey */
/** @var array $actions */

$formQueryParams = http_build_query(array_merge([
    'entity' => $entityName,
    'action' => 'edit'
], $primaryKey));

?>
<h1><?= htmlspecialchars($title, ENT_QUOTES, 'UTF-8') ?></h1>
<section class="edit-content">
    <form method="POST" action="?<?= $formQueryParams ?>">
        <table>
            <tbody>
            <?php foreach ($fields as $fieldName => $control): ?>
                <tr>
                    <td class="field-name"><?= htmlspecialchars($header[$fieldName], ENT_QUOTES, 'UTF-8') ?></td>
                    <td class="field-<?= $entityName ?>-<?= $fieldName ?>">
                        <?= $control->getHtml() ?>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
        <div class="form-buttons">
            <button type="submit">Save</button>
        </div>
    </form>
</section>
<section class="edit-actions">
    <?php
    foreach ($actions as $action) {
        $queryParams = http_build_query([
            'entity' => $entityName,
            'action' => $action['name'],
            ...($action['name'] === 'show' || $action['name'] === 'delete' ? $primaryKey : [])
        ]);
        ?>
        <a class="link-as-button edit-action-link edit-action-link-<?= $action['name'] ?> <?= $action['name'] === 'delete' ? 'danger' : '' ?>"
           title="<?= $action['name'] ?>"
           href="?<?= $queryParams ?>"><span><?= $action['name'] ?></span></a>
        <?php
    }
    ?>
</section>
