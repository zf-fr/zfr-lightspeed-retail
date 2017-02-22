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

use GuzzleHttp\Command\CommandInterface;
use GuzzleHttp\Command\Guzzle\Description;
use GuzzleHttp\Command\Guzzle\Deserializer as GuzzleDeserializer;
use GuzzleHttp\Command\Result;
use GuzzleHttp\Command\ResultInterface;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;

/**
 * @author Daniel Gimenes
 */
final class Deserializer
{
    /**
     * @var GuzzleDeserializer
     */
    private $guzzleDeserializer;

    /**
     * @var Description
     */
    private $serviceDescription;

    /**
     * @param GuzzleDeserializer $guzzleDeserializer
     * @param Description        $serviceDescription
     */
    public function __construct(GuzzleDeserializer $guzzleDeserializer, Description $serviceDescription)
    {
        $this->guzzleDeserializer = $guzzleDeserializer;
        $this->serviceDescription = $serviceDescription;
    }

    /**
     * @param ResponseInterface $response
     * @param RequestInterface  $request
     * @param CommandInterface  $command
     *
     * @return ResultInterface|ResponseInterface
     */
    public function __invoke(ResponseInterface $response, RequestInterface $request, CommandInterface $command)
    {
        $result = ($this->guzzleDeserializer)($response, $request, $command);

        if (! $result instanceof ResultInterface) {
            return $result;
        }

        $operation    = $this->serviceDescription->getOperation($command->getName());
        $rootKey      = $operation->getData('root_key');
        $isCollection = $operation->getData('is_collection');

        // In Lightspeed Retail API, all responses wrap the data by the resource name.
        // For instance, using the customers endpoint will wrap the data by the "Customer" key.
        // This is a bit inconvenient to use in userland. As a consequence, we always "unwrap" the result.
        if (null !== $rootKey) {
            $result = new Result($result[$rootKey] ?? []);
        }

        // When a collection contains a single item in Lightspeed,
        // they return the item directly instead of an array containing a single item.
        // In these cases we "wrap" the item in an array to make sure that collections are always arrays of items.
        if (true === $isCollection) {
            $result = new Result(Filter::normalizeCollection($result->toArray()));
        }

        return $result;
    }
}
