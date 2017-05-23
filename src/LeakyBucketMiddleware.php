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

use GuzzleHttp\HandlerStack;
use GuzzleHttp\Promise\PromiseInterface;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;

/**
 * @author Daniel Gimenes
 */
final class LeakyBucketMiddleware
{
    private const REQUIRED_UNITS = [
        'GET'    => 1,
        'POST'   => 10,
        'PUT'    => 10,
        'DELETE' => 10,
    ];

    /**
     * @var callable
     */
    private $nextHandler;

    /**
     * @var null|ResponseInterface
     */
    private $lastResponse;

    /**
     * @param callable $nextHandler
     */
    public function __construct(callable $nextHandler)
    {
        $this->nextHandler = $nextHandler;
    }

    /**
     * Creates a callable that wraps the middleware with the next handler of HandlerStack
     *
     * @see HandlerStack
     *
     * @return callable
     */
    public static function wrapped(): callable
    {
        return function (callable $nextHandler): self {
            return new self($nextHandler);
        };
    }

    /**
     * @param RequestInterface $request
     * @param array            $options
     *
     * @return PromiseInterface
     */
    public function __invoke(RequestInterface $request, array $options): PromiseInterface
    {
        if (null === $this->lastResponse || ! $this->lastResponse->hasHeader('X-LS-API-Bucket-Level')) {
            return $this->execute($request, $options);
        }

        $headerParts    = explode('/', $this->lastResponse->getHeaderLine('X-LS-API-Bucket-Level'));
        $usedUnits      = (float) $headerParts[0];
        $bucketSize     = (int) $headerParts[1];
        $availableUnits = $bucketSize - $usedUnits;
        $requiredUnits  = self::REQUIRED_UNITS[strtoupper($request->getMethod())];

        if ($requiredUnits <= $availableUnits) {
            return $this->execute($request, $options);
        }

        $dripRate    = $bucketSize / 60;
        $unitsToWait = $requiredUnits - $availableUnits;

        // Time to wait (in ms) until we have enough units to execute this request without throttling
        $options['delay'] = (int) ceil($unitsToWait / $dripRate * 1000);

        return $this->execute($request, $options);
    }

    /**
     * @param RequestInterface $request
     * @param array            $options
     *
     * @return PromiseInterface
     */
    private function execute(RequestInterface $request, array $options): PromiseInterface
    {
        // Execute request and store the response for later usage
        return ($this->nextHandler)($request, $options)->then(function (ResponseInterface $response) {
            $this->lastResponse = $response;

            return $response;
        });
    }
}
