<?php

namespace Pterodactyl\Tests\Integration\Api\Client\Server\Files;

use Mockery\MockInterface;
use Illuminate\Http\Response;
use Pterodactyl\Models\Permission;
use Pterodactyl\Enum\ResourceLimit;
use Illuminate\Support\Facades\RateLimiter;
use Pterodactyl\Repositories\Wings\DaemonFileRepository;
use Pterodactyl\Tests\Integration\Api\Client\ClientApiIntegrationTestCase;

class CompressFilesThrottleTest extends ClientApiIntegrationTestCase
{
    protected function tearDown(): void
    {
        RateLimiter::clear(ResourceLimit::FileArchive->throttleKey());

        parent::tearDown();
    }

    /**
     * Compressing an archive blocks a Panel worker on a long-running Wings call, so the route
     * is throttled per-server to avoid exhausting the worker pool. Once the per-server budget is
     * spent the endpoint must respond with a 429 rather than dispatching another daemon request.
     */
    public function testCompressRequestsAreThrottledPerServer(): void
    {
        [$user, $server] = $this->generateTestAccount([Permission::ACTION_FILE_ARCHIVE]);

        $this->mock(DaemonFileRepository::class, function (MockInterface $mock) {
            $mock->shouldReceive('setServer->compressFiles')->andReturn([
                'name' => 'test.tar.gz',
                'mime' => 'application/gzip',
            ]);
        });

        $endpoint = $this->link($server, '/files/compress');
        $body = ['root' => '/', 'files' => ['test.txt']];

        // The FileArchive limit allows five attempts before the throttle trips.
        for ($i = 0; $i < 5; ++$i) {
            $this->actingAs($user)->postJson($endpoint, $body)->assertOk();
        }

        $this->actingAs($user)
            ->postJson($endpoint, $body)
            ->assertStatus(Response::HTTP_TOO_MANY_REQUESTS);
    }
}
