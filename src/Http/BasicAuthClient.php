<?php declare(strict_types=1);

namespace HelpPC\Bundle\SupervisorBundle\Http;

use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;

/**
 * PSR-18 decorator adding an HTTP Basic Authorization header to every request.
 *
 * Used instead of URL userinfo so credentials never leak into logged URLs or
 * transport exception messages.
 */
final class BasicAuthClient implements ClientInterface
{
    public function __construct(
        private readonly ClientInterface $client,
        private readonly string $username,
        #[\SensitiveParameter] private readonly string $password,
    ) {
    }

    public function sendRequest(RequestInterface $request): ResponseInterface
    {
        return $this->client->sendRequest($request->withHeader(
            'Authorization',
            'Basic ' . base64_encode($this->username . ':' . $this->password),
        ));
    }
}
