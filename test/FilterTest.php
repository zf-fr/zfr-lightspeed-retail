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

use PHPUnit\Framework\TestCase;
use ZfrLightspeedRetail\Filter;

/**
 * @author Daniel Gimenes
 */
final class FilterTest extends TestCase
{
    public function testNormalizesEmptyCollection()
    {
        $this->assertSame([], Filter::normalizeCollection([]));
    }

    public function testNormalizesCollectionWithASingleItem()
    {
        $this->assertSame([
            ['foo' => 'bar'],
        ], Filter::normalizeCollection([
            'foo' => 'bar',
        ]));
    }

    public function testNormalizesCollectionWithMultipleItems()
    {
        $this->assertSame([
            ['foo' => 'bar'],
            ['baz' => 'bat'],
        ], Filter::normalizeCollection([
            ['foo' => 'bar'],
            ['baz' => 'bat'],
        ]));
    }
}
