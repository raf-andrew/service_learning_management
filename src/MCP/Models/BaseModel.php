<?php

namespace MCP\Models;

abstract class BaseModel
{
    protected $db;
    protected $logger;
    protected $table;
    protected $primaryKey = 'id';

    public function __construct(\PDO $db, \Monolog\Logger $logger)
    {
        $this->db = $db;
        $this->logger = $logger;
    }

    public function find($id)
    {
        try {
            $stmt = $this->db->prepare("SELECT * FROM {$this->table} WHERE {$this->primaryKey} = ?");
            $stmt->execute([$id]);
            return $stmt->fetch(\PDO::FETCH_ASSOC);
        } catch (\PDOException $e) {
            $this->logger->error("Error finding record: " . $e->getMessage());
            throw $e;
        }
    }

    public function create(array $data)
    {
        try {
            $columns = implode(', ', array_keys($data));
            $values = implode(', ', array_fill(0, count($data), '?'));
            
            $stmt = $this->db->prepare("INSERT INTO {$this->table} ({$columns}) VALUES ({$values})");
            $stmt->execute(array_values($data));
            
            return $this->db->lastInsertId();
        } catch (\PDOException $e) {
            $this->logger->error("Error creating record: " . $e->getMessage());
            throw $e;
        }
    }

    public function update($id, array $data)
    {
        try {
            $set = implode(' = ?, ', array_keys($data)) . ' = ?';
            $stmt = $this->db->prepare("UPDATE {$this->table} SET {$set} WHERE {$this->primaryKey} = ?");
            
            $values = array_values($data);
            $values[] = $id;
            
            return $stmt->execute($values);
        } catch (\PDOException $e) {
            $this->logger->error("Error updating record: " . $e->getMessage());
            throw $e;
        }
    }

    public function delete($id)
    {
        try {
            $stmt = $this->db->prepare("DELETE FROM {$this->table} WHERE {$this->primaryKey} = ?");
            return $stmt->execute([$id]);
        } catch (\PDOException $e) {
            $this->logger->error("Error deleting record: " . $e->getMessage());
            throw $e;
        }
    }

    public function all()
    {
        try {
            $stmt = $this->db->prepare("SELECT * FROM {$this->table}");
            $stmt->execute();
            return $stmt->fetchAll(\PDO::FETCH_ASSOC);
        } catch (\PDOException $e) {
            $this->logger->error("Error fetching all records: " . $e->getMessage());
            throw $e;
        }
    }
} 