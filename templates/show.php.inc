<?php

declare(strict_types=1);

/** @var callable $trans */
/** @var string $title */
/** @var string $entityName */
/** @var array $header */
/** @var array $row */
/** @var string $csrfToken */
/** @var array $primaryKey */
/** @var array $actions */
?>
<h1><?= htmlspecialchars($title, ENT_QUOTES, 'UTF-8') ?></h1>
<section class="show-content">
    <table class="show-table">
        <tbody>
        <?php foreach ($row['cells'] as $fieldName => $cell): ?>
            <tr>
                <td class="field-name"><?= htmlspecialchars($header[$fieldName], ENT_QUOTES, 'UTF-8') ?></td>
                <td class="type-<?= $cell['type'] ?> field-<?= $entityName ?>-<?= $fieldName ?>"><?= $cell['content'] ?></td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
</section>
<section class="show-actions">
    <?php
    foreach ($actions as $action) {
        $queryParams = http_build_query([
            'entity' => $entityName,
            'action' => $action['name'],
            ...($action['name'] === 'edit' || $action['name'] === 'delete' ? $primaryKey : [])
        ]);
        ?>
        <?php if ($action['name'] === 'delete'): ?>
            <a class="link-as-button show-action-link show-action-link-delete danger"
               title="<?= $trans($action['name']) ?>"
               href="?<?= $queryParams ?>"
               onclick="if (confirm('<?= $trans('Are you sure you want to delete?') ?>')) {fetch(this.href, {method: 'POST', body: new URLSearchParams('csrf_token=<?= $csrfToken ?>') }).then(function () { window.location = '?<?= http_build_query(['entity' => $entityName, 'action' => 'list']) ?>'; } );} return false;"><span><?= $trans($action['name']) ?></span></a>
        <?php else: ?>
            <a class="link-as-button show-action-link show-action-link-<?= $action['name'] ?>"
               title="<?= $trans($action['name']) ?>"
               href="?<?= $queryParams ?>"><span><?= $trans($action['name']) ?></span></a>
        <?php endif; ?>
        <?php
    }
    ?>
</section>
