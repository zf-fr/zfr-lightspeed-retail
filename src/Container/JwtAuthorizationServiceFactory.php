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

namespace ZfrLightspeedRetail\Container;

use GuzzleHttp\Client;
use Interop\Container\ContainerInterface;
use Lcobucci\JWT\Signer\Hmac\Sha256;
use OutOfBoundsException;
use ZfrLightspeedRetail\OAuth\AuthorizationServiceInterface;
use ZfrLightspeedRetail\OAuth\CredentialStorage\CredentialStorageInterface;
use ZfrLightspeedRetail\OAuth\JwtAuthorizationService;

/**
 * @author Daniel Gimenes
 */
final class JwtAuthorizationServiceFactory
{
    /**
     * @param ContainerInterface $container
     *
     * @return AuthorizationServiceInterface
     */
    public function __invoke(ContainerInterface $container): AuthorizationServiceInterface
    {
        $credentialStorage = $container->get(CredentialStorageInterface::class);
        $config            = $container->get('config') ?? [];
        $config            = $config['zfr_lightspeed_retail'] ?? [];

        if (empty($config['client_id']) || empty($config['client_secret'])) {
            throw new OutOfBoundsException(
                'Missing "client_id" and "client_secret" config for ZfrLightspeedRetail'
            );
        }

        return new JwtAuthorizationService(
            $credentialStorage,
            new Client(),
            new Sha256(),
            $config['client_id'],
            $config['client_secret']
        );
    }
}
