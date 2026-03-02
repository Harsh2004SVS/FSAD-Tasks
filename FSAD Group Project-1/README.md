# Hotel Booking System

A complete hotel booking system built with HTML, CSS, JavaScript, and SQL.

## 📋 Project Structure

```
hotel-booking-system/
├── index.html          # Main HTML file with booking interface
├── styles.css          # Complete styling for the application
├── script.js           # Frontend JavaScript logic
├── database.sql        # Database schema with sample data
└── README.md           # This file
```

## 🚀 Features

### Frontend Features
- **Hotel Search**: Search hotels by destination with date filtering
- **Hotel Display**: Browse available hotels with ratings, amenities, and pricing
- **Booking Form**: Complete booking form with guest information
- **Price Calculation**: Dynamic price calculation based on room type, nights, and number of rooms
- **Form Validation**: Input validation for dates, email, phone numbers
- **Booking Confirmation**: Instant booking confirmation with booking ID and details
- **Responsive Design**: Mobile-friendly interface for all devices
- **Beautiful UI**: Modern gradient design with smooth animations

### Backend Database (SQL)
- **users**: Store guest information
- **hotels**: Hotel details and information
- **rooms**: Individual room information
- **amenities**: Hotel amenities and services
- **bookings**: Booking records
- **booking_details**: Individual room assignments in bookings
- **payments**: Payment transaction records
- **reviews**: Guest reviews and ratings
- **discounts**: Discount codes and coupons
- **room_types**: Different categories of rooms

## 🎯 How to Use

### 1. **Open the Application**
   - Open `index.html` in a web browser
   - You'll see the hotel booking interface

### 2. **Search for Hotels**
   - Enter destination (e.g., "New York", "Miami")
   - Select check-in and check-out dates
   - Enter number of guests and rooms
   - Click "Search Hotels"

### 3. **View Hotel Details**
   - Browse available hotels with photos, ratings, and amenities
   - See the total price for your stay
   - Each hotel shows: name, location, rating, price, and amenities

### 4. **Make a Booking**
   - Click "Book Now" on any hotel
   - Fill in guest information (name, email, phone)
   - Select room type (Single, Double, or Suite)
   - Review the calculated total price
   - Click "Confirm Booking"

### 5. **Confirmation**
   - Receive a booking confirmation with:
     - Unique booking ID
     - All booking details
     - Guest information
     - Total amount charged
   - Print or save the confirmation

## 🗄️ Setting Up the Database

### Using MySQL/MariaDB:

1. **Open your MySQL client** (MySQL Workbench, phpMyAdmin, or command line)

2. **Run the SQL script**:
   ```sql
   source database.sql;
   ```
   
   Or copy and paste the entire contents of `database.sql` and execute it.

3. **Verify the database** was created:
   ```sql
   USE hotel_booking_system;
   SHOW TABLES;
   ```

### Database Tables:

| Table | Purpose |
|-------|---------|
| **users** | Guest/Customer information |
| **hotels** | Hotel details and metadata |
| **rooms** | Individual room records |
| **room_types** | Types of rooms (Single, Double, Suite) |
| **amenities** | Hotel amenities and services |
| **hotel_amenities** | Links hotels to their amenities |
| **bookings** | Booking records |
| **booking_details** | Specific rooms in each booking |
| **payments** | Payment transaction tracking |
| **reviews** | Guest reviews and ratings |
| **discounts** | Coupon codes and discounts |

## 💾 Sample Data

The SQL script includes sample data for:
- 6 hotels across different US cities
- 4 room types
- 12 amenity types
- Sample rooms for each hotel

## 🔌 Backend Integration

To integrate with a backend (Node.js, PHP, Python, etc.):

### 1. **API Endpoints Needed**:
   ```
   GET  /api/hotels/search         - Search hotels
   GET  /api/hotels/:id            - Get hotel details
   GET  /api/hotels/:id/rooms      - Get available rooms
   POST /api/bookings              - Create new booking
   GET  /api/bookings/:id          - Get booking details
   POST /api/payments              - Process payment
   ```

### 2. **Database Queries**:

   **Search Hotels by Location**:
   ```sql
   SELECT h.* FROM hotels h
   WHERE h.location LIKE '%destination%'
   ORDER BY h.rating DESC;
   ```

   **Get Available Rooms**:
   ```sql
   SELECT r.room_id, r.room_number, rt.type_name, r.price_per_night
   FROM rooms r
   JOIN room_types rt ON r.room_type_id = rt.room_type_id
   WHERE r.hotel_id = ? 
   AND r.availability_status = 'available'
   AND r.room_id NOT IN (
       SELECT room_id FROM booking_details bd
       JOIN bookings b ON bd.booking_id = b.booking_id
       WHERE b.hotel_id = ?
       AND b.booking_status != 'cancelled'
       AND b.check_in_date < ? AND b.check_out_date > ?
   );
   ```

   **Create Booking**:
   ```sql
   INSERT INTO bookings (user_id, hotel_id, check_in_date, check_out_date, 
   number_of_guests, number_of_rooms, total_price, booking_status)
   VALUES (?, ?, ?, ?, ?, ?, ?, 'pending');
   ```

### 3. **Modify JavaScript**:
   
   Update the `searchHotels()` function to fetch from API:
   ```javascript
   async function searchHotels() {
       const destination = document.getElementById('destination').value;
       const checkIn = document.getElementById('checkIn').value;
       const checkOut = document.getElementById('checkOut').value;
       
       const response = await fetch(
           `/api/hotels/search?destination=${destination}&checkIn=${checkIn}&checkOut=${checkOut}`
       );
       const hotels = await response.json();
       displayHotels(hotels);
   }
   ```

## 📱 Responsive Breakpoints

- **Desktop**: 1200px and above (3-column grid)
- **Tablet**: 768px to 1199px (2-column grid)
- **Mobile**: Below 768px (1-column layout)

## 🎨 Color Scheme

- **Primary Gradient**: Purple (#667eea) to Dark Purple (#764ba2)
- **Text**: Dark Gray (#333)
- **Background**: Light Gray (#f8f9fa)
- **Accent**: Green (#27ae60) for success, Blue (#3498db) for info

## ⚙️ Technologies Used

- **HTML5**: Semantic markup
- **CSS3**: Flexbox, Grid, Gradients, Animations
- **JavaScript**: ES6+, DOM Manipulation, Form Validation
- **SQL**: MySQL/MariaDB Database

## 🔐 Security Considerations

For production, implement:
- **Password Hashing**: bcrypt or similar
- **HTTPS**: Encrypt data in transit
- **Input Validation**: Server-side validation
- **SQL Injection Prevention**: Prepared statements
- **CSRF Protection**: Token-based protection
- **XSS Prevention**: Sanitize user input
- **Rate Limiting**: Prevent brute force attacks

## 📝 Future Enhancements

- [ ] User authentication and login system
- [ ] Payment gateway integration (Stripe, PayPal)
- [ ] Email confirmation notifications
- [ ] Guest reviews and ratings system
- [ ] Cancellation and modification policies
- [ ] Admin dashboard for hotel management
- [ ] Booking history for logged-in users
- [ ] Special offers and promotional codes
- [ ] Multi-language support
- [ ] Advanced search filters
- [ ] Map integration
- [ ] Photo gallery per hotel

## 📄 License

This project is open source and available for educational purposes.

## 👨‍💻 Author

Created as a full-stack practice project demonstrating HTML, CSS, JavaScript, and SQL integration.

---

**Happy Booking! 🏨✈️**
