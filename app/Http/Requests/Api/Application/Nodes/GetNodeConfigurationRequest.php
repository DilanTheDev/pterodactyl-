<?php

namespace Pterodactyl\Http\Requests\Api\Application\Nodes;

use Pterodactyl\Models\Node;
use Pterodactyl\Models\ApiKey;
use Pterodactyl\Services\Acl\Api\AdminAcl;

class GetNodeConfigurationRequest extends GetNodesRequest
{
    protected int $permission = AdminAcl::WRITE;

    public function authorize(): bool
    {
        $token = $this->user()->currentAccessToken();
        if ($token instanceof ApiKey && AdminAcl::check($token, $this->resource, AdminAcl::READ_CONFIGURATION)) {
            $node = $this->parameter('node', Node::class);

            if ($token->node_id === $node->id) {
                return true;
            }
        }

        return parent::authorize();
    }
}
