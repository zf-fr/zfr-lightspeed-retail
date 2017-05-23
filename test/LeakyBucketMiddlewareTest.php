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

namespace ZfrLightspeedRetailTest;

use GuzzleHttp\Promise\Promise;
use GuzzleHttp\Promise\PromiseInterface;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\Response;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use Traversable;
use ZfrLightspeedRetail\LeakyBucketMiddleware;

/**
 * @author Daniel Gimenes
 */
final class LeakyBucketMiddlewareTest extends TestCase
{
    public function testDoesNotDelayIfUnknownBucketLevel()
    {
        $nextHandler = function (RequestInterface $request, array $options): PromiseInterface {
            $this->assertArrayNotHasKey('delay', $options);

            $promise = new Promise();

            $promise->resolve(new Response());

            return $promise;
        };

        $request    = new Request('GET', 'http://localhost');
        $middleware = new LeakyBucketMiddleware($nextHandler);

        // Initial request should not be throttled because there's no last response
        $response = $middleware($request, [])->wait();

        $this->assertInstanceOf(ResponseInterface::class, $response);

        // Second request should not be throttled because the last response does not have bucket level header
        $response = $middleware($request, [])->wait();

        $this->assertInstanceOf(ResponseInterface::class, $response);
    }

    /**
     * @dataProvider provideRequests
     *
     * @param string   $requestMethod
     * @param string   $bucketLevel
     * @param null|int $expectedDelay
     */
    public function testAvoidsThrottling(
        string $requestMethod,
        string $bucketLevel,
        int $expectedDelay = null
    ) {
        $request          = new Request($requestMethod, 'http://localhost');
        $delayedTime      = null;
        $expectedResponse = null;

        $nextHandler = function (RequestInterface $request, array $options) use ($bucketLevel, &$delayedTime) {
            $delayedTime = $options['delay'] ?? null;
            $promise     = new Promise();

            $promise->resolve(new Response(200, ['X-LS-API-Bucket-Level' => $bucketLevel]));

            return $promise;
        };

        $middleware = new LeakyBucketMiddleware($nextHandler);

        // Make initial request so that it stores the last response internally
        $middleware($request, [])->wait();

        // Make the second request, which should be delayed
        $response = $middleware($request, [])->wait();

        $this->assertSame($expectedDelay, $delayedTime);
        $this->assertInstanceOf(ResponseInterface::class, $response);
    }

    /**
     * @return Traversable
     */
    public function provideRequests(): Traversable
    {
        yield ['GET',  '59/60', null];
        yield ['POST', '50/60', null];
        yield ['GET',  '60/60', 1000];
        yield ['GET',  '61/60', 2000];
        yield ['POST', '55/60', 5000];
    }
}
