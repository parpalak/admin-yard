<?php
/**
 * @copyright 2024 Roman Parpalak
 * @license   http://opensource.org/licenses/MIT MIT
 * @package   AdminYard
 */

declare(strict_types=1);

namespace S2\AdminYard;

use Symfony\Contracts\Translation\TranslatorInterface;
use Symfony\Contracts\Translation\TranslatorTrait;

class Translator implements TranslatorInterface
{
    use TranslatorTrait {
        trans as protected parentTrans;
    }

    public function __construct(private readonly array $translations, string $locale)
    {
        $this->setLocale($locale);
    }

    public function trans(?string $id, array $parameters = [], ?string $domain = null, ?string $locale = null): string
    {
        $id = isset($this->translations[$id]) ? (string)$this->translations[$id] : $id;

        return $this->parentTrans($id, $parameters, $domain, $locale);
    }
}
