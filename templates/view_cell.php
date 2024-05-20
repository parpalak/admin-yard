<?php

declare(strict_types=1);

/** @var string $value From database, normalized and converted to view format */
/** @var string $label Calculated SQL expression for the label */
/** @var string $linkToAction Action name to link to */
/** @var string $entity Name of current entity */
/** @var array $primaryKey Name of current entity */
/** @var string $foreign_entity Name of the entity to be linked to, not set for usual fields with no associations */
/** @var string $foreign_id_column Primary key column name of the foreign entity (for Many-To-One) */
/** @var string $inverse_column Column name of the foreign entity that links to the current entity (for One-To-Many) */
/** @var string $inverse_id Content of the inverse column (primary key value of the current entity) */
?>
<?php if (isset($foreign_id_column)): ?>
    <a href="?<?= http_build_query([
        'entity'           => $foreign_entity,
        'action'           => 'show',
        $foreign_id_column => $value
    ]) ?>"><?= htmlspecialchars($label, ENT_QUOTES, 'UTF-8') ?></a>
<?php elseif (isset($inverse_column)): ?>
    <a href="?<?= http_build_query([
        'entity'        => $foreign_entity,
        'action'        => 'list',
        $inverse_column => $inverse_id
    ]) ?>"><?= htmlspecialchars($label, ENT_QUOTES, 'UTF-8') ?></a>
<?php elseif ($linkToAction !== null): ?>
    <a href="?<?= http_build_query(['entity' => $entity, 'action' => $linkToAction, ...$primaryKey]) ?>"><?= htmlspecialchars($value, ENT_QUOTES, 'UTF-8') ?></a>
<?php elseif ($value === null): ?>
    <span class="null">null</span>
<?php else: ?>
    <?= htmlspecialchars($value, ENT_QUOTES, 'UTF-8') ?>
<?php endif; ?>
