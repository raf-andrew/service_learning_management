<?php

namespace App\Console\Commands\Auth;

class ManageAuthEventsCommand extends BaseAuthCommand
{
    protected $signature = 'auth:events
        {action : The action to perform (list|register|unregister|fire)}
        {--name= : Event name}
        {--class= : Event class}
        {--listener= : Listener class}
        {--data= : Event data (JSON)}';

    protected $description = 'Manage authentication events';

    public function handle()
    {
        if (!$this->validateAuthConfig()) {
            return 1;
        }

        $action = $this->argument('action');

        switch ($action) {
            case 'list':
                return $this->listEvents();
            case 'register':
                return $this->registerEvent();
            case 'unregister':
                return $this->unregisterEvent();
            case 'fire':
                return $this->fireEvent();
            default:
                $this->error("Unknown action: {$action}");
                return 1;
        }
    }

    protected function listEvents()
    {
        try {
            $events = $this->authService->getAllEvents();

            $this->table(
                ['Name', 'Class', 'Listeners'],
                $events->map(fn($event) => [
                    $event->name,
                    $event->class,
                    implode(', ', $event->listeners)
                ])
            );

            return 0;
        } catch (\Exception $e) {
            $this->error("Failed to list events: {$e->getMessage()}");
            return 1;
        }
    }

    protected function registerEvent()
    {
        $name = $this->option('name');
        $class = $this->option('class');
        $listener = $this->option('listener');

        if (!$name || !$class) {
            $this->error('Event name and class are required');
            return 1;
        }

        try {
            $this->authService->registerEvent([
                'name' => $name,
                'class' => $class,
                'listener' => $listener
            ]);

            $this->info("Event registered successfully: {$name}");
            return 0;
        } catch (\Exception $e) {
            $this->error("Failed to register event: {$e->getMessage()}");
            return 1;
        }
    }

    protected function unregisterEvent()
    {
        $name = $this->option('name');
        if (!$name) {
            $this->error('Event name is required');
            return 1;
        }

        if ($this->confirm("Are you sure you want to unregister event {$name}?")) {
            try {
                $this->authService->unregisterEvent($name);
                $this->info("Event unregistered successfully: {$name}");
                return 0;
            } catch (\Exception $e) {
                $this->error("Failed to unregister event: {$e->getMessage()}");
                return 1;
            }
        }

        return 0;
    }

    protected function fireEvent()
    {
        $name = $this->option('name');
        $data = $this->option('data');

        if (!$name) {
            $this->error('Event name is required');
            return 1;
        }

        try {
            $eventData = $data ? json_decode($data, true) : [];
            $result = $this->authService->fireEvent($name, $eventData);

            $this->info("Event fired successfully: {$name}");
            if ($result) {
                $this->info("Event result:");
                $this->table(
                    ['Listener', 'Status'],
                    collect($result)->map(fn($item) => [
                        $item['listener'],
                        $item['status']
                    ])
                );
            }
            return 0;
        } catch (\Exception $e) {
            $this->error("Failed to fire event: {$e->getMessage()}");
            return 1;
        }
    }
} 