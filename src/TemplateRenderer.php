<?php
/**
 * @copyright 2024 Roman Parpalak
 * @license http://opensource.org/licenses/MIT MIT
 * @package AdminYard
 */

declare(strict_types=1);

namespace S2\AdminYard;

class TemplateRenderer
{
    public function render(string $_template_path, array $data): string
    {
        extract($data);
        ob_start();
        require $_template_path;
        return ob_get_clean();
    }
}
