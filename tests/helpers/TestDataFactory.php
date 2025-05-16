<?php

namespace Tests\Helpers;

use Illuminate\Database\Eloquent\Factory;
use Illuminate\Support\Str;

abstract class TestDataFactory
{
    protected $factory;
    protected $model;
    protected $defaultAttributes = [];
    protected $states = [];

    public function __construct()
    {
        $this->factory = app(Factory::class);
        $this->model = $this->getModelClass();
        $this->registerStates();
    }

    /**
     * Get the model class name
     *
     * @return string
     */
    abstract protected function getModelClass(): string;

    /**
     * Register factory states
     *
     * @return void
     */
    protected function registerStates(): void
    {
        // Override in child classes to register states
    }

    /**
     * Create a new model instance
     *
     * @param array $attributes
     * @return \Illuminate\Database\Eloquent\Model
     */
    public function create(array $attributes = [])
    {
        return $this->factory->of($this->model)->create(
            array_merge($this->defaultAttributes, $attributes)
        );
    }

    /**
     * Create multiple model instances
     *
     * @param int $count
     * @param array $attributes
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function createMany(int $count, array $attributes = [])
    {
        return $this->factory->of($this->model)->times($count)->create(
            array_merge($this->defaultAttributes, $attributes)
        );
    }

    /**
     * Make a new model instance without saving
     *
     * @param array $attributes
     * @return \Illuminate\Database\Eloquent\Model
     */
    public function make(array $attributes = [])
    {
        return $this->factory->of($this->model)->make(
            array_merge($this->defaultAttributes, $attributes)
        );
    }

    /**
     * Make multiple model instances without saving
     *
     * @param int $count
     * @param array $attributes
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function makeMany(int $count, array $attributes = [])
    {
        return $this->factory->of($this->model)->times($count)->make(
            array_merge($this->defaultAttributes, $attributes)
        );
    }

    /**
     * Create a raw model instance
     *
     * @param array $attributes
     * @return array
     */
    public function raw(array $attributes = [])
    {
        return $this->factory->of($this->model)->raw(
            array_merge($this->defaultAttributes, $attributes)
        );
    }

    /**
     * Create multiple raw model instances
     *
     * @param int $count
     * @param array $attributes
     * @return array
     */
    public function rawMany(int $count, array $attributes = [])
    {
        return $this->factory->of($this->model)->times($count)->raw(
            array_merge($this->defaultAttributes, $attributes)
        );
    }

    /**
     * Add a state to the factory
     *
     * @param string $state
     * @param array $attributes
     * @return self
     */
    public function state(string $state, array $attributes): self
    {
        $this->states[$state] = $attributes;
        return $this;
    }

    /**
     * Apply a state to the factory
     *
     * @param string $state
     * @return self
     */
    public function applyState(string $state): self
    {
        if (isset($this->states[$state])) {
            $this->defaultAttributes = array_merge(
                $this->defaultAttributes,
                $this->states[$state]
            );
        }
        return $this;
    }

    /**
     * Generate a unique value
     *
     * @param string $prefix
     * @return string
     */
    protected function unique(string $prefix = ''): string
    {
        return $prefix . Str::random(8);
    }
} 