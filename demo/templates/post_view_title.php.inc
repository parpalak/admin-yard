<?php

if ($row['column_is_active']) {
    echo htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
} else {
    echo '<del>' . htmlspecialchars($value, ENT_QUOTES, 'UTF-8') . '</del>';
}
