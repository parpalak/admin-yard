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
        switch (getenv('APP_DB_TYPE')) {
            case 'pgsql':
                shell_exec('sudo -u postgres psql adminyard_test < ' . __DIR__ . '/../../../demo/init_pgsql.sql');
                $this->pdo = new \PDO('pgsql:host=localhost;dbname=adminyard_test', 'postgres', '12345');
                break;
            case 'sqlite':
                shell_exec('pwd; sqlite3 adminyard_test.db < ' . __DIR__ . '/../../../demo/init_sqlite.sql');
                $this->pdo = new \PDO('sqlite:adminyard_test.db', '', '');
                $this->pdo->exec('PRAGMA foreign_keys = ON;');
                break;
            default:
                shell_exec('mysql -u root ' . (getenv('DB_PASSWORD') ? '-p' . getenv('DB_PASSWORD') : '') . ' --database adminyard_test < ' . __DIR__ . '/../../../demo/init_mysql.sql');
                $this->pdo = new \PDO('mysql:host=127.0.0.1;dbname=adminyard_test;', 'root', getenv('DB_PASSWORD') ?: '');
        }

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

    public function createAdminPanel(AdminConfig $adminConfig, ?\PDO $pdo = null): AdminPanel
    {
        return DefaultAdminFactory::createAdminPanel($adminConfig, $pdo ?? $this->pdo);
    }

    public function amOnPage(string $url): void
    {
        $this->doRequest(Request::create($url));
    }

    public function grabResponse(): string
    {
        return $this->response->getContent();
    }

    /**
     * @throws \JsonException
     */
    public function grabJson(): ?array
    {
        $content = $this->response->getContent();
        return json_decode($content, true, 512, JSON_THROW_ON_ERROR);
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
            $this->debug($content);
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
            $this->debug($content);
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
            $this->debug($this->response->getContent());
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

    public function seeFlashMessage(string $check): void
    {
        $flashMessages = $this->session->getFlashBag()->peekAll();
        $condition     = false;
        foreach ($flashMessages as $type => $messages) {
            foreach ($messages as $message) {
                if (str_contains($message, $check)) {
                    $condition = true;
                    break;
                }
            }
        }

        $this->assertTrue($condition);
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
        $crawler = $this->crawler->selectLink($selector);
        if ($crawler->count() === 0) {
            $crawler = $this->crawler->filter($selector);
        }
        $this->doRequest(Request::create($crawler->link()->getUri()));
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

    public function grabAndMatch(string $selector, string $regex): ?array
    {
        $html = $this->crawler->filter($selector)->outerHtml();
        if (preg_match($regex, $html, $matches)) {
            return $matches;
        }
        return null;
    }

    private function doRequest(Request $request): void
    {
        $request->setSession($this->session);
        $this->debug(sprintf("%s %s", $request->getMethod(), $request->getUri()));
        $this->response = $this->adminPanel->handleRequest($request);
        $content        = $this->response->getContent();
        $this->debug(sprintf(
            "Get %s response:\n%s\n\n%s",
            $this->response->getStatusCode(),
            $this->response->headers,
            mb_substr($content, 0, 100)
        ));
        $this->crawler = new Crawler($content, 'http://localhost');
    }
}
