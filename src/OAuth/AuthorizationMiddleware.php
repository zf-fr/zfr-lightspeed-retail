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

namespace ZfrLightspeedRetail\OAuth;

use GuzzleHttp\ClientInterface;
use GuzzleHttp\Command\CommandInterface;
use GuzzleHttp\Command\Exception\CommandException;
use GuzzleHttp\Exception\ClientException;
use GuzzleHttp\Promise\PromiseInterface;
use GuzzleHttp\Promise\RejectedPromise;
use ZfrLightspeedRetail\Exception\UnauthorizedException;
use ZfrLightspeedRetail\OAuth\CredentialStorage\CredentialStorageInterface;
use function GuzzleHttp\json_decode as guzzle_json_decode;

/**
 * @author Daniel Gimenes
 */
final class AuthorizationMiddleware
{
    private const LS_ENDPOINT_ACCESS_TOKEN = 'https://cloud.merchantos.com/oauth/access_token.php';

    /**
     * @var callable
     */
    private $nextHandler;

    /**
     * @var CredentialStorageInterface
     */
    private $credentialStorage;

    /**
     * @var ClientInterface
     */
    private $httpClient;

    /**
     * @var string
     */
    private $clientId;

    /**
     * @var string
     */
    private $clientSecret;

    /**
     * @param callable                   $nextHandler
     * @param CredentialStorageInterface $credentialStorage
     * @param ClientInterface            $httpClient
     * @param string                     $clientId
     * @param string                     $clientSecret
     */
    public function __construct(
        callable $nextHandler,
        CredentialStorageInterface $credentialStorage,
        ClientInterface $httpClient,
        string $clientId,
        string $clientSecret
    ) {
        $this->nextHandler       = $nextHandler;
        $this->credentialStorage = $credentialStorage;
        $this->httpClient        = $httpClient;
        $this->clientId          = $clientId;
        $this->clientSecret      = $clientSecret;
    }

    /**
     * Creates a callable that wraps the middleware with the next handler of @see HandlerStack
     *
     * @param CredentialStorageInterface $credentialStorage
     * @param ClientInterface            $httpClient
     * @param string                     $clientId
     * @param string                     $clientSecret
     *
     * @return callable
     */
    public static function wrapped(
        CredentialStorageInterface $credentialStorage,
        ClientInterface $httpClient,
        string $clientId,
        string $clientSecret
    ): callable {
        return function (callable $nextHandler) use ($credentialStorage, $httpClient, $clientId, $clientSecret) {
            return new self($nextHandler, $credentialStorage, $httpClient, $clientId, $clientSecret);
        };
    }

    /**
     * @param CommandInterface $command
     *
     * @return PromiseInterface
     */
    public function __invoke(CommandInterface $command): PromiseInterface
    {
        if (empty($command['referenceID'])) {
            return new RejectedPromise(UnauthorizedException::missingReferenceId());
        }

        $referenceID = $command['referenceID'];
        $credential  = $this->credentialStorage->get($referenceID);

        if (null === $credential) {
            return new RejectedPromise(UnauthorizedException::missingCredential($referenceID));
        }

        $command = $this->authorizeCommand($command, $credential);

        /** @var PromiseInterface $promise */
        $promise = ($this->nextHandler)($command);

        // If authentication fails, we refresh the token and try again
        return $promise->otherwise(function (CommandException $exception) use ($command, $credential) {
            $response = $exception->getResponse();

            // Forward non 401 errors
            if (null === $response || 401 !== $response->getStatusCode()) {
                throw $exception;
            }

            $credential = $this->refreshToken($credential);
            $command    = $this->authorizeCommand($command, $credential);

            // Try again
            return ($this->nextHandler)($command);
        });
    }

    /**
     * @param CommandInterface $command
     * @param Credential       $credential
     *
     * @return CommandInterface
     */
    private function authorizeCommand(CommandInterface $command, Credential $credential): CommandInterface
    {
        $command['accountID'] = $credential->getLightspeedAccountId();
        $command['@http']     = [
            'headers' => ['Authorization' => 'Bearer ' . $credential->getAccessToken()],
        ];

        return $command;
    }

    /**
     * @param Credential $credential
     *
     * @return Credential
     */
    private function refreshToken(Credential $credential): Credential
    {
        $refreshToken = $credential->getRefreshToken();

        try {
            $response = $this->httpClient->request('POST', self::LS_ENDPOINT_ACCESS_TOKEN, [
                'json' => [
                    'client_id'     => $this->clientId,
                    'client_secret' => $this->clientSecret,
                    'refresh_token' => $refreshToken,
                    'grant_type'    => 'refresh_token',
                ],
            ]);
        } catch (ClientException $exception) {
            throw UnauthorizedException::refreshTokenRejected($refreshToken, $exception);
        }

        $result     = guzzle_json_decode((string) $response->getBody(), true);
        $credential = $credential->withAccessToken($result['access_token']);

        $this->credentialStorage->save($credential);

        return $credential;
    }
}
