<?php
/**
 * SAE v4 API - Base Handler Class
 * 
 * Kelas dasar untuk semua handler data
 */

abstract class BaseHandler {
    
    protected $connection;
    
    public function __construct() {
        global $connection;
        $this->connection = $connection;
    }
    
    /**
     * Abstract method untuk menyimpan data
     */
    abstract public function save($data);
    
    /**
     * Get table name
     */
    abstract protected function getTableName();
    
    /**
     * Execute prepared statement safely
     */
    protected function executePreparedStatement($stmt, $params = []) {
        try {
            if (!empty($params)) {
                $stmt->bind_param(...$params);
            }
            
            $result = $stmt->execute();
            
            if (!$result) {
                throw new Exception("Execute failed: " . $stmt->error);
            }
            
            return $result;
        } catch (Exception $e) {
            ApiLogger::logError(
                get_class($this) . '::executePreparedStatement',
                $e->getMessage(),
                ['params' => $params]
            );
            throw $e;
        }
    }
    
    /**
     * Begin transaction
     */
    protected function beginTransaction() {
        return $this->connection->begin_transaction();
    }
    
    /**
     * Commit transaction
     */
    protected function commitTransaction() {
        return $this->connection->commit();
    }
    
    /**
     * Rollback transaction
     */
    protected function rollbackTransaction() {
        return $this->connection->rollback();
    }
    
    /**
     * Escape string for safe SQL
     */
    protected function escape($value) {
        return $this->connection->real_escape_string($value);
    }
    
    /**
     * Get current timestamp
     */
    protected function getCurrentTimestamp() {
        return date('Y-m-d H:i:s');
    }
}