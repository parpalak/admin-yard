<?php
/**
 * @copyright 2024 Roman Parpalak
 * @license   http://opensource.org/licenses/MIT MIT
 * @package   AdminYard
 */

declare(strict_types=1);

namespace Tests\Support\Helper;

use Codeception\TestInterface;
use Psr\Container\ContainerExceptionInterface;
use S2\AdminYard\AdminPanel;
use S2\AdminYard\Config\AdminConfig;
use S2\AdminYard\DefaultAdminFactory;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\HttpFoundation\Session\Storage\MockArraySessionStorage;

class Integration extends AbstractBrowserModule
{
    protected ?\PDO $pdo = null;
    protected ?AdminPanel $adminPanel = null;
    protected ?SessionInterface $session = null;

    /**
     * @throws ContainerExceptionInterface
     * @throws \PDOException
     */
    public function _initialize(): void
    {
        parent::_initialize();

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

    protected function doRealRequest(Request $request): Response
    {
        $request->setSession($this->session);
        return $this->adminPanel->handleRequest($request);
    }
}
