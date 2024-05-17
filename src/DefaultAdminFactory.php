<?php
/**
 * @copyright 2024 Roman Parpalak
 * @license http://opensource.org/licenses/MIT MIT
 * @package AdminYard
 */

declare(strict_types=1);

namespace S2\AdminYard;

use S2\AdminYard\Config\AdminConfig;
use S2\AdminYard\Database\PdoDataProvider;
use S2\AdminYard\Database\TypeTransformer;
use S2\AdminYard\Form\FormControlFactory;
use S2\AdminYard\Form\FormFactory;
use S2\AdminYard\Transformer\ViewTransformer;

/**
 * Example of AdminYard factory. Feel free to create your own or use DI instead.
 */
class DefaultAdminFactory
{
    public static function createAdminPanel(
        AdminConfig $adminConfig,
        \PDO        $pdo
    ): AdminPanel {
        $templateRenderer = new TemplateRenderer();
        $dataProvider     = new PdoDataProvider($pdo, new TypeTransformer());

        return new AdminPanel(
            $adminConfig,
            $dataProvider,
            new ViewTransformer(),
            new MenuGenerator($adminConfig, $templateRenderer),
            $templateRenderer,
            new FormFactory(new FormControlFactory(), $dataProvider)
        );
    }
}
