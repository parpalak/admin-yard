<?php

declare(strict_types=1);

/** @var string $title */
/** @var string $entityName */
/** @var array $header */
/** @var array $filterControls */
/** @var array $rows */
/** @var array $rowActions */
/** @var array $entityActions */

?>
<section class="list-header">
    <h1><?= htmlspecialchars($title, ENT_QUOTES, 'UTF-8') ?></h1>
    <?php if (!empty($entityActions)): ?>
        <div class="list-header-actions">
            <?php foreach ($entityActions as $action): ?>
                <a class="link-as-button entity-action entity-action-<?= $action['name'] ?>"
                   href="?<?= http_build_query(['entity' => $entityName, 'action' => $action['name']]) ?>"><?= $action['name'] ?></a>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</section>
<?php if (!empty($filterControls)): ?>
    <section class="filter-content">
        <form method="GET" action="?">
            <input type="hidden" name="entity" value="<?= $entityName ?>">
            <input type="hidden" name="action" value="list">
            <table>
                <tbody>
                <?php foreach ($filterControls as $fieldName => $control): ?>
                    <tr>
                        <td class="field-name"><?= htmlspecialchars($filterLabels[$fieldName] ?? $header[$fieldName], ENT_QUOTES, 'UTF-8') ?></td>
                        <td class="field-<?= $entityName ?>-<?= $fieldName ?>">
                            <?= $control->getHtml() ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
            <div class="form-buttons">
                <button class="secondary" type="submit">Filter</button>
            </div>
        </form>
    </section>
<?php endif; ?>
<section class="list-content">
    <table>
        <thead>
        <tr>
            <?php foreach ($header as $cell): ?>
                <th><?= htmlspecialchars($cell, ENT_QUOTES, 'UTF-8') ?></th>
            <?php endforeach; ?>
            <?php if (!empty($rowActions)): ?>
                <th>Actions</th>
            <?php endif; ?>
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
                <?php if (!empty($rowActions)): ?>
                    <td>
                        <?php foreach ($rowActions as $action) {
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
                <?php endif; ?>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
</section>
