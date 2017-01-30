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

/**
 * @author Daniel Gimenes
 */
final class Credential
{
    /**
     * @var string
     */
    private $referenceId;

    /**
     * @var int
     */
    private $lightspeedAccountId;

    /**
     * @var string
     */
    private $accessToken;

    /**
     * @var string
     */
    private $refreshToken;

    /**
     * @param string $referenceId
     * @param int    $lightspeedAccountId
     * @param string $accessToken
     * @param string $refreshToken
     */
    public function __construct(
        string $referenceId,
        int $lightspeedAccountId,
        string $accessToken,
        string $refreshToken
    ) {
        $this->referenceId         = $referenceId;
        $this->lightspeedAccountId = $lightspeedAccountId;
        $this->accessToken         = $accessToken;
        $this->refreshToken        = $refreshToken;
    }

    /**
     * @return string
     */
    public function getReferenceId(): string
    {
        return $this->referenceId;
    }

    /**
     * @return int
     */
    public function getLightspeedAccountId(): int
    {
        return $this->lightspeedAccountId;
    }

    /**
     * @return string
     */
    public function getAccessToken(): string
    {
        return $this->accessToken;
    }

    /**
     * @return string
     */
    public function getRefreshToken(): string
    {
        return $this->refreshToken;
    }

    /**
     * @param string $accessToken
     * @param string $refreshToken
     *
     * @return Credential
     */
    public function withRefreshedTokens(string $accessToken, string $refreshToken): self
    {
        $clone = clone $this;

        $clone->accessToken  = $accessToken;
        $clone->refreshToken = $refreshToken;

        return $clone;
    }
}
