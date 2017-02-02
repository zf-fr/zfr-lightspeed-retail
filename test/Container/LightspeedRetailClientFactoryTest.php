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

namespace ZfrLightspeedRetailTest\Container;

use Interop\Container\ContainerInterface;
use PHPUnit\Framework\TestCase;
use ZfrLightspeedRetail\Container\LightspeedRetailClientFactory;
use ZfrLightspeedRetail\OAuth\CredentialStorage\CredentialStorageInterface;

/**
 * @author Daniel Gimenes
 */
final class LightspeedRetailClientFactoryTest extends TestCase
{
    public function testInjectsDependencies()
    {
        $container = $this->prophesize(ContainerInterface::class);
        $storage   = $this->prophesize(CredentialStorageInterface::class);
        $config    = [
            'zfr_lightspeed_retail' => [
                'client_id'     => 'foo',
                'client_secret' => 'bar',
            ],
        ];

        $container->get(CredentialStorageInterface::class)->shouldBeCalled()->willReturn($storage->reveal());
        $container->get('config')->shouldBeCalled()->willReturn($config);

        (new LightspeedRetailClientFactory())($container->reveal());
    }
}
