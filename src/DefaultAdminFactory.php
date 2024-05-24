<?php
/**
 * @copyright 2024 Roman Parpalak
 * @license   http://opensource.org/licenses/MIT MIT
 * @package   AdminYard
 */

declare(strict_types=1);

namespace S2\AdminYard;

use S2\AdminYard\Config\AdminConfig;
use S2\AdminYard\Database\PdoDataProvider;
use S2\AdminYard\Database\TypeTransformer;
use S2\AdminYard\Form\FormControlFactory;
use S2\AdminYard\Form\FormFactory;
use S2\AdminYard\Transformer\ViewTransformer;
use Symfony\Component\EventDispatcher\EventDispatcher;

/**
 * Example of AdminYard factory. Feel free to create your own or use DI instead.
 */
class DefaultAdminFactory
{
    public static function createAdminPanel(
        AdminConfig $adminConfig,
        \PDO        $pdo,
        array       $translations = [],
        string      $locale = 'en'
    ): AdminPanel {
        $translator       = new Translator($translations, $locale);
        $templateRenderer = new TemplateRenderer($translator);
        $dataProvider     = new PdoDataProvider($pdo, new TypeTransformer());

        $eventDispatcher = new EventDispatcher();
        foreach ($adminConfig->getEntities() as $entityConfig) {
            foreach ($entityConfig->getListeners() as $eventName => $listener) {
                $eventDispatcher->addListener('adminyard.' . $eventName, $listener);
            }
        }

        return new AdminPanel(
            $adminConfig,
            $eventDispatcher,
            $dataProvider,
            new ViewTransformer(),
            new MenuGenerator($adminConfig, $templateRenderer),
            $translator,
            $templateRenderer,
            new FormFactory(new FormControlFactory(), $translator, $dataProvider)
        );
    }
}
