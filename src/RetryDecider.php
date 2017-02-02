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

use GuzzleHttp\Exception\ConnectException;
use GuzzleHttp\Exception\RequestException;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;

/**
 * @author Daniel Gimenes
 * @author Michaël Gallego
 */
class RetryDecider
{
    /**
     * The max number of retries we should do before failing
     *
     * @var int
     */
    private $maxRetries;

    /**
     * @param int $maxRetries
     */
    public function __construct(int $maxRetries)
    {
        $this->maxRetries = $maxRetries;
    }

    /**
     * Decides whether or not to retry a request
     *
     * @param int                    $retries
     * @param RequestInterface       $request
     * @param null|ResponseInterface $response
     * @param null|RequestException  $exception
     *
     * @return bool
     */
    public function __invoke(
        int $retries,
        RequestInterface $request,
        ResponseInterface $response = null,
        RequestException $exception = null
    ): bool {
        // Limit to the number of max retries
        if ($retries >= $this->maxRetries) {
            return false;
        }

        // Retry connection exceptions
        if ($exception instanceof ConnectException) {
            return true;
        }

        // Otherwise, retry when we're having a 429 response
        if (null !== $response && $response->getStatusCode() === 429) {
            return true;
        }

        return false;
    }
}
