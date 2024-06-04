<?php

if ($row['column_is_active']) {
    echo $value;
} else {
    echo '<del>' . $value . '</del>';
}
