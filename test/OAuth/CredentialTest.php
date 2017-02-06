<?php

namespace ZfrLightspeedRetailTest\OAuth;

use PHPUnit\Framework\TestCase;
use ZfrLightspeedRetail\OAuth\Credential;

/**
 * @author Daniel Gimenes
 */
final class CredentialTest extends TestCase
{
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
