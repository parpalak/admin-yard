<?php

declare(strict_types=1);

/** @var array $links */

?>
<ul>
    <?php foreach ($links as $link): ?>
        <li><a href="<?= htmlspecialchars($link['url'], ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars($link['name'], ENT_QUOTES, 'UTF-8') ?></a></li>
    <?php endforeach; ?>
</ul>
