<?php

namespace MCP\Interfaces;

/**
 * Authenticatable Interface
 * 
 * Defines the contract for entities that can be authenticated in the MCP system.
 * 
 * @package MCP\Interfaces
 */
interface Authenticatable
{
    /**
     * Get the unique identifier for the user
     * 
     * @return string|int
     */
    public function getId(): string|int;

    /**
     * Get the username for the user
     * 
     * @return string
     */
    public function getUsername(): string;

    /**
     * Get the password hash for the user
     * 
     * @return string
     */
    public function getPasswordHash(): string;

    /**
     * Check if the user has MFA enabled
     * 
     * @return bool
     */
    public function hasMFA(): bool;

    /**
     * Get the MFA secret for the user
     * 
     * @return string|null
     */
    public function getMFASecret(): ?string;

    /**
     * Check if the user is active
     * 
     * @return bool
     */
    public function isActive(): bool;

    /**
     * Get the last login timestamp for the user
     * 
     * @return \DateTime|null
     */
    public function getLastLoginAt(): ?\DateTime;

    /**
     * Update the last login timestamp
     * 
     * @param \DateTime $timestamp
     * @return void
     */
    public function updateLastLoginAt(\DateTime $timestamp): void;
} 