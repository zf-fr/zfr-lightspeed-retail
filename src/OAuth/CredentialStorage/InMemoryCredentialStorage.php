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

namespace ZfrLightspeedRetail\OAuth\CredentialStorage;

use ZfrLightspeedRetail\OAuth\Credential;

/**
 * @author Daniel Gimenes
 */
final class InMemoryCredentialStorage implements CredentialStorageInterface
{
    /**
     * @var array
     */
    private $credentials;

    /**
     * @param array $credentials Array of credentials indexed by reference ID
     */
    public function __construct(array $credentials)
    {
        $this->credentials = $credentials;
    }

    /**
     * @param Credential $credential
     */
    public function save(Credential $credential)
    {
        $this->credentials[$credential->getReferenceId()] = $credential;
    }

    /**
     * @param string $referenceId
     *
     * @return null|Credential
     */
    public function get(string $referenceId): ?Credential
    {
        return $this->credentials[$referenceId] ?? null;
    }
}
