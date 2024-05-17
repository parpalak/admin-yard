<?php

declare(strict_types=1);

/** @var string $title */
/** @var string $entityName */
/** @var array $header */
/** @var array $row */
/** @var array $primaryKey */
/** @var array $actions */
?>
<h1><?= htmlspecialchars($title, ENT_QUOTES, 'UTF-8') ?></h1>
<section class="show-content">
    <table>
        <tbody>
        <?php foreach ($row['cells'] as $fieldName => $cell): ?>
            <tr>
                <td class="field-name"><?= htmlspecialchars($header[$fieldName], ENT_QUOTES, 'UTF-8') ?></td>
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
        <a class="link-as-button show-action-link show-action-link-<?= $action['name'] ?> <?= $action['name'] === 'delete' ? 'danger' : '' ?>"
           title="<?= $action['name'] ?>"
           href="?<?= $queryParams ?>"><span><?= $action['name'] ?></span></a>
        <?php
    }
    ?>
</section>
