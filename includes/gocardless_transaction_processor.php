<?php
/**
 * GOCARDLESS TRANSACTION PROCESSOR
 * Comprehensive processing and storage of GoCardless transaction data
 * Based on official GoCardless API documentation
 */

class GoCardlessTransactionProcessor {
    private $pdo;
    
    public function __construct($pdo) {
        $this->pdo = $pdo;
    }
    
    /**
     * Process and store raw GoCardless transaction data
     */
    public function processTransactions($userId, $accountId, $rawTransactionData) {
        try {
            // Validate input structure
            if (!isset($rawTransactionData['transactions'])) {
                throw new Exception('Invalid transaction data: missing transactions array');
            }
            
            $transactions = $rawTransactionData['transactions'];
            
            // Handle both 'booked' and 'pending' transactions if they exist
            $allTransactions = [];
            
            if (isset($transactions['booked']) && is_array($transactions['booked'])) {
                foreach ($transactions['booked'] as $transaction) {
                    $transaction['status'] = 'booked';
                    $allTransactions[] = $transaction;
                }
            }
            
            if (isset($transactions['pending']) && is_array($transactions['pending'])) {
                foreach ($transactions['pending'] as $transaction) {
                    $transaction['status'] = 'pending';
                    $allTransactions[] = $transaction;
                }
            }
            
            // If transactions is a flat array (not nested), use it directly
            if (isset($transactions[0]) && is_array($transactions[0])) {
                $allTransactions = $transactions;
                // Set default status for flat array transactions
                foreach ($allTransactions as &$transaction) {
                    if (!isset($transaction['status'])) {
                        $transaction['status'] = 'booked';
                    }
                }
            }
            
            error_log("GoCardless: Processing " . count($allTransactions) . " transactions for account $accountId");
            
            $processedTransactions = [];
            $validTransactions = 0;
            $invalidTransactions = 0;
            
            foreach ($allTransactions as $rawTransaction) {
                if ($rawTransaction === null) {
                    $invalidTransactions++;
                    continue;
                }
                
                $processed = $this->processTransaction($rawTransaction);
                if ($processed) {
                    $processedTransactions[] = $processed;
                    $validTransactions++;
                } else {
                    $invalidTransactions++;
                }
            }
            
            error_log("GoCardless: Processed $validTransactions valid transactions, $invalidTransactions invalid");
            
            // Store transactions in database
            $this->storeTransactions($userId, $accountId, $processedTransactions);
            
            return [
                'success' => true,
                'total_transactions' => count($allTransactions),
                'valid_transactions' => $validTransactions,
                'invalid_transactions' => $invalidTransactions,
                'processed_transactions' => $processedTransactions
            ];
            
        } catch (Exception $e) {
            error_log("GoCardless Transaction Processor Error: " . $e->getMessage());
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }
    
    /**
     * Process a single transaction according to GoCardless API structure
     */
    private function processTransaction($transaction) {
        try {
            // Extract transaction amount (mandatory field)
            $amount = null;
            $currency = null;
            
            if (isset($transaction['transactionAmount'])) {
                if (is_array($transaction['transactionAmount'])) {
                    $amount = $transaction['transactionAmount']['amount'] ?? null;
                    $currency = $transaction['transactionAmount']['currency'] ?? null;
                } else {
                    $amount = $transaction['transactionAmount'];
                }
            }
            
            if ($amount === null) {
                error_log("GoCardless: Transaction missing amount: " . json_encode($transaction));
                return null;
            }
            
            // Extract dates
            $bookingDate = $transaction['bookingDate'] ?? $transaction['valueDate'] ?? null;
            $valueDate = $transaction['valueDate'] ?? $transaction['bookingDate'] ?? null;
            
            // Extract merchant/counterparty information
            $creditorName = $transaction['creditorName'] ?? null;
            $debtorName = $transaction['debtorName'] ?? null;
            
            // Determine merchant name (for outgoing payments, creditor is the merchant)
            $merchantName = null;
            if (floatval($amount) < 0) {
                // Outgoing payment - creditor is the merchant
                $merchantName = $creditorName;
            } else {
                // Incoming payment - debtor is the payer
                $merchantName = $debtorName;
            }
            
            // Extract description/reference information
            $description = $transaction['remittanceInformationUnstructured'] ?? 
                          $transaction['additionalInformation'] ?? 
                          $transaction['entryReference'] ?? 
                          '';
            
            // Handle structured remittance information
            if (isset($transaction['remittanceInformationStructured'])) {
                if (empty($description)) {
                    $description = $transaction['remittanceInformationStructured'];
                } else {
                    $description .= ' | ' . $transaction['remittanceInformationStructured'];
                }
            }
            
            // Extract additional metadata
            $transactionId = $transaction['transactionId'] ?? 
                           $transaction['internalTransactionId'] ?? 
                           $transaction['entryReference'] ?? 
                           null;
            
            $bankTransactionCode = $transaction['bankTransactionCode'] ?? null;
            $merchantCategoryCode = $transaction['merchantCategoryCode'] ?? null;
            $endToEndId = $transaction['endToEndId'] ?? null;
            $mandateId = $transaction['mandateId'] ?? null;
            $status = $transaction['status'] ?? 'booked';
            
            // Create processed transaction object
            return [
                'transaction_id' => $transactionId,
                'amount' => floatval($amount),
                'currency' => $currency ?? 'EUR',
                'booking_date' => $bookingDate,
                'value_date' => $valueDate,
                'merchant_name' => $merchantName,
                'creditor_name' => $creditorName,
                'debtor_name' => $debtorName,
                'description' => trim($description),
                'bank_transaction_code' => $bankTransactionCode,
                'merchant_category_code' => $merchantCategoryCode,
                'end_to_end_id' => $endToEndId,
                'mandate_id' => $mandateId,
                'status' => $status,
                'raw_data' => json_encode($transaction)
            ];
            
        } catch (Exception $e) {
            error_log("GoCardless: Error processing transaction: " . $e->getMessage());
            return null;
        }
    }
    
    /**
     * Store processed transactions in database
     */
    private function storeTransactions($userId, $accountId, $transactions) {
        try {
            // Create transactions table if it doesn't exist
            $this->createTransactionsTable();
            
            foreach ($transactions as $transaction) {
                // Check if transaction already exists
                $stmt = $this->pdo->prepare("
                    SELECT id FROM raw_transactions 
                    WHERE user_id = ? AND account_id = ? AND transaction_id = ? AND booking_date = ?
                ");
                $stmt->execute([
                    $userId, 
                    $accountId, 
                    $transaction['transaction_id'], 
                    $transaction['booking_date']
                ]);
                
                if ($stmt->fetch()) {
                    continue; // Skip duplicate
                }
                
                // Insert new transaction
                $stmt = $this->pdo->prepare("
                    INSERT INTO raw_transactions (
                        user_id, account_id, transaction_id, amount, currency,
                        booking_date, value_date, merchant_name, creditor_name, debtor_name,
                        description, bank_transaction_code, merchant_category_code,
                        end_to_end_id, mandate_id, status, raw_data, created_at
                    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())
                ");
                
                $stmt->execute([
                    $userId,
                    $accountId,
                    $transaction['transaction_id'],
                    $transaction['amount'],
                    $transaction['currency'],
                    $transaction['booking_date'],
                    $transaction['value_date'],
                    $transaction['merchant_name'],
                    $transaction['creditor_name'],
                    $transaction['debtor_name'],
                    $transaction['description'],
                    $transaction['bank_transaction_code'],
                    $transaction['merchant_category_code'],
                    $transaction['end_to_end_id'],
                    $transaction['mandate_id'],
                    $transaction['status'],
                    $transaction['raw_data']
                ]);
            }
            
        } catch (Exception $e) {
            error_log("GoCardless: Error storing transactions: " . $e->getMessage());
            throw $e;
        }
    }
    
    /**
     * Create raw transactions table
     */
    private function createTransactionsTable() {
        try {
            $this->pdo->exec("
                CREATE TABLE IF NOT EXISTS raw_transactions (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    user_id INT NOT NULL,
                    account_id VARCHAR(255) NOT NULL,
                    transaction_id VARCHAR(255),
                    amount DECIMAL(10,2) NOT NULL,
                    currency VARCHAR(3) DEFAULT 'EUR',
                    booking_date DATE,
                    value_date DATE,
                    merchant_name VARCHAR(255),
                    creditor_name VARCHAR(255),
                    debtor_name VARCHAR(255),
                    description TEXT,
                    bank_transaction_code VARCHAR(32),
                    merchant_category_code VARCHAR(4),
                    end_to_end_id VARCHAR(35),
                    mandate_id VARCHAR(48),
                    status ENUM('booked', 'pending') DEFAULT 'booked',
                    raw_data JSON,
                    created_at DATETIME NOT NULL,
                    INDEX idx_user_account (user_id, account_id),
                    INDEX idx_booking_date (booking_date),
                    INDEX idx_amount (amount),
                    INDEX idx_merchant (merchant_name),
                    UNIQUE KEY unique_transaction (user_id, account_id, transaction_id, booking_date)
                )
            ");
            
        } catch (Exception $e) {
            error_log("GoCardless: Error creating transactions table: " . $e->getMessage());
            throw $e;
        }
    }
    
    /**
     * Get stored transactions for subscription analysis
     */
    public function getTransactionsForAnalysis($userId, $accountId = null, $daysBack = 365) {
        try {
            $sql = "
                SELECT * FROM raw_transactions 
                WHERE user_id = ? 
                AND booking_date >= DATE_SUB(NOW(), INTERVAL ? DAY)
                AND amount < 0
            ";
            $params = [$userId, $daysBack];
            
            if ($accountId) {
                $sql .= " AND account_id = ?";
                $params[] = $accountId;
            }
            
            $sql .= " ORDER BY booking_date DESC";
            
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute($params);
            
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
            
        } catch (Exception $e) {
            error_log("GoCardless: Error getting transactions for analysis: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Get transaction statistics
     */
    public function getTransactionStats($userId, $accountId = null) {
        try {
            $sql = "
                SELECT 
                    COUNT(*) as total_transactions,
                    COUNT(CASE WHEN amount < 0 THEN 1 END) as outgoing_transactions,
                    COUNT(CASE WHEN amount > 0 THEN 1 END) as incoming_transactions,
                    COUNT(DISTINCT merchant_name) as unique_merchants,
                    MIN(booking_date) as oldest_transaction,
                    MAX(booking_date) as newest_transaction,
                    SUM(CASE WHEN amount < 0 THEN ABS(amount) ELSE 0 END) as total_outgoing,
                    SUM(CASE WHEN amount > 0 THEN amount ELSE 0 END) as total_incoming
                FROM raw_transactions 
                WHERE user_id = ?
            ";
            $params = [$userId];
            
            if ($accountId) {
                $sql .= " AND account_id = ?";
                $params[] = $accountId;
            }
            
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute($params);
            
            return $stmt->fetch(PDO::FETCH_ASSOC);
            
        } catch (Exception $e) {
            error_log("GoCardless: Error getting transaction stats: " . $e->getMessage());
            return null;
        }
    }
}
?>
