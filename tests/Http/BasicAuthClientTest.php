<?php declare(strict_types=1);

namespace HelpPC\Bundle\SupervisorBundle\Tests\Http;

use HelpPC\Bundle\SupervisorBundle\Http\BasicAuthClient;
use HelpPC\Bundle\SupervisorBundle\Tests\Fixtures\RecordingHttpClient;
use Nyholm\Psr7\Factory\Psr17Factory;
use PHPUnit\Framework\TestCase;

final class BasicAuthClientTest extends TestCase
{
    public function testAddsBasicAuthorizationHeader(): void
    {
        $factory = new Psr17Factory();
        $inner = new RecordingHttpClient($factory->createResponse());
        $client = new BasicAuthClient($inner, 'user', 's3cret');

        $client->sendRequest($factory->createRequest('POST', 'http://127.0.0.1:9001/RPC2'));

        self::assertCount(1, $inner->requests);
        self::assertSame(
            'Basic ' . base64_encode('user:s3cret'),
            $inner->requests[0]->getHeaderLine('Authorization'),
        );
    }
}
