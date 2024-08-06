<?php
/**
 * @copyright 2024 Roman Parpalak
 * @license   http://opensource.org/licenses/MIT MIT
 * @package   AdminYard
 */

declare(strict_types=1);

namespace S2\AdminYard;

use Psr\Log\LoggerAwareTrait;
use S2\AdminYard\Config\AdminConfig;
use S2\AdminYard\Config\FieldConfig;
use S2\AdminYard\Controller\EntityController;
use S2\AdminYard\Controller\InvalidConfigException;
use S2\AdminYard\Controller\InvalidRequestException;
use S2\AdminYard\Controller\NotFoundException;
use S2\AdminYard\Database\PdoDataProvider;
use S2\AdminYard\Form\FormFactory;
use S2\AdminYard\Transformer\ViewTransformer;
use Symfony\Component\EventDispatcher\EventDispatcher;
use Symfony\Component\HttpFoundation\Exception\SessionNotFoundException;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class AdminPanel
{
    use LoggerAwareTrait;

    public function __construct(
        private readonly AdminConfig      $config,
        private readonly EventDispatcher  $eventDispatcher,
        private readonly PdoDataProvider  $dataProvider,
        private readonly ViewTransformer  $dataTransformer,
        private readonly MenuGenerator    $menuGenerator,
        private readonly Translator       $translator,
        private readonly TemplateRenderer $templateRenderer,
        private readonly FormFactory      $formFactory,
    ) {
    }

    public function handleRequest(Request $request): Response
    {
        $entityName = $request->query->get('entity');
        if ($entityName !== null && ($page = $this->config->getServicePage($entityName))) {
            $html = $this->templateRenderer->render($this->config->getLayoutTemplate(), [
                'menu'          => $this->menuGenerator->generateMainMenu('', $entityName),
                'content'       => $page(),
                'flashMessages' => $this->getFlashMessages($request),
            ]);

            return new Response($html);
        }

        if ($entityName === null) {
            // No entity was requested, consider as a "main" page and display a list of default entities
            $entityConfig = $this->config->findDefaultEntity();
            if ($entityConfig === null) {
                return $this->errorResponse(
                    $request,
                    $this->translator->trans('No entity was requested.'),
                    Response::HTTP_INTERNAL_SERVER_ERROR
                );
            }
            $action = $request->query->get('action', FieldConfig::ACTION_LIST);

        } else {
            $entityConfig = $this->config->findEntityByName($entityName);
            if ($entityConfig === null) {
                return $this->errorResponse(
                    $request,
                    sprintf($this->translator->trans('Unknown entity "%s" was requested.'), $entityName),
                    Response::HTTP_NOT_FOUND
                );
            }
            $action = $request->query->get('action');
        }

        if ($action === null || $action === '') {
            return $this->errorResponse($request, $this->translator->trans('No action was requested.'));
        }

        // TODO: Implement a controller resolver instead of $entityConfig->getControllerClass()?
        $controllerClass = $entityConfig->getControllerClass() ?? EntityController::class;
        $controller      = new $controllerClass(
            $entityConfig,
            $this->eventDispatcher,
            $this->dataProvider,
            $this->dataTransformer,
            $this->translator,
            $this->templateRenderer,
            $this->formFactory,
        );

        if ($this->logger !== null) {
            $controller->setLogger($this->logger);
        }

        $methodName = $action . 'Action';
        if (!method_exists($controller, $methodName)) {
            return $this->errorResponse($request, sprintf($this->translator->trans('Action "%s" is unsupported.'), $action));
        }
        if (!$entityConfig->isAllowedAction($action) && method_exists(EntityController::class, $methodName)) {
            // Allowed actions are checked only for default actions defined in the EntityController.
            // If a custom controller for an entity defines a custom action, it is supposed to be allowed.
            return $this->errorResponse($request, sprintf($this->translator->trans('Action "%s" is not allowed for entity "%s".'), $action, $entityConfig->getName()), Response::HTTP_FORBIDDEN);
        }

        try {
            $content = $controller->{$methodName}($request);
        } catch (SessionNotFoundException $e) {
            return $this->errorResponse($request, sprintf(
                'No session has been provided. One must set session via Request::setSession() before calling %s().',
                __METHOD__
            ), Response::HTTP_INTERNAL_SERVER_ERROR);
        } catch (NotFoundException $e) {
            return $this->errorResponse($request, $e->getMessage(), Response::HTTP_NOT_FOUND);
        } catch (InvalidConfigException $e) {
            return $this->errorResponse($request, 'Configuration contains some errors to be fixed: ' . $e->getMessage(), Response::HTTP_INTERNAL_SERVER_ERROR);
        } catch (InvalidRequestException $e) {
            return $this->errorResponse($request, $e->getMessage(), $e->getCode() ?: Response::HTTP_BAD_REQUEST);
        }
        if ($content instanceof Response) {
            return $content;
        }

        $html = $this->templateRenderer->render($this->config->getLayoutTemplate(), [
            'menu'          => $this->menuGenerator->generateMainMenu('', $entityName),
            'content'       => $content,
            'flashMessages' => $this->getFlashMessages($request),
        ]);

        return new Response($html);
    }

    private function errorResponse(Request $request, string $errorMessage, int $responseCode = Response::HTTP_BAD_REQUEST): Response
    {
        $html = $this->templateRenderer->render($this->config->getLayoutTemplate(), [
            'menu'          => $this->menuGenerator->generateMainMenu(''),
            'content'       => null,
            'errorMessage'  => $errorMessage,
            'flashMessages' => $this->getFlashMessages($request),
        ]);

        return new Response($html, $responseCode);
    }

    private function getFlashMessages(Request $request): array
    {
        try {
            return $request->getSession()->getFlashBag()->all();
        } catch (SessionNotFoundException $e) {
            return [];
        }
    }
}
