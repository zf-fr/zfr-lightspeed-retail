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

namespace ZfrLightspeedRetail;

use GuzzleHttp\Client;
use GuzzleHttp\Command\CommandInterface;
use GuzzleHttp\Command\Guzzle\Description;
use GuzzleHttp\Command\Guzzle\Deserializer as GuzzleDeserializer;
use GuzzleHttp\Command\Guzzle\GuzzleClient;
use GuzzleHttp\Command\ResultInterface;
use GuzzleHttp\Command\ServiceClientInterface;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Middleware;
use OutOfBoundsException;
use Psr\Http\Message\ResponseInterface;
use Traversable;
use ZfrLightspeedRetail\OAuth\AuthorizationMiddleware;
use ZfrLightspeedRetail\OAuth\CredentialStorage\CredentialStorageInterface;

/**
 * HTTP Client used to interact with the Lightspeed Retail API
 *
 * @author Daniel Gimenes
 * @author Michaël Gallego
 *
 * CUSTOMER RELATED METHODS:
 *
 * @method ResultInterface getCustomers(array $args = [])
 * @method ResultInterface getCustomer(array $args = [])
 * @method ResultInterface createCustomer(array $args = [])
 * @method ResultInterface updateCustomer(array $args = [])
 *
 * ITEM RELATED METHODS:
 *
 * @method ResultInterface getItems(array $args = [])
 * @method ResultInterface createItem(array $args = [])
 * @method ResultInterface updateItem(array $args = [])
 *
 * SALE RELATED METHODS:
 *
 * @method ResultInterface getSales(array $args = [])
 *
 * ITERATOR METHODS:
 *
 * @method Traversable getCustomersIterator(array $args = [])
 * @method Traversable getItemsIterator(array $args = [])
 * @method Traversable getSalesIterator(array $args = [])
 */
class LightspeedRetailClient
{
    /**
     * @var ServiceClientInterface
     */
    private $serviceClient;

    /**
     * @param ServiceClientInterface $serviceClient
     */
    public function __construct(ServiceClientInterface $serviceClient)
    {
        $this->serviceClient = $serviceClient;
    }

    /**
     * @param CredentialStorageInterface $credentialStorage
     * @param array                      $config
     *
     * @return LightspeedRetailClient
     */
    public static function fromDefaults(CredentialStorageInterface $credentialStorage, array $config): self
    {
        if (empty($config['client_id']) || empty($config['client_secret'])) {
            throw new OutOfBoundsException(
                'Missing "client_id" and "client_secret" config for ZfrLightspeedRetail'
            );
        }

        $handlerStack = HandlerStack::create();

        // Push middleware to retry requests when decided by RetryDecider
        $handlerStack->push(Middleware::retry(
            new RetryDecider($config['max_retries'] ?? 10)
        ));

        $httpClient   = new Client(['handler' => $handlerStack]);
        $description  = new Description(require __DIR__ . '/ServiceDescription/Lightspeed-Retail-2016.25.php');
        $deserializer = new Deserializer(new GuzzleDeserializer($description, true), $description);
        $clientConfig = [];

        // If a default reference ID is provided in config, we add it as default command param
        if (! empty($config['reference_id'])) {
            $clientConfig['defaults']['referenceID'] = $config['reference_id'];
        }

        $serviceClient = new GuzzleClient($httpClient, $description, null, $deserializer, null, $clientConfig);

        // Push middleware to authorize requests
        $serviceClient->getHandlerStack()->push(AuthorizationMiddleware::wrapped(
            $credentialStorage,
            $httpClient,
            $config['client_id'],
            $config['client_secret']
        ));

        return new self($serviceClient);
    }

    /**
     * Directly call a specific endpoint by creating the command and executing it
     *
     * Using __call magic methods is equivalent to creating and executing a single command.
     * It also supports using optimized iterator requests by adding "Iterator" suffix to the command
     *
     * @param string $method
     * @param array  $args
     *
     * @return ResponseInterface|ResultInterface|Traversable
     */
    public function __call(string $method, array $args = [])
    {
        $params = $args[0] ?? [];

        // Allow magic method calls for iterators
        // (e.g. $client->getSalesIterator($internalAccountId, $params))
        if (substr($method, -8) === 'Iterator') {
            $command = $this->serviceClient->getCommand(substr($method, 0, -8), $params);

            return $this->iterateResources($command);
        }

        return $this->serviceClient->$method($params);
    }

    /**
     * @param CommandInterface $command
     *
     * @return Traversable
     */
    private function iterateResources(CommandInterface $command): Traversable
    {
        // When using the iterator, we force the maximum number of items per page to 100,
        // and we init the offset to 0 to start from start
        $command['limit']  = 100;
        $command['offset'] = 0;

        do {
            $result = $this->serviceClient->execute($command);

            foreach ($result as $item) {
                yield $item;
            }

            // Create command to next page
            $command           = clone $command;
            $command['offset'] += 100;
        } while (count($result) >= 100);
    }
}
