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
use Traversable;
use ZfrLightspeedRetail\Deserializer;
use function GuzzleHttp\Psr7\stream_for;

/**
 * @author Daniel Gimenes
 */
final class DeserializerTest extends TestCase
{
    public function testUnwrapsResults()
    {
        $description  = $this->createDescription(true);
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
        $description  = $this->createDescription(true);
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
        $description  = $this->createDescription(true);
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
     * @dataProvider provideCollections
     *
     * @param array $responseData
     * @param array $expectedResult
     */
    public function testNormalizesEmptyCollections(array $responseData, array $expectedResult)
    {
        $description  = $this->createDescription(true, true);
        $deserializer = new Deserializer(
            new GuzzleDeserializer($description, true),
            $description
        );

        $result = $deserializer(
            new Response(200, [], stream_for(json_encode($responseData))),
            new Request('GET', '/something'),
            new Command('GetSomething')
        );

        $this->assertSame($expectedResult, $result->toArray());
    }

    public function provideCollections(): Traversable
    {
        yield 'Empty collection' => [
            [
                'Something' => []
            ],
            [
            ],
        ];

        yield 'Collection with a single item' => [
            [
                'Something' => [
                    'foo' => 'bar'
                ]
            ],
            [
                ['foo' => 'bar'],
            ],
        ];

        yield 'Collection with multiple items' => [
            [
                'Something' => [
                    ['foo' => 'bar'],
                    ['baz' => 'bat'],
                ]
            ],
            [
                ['foo' => 'bar'],
                ['baz' => 'bat'],
            ],
        ];
    }

    /**
     * @param bool $withRootKey
     * @param bool $isCollection
     *
     * @return Description
     */
    private function createDescription(bool $withRootKey = false, bool $isCollection = false): Description
    {
        $config = [
            'operations' => [
                'GetSomething' => [
                    'responseModel' => 'GenericModel',
                ],
            ],
            'models' => [
                'GenericModel' => [
                    'type'                 => 'object',
                    'additionalProperties' => ['location' => 'json'],
                ],
            ],
        ];

        if ($withRootKey) {
            $config['operations']['GetSomething']['data']['root_key'] = 'Something';
        }

        if ($isCollection) {
            $config['operations']['GetSomething']['data']['is_collection'] = true;
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
