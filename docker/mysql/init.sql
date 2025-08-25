-- MySQL initialization script for SavedFeast
-- This script runs when the MySQL container is first created

-- Set character encoding
SET NAMES utf8mb4;
SET CHARACTER SET utf8mb4;
SET character_set_connection=utf8mb4;

-- Create database if it doesn't exist
CREATE DATABASE IF NOT EXISTS savedfeast
CHARACTER SET utf8mb4
COLLATE utf8mb4_unicode_ci;

-- Grant privileges to the application user
GRANT ALL PRIVILEGES ON savedfeast.* TO 'savedfeast_user'@'%';
FLUSH PRIVILEGES;

-- Use the database
USE savedfeast;

-- Create any additional tables or initial data if needed
-- (Laravel migrations will handle the main schema)
