<?php

declare(strict_types=1);

/**
 * @var string $value      From database, normalized and converted to view format
 * @var string $label      Calculated SQL expression for the label or $value
 * @var array  $linkParams Additional parameters for the link when $linkToAction is set
 */

use S2\AdminYard\Transformer\RowsDto;

?>
<?php if ($linkParams !== null): ?>
<a href="?<?= http_build_query($linkParams) ?>"><?= htmlspecialchars($label, ENT_QUOTES, 'UTF-8') ?></a>
<?php elseif ($value === null): ?>
<span class="null">null</span>
<?php elseif ($value === '✓'): ?>
<span style="color: var(--color-success)">✓</span>
<?php elseif ($value === '✗'): ?>
<span style="color: var(--color-error)">✗</span>
<?php elseif ($value instanceof RowsDto): ?>
    <table class="rows-table">
        <thead>
        <tr>
            <?php $labels = $value->getLabels();
            foreach ($labels as $label): ?>
                <th><?= htmlspecialchars($label, ENT_QUOTES, 'UTF-8') ?></th>
            <?php endforeach; ?>
        </tr>
        </thead>
        <tbody>
        <?php $rows = $value->getRows(); ?>
        <?php if (is_array($rows)): ?>
            <?php foreach ($rows as $row): ?>
                <tr>
                    <?php foreach ($row as $cell): ?>
                        <td><?= htmlspecialchars($cell, ENT_QUOTES, 'UTF-8') ?></td>
                    <?php endforeach; ?>
                </tr>
            <?php endforeach; ?>
        <?php else: ?>
            <tr>
                <td colspan="<?= count($labels) ?>"><span class="null">null</span></td>
            </tr>
        <?php endif; ?>
        </tbody>
    </table>
<?php else: ?>
<?= htmlspecialchars($label, ENT_QUOTES, 'UTF-8') ?>
<?php endif; ?>
