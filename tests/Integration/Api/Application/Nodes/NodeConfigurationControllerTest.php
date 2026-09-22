<?php

namespace Pterodactyl\Tests\Integration\Api\Application\Nodes;

use Pterodactyl\Models\Node;
use Pterodactyl\Models\Location;
use Pterodactyl\Services\Acl\Api\AdminAcl;
use Pterodactyl\Tests\Integration\Api\Application\ApplicationApiIntegrationTestCase;

class NodeConfigurationControllerTest extends ApplicationApiIntegrationTestCase
{
    public function testConfigurationKeyCanGetNodeConfiguration(): void
    {
        $node = Node::factory()->for(Location::factory())->create();
        $this->createNewDefaultApiKey($this->getApiUser(), [
            'node_id' => $node->id,
            'r_nodes' => AdminAcl::READ_CONFIGURATION,
        ]);

        $response = $this->getJson('/api/application/nodes/' . $node->id . '/configuration');

        $response->assertOk();
        $response->assertJsonPath('uuid', $node->uuid);
        $response->assertJsonPath('token_id', $node->daemon_token_id);
        $this->assertSame(decrypt($node->daemon_token), $response->json('token'));
    }

    public function testConfigurationKeyCannotGetOtherNodeConfiguration(): void
    {
        $node = Node::factory()->for(Location::factory())->create();
        $otherNode = Node::factory()->for(Location::factory())->create();
        $this->createNewDefaultApiKey($this->getApiUser(), [
            'node_id' => $node->id,
            'r_nodes' => AdminAcl::READ_CONFIGURATION,
        ]);

        $this->assertAccessDeniedJson($this->getJson('/api/application/nodes/' . $otherNode->id . '/configuration'));
    }

    public function testWriteKeyCanGetNodeConfiguration(): void
    {
        $this->createNewDefaultApiKey($this->getApiUser(), ['r_nodes' => AdminAcl::WRITE]);

        $node = Node::factory()->for(Location::factory())->create();

        $this->getJson('/api/application/nodes/' . $node->id . '/configuration')->assertOk();
    }

    public function testReadOnlyNodeKeyCannotGetNodeConfiguration(): void
    {
        $this->createNewDefaultApiKey($this->getApiUser(), ['r_nodes' => AdminAcl::READ]);

        $node = Node::factory()->for(Location::factory())->create();

        $this->assertAccessDeniedJson($this->getJson('/api/application/nodes/' . $node->id . '/configuration'));
    }

    public function testConfigurationKeyCannotUpdateNode(): void
    {
        $node = Node::factory()->for(Location::factory())->create();
        $this->createNewDefaultApiKey($this->getApiUser(), [
            'node_id' => $node->id,
            'r_nodes' => AdminAcl::READ_CONFIGURATION,
        ]);

        $this->assertAccessDeniedJson($this->patchJson(route('api.application.nodes.update', ['node' => $node])));
    }
}
