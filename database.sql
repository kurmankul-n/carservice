-- ============================================================
-- Mobile Car Service App - Database Setup
-- Run this file once to create and populate the database
-- ============================================================

CREATE DATABASE IF NOT EXISTS car_service_db;
USE car_service_db;

-- Table 1: services
CREATE TABLE IF NOT EXISTS services (
    service_id   INT AUTO_INCREMENT PRIMARY KEY,
    service_name VARCHAR(100) NOT NULL,
    description  VARCHAR(255) NOT NULL,
    price        DECIMAL(10,2) NOT NULL,
    duration_min INT NOT NULL
);

-- Table 2: mechanics
CREATE TABLE IF NOT EXISTS mechanics (
    mechanic_id   INT AUTO_INCREMENT PRIMARY KEY,
    full_name     VARCHAR(100) NOT NULL,
    specialization VARCHAR(100) NOT NULL,
    rating        DECIMAL(3,1) NOT NULL
);

-- Table 3: orders
CREATE TABLE IF NOT EXISTS orders (
    order_id     INT AUTO_INCREMENT PRIMARY KEY,
    customer_name VARCHAR(100) NOT NULL,
    service_id   INT NOT NULL,
    mechanic_id  INT NOT NULL,
    order_date   DATE NOT NULL,
    status       VARCHAR(50) NOT NULL DEFAULT 'Pending',
    FOREIGN KEY (service_id)  REFERENCES services(service_id),
    FOREIGN KEY (mechanic_id) REFERENCES mechanics(mechanic_id)
);

-- ---- Sample data ----

INSERT INTO services (service_name, description, price, duration_min) VALUES
('Oil Change',         'Full engine oil replacement with filter',       35.00,  30),
('Battery Diagnostics','Complete battery health check and test',         20.00,  20),
('Spark Plug Replacement','Replace all spark plugs for better ignition',45.00,  40),
('Computer Diagnostics','Read and clear error codes with OBD scanner',  30.00,  25),
('Brake Inspection',   'Check brake pads, discs and fluid levels',      25.00,  35),
('Air Filter Replacement','Replace engine air filter',                  18.00,  15),
('Tire Rotation',      'Rotate all four tires for even wear',           22.00,  30),
('Coolant Flush',      'Drain and refill engine coolant system',        40.00,  45);

INSERT INTO mechanics (full_name, specialization, rating) VALUES
('Arman Bekov',    'Engine & Electrical',   4.9),
('Dana Seitkali',  'Diagnostics & Brakes',  4.7),
('Ruslan Akhmetov','General Maintenance',   4.8),
('Asel Nurlanovna','Electrical Systems',    4.6);

INSERT INTO orders (customer_name, service_id, mechanic_id, order_date, status) VALUES
('Marat Usenov',    1, 1, '2025-01-10', 'Completed'),
('Ainur Bekova',    3, 2, '2025-01-12', 'Completed'),
('Timur Satybaldiev',4, 1, '2025-01-14', 'In Progress'),
('Zhanna Ospanova', 5, 3, '2025-01-15', 'Completed'),
('Kairat Dzhaksybekov',2,4,'2025-01-16', 'Pending'),
('Saule Nurmagambetova',6,2,'2025-01-17','Completed'),
('Bolat Seilov',    7, 3, '2025-01-18', 'In Progress'),
('Dinara Alimova',  8, 1, '2025-01-19', 'Pending');
