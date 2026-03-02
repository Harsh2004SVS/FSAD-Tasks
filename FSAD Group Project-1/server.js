/**
 * Hotel Booking System - Backend API (Node.js + Express)
 * This is an optional backend implementation example
 * 
 * Installation:
 * npm install express mysql2 cors dotenv bcryptjs jsonwebtoken
 */

const express = require('express');
const mysql = require('mysql2/promise');
const cors = require('cors');
const bcryptjs = require('bcryptjs');
const jwt = require('jsonwebtoken');
require('dotenv').config();

const app = express();

// Middleware
app.use(cors());
app.use(express.json());

// Database Pool Configuration
const pool = mysql.createPool({
    host: process.env.DB_HOST || 'localhost',
    user: process.env.DB_USER || 'root',
    password: process.env.DB_PASSWORD || '',
    database: 'hotel_booking_system',
    waitForConnections: true,
    connectionLimit: 10,
    queueLimit: 0
});

// Error Handling Middleware
app.use((error, req, res, next) => {
    console.error(error);
    res.status(500).json({ error: 'Internal Server Error' });
});

// ============= HOTEL ROUTES =============

/**
 * GET /api/hotels
 * Get all hotels with optional filtering
 * Query params: location, city, minRating
 */
app.get('/api/hotels', async (req, res) => {
    try {
        const { location, city, minRating } = req.query;
        let query = 'SELECT * FROM hotels WHERE 1=1';
        const params = [];

        if (location) {
            query += ' AND location LIKE ?';
            params.push(`%${location}%`);
        }
        if (city) {
            query += ' AND city = ?';
            params.push(city);
        }
        if (minRating) {
            query += ' AND rating >= ?';
            params.push(parseFloat(minRating));
        }

        query += ' ORDER BY rating DESC';

        const connection = await pool.getConnection();
        const [hotels] = await connection.execute(query, params);
        
        // Get amenities for each hotel
        for (let hotel of hotels) {
            const [amenities] = await connection.execute(
                `SELECT a.amenity_name FROM amenities a
                 JOIN hotel_amenities ha ON a.amenity_id = ha.amenity_id
                 WHERE ha.hotel_id = ?`,
                [hotel.hotel_id]
            );
            hotel.amenities = amenities.map(a => a.amenity_name);
        }

        connection.release();
        res.json(hotels);
    } catch (error) {
        console.error(error);
        res.status(500).json({ error: 'Failed to fetch hotels' });
    }
});

/**
 * GET /api/hotels/:hotelId
 * Get specific hotel details
 */
app.get('/api/hotels/:hotelId', async (req, res) => {
    try {
        const { hotelId } = req.params;
        const connection = await pool.getConnection();

        const [hotels] = await connection.execute(
            'SELECT * FROM hotels WHERE hotel_id = ?',
            [hotelId]
        );

        if (hotels.length === 0) {
            connection.release();
            return res.status(404).json({ error: 'Hotel not found' });
        }

        const hotel = hotels[0];

        // Get amenities
        const [amenities] = await connection.execute(
            `SELECT a.amenity_name FROM amenities a
             JOIN hotel_amenities ha ON a.amenity_id = ha.amenity_id
             WHERE ha.hotel_id = ?`,
            [hotelId]
        );
        hotel.amenities = amenities.map(a => a.amenity_name);

        // Get reviews
        const [reviews] = await connection.execute(
            `SELECT r.rating, r.review_text, u.first_name, u.last_name, r.review_date
             FROM reviews r
             JOIN users u ON r.user_id = u.user_id
             WHERE r.hotel_id = ?
             ORDER BY r.review_date DESC LIMIT 5`,
            [hotelId]
        );
        hotel.reviews = reviews;

        connection.release();
        res.json(hotel);
    } catch (error) {
        console.error(error);
        res.status(500).json({ error: 'Failed to fetch hotel details' });
    }
});

/**
 * GET /api/hotels/:hotelId/rooms
 * Get available rooms for a hotel in date range
 * Query params: checkIn, checkOut, roomType
 */
app.get('/api/hotels/:hotelId/rooms', async (req, res) => {
    try {
        const { hotelId } = req.params;
        const { checkIn, checkOut, roomType } = req.query;

        const connection = await pool.getConnection();

        let query = `
            SELECT r.room_id, r.room_number, rt.type_name, r.price_per_night, r.view_type
            FROM rooms r
            JOIN room_types rt ON r.room_type_id = rt.room_type_id
            WHERE r.hotel_id = ?
            AND r.availability_status = 'available'
        `;
        const params = [hotelId];

        if (roomType) {
            query += ' AND rt.type_name = ?';
            params.push(roomType);
        }

        // Check for overlapping bookings
        if (checkIn && checkOut) {
            query += `
                AND r.room_id NOT IN (
                    SELECT DISTINCT bd.room_id FROM booking_details bd
                    JOIN bookings b ON bd.booking_id = b.booking_id
                    WHERE b.check_in_date < ? AND b.check_out_date > ?
                    AND b.booking_status IN ('confirmed', 'pending')
                )
            `;
            params.push(checkOut, checkIn);
        }

        const [rooms] = await connection.execute(query, params);
        connection.release();
        res.json(rooms);
    } catch (error) {
        console.error(error);
        res.status(500).json({ error: 'Failed to fetch rooms' });
    }
});

// ============= USER ROUTES =============

/**
 * POST /api/users/register
 * Register a new user
 */
app.post('/api/users/register', async (req, res) => {
    try {
        const { firstName, lastName, email, phone, password } = req.body;

        // Validation
        if (!firstName || !lastName || !email || !phone || !password) {
            return res.status(400).json({ error: 'All fields are required' });
        }

        const connection = await pool.getConnection();

        // Check if user exists
        const [existingUsers] = await connection.execute(
            'SELECT user_id FROM users WHERE email = ?',
            [email]
        );

        if (existingUsers.length > 0) {
            connection.release();
            return res.status(400).json({ error: 'Email already registered' });
        }

        // Hash password
        const hashedPassword = await bcryptjs.hash(password, 10);

        const [result] = await connection.execute(
            'INSERT INTO users (first_name, last_name, email, phone, password_hash) VALUES (?, ?, ?, ?, ?)',
            [firstName, lastName, email, phone, hashedPassword]
        );

        connection.release();
        res.status(201).json({ 
            message: 'User registered successfully',
            userId: result.insertId 
        });
    } catch (error) {
        console.error(error);
        res.status(500).json({ error: 'Registration failed' });
    }
});

/**
 * POST /api/users/login
 * Login user and return JWT token
 */
app.post('/api/users/login', async (req, res) => {
    try {
        const { email, password } = req.body;

        if (!email || !password) {
            return res.status(400).json({ error: 'Email and password required' });
        }

        const connection = await pool.getConnection();

        const [users] = await connection.execute(
            'SELECT * FROM users WHERE email = ?',
            [email]
        );

        if (users.length === 0) {
            connection.release();
            return res.status(401).json({ error: 'Invalid credentials' });
        }

        const user = users[0];
        const passwordMatch = await bcryptjs.compare(password, user.password_hash);

        if (!passwordMatch) {
            connection.release();
            return res.status(401).json({ error: 'Invalid credentials' });
        }

        const token = jwt.sign(
            { userId: user.user_id, email: user.email },
            process.env.JWT_SECRET || 'your_secret_key',
            { expiresIn: '24h' }
        );

        connection.release();
        res.json({ token, user: { userId: user.user_id, email: user.email } });
    } catch (error) {
        console.error(error);
        res.status(500).json({ error: 'Login failed' });
    }
});

// ============= BOOKING ROUTES =============

/**
 * POST /api/bookings
 * Create a new booking
 */
app.post('/api/bookings', async (req, res) => {
    try {
        const { 
            firstName, lastName, email, phone,
            hotelId, checkInDate, checkOutDate, 
            numberOfGuests, numberOfRooms, roomType, 
            pricePerNight 
        } = req.body;

        const connection = await pool.getConnection();
        await connection.beginTransaction();

        try {
            // Check if user exists by email, if not create one
            let [users] = await connection.execute(
                'SELECT user_id FROM users WHERE email = ?',
                [email]
            );

            let userId;
            if (users.length > 0) {
                userId = users[0].user_id;
            } else {
                const [result] = await connection.execute(
                    'INSERT INTO users (first_name, last_name, email, phone, password_hash) VALUES (?, ?, ?, ?, ?)',
                    [firstName, lastName, email, phone, 'guest_booking']
                );
                userId = result.insertId;
            }

            // Calculate nights and total price
            const checkIn = new Date(checkInDate);
            const checkOut = new Date(checkOutDate);
            const nights = Math.ceil((checkOut - checkIn) / (1000 * 60 * 60 * 24));
            const subtotal = pricePerNight * nights * numberOfRooms;
            const tax = subtotal * 0.1;
            const totalPrice = subtotal + tax;

            // Create booking
            const [bookingResult] = await connection.execute(
                `INSERT INTO bookings (
                    user_id, hotel_id, check_in_date, check_out_date,
                    number_of_guests, number_of_rooms, total_price, booking_status
                ) VALUES (?, ?, ?, ?, ?, ?, ?, 'pending')`,
                [userId, hotelId, checkInDate, checkOutDate, numberOfGuests, numberOfRooms, totalPrice]
            );

            const bookingId = bookingResult.insertId;

            // Get available rooms
            const [rooms] = await connection.execute(
                `SELECT r.room_id FROM rooms r
                 JOIN room_types rt ON r.room_type_id = rt.room_type_id
                 WHERE r.hotel_id = ? AND rt.type_name = ?
                 AND r.availability_status = 'available'
                 AND r.room_id NOT IN (
                     SELECT DISTINCT bd.room_id FROM booking_details bd
                     JOIN bookings b ON bd.booking_id = b.booking_id
                     WHERE b.check_in_date < ? AND b.check_out_date > ?
                     AND b.booking_status IN ('confirmed', 'pending')
                 )
                 LIMIT ?`,
                [hotelId, roomType, checkOutDate, checkInDate, numberOfRooms]
            );

            if (rooms.length < numberOfRooms) {
                throw new Error('Not enough rooms available');
            }

            // Add booking details for each room
            const [roomTypeInfo] = await connection.execute(
                'SELECT room_type_id FROM room_types WHERE type_name = ?',
                [roomType]
            );

            for (let room of rooms) {
                await connection.execute(
                    `INSERT INTO booking_details (
                        booking_id, room_id, room_type_id, price_per_night, number_of_nights, subtotal
                    ) VALUES (?, ?, ?, ?, ?, ?)`,
                    [bookingId, room.room_id, roomTypeInfo[0].room_type_id, pricePerNight, nights, subtotal / numberOfRooms]
                );
            }

            await connection.commit();
            connection.release();

            res.status(201).json({
                message: 'Booking created successfully',
                bookingId: bookingId,
                totalPrice: totalPrice,
                tax: tax
            });
        } catch (error) {
            await connection.rollback();
            connection.release();
            throw error;
        }
    } catch (error) {
        console.error(error);
        res.status(500).json({ error: error.message || 'Booking failed' });
    }
});

/**
 * GET /api/bookings/:bookingId
 * Get booking details
 */
app.get('/api/bookings/:bookingId', async (req, res) => {
    try {
        const { bookingId } = req.params;
        const connection = await pool.getConnection();

        const [bookings] = await connection.execute(
            `SELECT b.*, h.hotel_name, u.first_name, u.last_name, u.email, u.phone
             FROM bookings b
             JOIN hotels h ON b.hotel_id = h.hotel_id
             JOIN users u ON b.user_id = u.user_id
             WHERE b.booking_id = ?`,
            [bookingId]
        );

        if (bookings.length === 0) {
            connection.release();
            return res.status(404).json({ error: 'Booking not found' });
        }

        const booking = bookings[0];

        // Get booking details (rooms)
        const [details] = await connection.execute(
            `SELECT bd.*, rt.type_name, r.room_number
             FROM booking_details bd
             JOIN room_types rt ON bd.room_type_id = rt.room_type_id
             JOIN rooms r ON bd.room_id = r.room_id
             WHERE bd.booking_id = ?`,
            [bookingId]
        );

        booking.rooms = details;
        connection.release();
        res.json(booking);
    } catch (error) {
        console.error(error);
        res.status(500).json({ error: 'Failed to fetch booking' });
    }
});

// ============= PAYMENT ROUTES =============

/**
 * POST /api/payments
 * Process payment for booking
 */
app.post('/api/payments', async (req, res) => {
    try {
        const { bookingId, paymentAmount, paymentMethod, transactionId } = req.body;

        const connection = await pool.getConnection();

        // Create payment record
        const [result] = await connection.execute(
            `INSERT INTO payments (booking_id, payment_amount, payment_method, transaction_id, payment_status)
             VALUES (?, ?, ?, ?, 'completed')`,
            [bookingId, paymentAmount, paymentMethod, transactionId]
        );

        // Update booking status
        await connection.execute(
            'UPDATE bookings SET booking_status = ? WHERE booking_id = ?',
            ['confirmed', bookingId]
        );

        connection.release();
        res.status(201).json({
            message: 'Payment processed successfully',
            paymentId: result.insertId
        });
    } catch (error) {
        console.error(error);
        res.status(500).json({ error: 'Payment processing failed' });
    }
});

// ============= REVIEW ROUTES =============

/**
 * POST /api/reviews
 * Add a review for a completed booking
 */
app.post('/api/reviews', async (req, res) => {
    try {
        const { bookingId, userId, hotelId, rating, reviewText } = req.body;

        const connection = await pool.getConnection();

        const [result] = await connection.execute(
            `INSERT INTO reviews (booking_id, user_id, hotel_id, rating, review_text)
             VALUES (?, ?, ?, ?, ?)`,
            [bookingId, userId, hotelId, rating, reviewText]
        );

        connection.release();
        res.status(201).json({
            message: 'Review posted successfully',
            reviewId: result.insertId
        });
    } catch (error) {
        console.error(error);
        res.status(500).json({ error: 'Failed to post review' });
    }
});

/**
 * GET /api/hotels/:hotelId/reviews
 * Get all reviews for a hotel
 */
app.get('/api/hotels/:hotelId/reviews', async (req, res) => {
    try {
        const { hotelId } = req.params;
        const connection = await pool.getConnection();

        const [reviews] = await connection.execute(
            `SELECT r.*, u.first_name, u.last_name
             FROM reviews r
             JOIN users u ON r.user_id = u.user_id
             WHERE r.hotel_id = ?
             ORDER BY r.review_date DESC`,
            [hotelId]
        );

        connection.release();
        res.json(reviews);
    } catch (error) {
        console.error(error);
        res.status(500).json({ error: 'Failed to fetch reviews' });
    }
});

// ============= SEARCH ROUTE =============

/**
 * POST /api/search
 * Complex search with multiple filters
 */
app.post('/api/search', async (req, res) => {
    try {
        const { location, checkIn, checkOut, numberOfGuests, numberOfRooms, minRating } = req.body;

        const connection = await pool.getConnection();

        let query = `
            SELECT DISTINCT h.*, COUNT(DISTINCT r.room_id) as available_rooms
            FROM hotels h
            LEFT JOIN rooms r ON h.hotel_id = r.hotel_id
            WHERE r.availability_status = 'available'
        `;
        const params = [];

        if (location) {
            query += ' AND (h.location LIKE ? OR h.city LIKE ?)';
            params.push(`%${location}%`, `%${location}%`);
        }

        if (minRating) {
            query += ' AND h.rating >= ?';
            params.push(parseFloat(minRating));
        }

        // Filter by date availability
        if (checkIn && checkOut) {
            query += `
                AND r.room_id NOT IN (
                    SELECT DISTINCT bd.room_id FROM booking_details bd
                    JOIN bookings b ON bd.booking_id = b.booking_id
                    WHERE b.check_in_date < ? AND b.check_out_date > ?
                    AND b.booking_status IN ('confirmed', 'pending')
                )
            `;
            params.push(checkOut, checkIn);
        }

        query += ` GROUP BY h.hotel_id HAVING available_rooms >= ? ORDER BY h.rating DESC`;
        params.push(numberOfRooms);

        const [hotels] = await connection.execute(query, params);

        // Get amenities for each hotel
        for (let hotel of hotels) {
            const [amenities] = await connection.execute(
                `SELECT a.amenity_name FROM amenities a
                 JOIN hotel_amenities ha ON a.amenity_id = ha.amenity_id
                 WHERE ha.hotel_id = ?`,
                [hotel.hotel_id]
            );
            hotel.amenities = amenities.map(a => a.amenity_name);
        }

        connection.release();
        res.json(hotels);
    } catch (error) {
        console.error(error);
        res.status(500).json({ error: 'Search failed' });
    }
});

// Start Server
const PORT = process.env.PORT || 3000;
app.listen(PORT, () => {
    console.log(`Hotel Booking System API running on port ${PORT}`);
});

module.exports = app;
