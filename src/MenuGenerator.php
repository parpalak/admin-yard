<?php
/**
 * @copyright 2024 Roman Parpalak
 * @license   http://opensource.org/licenses/MIT MIT
 * @package   AdminYard
 */

declare(strict_types=1);

namespace S2\AdminYard;

use S2\AdminYard\Config\AdminConfig;
use S2\AdminYard\Config\FieldConfig;

readonly class MenuGenerator
{
    public function __construct(
        protected AdminConfig      $config,
        protected TemplateRenderer $templateRenderer
    ) {
    }

    public function generateMainMenu(string $baseUrl, ?string $currentEntity = null): string
    {
        $links = $this->getLinkArray($baseUrl, $currentEntity);

        return $this->templateRenderer->render($this->config->getMenuTemplate(), [
            'links' => $links
        ]);
    }

    protected function getLinkArray(string $baseUrl, ?string $currentEntity): array
    {
        $links = $this->config->getPriorities();
        asort($links);

        foreach ($this->config->getEntities() as $entity) {
            $name = $entity->getName();
            if (!$entity->isAllowedAction(FieldConfig::ACTION_LIST)) {
                unset($links[$name]);
                continue;
            }
            $links[$name] = [
                'name'   => $entity->getPluralName(),
                'url'    => $baseUrl . '?entity=' . urlencode($name) . '&action=list',
                'active' => $currentEntity === $name,
            ];
        }

        foreach ($this->config->getServicePageNames() as $page) {
            $links[$page] = [
                'name'   => $this->config->getReadableName($page),
                'url'    => $baseUrl . '?entity=' . urlencode($page),
                'active' => $currentEntity === $page,
            ];
        }
        return $links;
    }
}
