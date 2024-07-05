<?php

/** @var callable $trans */
/** @var array $row */
/** @var array $rowActions */
/** @var string $csrfToken */
/** @var string $entityName */
/** @var array $primaryKey */

foreach ($rowActions as $action) {
    $queryParams = http_build_query(array_merge([
        'entity' => $entityName,
        'action' => $action['name']
    ], $primaryKey));

    if ($action['name'] === 'delete') {
        ?>
        <a class="list-action-link list-action-link-<?= $action['name'] ?>" href="#"
           title="<?= $trans($action['name']) ?>"
           onclick="this.parentNode.querySelector('span.list-action-delete-popup').classList.toggle('hidden'); return false"><span><?= $trans($action['name']) ?></span></a>
        <span class="hidden list-action-delete-popup">
            <a class="link-as-button danger list-action-link list-action-link-delete-confirm"
               title="<?= $trans($action['name']) ?>"
               href="?<?= $queryParams ?>"
               onclick="fetch(this.href, {method: 'POST', body: new URLSearchParams('csrf_token=<?= $csrfToken ?>') }).then(function () { window.location.reload(); } ); return false;"><?= $trans('Confirm deletion') ?></a>
            <a class="link-as-button list-action-link list-action-link-delete-cancel"
               title="<?= $trans('Cancel') ?>"
               href="#"
               onclick="this.parentNode.classList.toggle('hidden'); return false;"><?= $trans('Cancel') ?></a>
        </span>
        <?php
    } else {
        ?>
        <a class="list-action-link list-action-link-<?= $action['name'] ?>"
           title="<?= $trans($action['name']) ?>"
           href="?<?= $queryParams ?>"><span><?= $trans($action['name']) ?></span></a>
        <?php
    }
}
