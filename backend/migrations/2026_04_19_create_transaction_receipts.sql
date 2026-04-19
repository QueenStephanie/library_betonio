CREATE TABLE IF NOT EXISTS transaction_receipts (
  id INT PRIMARY KEY AUTO_INCREMENT,
  receipt_code VARCHAR(64) NOT NULL,
  transaction_type VARCHAR(64) NOT NULL,
  transaction_ref_id INT NOT NULL,
  borrower_user_id INT NULL,
  actor_user_id INT NULL,
  amount DECIMAL(10,2) NULL,
  payload_json LONGTEXT NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY uq_transaction_receipts_code (receipt_code),
  UNIQUE KEY uq_transaction_receipts_tx_ref (transaction_type, transaction_ref_id),
  INDEX idx_transaction_receipts_created_at (created_at),
  INDEX idx_transaction_receipts_borrower (borrower_user_id),
  INDEX idx_transaction_receipts_actor (actor_user_id),
  CONSTRAINT fk_transaction_receipts_borrower FOREIGN KEY (borrower_user_id) REFERENCES users(id) ON DELETE SET NULL,
  CONSTRAINT fk_transaction_receipts_actor FOREIGN KEY (actor_user_id) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
