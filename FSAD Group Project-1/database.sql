-- Hotel Booking System Database Schema
-- This SQL schema creates the necessary tables for a hotel booking system

-- Create Database
CREATE DATABASE hotel_booking_system;
USE hotel_booking_system;

-- Users Table
CREATE TABLE users (
    user_id INT PRIMARY KEY AUTO_INCREMENT,
    first_name VARCHAR(50) NOT NULL,
    last_name VARCHAR(50) NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    phone VARCHAR(20) NOT NULL,
    password_hash VARCHAR(255) NOT NULL,
    date_of_birth DATE,
    address VARCHAR(255),
    city VARCHAR(50),
    country VARCHAR(50),
    postal_code VARCHAR(20),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_email (email)
);

-- Hotels Table
CREATE TABLE hotels (
    hotel_id INT PRIMARY KEY AUTO_INCREMENT,
    hotel_name VARCHAR(100) NOT NULL,
    location VARCHAR(100) NOT NULL,
    city VARCHAR(50) NOT NULL,
    country VARCHAR(50) NOT NULL,
    postal_code VARCHAR(20),
    address VARCHAR(255) NOT NULL,
    phone VARCHAR(20),
    email VARCHAR(100),
    website VARCHAR(255),
    rating DECIMAL(3, 2) CHECK (rating >= 0 AND rating <= 5),
    description TEXT,
    image_url VARCHAR(255),
    total_rooms INT NOT NULL,
    rooms_available INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_location (location),
    INDEX idx_city (city)
);

-- Hotel Amenities Table
CREATE TABLE amenities (
    amenity_id INT PRIMARY KEY AUTO_INCREMENT,
    amenity_name VARCHAR(50) NOT NULL UNIQUE,
    description VARCHAR(255),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Hotel Amenities Relationship Table
CREATE TABLE hotel_amenities (
    hotel_id INT NOT NULL,
    amenity_id INT NOT NULL,
    PRIMARY KEY (hotel_id, amenity_id),
    FOREIGN KEY (hotel_id) REFERENCES hotels(hotel_id) ON DELETE CASCADE,
    FOREIGN KEY (amenity_id) REFERENCES amenities(amenity_id) ON DELETE CASCADE
);

-- Room Types Table
CREATE TABLE room_types (
    room_type_id INT PRIMARY KEY AUTO_INCREMENT,
    type_name VARCHAR(50) NOT NULL UNIQUE,
    description TEXT,
    max_occupancy INT NOT NULL,
    base_price DECIMAL(10, 2) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Rooms Table
CREATE TABLE rooms (
    room_id INT PRIMARY KEY AUTO_INCREMENT,
    hotel_id INT NOT NULL,
    room_number VARCHAR(10) NOT NULL,
    room_type_id INT NOT NULL,
    price_per_night DECIMAL(10, 2) NOT NULL,
    availability_status ENUM('available', 'occupied', 'maintenance') DEFAULT 'available',
    floor_number INT,
    view_type VARCHAR(50),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (hotel_id) REFERENCES hotels(hotel_id) ON DELETE CASCADE,
    FOREIGN KEY (room_type_id) REFERENCES room_types(room_type_id),
    UNIQUE KEY unique_room_number (hotel_id, room_number),
    INDEX idx_availability (availability_status),
    INDEX idx_hotel (hotel_id)
);

-- Bookings Table
CREATE TABLE bookings (
    booking_id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    hotel_id INT NOT NULL,
    check_in_date DATE NOT NULL,
    check_out_date DATE NOT NULL,
    number_of_nights INT GENERATED ALWAYS AS (DATEDIFF(check_out_date, check_in_date)) STORED,
    number_of_guests INT NOT NULL,
    number_of_rooms INT NOT NULL,
    booking_status ENUM('pending', 'confirmed', 'cancelled', 'completed') DEFAULT 'pending',
    total_price DECIMAL(12, 2) NOT NULL,
    tax_amount DECIMAL(10, 2),
    discount_applied DECIMAL(10, 2) DEFAULT 0,
    booking_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE,
    FOREIGN KEY (hotel_id) REFERENCES hotels(hotel_id),
    INDEX idx_user (user_id),
    INDEX idx_hotel (hotel_id),
    INDEX idx_status (booking_status),
    INDEX idx_check_in (check_in_date)
);

-- Booking Details Table (Individual rooms in a booking)
CREATE TABLE booking_details (
    booking_detail_id INT PRIMARY KEY AUTO_INCREMENT,
    booking_id INT NOT NULL,
    room_id INT NOT NULL,
    room_type_id INT NOT NULL,
    price_per_night DECIMAL(10, 2) NOT NULL,
    number_of_nights INT NOT NULL,
    subtotal DECIMAL(12, 2) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (booking_id) REFERENCES bookings(booking_id) ON DELETE CASCADE,
    FOREIGN KEY (room_id) REFERENCES rooms(room_id),
    FOREIGN KEY (room_type_id) REFERENCES room_types(room_type_id),
    INDEX idx_booking (booking_id)
);

-- Payments Table
CREATE TABLE payments (
    payment_id INT PRIMARY KEY AUTO_INCREMENT,
    booking_id INT NOT NULL,
    payment_amount DECIMAL(12, 2) NOT NULL,
    payment_method ENUM('credit_card', 'debit_card', 'bank_transfer', 'paypal') NOT NULL,
    payment_status ENUM('pending', 'completed', 'failed', 'refunded') DEFAULT 'pending',
    transaction_id VARCHAR(100),
    payment_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (booking_id) REFERENCES bookings(booking_id) ON DELETE CASCADE,
    INDEX idx_booking (booking_id),
    INDEX idx_status (payment_status)
);

-- Reviews Table
CREATE TABLE reviews (
    review_id INT PRIMARY KEY AUTO_INCREMENT,
    booking_id INT NOT NULL,
    user_id INT NOT NULL,
    hotel_id INT NOT NULL,
    rating INT CHECK (rating >= 1 AND rating <= 5),
    review_text TEXT,
    review_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    helpful_count INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (booking_id) REFERENCES bookings(booking_id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE,
    FOREIGN KEY (hotel_id) REFERENCES hotels(hotel_id),
    INDEX idx_hotel (hotel_id),
    INDEX idx_rating (rating)
);

-- Discounts/Coupons Table
CREATE TABLE discounts (
    discount_id INT PRIMARY KEY AUTO_INCREMENT,
    coupon_code VARCHAR(50) UNIQUE NOT NULL,
    discount_type ENUM('percentage', 'fixed') NOT NULL,
    discount_value DECIMAL(10, 2) NOT NULL,
    min_booking_amount DECIMAL(10, 2),
    max_uses INT,
    current_uses INT DEFAULT 0,
    start_date DATE NOT NULL,
    end_date DATE NOT NULL,
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_code (coupon_code)
);

-- Sample Data Insertion

-- Insert Room Types
INSERT INTO room_types (type_name, description, max_occupancy, base_price) VALUES
('Single Room', 'Single bed room suitable for one guest', 1, 100.00),
('Double Room', 'Room with double bed, suitable for couples or two guests', 2, 150.00),
('Suite', 'Luxury suite with separate living and bedroom areas', 4, 250.00),
('Deluxe Suite', 'Premium suite with premium amenities', 4, 350.00);

-- Insert Hotel Amenities
INSERT INTO amenities (amenity_name, description) VALUES
('WiFi', 'High-speed internet access'),
('Pool', 'Outdoor or indoor swimming pool'),
('Gym', 'Fitness center with equipment'),
('Restaurant', 'On-site dining facility'),
('Spa', 'Wellness and spa services'),
('Bar', 'Lounge and bar area'),
('Parking', 'Free parking facility'),
('Business Center', 'Conference and meeting facilities'),
('Beach Access', 'Direct access to the beach'),
('24/7 Service', 'Round-the-clock customer service');

-- Insert Sample Hotels
INSERT INTO hotels (hotel_name, location, city, country, address, phone, email, rating, description, total_rooms, rooms_available) VALUES
('Luxury Palace Hotel', 'New York', 'New York', 'USA', '123 Broadway, New York, NY 10001', '+1-212-555-0001', 'info@luxurypalace.com', 4.8, 'A luxurious 5-star hotel in the heart of Manhattan', 150, 45),
('Ocean View Resort', 'Miami', 'Miami', 'USA', '456 Ocean Drive, Miami, FL 33139', '+1-305-555-0002', 'info@oceanview.com', 4.6, 'Beautiful beachfront resort with ocean views', 200, 67),
('Mountain Retreat', 'Denver', 'Denver', 'USA', '789 Peak Road, Denver, CO 80202', '+1-303-555-0003', 'info@mountainretreat.com', 4.5, 'Scenic hotel in the Rocky Mountains', 120, 40),
('City Center Comfort', 'Chicago', 'Chicago', 'USA', '321 Michigan Ave, Chicago, IL 60601', '+1-312-555-0004', 'info@citycenter.com', 4.3, 'Convenient downtown hotel with excellent service', 100, 30),
('Desert Oasis Hotel', 'Phoenix', 'Phoenix', 'USA', '555 Desert Lane, Phoenix, AZ 85001', '+1-602-555-0005', 'info@desertoasis.com', 4.4, 'Contemporary hotel with desert ambiance', 110, 35),
('Historic Heritage Inn', 'Boston', 'Boston', 'USA', '999 Freedom Trail, Boston, MA 02109', '+1-617-555-0006', 'info@heritage.com', 4.7, 'Historic inn near major landmarks', 95, 25);

-- Associate hotels with amenities
INSERT INTO hotel_amenities (hotel_id, amenity_id) VALUES
(1, 1), (1, 2), (1, 3), (1, 4), (1, 5),
(2, 1), (2, 6), (2, 9), (2, 10),
(3, 1), (3, 3), (3, 4), (3, 8),
(4, 1), (4, 3), (4, 8), (4, 10),
(5, 2), (5, 5), (5, 1), (5, 4),
(6, 1), (6, 4), (6, 8), (6, 10);

-- Insert Sample Rooms
INSERT INTO rooms (hotel_id, room_number, room_type_id, price_per_night, floor_number, view_type) VALUES
(1, '101', 1, 250, 1, 'Street View'),
(1, '201', 2, 350, 2, 'City View'),
(1, '301', 3, 500, 3, 'Park View'),
(2, '101', 1, 180, 1, 'Ocean View'),
(2, '201', 2, 280, 2, 'Ocean View'),
(2, '301', 3, 420, 3, 'Ocean View'),
(3, '101', 1, 150, 1, 'Mountain View'),
(3, '201', 2, 220, 2, 'Mountain View'),
(3, '301', 3, 350, 3, 'Peak View'),
(4, '101', 1, 120, 1, 'Downtown View'),
(4, '201', 2, 180, 2, 'Downtown View'),
(4, '301', 3, 280, 3, 'Lake View');

-- USEFUL QUERIES

-- Find available rooms in a specific date range and hotel
-- SELECT r.room_id, r.room_number, rt.type_name, r.price_per_night
-- FROM rooms r
-- JOIN room_types rt ON r.room_type_id = rt.room_type_id
-- WHERE r.hotel_id = 1
-- AND r.availability_status = 'available'
-- AND r.room_id NOT IN (
--     SELECT DISTINCT room_id FROM booking_details bd
--     JOIN bookings b ON bd.booking_id = b.booking_id
--     WHERE b.check_in_date < '2024-12-25' AND b.check_out_date > '2024-12-20'
--     AND b.booking_status IN ('confirmed', 'pending')
-- );

-- Get booking details with guest and hotel information
-- SELECT b.booking_id, u.first_name, u.last_name, h.hotel_name, 
--        b.check_in_date, b.check_out_date, b.total_price, b.booking_status
-- FROM bookings b
-- JOIN users u ON b.user_id = u.user_id
-- JOIN hotels h ON b.hotel_id = h.hotel_id
-- WHERE b.booking_status = 'confirmed'
-- ORDER BY b.check_in_date;

-- Get hotel availability
-- SELECT h.hotel_id, h.hotel_name, h.total_rooms, COUNT(DISTINCT r.room_id) as rooms_available
-- FROM hotels h
-- LEFT JOIN rooms r ON h.hotel_id = r.hotel_id
-- WHERE r.availability_status = 'available'
-- GROUP BY h.hotel_id;

-- Get customer reviews for a hotel
-- SELECT r.review_id, u.first_name, u.last_name, r.rating, r.review_text, r.review_date
-- FROM reviews r
-- JOIN users u ON r.user_id = u.user_id
-- WHERE r.hotel_id = 1
-- ORDER BY r.review_date DESC;

-- Update room availability after booking
-- UPDATE rooms r
-- SET r.availability_status = 'occupied'
-- WHERE r.room_id IN (SELECT room_id FROM booking_details WHERE booking_id = 1)
-- AND EXISTS (
--     SELECT 1 FROM bookings b 
--     WHERE b.booking_id = 1 
--     AND CURDATE() BETWEEN b.check_in_date AND DATE_SUB(b.check_out_date, INTERVAL 1 DAY)
-- );
