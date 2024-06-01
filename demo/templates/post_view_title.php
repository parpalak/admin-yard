<?php

if ($row['field_is_active']) {
    echo $value;
} else {
    echo '<del>' . $value . '</del>';
}
