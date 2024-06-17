<?php
/**
 * @copyright 2024 Roman Parpalak
 * @license http://opensource.org/licenses/MIT MIT
 * @package AdminYard
 */

declare(strict_types=1);

namespace S2\AdminYard\Config;

class AdminConfig
{
    /**
     * @var EntityConfig[]
     */
    private array $entities = [];
    private string $menuTemplate = __DIR__ . '/../../templates/menu.php.inc';
    private string $layoutTemplate = __DIR__ . '/../../templates/layout.php.inc';

    public function addEntity(EntityConfig $entity, $priority = 0): static
    {
        $this->entities[] = ['entity' => $entity, 'priority' => $priority];

        return $this;
    }

    /**
     * @return EntityConfig[]
     */
    public function getEntities(): array
    {
        usort($this->entities, static function ($a, $b) {
            return $a['priority'] <=> $b['priority'];
        });

        return array_column($this->entities, 'entity');
    }

    public function findEntityByName(string $name): ?EntityConfig
    {
        foreach ($this->getEntities() as $entity) {
            if ($entity->getName() === $name) {
                return $entity;
            }
        }

        return null;
    }

    /**
     * @return EntityConfig|null
     */
    public function findDefaultEntity(): ?EntityConfig
    {
        foreach ($this->getEntities() as $entity) {
            if ($entity->isDefault()) {
                return $entity;
            }
        }

        return null;
    }

    public function setLayoutTemplate(string $template): self
    {
        $this->layoutTemplate = $template;
        return $this;
    }

    public function getLayoutTemplate(): string
    {
        return $this->layoutTemplate;
    }

    public function setMenuTemplate(string $menuTemplate): self
    {
        $this->menuTemplate = $menuTemplate;
        return $this;
    }

    public function getMenuTemplate(): string
    {
        return $this->menuTemplate;
    }
}
