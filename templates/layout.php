<?php

declare(strict_types=1);

/** @var string $menu */
/** @var ?string $content */
/** @var ?string $errorMessage */

?>
<html>
<head>
    <title>Admin panel</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
<nav><?= $menu ?></nav>
<?php if ($content === null && isset($errorMessage)): ?>
    <article class="error">
        <h1>Error</h1>
        <p class="error-message"><?= htmlspecialchars($errorMessage, ENT_QUOTES, 'UTF-8') ?></p>
    </article>
<?php else: ?>
    <article><?= $content ?></article>
<?php endif; ?>
</body>
</html>
