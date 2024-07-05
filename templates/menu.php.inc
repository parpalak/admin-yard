<?php

declare(strict_types=1);

/** @var array $links */
/** @var callable $trans */

?>
<ul class="main-menu-list">
    <?php foreach ($links as $link): ?>
        <li class="main-menu-item <?= $link['active'] ? 'active' : '' ?>">
            <a class="main-menu-link" href="<?= htmlspecialchars($link['url'], ENT_QUOTES, 'UTF-8') ?>">
                <?= htmlspecialchars($trans($link['name']), ENT_QUOTES, 'UTF-8') ?>
            </a>
        </li>
    <?php endforeach; ?>
</ul>
