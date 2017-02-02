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

use GuzzleHttp\Command\Command;
use GuzzleHttp\Command\Guzzle\Description;
use GuzzleHttp\Command\Guzzle\Deserializer as GuzzleDeserializer;
use GuzzleHttp\Command\Result;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\Response;
use PHPUnit\Framework\TestCase;
use ZfrLightspeedRetail\Deserializer;
use function GuzzleHttp\Psr7\stream_for;

/**
 * @author Daniel Gimenes
 */
final class DeserializerTest extends TestCase
{
    public function testUnwrapsResults()
    {
        $description  = $this->createDescription();
        $deserializer = new Deserializer(
            new GuzzleDeserializer($description, true),
            $description
        );

        $result = $this->deserialize($deserializer, [
            'Something' => [
                'foo' => 'bar',
            ],
        ]);

        $this->assertEquals(new Result(['foo' => 'bar']), $result);
    }

    public function testDoesNotUnwrapIfNoRootKey()
    {
        $description  = $this->createDescription(false);
        $deserializer = new Deserializer(
            new GuzzleDeserializer($description, true),
            $description
        );

        $result = $deserializer(
            new Response(200, [], stream_for(json_encode([
                'Something' => [
                    'foo' => 'bar',
                ],
            ]))),
            new Request('GET', '/something'),
            new Command('GetSomething')
        );

        $this->assertEquals(new Result(['Something' => ['foo' => 'bar']]), $result);
    }

    public function testReturnsEmptyResultIfResponseDoesNotContainRootKey()
    {
        $description  = $this->createDescription();
        $deserializer = new Deserializer(
            new GuzzleDeserializer($description, true),
            $description
        );

        $result = $this->deserialize($deserializer, [
            'AnotherThing' => [
                'foo' => 'bar',
            ],
        ]);

        $this->assertEquals(new Result(), $result);
    }

    public function testReturnsResponseIfNoResultAvailable()
    {
        $description  = $this->createDescription();
        $deserializer = new Deserializer(
            // When $process = false it does not convert response to result
            new GuzzleDeserializer($description, false),
            $description
        );

        $response = new Response(200, [], stream_for(json_encode([
            'Something' => [
                'foo' => 'bar',
            ],
        ])));

        $result = $deserializer(
            $response,
            new Request('GET', '/something'),
            new Command('GetSomething')
        );

        $this->assertSame($response, $result);
    }

    /**
     * @param bool $withRootKey
     *
     * @return Description
     */
    private function createDescription(bool $withRootKey = true): Description
    {
        $config = [
            'operations' => [
                'GetSomething' => [
                    'responseModel' => 'GenericModel',
                ],
            ],
            'models'     => [
                'GenericModel' => [
                    'type'                 => 'object',
                    'additionalProperties' => ['location' => 'json'],
                ],
            ],
        ];

        if ($withRootKey) {
            $config['operations']['GetSomething']['data'] = ['root_key' => 'Something'];
        }

        return new Description($config);
    }

    /**
     * @param $deserializer
     * @param $payload
     *
     * @return mixed
     */
    private function deserialize($deserializer, $payload)
    {
        $result = $deserializer(
            new Response(200, [], stream_for(json_encode($payload))),
            new Request('GET', '/something'),
            new Command('GetSomething')
        );

        return $result;
    }
}
