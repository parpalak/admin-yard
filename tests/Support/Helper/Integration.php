<?php
/**
 * @copyright 2024 Roman Parpalak
 * @license   http://opensource.org/licenses/MIT MIT
 * @package   AdminYard
 */

declare(strict_types=1);

namespace Tests\Support\Helper;

use Codeception\Module;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;
use S2\AdminYard\AdminPanel;
use S2\AdminYard\Config\AdminConfig;
use S2\AdminYard\DefaultAdminFactory;
use Symfony\Component\DomCrawler\Crawler;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class Integration extends Module
{
    private ?\PDO $pdo = null;
    protected ?AdminPanel $adminPanel = null;
    protected ?Response $response = null;
    private ?Crawler $crawler = null;

    /**
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    public function _initialize()
    {
        shell_exec('mysql -u root --database adminyard_test < ' . __DIR__ . '/../../../demo/init.sql');
        $this->pdo = new \PDO('mysql:host=localhost;dbname=adminyard_test', 'root', '');

        $adminConfig      = require __DIR__ . '/../../../demo/admin_config.php';
        $this->adminPanel = $this->createAdminPanel($adminConfig, $this->pdo);
    }

    public function createAdminPanel(AdminConfig $adminConfig, \PDO $pdo): AdminPanel
    {
        return DefaultAdminFactory::createAdminPanel($adminConfig, $pdo);
    }

    public function amOnPage(string $url): void
    {
        $this->doRequest(Request::create($url));
    }

    public function grabResponse(): string
    {
        return $this->response->getContent();
    }

    public function see(string $text, ?string $selector = null): void
    {
        if ($selector !== null) {
            try {
                $content = $this->crawler->filter($selector)->text();
            } catch (\Exception $e) {
                $this->fail('Selector "' . $selector . '" is not found. Exception: ' . $e->getMessage());
                return;
            }
        } else {
            $content = $this->response->getContent();
        }
        $condition = str_contains($content, $text) || str_contains($content, htmlspecialchars($text, ENT_QUOTES, 'UTF-8'));
        if (!$condition) {
            codecept_debug($content);
            $this->fail('Cannot see "' . $text . '" in response');
        }
        $this->assertTrue($condition);
    }

    public function grabMultiple(string $selector): array
    {
        return $this->crawler->filter($selector)->each(static function (Crawler $node) {
            return $node->text();
        });
    }

    public function seeElement(string $selector): void
    {
        $condition = $this->crawler->filter($selector)->count() > 0;
        if (!$condition) {
            codecept_debug($this->response->getContent());
            $this->fail('Cannot see "' . $selector . '" in response');
        }
        $this->assertTrue($condition);
    }

    public function dontSeeElement(string $selector): void
    {
        $this->assertSame(0, $this->crawler->filter($selector)->count());
    }

    public function grabAttributeFrom(string $selector, string $attribute): ?string
    {
        return $this->crawler->filter($selector)->attr($attribute);
    }

    public function seeResponseCodeIs(int $code): void
    {
        $this->assertEquals($code, $this->response->getStatusCode());
    }

    public function seeLocationIs(string $location): void
    {
        $this->assertEquals($location, $this->response->headers->get('Location'));
    }

    public function seeLocationMatches(string $locationRegex): void
    {
        $this->assertMatchesRegularExpression($locationRegex, $this->response->headers->get('Location'));
    }

    public function followRedirect(): void
    {
        $this->doRequest(Request::create($this->response->headers->get('Location')));
    }

    public function click(string $selector): void
    {
        $this->doRequest(Request::create($this->crawler->selectLink($selector)->link()->getUri()));
    }

    public function submitForm(string $selector, array $data): void
    {
        $form = $this->crawler->filter($selector)->form();
        $form->disableValidation(); // see https://stackoverflow.com/questions/57386450/how-to-tick-a-specific-checkbox-from-a-multi-dimensional-field-in-a-symfony-form
        $form->setValues($data);

        $request = Request::create($form->getUri(), $form->getMethod(), $form->getPhpValues());
        $this->doRequest($request);
    }

    public function sendPost(string $url, array $data): void
    {
        $request = Request::create($url, 'POST', $data);
        $this->doRequest($request);
    }

    public function grabFormValues(string $selector): array
    {
        return $this->crawler->filter($selector)->form()->getValues();
    }

    private function doRequest(Request $request): void
    {
        $this->response = $this->adminPanel->handleRequest($request);
        $this->crawler  = new Crawler($this->response->getContent(), 'http://localhost');
    }
}
