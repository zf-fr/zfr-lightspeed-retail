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

use GuzzleHttp\Exception\ClientException;
use GuzzleHttp\Exception\ConnectException;
use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\Response;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use Traversable;
use ZfrLightspeedRetail\RetryStrategy;

/**
 * @author Daniel Gimenes
 */
final class RetryStrategyTest extends TestCase
{
    /**
     * @dataProvider provideRetries
     *
     * @param bool                   $shouldRetry
     * @param int                    $retries
     * @param RequestInterface       $request
     * @param ResponseInterface|null $response
     * @param RequestException|null  $exception
     */
    public function testDecides(
        bool $shouldRetry,
        int $retries,
        RequestInterface $request,
        ResponseInterface $response = null,
        RequestException $exception = null
    ) {
        $this->assertSame(
            $shouldRetry,
            (new RetryStrategy(10))->decide($retries, $request, $response, $exception)
        );
    }

    public function provideRetries(): Traversable
    {
        $request = new Request('GET', '/something');

        // Null Response, no exception
        yield [false, 1, $request];

        // 429 Response
        yield [true, 1, $request, new Response(429)];
        yield [false, 10, $request, new Response(429)];

        // Other response
        yield [false, 10, $request, new Response(200)];

        // ConnectionException
        yield [true, 1, $request, null, new ConnectException('Boom!', $request)];
        yield [false, 10, $request, null, new ConnectException('Boom!', $request)];

        // Other exception
        yield [false, 1, $request, null, new ClientException('Boom!', $request)];
    }

    public function testZeroDelayIfNoResponse()
    {
        $this->assertSame(0, (new RetryStrategy(10))->delay(1));
    }

    public function testZeroDelayIfResponseWithoutHeader()
    {
        $this->assertSame(0, (new RetryStrategy(10))->delay(1, new Response()));
    }

    public function testZeroDelayIfCantDetermineTheBucketSize()
    {
        $this->assertSame(0, (new RetryStrategy(10))->delay(
            1,
            (new Response())->withHeader('X-LS-API-Bucket-Level', 'Invalid')
        ));
    }

    public function testCalculatesDelayBasedOnResponseHeader()
    {
        // We used 55 of 60, and we need 10, so we should wait for 5 more units.
        // Since the drip rate is 1 unit per second, we should wait 5 seconds
        $this->assertSame(5000, (new RetryStrategy(10))->delay(
            1,
            (new Response())->withHeader('X-LS-API-Bucket-Level', '55/60')
        ));
    }
}
