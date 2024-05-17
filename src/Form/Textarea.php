<?php
/**
 * @copyright 2024 Roman Parpalak
 * @license http://opensource.org/licenses/MIT MIT
 * @package AdminYard
 */

declare(strict_types=1);

namespace S2\AdminYard\Form;

class Textarea extends Input
{
    public function getHtml(): string
    {
        return sprintf('<textarea name="%s">%s</textarea>', $this->fieldName, $this->value);
    }
}
