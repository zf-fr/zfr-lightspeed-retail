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

use Psr\Http\Message\UriInterface;
use ZfrLightspeedRetail\Exception\InvalidStateException;
use ZfrLightspeedRetail\Exception\MissingRequiredScopeException;
use ZfrLightspeedRetail\Exception\UnauthorizedException;

/**
 * @author Daniel Gimenes
 */
interface AuthorizationServiceInterface
{
    /**
     * Builds an authorization URL that identifies the internal account ID and the requested scope.
     *
     * @param string   $referenceId    Internal account ID (identifies the account in your application)
     * @param string[] $requestedScope Scope requested by your application
     *
     * @return UriInterface
     */
    public function buildAuthorizationUrl(string $referenceId, array $requestedScope): UriInterface;

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
     * @throws UnauthorizedException         If Lightspeed Retail authorization server rejects the provided authorization code.
     */
    public function processCallback(string $authorizationCode, string $state): void;
}
