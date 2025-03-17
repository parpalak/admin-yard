<?php

/** @var callable $trans */
/** @var string $title */
/** @var string $entityName */

/** @var array<string, \S2\AdminYard\Form\FormControlInterface> $filterControls */
/** @var array<string, string> $filterLabels */
/** @var array $filterData output from filter form */

/** @var array $sortableFields list of sortable field names */
/** @var string $sortField */
/** @var string $sortDirection */

/** @var array $header */
/** @var array $rows */
/** @var array $entityActions */
/** @var array $totalCount */
/** @var array $page */
/** @var array $limit */

/**
 * Generates HTML pagination links.
 *
 * @param int $page The current page number (starts from 1).
 * @param int|null $limit The number of records per page (null for no limit).
 * @param int|null $totalCount The total number of records (null if unknown).
 * @param array $queryParams Additional query parameters to include in the pagination links.
 * @return string The HTML block with pagination links.
 */
$renderPagination = static function (int $page, ?int $limit, ?int $totalCount, array $queryParams = []): string {
    if ($page < 1) {
        $page = 1;
    }

    $paginationHtml = '<div class="pagination">';

    if ($limit === null) {
        $paginationHtml .= '<span class="pagination-current">1</span>';
    } else {
        $totalPages = $totalCount !== null ? (int) ceil($totalCount / $limit) : $page + 1;
        $range = 2; // Количество соседних страниц
        $prevPage = 0; // Последняя отрисованная страница

        for ($i = 1; $i <= $totalPages; $i++) {
            if ($i === 1 || $i === $totalPages || abs($i - $page) <= $range) {
                if ($prevPage && $i > $prevPage + 1) {
                    $paginationHtml .= '<span class="pagination-ellipsis">...</span>';
                }

                $queryParams['page'] = $i;
                if ($i === $page) {
                    $paginationHtml .= '<span class="pagination-current">' . $i . '</span>';
                } else {
                    $paginationHtml .= '<a class="pagination-link" href="?' . http_build_query($queryParams) . '">' . $i . '</a>';
                }

                $prevPage = $i;
            }
        }
    }

    $paginationHtml .= '</div>';

    return $paginationHtml;
};

$hasActions = false;
foreach ($rows as $row) {
    if (($row['rendered_actions'] ?? '') !== '') {
        $hasActions = true;
        break;
    }
}

?>
<section class="list-header">
    <h1><?= htmlspecialchars($title, ENT_QUOTES, 'UTF-8') ?></h1>
    <?php if (!empty($entityActions)): ?>
        <div class="list-header-actions">
            <?php foreach ($entityActions as $action): ?>
                <a class="link-as-button entity-action entity-action-<?= $action['name'] ?>"
                   href="?<?= http_build_query(['entity' => $entityName, 'action' => $action['name']]) ?>"><?= $trans($action['name']) ?></a>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</section>
<?php if (!empty($filterControls)): ?>
    <section class="filter-content">
        <form method="GET" action="?">
            <input type="hidden" name="entity" value="<?= $entityName ?>">
            <input type="hidden" name="action" value="list">
            <div class="filter-controls">
                <?php foreach ($filterControls as $fieldName => $control): ?>
                    <div
                        class="filter-control filter-control-<?= strtolower(basename(strtr(get_class($control), ['\\' => '/']))) ?> filter-<?= $entityName ?>-<?= $fieldName ?>">
                        <span class="filter-label">
                            <?= htmlspecialchars($filterLabels[$fieldName] ?? $header[$fieldName], ENT_QUOTES, 'UTF-8') ?>
                        </span>
                        <span class="filter-wrapper">
                        <?= $control->getHtml() ?>
                        </span>
                        <?php foreach ($control->getValidationErrors() as $error): ?>
                            <span class="filter-validation-error validation-error">
                                <?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?>
                            </span>
                        <?php endforeach; ?>
                    </div>
                <?php endforeach; ?>
                <div class="filter-control-button">
                    <button class="secondary filter-button" type="submit" name="apply_filter" value="1">
                        <?= $trans('Filter') ?>
                    </button>
                </div>
            </div>
        </form>
    </section>
<?php endif; ?>
<section class="list-content">
    <div class="table-wrapper">
        <table class="list-table">
            <thead>
            <tr>
                <?php foreach ($header as $fieldName => $cell): ?>
                    <?php if (in_array($fieldName, $sortableFields, true)): ?>
                        <th
                            title="<?= htmlspecialchars($hint[$fieldName] ?? '', ENT_QUOTES, 'UTF-8') ?>"
                            class="field-<?= $entityName ?>-<?= $fieldName ?> <?= $fieldName === $sortField ? 'current-sort' : '' ?>">
                            <a class="sort-link"
                               href="?<?= http_build_query(['entity' => $entityName, 'action' => 'list', ...$filterData, 'sort_field' => $fieldName, 'sort_direction' => $sortDirection === 'asc' && $fieldName === $sortField ? 'desc' : 'asc']) ?>">
                                <?= htmlspecialchars($cell, ENT_QUOTES, 'UTF-8') ?><?= $fieldName === $sortField ? ($sortDirection === 'asc' ? ' ▴' : ' ▾') : '' ?>
                            </a>
                        </th>
                    <?php else: ?>
                        <th
                            title="<?= htmlspecialchars($hint[$fieldName] ?? '', ENT_QUOTES, 'UTF-8') ?>"
                            class="field-<?= $entityName ?>-<?= $fieldName ?>"><?= htmlspecialchars($cell, ENT_QUOTES, 'UTF-8') ?></th>
                    <?php endif; ?>
                <?php endforeach; ?>
                <?php if ($hasActions): ?>
                    <th><?= $trans('Actions') ?></th>
                <?php endif; ?>
            </tr>
            </thead>

            <tbody>
            <?php foreach ($rows as $row): ?>
                <tr>
                    <?php foreach ($row['cells'] as $fieldName => $cell): ?>
                        <td class="type-<?= $cell['type'] ?> field-<?= $entityName ?>-<?= $fieldName ?> <?= $fieldName === $sortField ? 'current-sort' : '' ?>"><?= $cell['content'] ?></td>
                    <?php endforeach; ?>
                    <?php if ($hasActions): ?>
                        <td class="row-actions">
                            <?= $row['rendered_actions'] ?? '' ?>
                        </td>
                    <?php endif; ?>
                </tr>
            <?php endforeach; ?>
            <?php if (count($rows) === 0): ?>
                <tr>
                    <td colspan="<?= count($header) + ($hasActions ? 1 : 0) ?>"><?= $trans('No results') ?></td>
                </tr>
            <?php endif; ?>
            </tbody>
        </table>
    </div>

    <?php echo $renderPagination($page, $limit, $totalCount, array_merge(['entity' => $entityName, 'action' => 'list'], $filterData)); ?>
</section>
