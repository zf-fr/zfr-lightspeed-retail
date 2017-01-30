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
use GuzzleHttp\Exception\ClientException;
use GuzzleHttp\Psr7\Uri;
use InvalidArgumentException;
use Lcobucci\JWT\Builder;
use Lcobucci\JWT\Parser;
use Lcobucci\JWT\Signer;
use Lcobucci\JWT\Token;
use Lcobucci\JWT\ValidationData;
use Psr\Http\Message\UriInterface;
use RuntimeException;
use ZfrLightspeedRetail\Exception\InvalidStateException;
use ZfrLightspeedRetail\Exception\MissingRequiredScopeException;
use ZfrLightspeedRetail\Exception\UnauthorizedException;
use ZfrLightspeedRetail\OAuth\CredentialStorage\CredentialStorageInterface;
use function GuzzleHttp\json_decode as guzzle_json_decode;

/**
 * @author Daniel Gimenes
 */
final class JwtAuthorizationService implements AuthorizationServiceInterface
{
    // @codingStandardsIgnoreStart
    private const LS_ENDPOINT_AUTHORIZE    = 'https://cloud.merchantos.com/oauth/authorize.php?response_type=code&client_id=%s&scope=%s&state=%s';
    private const LS_ENDPOINT_ACCESS_TOKEN = 'https://cloud.merchantos.com/oauth/access_token.php';
    private const LS_ENDPOINT_ACCOUNT      = 'https://api.merchantos.com/API/Account.json';
    // @codingStandardsIgnoreEnd

    /**
     * @var CredentialStorageInterface
     */
    private $credentialStorage;

    /**
     * @var ClientInterface
     */
    private $httpClient;

    /**
     * @var Signer
     */
    private $jwtSigner;

    /**
     * @var string
     */
    private $clientId;

    /**
     * @var string
     */
    private $clientSecret;

    /**
     * @param CredentialStorageInterface $credentialStorage
     * @param ClientInterface            $httpClient
     * @param Signer                     $jwtSigner
     * @param string                     $clientId
     * @param string                     $clientSecret
     */
    public function __construct(
        CredentialStorageInterface $credentialStorage,
        ClientInterface $httpClient,
        Signer $jwtSigner,
        string $clientId,
        string $clientSecret
    ) {
        $this->credentialStorage = $credentialStorage;
        $this->httpClient        = $httpClient;
        $this->jwtSigner         = $jwtSigner;
        $this->clientId          = $clientId;
        $this->clientSecret      = $clientSecret;
    }

    /**
     * Builds an authorization URL that identifies the internal account ID and the requested scope.
     *
     * @param string   $referenceId    Internal account ID (identifies the account in your application)
     * @param string[] $requestedScope Scope requested by your application
     *
     * @return UriInterface
     */
    public function buildAuthorizationUrl(string $referenceId, array $requestedScope): UriInterface
    {
        $state = $this->buildState($referenceId, $requestedScope);

        return new Uri(sprintf(
            self::LS_ENDPOINT_AUTHORIZE,
            $this->clientId,
            implode('+', $requestedScope),
            $state
        ));
    }

    /**
     * 1 - Parses and validates the provided state
     * 2 - Exchanges the given authorization code by a token pair (access token + refresh token)
     * 3 - Validates if the requested scope is satisfied by granted scope
     * 4 - Fetches the Lightspeed Account ID (this is required for all API calls)
     * 5 - Stores Lightspeed Account ID and tokens in credential storage
     *
     * @param string $authorizationCode Temporary authorization code received from authorization server
     * @param string $state             State string received from authorization server
     *
     * @throws InvalidStateException         If the provided state is invalid or expired
     * @throws MissingRequiredScopeException If the granted scope does not satisfy the scope required by your app.
     * @throws UnauthorizedException         If Lightspeed Retail authorization server rejects the provided auth code.
     */
    public function processCallback(string $authorizationCode, string $state): void
    {
        $stateToken = $this->parseState($state);
        $result     = $this->exchangeAuthorizationCode($authorizationCode);

        $this->guardRequiredScope($stateToken, $result['scope']);

        $referenceId  = $stateToken->getClaim('uid');
        $accessToken  = $result['access_token'];
        $refreshToken = $result['refresh_token'];
        $lsAccountId  = $this->fetchLightspeedAccountId($accessToken);

        $this->credentialStorage->save(new Credential($referenceId, $lsAccountId, $accessToken, $refreshToken));
    }

    /**
     * @param string $referenceId
     * @param array  $requestedScope
     *
     * @return Token
     */
    private function buildState(string $referenceId, array $requestedScope): Token
    {
        return (new Builder())
            ->setIssuedAt(time())
            ->setExpiration(time() + 60 * 10)
            ->set('uid', $referenceId)
            ->set('scope', $requestedScope)
            ->sign($this->jwtSigner, $this->clientSecret)
            ->getToken();
    }

    /**
     * @param string $state
     *
     * @return Token
     * @throws InvalidStateException
     */
    private function parseState(string $state): Token
    {
        try {
            $token = (new Parser())->parse($state);
        } catch (InvalidArgumentException | RuntimeException $exception) {
            throw InvalidStateException::fromInvalidState($state, $exception);
        }

        if (! $token->validate(new ValidationData()) || ! $token->verify($this->jwtSigner, $this->clientSecret)) {
            throw InvalidStateException::fromInvalidState($state);
        }

        return $token;
    }

    /**
     * @param string $authorizationCode
     *
     * @return array
     * @throws UnauthorizedException
     */
    private function exchangeAuthorizationCode(string $authorizationCode): array
    {
        try {
            $response = $this->httpClient->request('POST', self::LS_ENDPOINT_ACCESS_TOKEN, [
                'json' => [
                    'client_id'     => $this->clientId,
                    'client_secret' => $this->clientSecret,
                    'code'          => $authorizationCode,
                    'grant_type'    => 'authorization_code',
                ],
            ]);
        } catch (ClientException $exception) {
            throw UnauthorizedException::authorizationCodeRejected($authorizationCode);
        }

        return guzzle_json_decode((string) $response->getBody(), true);
    }

    /**
     * @param Token  $stateToken
     * @param string $grantedScope
     *
     * @throws MissingRequiredScopeException
     */
    private function guardRequiredScope(Token $stateToken, string $grantedScope): void
    {
        $requestedScope = $stateToken->getClaim('scope');
        $grantedScope   = explode(' ', $grantedScope);
        $missingScope   = array_diff($requestedScope, $grantedScope);

        if (! empty($missingScope)) {
            throw MissingRequiredScopeException::fromMissingScope($missingScope);
        }
    }

    /**
     * @param string $accessToken
     *
     * @return int
     */
    private function fetchLightspeedAccountId(string $accessToken): int
    {
        $response = $this->httpClient->request('GET', self::LS_ENDPOINT_ACCOUNT, [
            'headers' => ['Authorization' => sprintf('Bearer %s', $accessToken)],
        ]);

        $result = guzzle_json_decode((string) $response->getBody(), true);

        return $result['Account']['accountID'];
    }
}
