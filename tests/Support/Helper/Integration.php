<?php
/**
 * @copyright 2024 Roman Parpalak
 * @license   http://opensource.org/licenses/MIT MIT
 * @package   AdminYard
 */

declare(strict_types=1);

namespace Tests\Support\Helper;

use Codeception\Module;
use Codeception\TestInterface;
use Psr\Container\ContainerExceptionInterface;
use S2\AdminYard\AdminPanel;
use S2\AdminYard\Config\AdminConfig;
use S2\AdminYard\DefaultAdminFactory;
use Symfony\Component\DomCrawler\Crawler;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\HttpFoundation\Session\Storage\MockArraySessionStorage;

class Integration extends Module
{
    protected ?\PDO $pdo = null;
    protected ?AdminPanel $adminPanel = null;
    protected ?Response $response = null;
    protected ?Crawler $crawler = null;
    protected ?SessionInterface $session = null;

    /**
     * @throws ContainerExceptionInterface
     * @throws \PDOException
     */
    public function _initialize(): void
    {
        shell_exec('mysql -u root ' . (getenv('DB_PASSWORD') ? '-p' . getenv('DB_PASSWORD') : '') . ' --database adminyard_test < ' . __DIR__ . '/../../../demo/init.sql');
        $this->pdo = new \PDO('mysql:host=127.0.0.1;dbname=adminyard_test;', 'root', getenv('DB_PASSWORD') ?: '');

        $adminConfig      = require __DIR__ . '/../../../demo/admin_config.php';
        $this->adminPanel = $this->createAdminPanel($adminConfig, $this->pdo);
        $this->session    = new Session(new MockArraySessionStorage());
    }

    public function _before(TestInterface $test)
    {
        $this->pdo->beginTransaction();
        $this->session->clear();
    }

    public function _after(TestInterface $test)
    {
        $this->pdo->rollBack();
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
                $content = implode("\n", $this->crawler->filter($selector)->each(function (Crawler $node) {
                    return $node->text();
                }));
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

    public function dontSee(string $text, ?string $selector = null): void
    {
        if ($selector !== null) {
            try {
                $content = implode("\n", $this->crawler->filter($selector)->each(function (Crawler $node) {
                    return $node->text();
                }));
            } catch (\Exception $e) {
                $this->fail('Selector "' . $selector . '" is not found. Exception: ' . $e->getMessage());
                return;
            }
        } else {
            $content = $this->response->getContent();
        }
        $condition = str_contains($content, $text) || str_contains($content, htmlspecialchars($text, ENT_QUOTES, 'UTF-8'));
        if ($condition) {
            codecept_debug($content);
            $this->fail('See "' . $text . '" in response');
        }
        $this->assertFalse($condition);
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
        $formCrawler = $this->crawler->filter($selector);
        $button      = $formCrawler->filter('button[type="submit"]');
        if ($button->count() > 0 && $button->attr('name')) {
            $buttonData = [$button->attr('name') => $button->attr('value') ?? ''];
        } else {
            $buttonData = [];
        }

        $form = $formCrawler->form();
        $form->disableValidation(); // see https://stackoverflow.com/questions/57386450/how-to-tick-a-specific-checkbox-from-a-multi-dimensional-field-in-a-symfony-form
        $form->setValues($data);

        // Hack for crawler. Looks like it doesn't add button data for no reason, so we do it manually here
        $phpValues = array_merge($buttonData, $form->getPhpValues());
        $request   = Request::create($form->getUri(), $form->getMethod(), $phpValues);
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
        $request->setSession($this->session);
        $this->response = $this->adminPanel->handleRequest($request);
        $this->crawler  = new Crawler($this->response->getContent(), 'http://localhost');
    }
}
