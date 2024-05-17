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
                    <td class="type-<?= isset($cell['foreign_entity']) ? 'string' : $cell['type'] ?> field-<?= $entityName ?>-<?= $fieldName ?>">
                        <?php if (isset($cell['foreign_entity'])): ?>
                            <a href="?<?= http_build_query([
                                'entity'                => $cell['foreign_entity'],
                                'action'                => 'show',
                                $cell['foreign_column'] => $cell['value']
                            ]) ?>"><?= htmlspecialchars($cell['label'], ENT_QUOTES, 'UTF-8') ?></a>
                        <?php else: ?>
                            <?= htmlspecialchars($cell['value'], ENT_QUOTES, 'UTF-8') ?>
                        <?php endif; ?>
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
