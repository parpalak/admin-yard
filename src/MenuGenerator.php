<?php
/**
 * @copyright 2024 Roman Parpalak
 * @license http://opensource.org/licenses/MIT MIT
 * @package AdminYard
 */

declare(strict_types=1);

namespace S2\AdminYard;

use S2\AdminYard\Config\AdminConfig;

readonly class MenuGenerator
{
    public function __construct(
        private AdminConfig      $config,
        private TemplateRenderer $templateRenderer
    ) {
    }

    public function generateMainMenu(string $baseUrl): string
    {
        $links = [];
        foreach ($this->config->getEntities() as $entity) {
            $links[] = [
                'name' => $entity->getName(),
                'url'  => $baseUrl . '?entity=' . urlencode($entity->getName()) . '&action=list',
            ];
        }

        return $this->templateRenderer->render($this->config->getMenuTemplate(), [
            'links' => $links
        ]);
    }
}
