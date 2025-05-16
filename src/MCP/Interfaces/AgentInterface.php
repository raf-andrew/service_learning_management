<?php

namespace App\MCP\Interfaces;

interface AgentInterface
{
    /**
     * Get the agent's unique identifier
     */
    public function getId(): string;

    /**
     * Get the agent's category (development, qa, operations, security)
     */
    public function getCategory(): string;

    /**
     * Get the agent's capabilities
     */
    public function getCapabilities(): array;

    /**
     * Check if the agent has a specific capability
     */
    public function hasCapability(string $capability): bool;

    /**
     * Execute an action with the given parameters
     */
    public function execute(string $action, array $parameters = []): mixed;

    /**
     * Get the agent's current status
     */
    public function getStatus(): array;

    /**
     * Get the agent's access level
     */
    public function getAccessLevel(): string;

    /**
     * Check if the agent has permission to perform an action
     */
    public function hasPermission(string $action): bool;

    /**
     * Get the agent's audit log
     */
    public function getAuditLog(): array;
} 