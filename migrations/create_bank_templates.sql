CREATE TABLE IF NOT EXISTS bank_statement_templates (
    id INT AUTO_INCREMENT PRIMARY KEY,
    bank_name VARCHAR(100) NOT NULL,
    detection_pattern TEXT NOT NULL, -- Keywords or regex to identify the bank
    row_pattern TEXT NOT NULL,       -- Regex to extract a transaction row
    date_format VARCHAR(50) NOT NULL DEFAULT 'd/m/Y',
    column_map JSON NOT NULL,        -- Mapping of regex groups to date, description, amount
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);
