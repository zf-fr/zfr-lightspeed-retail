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

namespace ZfrLightspeedRetailTest\OAuth;

use PHPUnit\Framework\TestCase;
use ZfrLightspeedRetail\OAuth\Credential;

/**
 * @author Daniel Gimenes
 */
final class CredentialTest extends TestCase
{
    public function testIsArraySerializable()
    {
        $data = [
            'reference_id'          => 'test',
            'lightspeed_account_id' => 1234567890,
            'access_token'          => 'foo',
            'refresh_token'         => 'bar',
        ];

        $this->assertSame($data, Credential::fromArray($data)->toArray());
    }

    public function testCreatesAnotherWithDifferentAccessToken()
    {
        $a = Credential::fromArray([
            'reference_id'          => 'test',
            'lightspeed_account_id' => 1234567890,
            'access_token'          => 'foo',
            'refresh_token'         => 'bar',
        ]);

        $b = $a->withAccessToken('baz');

        $this->assertSame('foo', $a->getAccessToken());
        $this->assertSame('baz', $b->getAccessToken());
    }
}
