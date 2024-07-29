<?php
/**
 * @copyright 2024 Roman Parpalak
 * @license   http://opensource.org/licenses/MIT MIT
 * @package   AdminYard
 */

declare(strict_types=1);

namespace S2\AdminYard\Form;

use S2\AdminYard\Config\FieldConfig;
use S2\AdminYard\Helper\RandomHelper;
use Symfony\Component\HttpFoundation\Request;

readonly class FormParams
{
    /**
     * @param array<string,FieldConfig> $fields
     */
    public function __construct(
        public string   $entityName,
        public array    $fields,
        private Request $request,
        private string  $action,
        private array   $primaryKey = [],
    ) {
    }

    public function getCsrfToken(): string
    {
        return self::generateCsrfToken($this->entityName, $this->action, array_keys($this->fields), $this->primaryKey, $this->request);
    }

    private static function generateCsrfToken(string $entityName, string $action, array $fieldNames, array $primaryKey, Request $request): string
    {
        $session = $request->getSession();
        if (!$session->has('main_csrf_token')) {
            $mainToken = RandomHelper::getRandomHexString32();
            $session->set('main_csrf_token', $mainToken);
        } else {
            $mainToken = $session->get('main_csrf_token');
        }

        return sha1(serialize([$entityName, $action, $fieldNames, $primaryKey, $mainToken]));
    }
}
