<?php
/**
 * @copyright 2024 Roman Parpalak
 * @license   http://opensource.org/licenses/MIT MIT
 * @package   AdminYard
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

    public function generateMainMenu(string $baseUrl, ?string $currentEntity = null): string
    {
        $links = $this->config->getPriorities();
        asort($links);

        foreach ($this->config->getEntities() as $entity) {
            $name    = $entity->getName();
            $links[$name] = [
                'name'   => $name,
                'url'    => $baseUrl . '?entity=' . urlencode($name) . '&action=list',
                'active' => $currentEntity === $name,
            ];
        }

        foreach ($this->config->getServicePageNames() as $page) {
            $links[$page] = [
                'name'   => $page,
                'url'    => $baseUrl . '?entity=' . urlencode($page),
                'active' => $currentEntity === $page,
            ];
        }

        return $this->templateRenderer->render($this->config->getMenuTemplate(), [
            'links' => $links
        ]);
    }
}
