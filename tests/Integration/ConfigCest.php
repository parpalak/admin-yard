<?php
/**
 * @copyright 2024 Roman Parpalak
 * @license   http://opensource.org/licenses/MIT MIT
 * @package   AdminYard
 */

declare(strict_types=1);

namespace Tests\Integration;

use S2\AdminYard\Config\AdminConfig;
use S2\AdminYard\Config\EntityConfig;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\HttpFoundation\Session\Storage\MockArraySessionStorage;
use Tests\Support\IntegrationTester;

/**
 * @group config
 */
class ConfigCest
{
    public function noDefaultEntityCest(IntegrationTester $I): void
    {
        $adminPanel = $I->createAdminPanel(new AdminConfig());
        $response   = $adminPanel->handleRequest(Request::create('?'));
        $I->assertStringContainsString('No entity was requested.', $response->getContent());
        $I->assertEquals(500, $response->getStatusCode());
    }

    public function noSessionCest(IntegrationTester $I): void
    {
        $adminPanel = $I->createAdminPanel((new AdminConfig())->addEntity(new EntityConfig('TestEntity')));
        $response   = $adminPanel->handleRequest(Request::create('?entity=TestEntity&action=list'));
        $I->assertStringContainsString('No session has been provided.', $response->getContent());
        $I->assertEquals(500, $response->getStatusCode());
    }

    public function noFieldsOnListCest(IntegrationTester $I): void
    {
        $adminPanel = $I->createAdminPanel((new AdminConfig())->addEntity(new EntityConfig('TestEntity', 'sequence')));
        $request    = Request::create('?entity=TestEntity&action=list');
        $request->setSession(new Session(new MockArraySessionStorage()));
        $response = $adminPanel->handleRequest($request);
        $I->assertEquals(200, $response->getStatusCode());
    }

    public function noFieldsOnEditCest(IntegrationTester $I): void
    {
        $adminPanel = $I->createAdminPanel((new AdminConfig())->addEntity(new EntityConfig('TestEntity', 'sequence')));
        $request    = Request::create('?entity=TestEntity&action=edit&id=1');
        $request->setSession(new Session(new MockArraySessionStorage()));
        $response = $adminPanel->handleRequest($request);
        $I->assertStringContainsString('Entity &quot;TestEntity&quot; without primary key columns cannot be accessed.', $response->getContent());
        $I->assertEquals(500, $response->getStatusCode());
    }
}
