<?php
/**
 * @copyright 2024 Roman Parpalak
 * @license   http://opensource.org/licenses/MIT MIT
 * @package   AdminYard
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
    /**
     * @var array<string, callable>
     */
    private array $pages = [];
    private array $priorities = [];
    private array $readableNames = [];

    public function addEntity(EntityConfig $entity, int $priority = 0): static
    {
        if (isset($this->priorities[$entity->getName()])) {
            throw new \InvalidArgumentException('Entity "' . $entity->getName() . '" already exists');
        }
        $this->priorities[$entity->getName()] = $priority;

        $this->entities[] = $entity;

        return $this;
    }

    /**
     * @return EntityConfig[]
     */
    public function getEntities(): array
    {
        return $this->entities;
    }

    public function findEntityByName(string $name): ?EntityConfig
    {
        foreach ($this->entities as $entity) {
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
        foreach ($this->entities as $entity) {
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

    public function setServicePage(string $pageName, callable $page, int $priority = 0, ?string $readableName = null): self
    {
        if (isset($this->priorities[$pageName])) {
            throw new \InvalidArgumentException('Page "' . $pageName . '" already exists');
        }
        $this->priorities[$pageName] = $priority;

        $this->pages[$pageName]         = $page;
        $this->readableNames[$pageName] = $readableName;

        return $this;
    }

    public function getServicePage(string $pageName): ?callable
    {
        return $this->pages[$pageName] ?? null;
    }

    public function getReadableName(string $pageName): string
    {
        return $this->readableNames[$pageName] ?? $pageName;
    }

    public function getServicePageNames(): array
    {
        return array_keys($this->pages);
    }

    public function getPriorities(): array
    {
        return $this->priorities;
    }
}
