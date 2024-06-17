<?php

declare(strict_types=1);

/** @var callable $trans */
/** @var string $menu */
/** @var ?string $content */
/** @var ?string $errorMessage */
/** @var array $flashMessages */

?>
<html>
<head>
    <title>Admin panel</title>
    <link rel="stylesheet" href="style.css">
    <script src="script.js"></script>
</head>
<body>
<nav><?= $menu ?></nav>
<?php
foreach ($flashMessages as $type => $messages) {
    foreach ($messages as $message) {
        ?>
        <div class="flash-message flash-<?= $type ?>">
            <?= htmlspecialchars($message, ENT_QUOTES, 'UTF-8') ?>
            <button type="button" class="flash-message-close" onclick="this.parentElement.remove()">✕</button>
        </div>
        <?php
    }
}
?>
<?php if ($content === null && isset($errorMessage)): ?>
    <article class="error">
        <h1><?= $trans('An error encountered') ?></h1>
        <div class="error-message-box"><p><?= htmlspecialchars($errorMessage, ENT_QUOTES, 'UTF-8') ?></p></div>
    </article>
<?php else: ?>
    <article class="admin-content"><?= $content ?></article>
<?php endif; ?>
</body>
</html>
