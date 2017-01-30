<?php
/*
 * THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDERS AND CONTRIBUTORS
 * "AS IS" AND ANY EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT
 * LIMITED TO, THE IMPLIED WARRANTIES OF MERCHANTABILITY AND FITNESS FOR
 * A PARTICULAR PURPOSE ARE DISCLAIMED. IN NO EVENT SHALL THE COPYRIGHT
 * OWNER OR CONTRIBUTORS BE LIABLE FOR ANY DIRECT, INDIRECT, INCIDENTAL,
 * SPECIAL, EXEMPLARY, OR CONSEQUENTIAL DAMAGES (INCLUDING, BUT NOT
 * LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR SERVICES; LOSS OF USE,
 * DATA, OR PROFITS; OR BUSINESS INTERRUPTION) HOWEVER CAUSED AND ON ANY
 * THEORY OF LIABILITY, WHETHER IN CONTRACT, STRICT LIABILITY, OR TORT
 * (INCLUDING NEGLIGENCE OR OTHERWISE) ARISING IN ANY WAY OUT OF THE USE
 * OF THIS SOFTWARE, EVEN IF ADVISED OF THE POSSIBILITY OF SUCH DAMAGE.
 *
 * This software consists of voluntary contributions made by many individuals
 * and is licensed under the MIT license.
 */

namespace ZfrLightspeedRetailTest\OAuth;

use GuzzleHttp\ClientInterface;
use GuzzleHttp\Command\Command;
use GuzzleHttp\Command\CommandInterface;
use GuzzleHttp\Command\Exception\CommandClientException;
use GuzzleHttp\Command\Result;
use GuzzleHttp\Exception\ClientException;
use GuzzleHttp\Promise\FulfilledPromise;
use GuzzleHttp\Promise\RejectedPromise;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\Response;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use ZfrLightspeedRetail\Exception\UnauthorizedException;
use ZfrLightspeedRetail\OAuth\AuthorizationMiddleware;
use ZfrLightspeedRetail\OAuth\Credential;
use ZfrLightspeedRetail\OAuth\CredentialStorage\InMemoryCredentialStorage;
use function GuzzleHttp\json_encode as guzzle_json_encode;
use function GuzzleHttp\Psr7\stream_for;

/**
 * @author Daniel Gimenes
 */
final class AuthorizationMiddlewareTest extends TestCase
{
    public function testAuthorizesCommand()
    {
        $referenceId = 'omc-demo.myshopify.com';

        // Initialize storage with valid access token
        $credentialStorage = new InMemoryCredentialStorage([
            $referenceId => new Credential($referenceId, 123456, 'valid', 'foo')
        ]);

        $httpClient = $this->prophesize(ClientInterface::class);

        $middleware = new AuthorizationMiddleware(
            $this->createNextHandler(),
            $credentialStorage,
            $httpClient->reveal(),
            '123456',
            'foobar'
        );

        $httpClient->request(Argument::any())->shouldNotBeCalled();

        $result = $middleware(new Command('Test', ['referenceID' => $referenceId]))->wait();

        $this->assertSame('success', $result['message']);
    }

    public function testForwardsNon401Errors()
    {
        $referenceId = 'omc-demo.myshopify.com';

        // Initialize storage with valid access token
        $credentialStorage = new InMemoryCredentialStorage([
            $referenceId => new Credential($referenceId, 123456, 'valid', 'foo')
        ]);

        $httpClient = $this->prophesize(ClientInterface::class);
        $middleware = new AuthorizationMiddleware(
            $this->createNextHandler(),
            $credentialStorage,
            $httpClient->reveal(),
            '123456',
            'foobar'
        );

        $httpClient->request(Argument::any())->shouldNotBeCalled();

        $this->expectException(CommandClientException::class);
        $this->expectExceptionMessage('Huh?');

        // NonExisting command throws 404
        $middleware(new Command('NonExisting', ['referenceID' => $referenceId]))->wait();
    }

    public function testRefreshesToken()
    {
        $clientId     = '123456';
        $clientSecret = 'foobar';
        $referenceId  = 'omc-demo.myshopify.com';

        // Initialize storage with an invalid access token
        $credentialStorage = new InMemoryCredentialStorage([
            $referenceId => new Credential($referenceId, 123456, 'invalid', 'foo')
        ]);

        $httpClient = $this->prophesize(ClientInterface::class);
        $middleware = new AuthorizationMiddleware(
            $this->createNextHandler(),
            $credentialStorage,
            $httpClient->reveal(),
            $clientId,
            $clientSecret
        );

        // Since Next handler returns CommandClientException, it refreshes the token
        $httpClient->request('POST', 'https://cloud.merchantos.com/oauth/access_token.php', [
            'json' => [
                'client_id'     => $clientId,
                'client_secret' => $clientSecret,
                'refresh_token' => 'foo',
                'grant_type'    => 'refresh_token',
            ],
        ])->shouldBeCalled()->willReturn(
            new Response(200, [], stream_for(guzzle_json_encode([
                'access_token'  => 'valid',
                'refresh_token' => 'bar',
            ])))
        );

        // Then it calls the next handler with refreshed access token, which returns a valid result
        $result = $middleware(new Command('Test', ['referenceID' => $referenceId]))->wait();

        $this->assertSame('success', $result['message']);

        // Credential storage should have been updated with the refreshed credentials
        $refreshedCredential = $credentialStorage->get($referenceId);

        $this->assertSame('valid', $refreshedCredential->getAccessToken());
        $this->assertSame('bar', $refreshedCredential->getRefreshToken());
    }

    public function testThrowsExceptionIfRejectedRefreshToken()
    {
        $clientId     = '123456';
        $clientSecret = 'foobar';
        $referenceId  = 'omc-demo.myshopify.com';

        // Initialize storage with an invalid access token
        $credentialStorage = new InMemoryCredentialStorage([
            $referenceId => new Credential($referenceId, 123456, 'invalid', 'foo')
        ]);

        $httpClient = $this->prophesize(ClientInterface::class);
        $middleware = new AuthorizationMiddleware(
            $this->createNextHandler(),
            $credentialStorage,
            $httpClient->reveal(),
            $clientId,
            $clientSecret
        );

        // Since Next handler returns CommandClientException, it refreshes the token
        $httpClient->request('POST', 'https://cloud.merchantos.com/oauth/access_token.php', [
            'json' => [
                'client_id'     => $clientId,
                'client_secret' => $clientSecret,
                'refresh_token' => 'foo',
                'grant_type'    => 'refresh_token',
            ],
        // But the authorization server rejects the refresh token
        ])->shouldBeCalled()->willThrow(new ClientException(
            'Boom!',
            new Request('POST', 'https://cloud.merchantos.com/oauth/access_token.php'),
            new Response(400, [], stream_for(guzzle_json_encode([
                'error'             => 'invalid_grant',
                'error_description' => 'Invalid refresh token',
            ])))
        ));

        $this->expectException(UnauthorizedException::class);
        $this->expectExceptionMessage('The refresh token "foo" was rejected by Lightspeed Authorization Server');

        $middleware(new Command('Test', ['referenceID' => $referenceId]))->wait();
    }

    public function testRejectsCommandsWithoutReferenceId()
    {
        $httpClient = $this->prophesize(ClientInterface::class);
        $middleware = new AuthorizationMiddleware(
            function () {
                $this->fail('Next handler should not be called');
            },
            new InMemoryCredentialStorage([]),
            $httpClient->reveal(),
            '123456',
            'foobar'
        );

        $httpClient->request(Argument::any())->shouldNotBeCalled();

        $this->expectException(UnauthorizedException::class);
        $this->expectExceptionMessage(
            'Missing "referenceID" command param. This is required to fetch the credentials from credential storage'
        );

        $middleware(new Command('Test'))->wait();
    }

    public function testRejectsUnauthorizedAccounts()
    {
        $httpClient = $this->prophesize(ClientInterface::class);
        $middleware = new AuthorizationMiddleware(
            function () {
                $this->fail('Next handler should not be called');
            },
            new InMemoryCredentialStorage([]),
            $httpClient->reveal(),
            '123456',
            'foobar'
        );

        $httpClient->request(Argument::any())->shouldNotBeCalled();

        $this->expectException(UnauthorizedException::class);
        $this->expectExceptionMessage(
            'No credential found in credential storage for "omc-demo.myshopify.com".'
        );

        $middleware(new Command('Test', ['referenceID' => 'omc-demo.myshopify.com']))->wait();
    }

    /**
     * Creates a handler that mimics the Lightspeed API Server
     *
     * @return callable
     */
    private function createNextHandler(): callable
    {
        return function (CommandInterface $command) {
            // Returns 401 if unauthorized command
            if (123456 !== $command['accountID'] || 'Bearer valid' !== $command['@http']['headers']['Authorization']) {
                return new RejectedPromise(new CommandClientException(
                    'Boom!',
                    $command,
                    null,
                    null,
                    new Response(401)
                ));
            }

            // Returns 404 if command named "NonExisting"
            if ('NonExisting' === $command->getName()) {
                return new RejectedPromise(new CommandClientException(
                    'Huh?',
                    $command,
                    null,
                    null,
                    new Response(404)
                ));
            }

            // Otherwise, returns a valid result
            return new FulfilledPromise(new Result(['message' => 'success']));
        };
    }
}
