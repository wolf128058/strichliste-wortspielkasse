<?php

/**
 * Handles pun-related transaction processing and table creation.
 */
class WortspielJob
{
    private PDO $connection;

    /**
     * WortspielJob constructor.
     *
     * @param PDO $connection A valid PDO database connection.
     */
    public function __construct(PDO $connection)
    {
        $this->connection = $connection;
    }

    /**
     * Creates the table to track paid pun transactions, if it does not exist.
     *
     * @return void
     */
    public function createTable(): void
    {
        $query = <<<SQL
            CREATE TABLE IF NOT EXISTS `paid-pun-transactions` (
                `id` INT(11) NOT NULL AUTO_INCREMENT,
                PRIMARY KEY (`id`),
                CONSTRAINT `fk_transaction_id` FOREIGN KEY (`id`) REFERENCES `transactions` (`id`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
        SQL;

        $this->connection->exec($query);
    }

    /**
     * Processes unpaid transactions for a given article and user, and applies them atomically.
     *
     * @param int $articleId The article ID to filter transactions.
     * @param int $userId The user ID whose balance will be adjusted.
     *
     * @return void
     */
    public function processUnknownTransactions(int $articleId, int $userId): void
    {
        $query = "SELECT id, amount FROM transactions ";
        $query .= "WHERE article_id = :articleId ";
        $query .= "AND id NOT IN (SELECT id FROM `paid-pun-transactions`)";

        $stmt = $this->connection->prepare($query);
        $stmt->execute(['articleId' => $articleId]);

        $results = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $sum = 0.0;

        foreach ($results as $transaction) {
            $id = (int)$transaction['id'];
            $amount = (float)$transaction['amount'];
            $sum += $amount;

            echo "Transaction ID: {$id}, Amount: {$amount}\n";

            try {
                $this->connection->beginTransaction();

                $updateQuery = "UPDATE user SET balance = balance - :amount WHERE id = :userId";
                $updateStmt = $this->connection->prepare($updateQuery);
                $updateStmt->execute([
                    'amount' => $amount,
                    'userId' => $userId,
                ]);

                $insertQuery = "INSERT INTO `paid-pun-transactions` (id) VALUES (:transactionId)";
                $insertStmt = $this->connection->prepare($insertQuery);
                $insertStmt->execute([
                    'transactionId' => $id,
                ]);

                $this->connection->commit();
            } catch (PDOException $e) {
                $this->connection->rollBack();
                echo "Transaction failed for ID {$id}: " . $e->getMessage() . "\n";
            }
        }

        echo "Sum of all Transactions: {$sum}\n";
    }

}