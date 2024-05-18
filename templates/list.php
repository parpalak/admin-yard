<?php

declare(strict_types=1);

/** @var string $title */
/** @var string $entityName */
/** @var array $header */
/** @var array $rows */
/** @var array $actions */

?>
<h1><?= htmlspecialchars($title, ENT_QUOTES, 'UTF-8') ?></h1>
<section class="list-header">
    <a class="link-as-button" href="?<?= http_build_query(['entity' => $entityName, 'action' => 'new']) ?>">New</a>
</section>
<section class="list-content">
    <table>
        <thead>
        <tr>
            <?php foreach ($header as $cell): ?>
                <th><?= htmlspecialchars($cell, ENT_QUOTES, 'UTF-8') ?></th>
            <?php endforeach; ?>
            <th></th>
        </tr>
        </thead>

        <tbody>
        <?php foreach ($rows as $row): ?>
            <tr>
                <?php foreach ($row['cells'] as $fieldName => $cell): ?>
                    <td class="type-<?= $cell['type'] ?> field-<?= $entityName ?>-<?= $fieldName ?>">
                        <?= $cell['content'] ?>
                    </td>
                <?php endforeach; ?>
                <td>
                    <?php foreach ($actions as $action) {
                        $queryParams = http_build_query(array_merge([
                            'entity' => $entityName,
                            'action' => $action['name']
                        ], $row['primary_key']));
                        ?>
                        <a class="list-action-link list-action-link-<?= $action['name'] ?>"
                           title="<?= $action['name'] ?>"
                           href="?<?= $queryParams ?>"><span><?= $action['name'] ?></span></a>
                    <?php } ?>
                </td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
</section>
