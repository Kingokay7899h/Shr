CREATE TABLE `disposal_forms` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `item_id` INT NOT NULL,
    `weight` VARCHAR(50),
    `book_value` DECIMAL(10,2),
    `purchase_date` DATE,
    `unserviceable_date` DATE,
    `period_of_use` VARCHAR(50),
    `current_condition` TEXT,
    `repair_efforts` TEXT,
    `location` TEXT,
    `condemnation_reason` TEXT,
    `remarks` TEXT,
    `status` VARCHAR(100) DEFAULT 'Pending Stores',
    `submitted_by` VARCHAR(100),
    `approved_by` VARCHAR(100),
    `rejection_reason` TEXT,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`item_id`) REFERENCES `register`(`sr_no`)
);

CREATE TABLE `past_disposals` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `item_id` INT NOT NULL,
    `item_name` VARCHAR(255) NOT NULL,
    `disposal_date` DATE NOT NULL,
    `reason` TEXT,
    `lab_id` VARCHAR(50),
    `status` VARCHAR(100) DEFAULT 'Disposed',
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`item_id`) REFERENCES `register`(`sr_no`)
);