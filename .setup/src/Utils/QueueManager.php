<?php

namespace Setup\Utils;

use PhpAmqpLib\Connection\AMQPStreamConnection;
use PhpAmqpLib\Message\AMQPMessage;
use PhpAmqpLib\Channel\AMQPChannel;

class QueueManager {
    private Logger $logger;
    private array $config;
    private ?AMQPStreamConnection $connection = null;
    private ?AMQPChannel $channel = null;

    public function __construct() {
        $this->logger = new Logger();
    }

    public function setConfig(array $config): void {
        $this->config = $config;
    }

    public function connect(): void {
        if ($this->connection !== null) {
            return;
        }

        try {
            $this->connection = new AMQPStreamConnection(
                $this->config['host'],
                $this->config['port'],
                $this->config['user'],
                $this->config['password']
            );

            $this->channel = $this->connection->channel();
            $this->logger->info('Queue connection established');
        } catch (\Exception $e) {
            throw new \RuntimeException('Failed to connect to queue: ' . $e->getMessage());
        }
    }

    public function disconnect(): void {
        if ($this->channel !== null) {
            $this->channel->close();
            $this->channel = null;
        }

        if ($this->connection !== null) {
            $this->connection->close();
            $this->connection = null;
            $this->logger->info('Queue connection closed');
        }
    }

    public function declareQueue(string $queue, bool $durable = true): void {
        if ($this->channel === null) {
            $this->connect();
        }

        try {
            $this->channel->queue_declare(
                $queue,
                false,
                $durable,
                false,
                false
            );
            $this->logger->info("Queue declared: {$queue}");
        } catch (\Exception $e) {
            throw new \RuntimeException("Failed to declare queue {$queue}: " . $e->getMessage());
        }
    }

    public function deleteQueue(string $queue): void {
        if ($this->channel === null) {
            $this->connect();
        }

        try {
            $this->channel->queue_delete($queue);
            $this->logger->info("Queue deleted: {$queue}");
        } catch (\Exception $e) {
            throw new \RuntimeException("Failed to delete queue {$queue}: " . $e->getMessage());
        }
    }

    public function purgeQueue(string $queue): void {
        if ($this->channel === null) {
            $this->connect();
        }

        try {
            $this->channel->queue_purge($queue);
            $this->logger->info("Queue purged: {$queue}");
        } catch (\Exception $e) {
            throw new \RuntimeException("Failed to purge queue {$queue}: " . $e->getMessage());
        }
    }

    public function publish(string $queue, $message, array $properties = []): void {
        if ($this->channel === null) {
            $this->connect();
        }

        try {
            if (is_array($message) || is_object($message)) {
                $message = json_encode($message);
            }

            $msg = new AMQPMessage($message, $properties);
            $this->channel->basic_publish($msg, '', $queue);
            $this->logger->info("Message published to queue: {$queue}");
        } catch (\Exception $e) {
            throw new \RuntimeException("Failed to publish message to queue {$queue}: " . $e->getMessage());
        }
    }

    public function consume(string $queue, callable $callback, bool $noAck = false): void {
        if ($this->channel === null) {
            $this->connect();
        }

        try {
            $this->channel->basic_consume(
                $queue,
                '',
                false,
                $noAck,
                false,
                false,
                function ($msg) use ($callback) {
                    $body = $msg->getBody();
                    $decoded = json_decode($body, true);
                    $data = $decoded !== null ? $decoded : $body;
                    
                    $callback($data, $msg);
                }
            );

            while ($this->channel->is_consuming()) {
                $this->channel->wait();
            }
        } catch (\Exception $e) {
            throw new \RuntimeException("Failed to consume from queue {$queue}: " . $e->getMessage());
        }
    }

    public function getMessageCount(string $queue): int {
        if ($this->channel === null) {
            $this->connect();
        }

        try {
            $info = $this->channel->queue_declare(
                $queue,
                false,
                true,
                false,
                false
            );
            return $info[1];
        } catch (\Exception $e) {
            throw new \RuntimeException("Failed to get message count for queue {$queue}: " . $e->getMessage());
        }
    }

    public function getConsumerCount(string $queue): int {
        if ($this->channel === null) {
            $this->connect();
        }

        try {
            $info = $this->channel->queue_declare(
                $queue,
                false,
                true,
                false,
                false
            );
            return $info[2];
        } catch (\Exception $e) {
            throw new \RuntimeException("Failed to get consumer count for queue {$queue}: " . $e->getMessage());
        }
    }

    public function getConnection(): ?AMQPStreamConnection {
        return $this->connection;
    }

    public function getChannel(): ?AMQPChannel {
        return $this->channel;
    }

    public function isConnected(): bool {
        return $this->connection !== null && $this->channel !== null;
    }

    public function setPrefetchCount(int $count): void {
        if ($this->channel === null) {
            $this->connect();
        }

        try {
            $this->channel->basic_qos(null, $count, null);
        } catch (\Exception $e) {
            throw new \RuntimeException('Failed to set prefetch count: ' . $e->getMessage());
        }
    }

    public function setExchange(string $exchange, string $type = 'direct', bool $durable = true): void {
        if ($this->channel === null) {
            $this->connect();
        }

        try {
            $this->channel->exchange_declare(
                $exchange,
                $type,
                false,
                $durable,
                false
            );
            $this->logger->info("Exchange declared: {$exchange}");
        } catch (\Exception $e) {
            throw new \RuntimeException("Failed to declare exchange {$exchange}: " . $e->getMessage());
        }
    }

    public function bindQueue(string $queue, string $exchange, string $routingKey = ''): void {
        if ($this->channel === null) {
            $this->connect();
        }

        try {
            $this->channel->queue_bind($queue, $exchange, $routingKey);
            $this->logger->info("Queue {$queue} bound to exchange {$exchange}");
        } catch (\Exception $e) {
            throw new \RuntimeException("Failed to bind queue {$queue} to exchange {$exchange}: " . $e->getMessage());
        }
    }

    public function unbindQueue(string $queue, string $exchange, string $routingKey = ''): void {
        if ($this->channel === null) {
            $this->connect();
        }

        try {
            $this->channel->queue_unbind($queue, $exchange, $routingKey);
            $this->logger->info("Queue {$queue} unbound from exchange {$exchange}");
        } catch (\Exception $e) {
            throw new \RuntimeException("Failed to unbind queue {$queue} from exchange {$exchange}: " . $e->getMessage());
        }
    }

    public function deleteExchange(string $exchange): void {
        if ($this->channel === null) {
            $this->connect();
        }

        try {
            $this->channel->exchange_delete($exchange);
            $this->logger->info("Exchange deleted: {$exchange}");
        } catch (\Exception $e) {
            throw new \RuntimeException("Failed to delete exchange {$exchange}: " . $e->getMessage());
        }
    }

    public function publishToExchange(string $exchange, string $routingKey, $message, array $properties = []): void {
        if ($this->channel === null) {
            $this->connect();
        }

        try {
            if (is_array($message) || is_object($message)) {
                $message = json_encode($message);
            }

            $msg = new AMQPMessage($message, $properties);
            $this->channel->basic_publish($msg, $exchange, $routingKey);
            $this->logger->info("Message published to exchange {$exchange} with routing key {$routingKey}");
        } catch (\Exception $e) {
            throw new \RuntimeException("Failed to publish message to exchange {$exchange}: " . $e->getMessage());
        }
    }
} 