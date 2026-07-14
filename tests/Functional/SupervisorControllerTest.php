<?php declare(strict_types=1);

namespace HelpPC\Bundle\SupervisorBundle\Tests\Functional;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

final class SupervisorControllerTest extends WebTestCase
{
    protected static function getKernelClass(): string
    {
        return TestKernel::class;
    }

    protected function tearDown(): void
    {
        parent::tearDown();
        // Symfony's HttpKernel leaves an exception handler registered, which
        // PHPUnit >= 10.5 reports as risky (symfony/symfony#53812).
        restore_exception_handler();
    }

    public function testIndexRendersOfflineStateForUnreachableServer(): void
    {
        $client = static::createClient();
        $client->request('GET', '/supervisor/');

        self::assertResponseIsSuccessful();
        $content = (string) $client->getResponse()->getContent();
        self::assertStringContainsString('api', $content);
        self::assertStringContainsString('Offline', $content);
    }

    public function testHiddenProcessActionIsNotFoundBeforeAnyRpc(): void
    {
        $client = static::createClient();

        // No RPC server is running; a 404 (instead of a redirect with an error
        // flash) proves the deny-list guard fires before any connection attempt.
        $client->request('POST', '/supervisor/api/process/php-fpm/php-fpm/stop');
        self::assertResponseStatusCodeSame(404);

        $client->request('POST', '/supervisor/api/process/nginx/nginx/start');
        self::assertResponseStatusCodeSame(404);

        $client->request('POST', '/supervisor/api/process/fatal_exit/fatal_exit/restart');
        self::assertResponseStatusCodeSame(404);

        // Hidden by group even when the process name differs (numprocs>1).
        $client->request('POST', '/supervisor/api/process/nginx/nginx_00/stop');
        self::assertResponseStatusCodeSame(404);

        // Log tails of hidden processes are denied as well.
        $client->request('GET', '/supervisor/api/process/php-fpm/php-fpm/log/stdout');
        self::assertResponseStatusCodeSame(404);
    }

    public function testUnknownServerKeyIsNotFound(): void
    {
        $client = static::createClient();
        $client->request('POST', '/supervisor/unknown/process/scheduler/scheduler/stop');

        self::assertResponseStatusCodeSame(404);
    }

    public function testVisibleProcessActionOnUnreachableServerRedirectsWithErrorFlash(): void
    {
        $client = static::createClient();
        $client->request('POST', '/supervisor/api/process/scheduler/scheduler/restart');

        self::assertResponseRedirects('/supervisor/');
        $crawler = $client->followRedirect();
        self::assertResponseIsSuccessful();
        self::assertGreaterThan(0, $crawler->filter('.flash-error')->count());
    }

    public function testGetOnStateChangingRouteIsMethodNotAllowed(): void
    {
        $client = static::createClient();
        $client->request('GET', '/supervisor/api/process/scheduler/scheduler/stop');

        self::assertResponseStatusCodeSame(405);
    }
}
