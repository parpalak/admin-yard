<?php
/**
 * @copyright 2024 Roman Parpalak
 * @license http://opensource.org/licenses/MIT MIT
 * @package AdminYard
 */

declare(strict_types=1);

namespace S2\AdminYard;

use S2\AdminYard\Config\AdminConfig;
use S2\AdminYard\Config\FieldConfig;
use S2\AdminYard\Controller\EntityController;
use S2\AdminYard\Database\PdoDataProvider;
use S2\AdminYard\Form\FormFactory;
use S2\AdminYard\Transformer\ViewTransformer;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

readonly class AdminPanel
{
    public function __construct(
        private AdminConfig      $config,
        private PdoDataProvider  $dataProvider,
        private ViewTransformer  $dataTransformer,
        private MenuGenerator    $menuGenerator,
        private TemplateRenderer $templateRenderer,
        private FormFactory     $formFactory,
    ) {
    }

    public function handleRequest(Request $request): Response
    {
        $entityName = $request->query->get('entity');
        if ($entityName === null) {
            // No entity was requested, consider as a "main" page and display a list of default entities
            $entityConfig = $this->config->findDefaultEntity();
            if ($entityConfig === null) {
                return $this->errorResponse('No entity was requested and no default entity has been configured.');
            }
            $action = $request->query->get('action', FieldConfig::ACTION_LIST);

        } else {
            $entityConfig = $this->config->findEntityByName($entityName);
            if ($entityConfig === null) {
                return $this->errorResponse(sprintf('Entity %s not found.', $entityName));
            }
            $action = $request->query->get('action');
        }

        // TODO: Implement a controller resolver instead of $entityConfig->getControllerClass()?
        $controllerClass = $entityConfig->getControllerClass() ?? EntityController::class;
        $controller      = new $controllerClass(
            $entityConfig,
            $this->dataProvider,
            $this->dataTransformer,
            $this->templateRenderer,
            $this->formFactory,
        );

        $methodName = $action . 'Action';
        if (!method_exists($controller, $methodName)) {
            return $this->errorResponse('Action ' . $action . ' is unsupported.');
        }

        // TODO: Exception handling?
        $content = $controller->{$methodName}($request);
        if ($content instanceof Response) {
            return $content;
        }

        $html = $this->templateRenderer->render($this->config->getLayoutTemplate(), [
            'menu'    => $this->menuGenerator->generateMainMenu(''),
            'content' => $content,
        ]);

        return new Response($html);
    }

    private function errorResponse(string $errorMessage): Response
    {
        $html = $this->templateRenderer->render($this->config->getLayoutTemplate(), [
            'menu'         => $this->menuGenerator->generateMainMenu(''),
            'content'      => null,
            'errorMessage' => $errorMessage,
        ]);

        return new Response($html);
    }
}
