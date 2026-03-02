# Hotel Booking System - Getting Started Guide

## 🎯 Quick Start (Frontend Only)

### Step 1: Open the Application
1. Open the `index.html` file in your web browser
2. You'll see the hotel booking interface immediately
3. No backend setup needed for the frontend to work with sample data

### Step 2: Use the Application
1. **Search Hotels**: 
   - Enter a destination (try "New York", "Miami", "Denver", "Chicago", "Phoenix", or "Boston")
   - Select check-in and check-out dates
   - Enter number of guests and rooms
   - Click "Search Hotels"

2. **Browse Results**:
   - View hotels with ratings and amenities
   - See dynamic pricing based on dates selected

3. **Make a Booking**:
   - Click "Book Now" on any hotel
   - Fill in guest information
   - Select room type
   - Review pricing
   - Click "Confirm Booking"

4. **Get Confirmation**:
   - Receive booking ID and confirmation details
   - Booking is stored in browser console (in real app, sent to database)

---

## 🗄️ Database Setup (SQL)

### Prerequisites:
- MySQL Server installed
- MySQL client or GUI tool (MySQL Workbench, phpMyAdmin, DBeaver, etc.)

### Option 1: Using MySQL Command Line
```bash
mysql -u root -p
```

Then run:
```sql
source /path/to/database.sql;
```

### Option 2: Using MySQL Workbench
1. Open MySQL Workbench
2. Create a new SQL Script
3. Copy and paste the contents of `database.sql`
4. Execute the script

### Option 3: Using phpMyAdmin
1. Login to phpMyAdmin
2. Click "New" to create a new database
3. Name it `hotel_booking_system`
4. Go to the Import tab
5. Select `database.sql` file
6. Click Import

### Verify Database Creation:
```bash
mysql -u root -p hotel_booking_system -e "SHOW TABLES;"
```

You should see these tables:
- users
- hotels
- rooms
- room_types
- amenities
- hotel_amenities
- bookings
- booking_details
- payments
- reviews
- discounts

---

## 🔌 Backend Setup (Node.js + Express)

### Prerequisites:
- Node.js (v14 or higher)
- npm or yarn
- MySQL database (from previous step)

### Step 1: Install Dependencies
```bash
npm install
```

This will install:
- express (web framework)
- mysql2 (database driver)
- cors (cross-origin requests)
- bcryptjs (password hashing)
- jsonwebtoken (authentication)
- dotenv (environment variables)

### Step 2: Configure Environment Variables
1. Copy `.env.example` to `.env`
   ```bash
   cp .env.example .env
   ```

2. Edit `.env` file with your values:
   ```
   DB_HOST=localhost
   DB_USER=root
   DB_PASSWORD=your_mysql_password
   PORT=3000
   JWT_SECRET=your_secret_key
   ```

### Step 3: Start the Server
**Development mode (with auto-reload):**
```bash
npm run dev
```

**Production mode:**
```bash
npm start
```

The server will run at: `http://localhost:3000`

### Available API Endpoints:

#### Hotels
- `GET /api/hotels` - Get all hotels
- `GET /api/hotels?location=New York` - Search by location
- `GET /api/hotels/:hotelId` - Get hotel details
- `GET /api/hotels/:hotelId/rooms?checkIn=2024-12-20&checkOut=2024-12-25` - Get available rooms

#### Users
- `POST /api/users/register` - Register new user
- `POST /api/users/login` - Login user

#### Bookings
- `POST /api/bookings` - Create new booking
- `GET /api/bookings/:bookingId` - Get booking details

#### Payments
- `POST /api/payments` - Process payment

#### Reviews
- `GET /api/hotels/:hotelId/reviews` - Get hotel reviews
- `POST /api/reviews` - Add new review

#### Search
- `POST /api/search` - Advanced search with filters

---

## 🔗 Connect Frontend to Backend

### Step 1: Update JavaScript
Modify `script.js` to use API endpoints:

```javascript
// Replace searchHotels() function with:
async function searchHotels() {
    if (!validateDates()) return;

    const destination = document.getElementById('destination').value;
    const checkIn = document.getElementById('checkIn').value;
    const checkOut = document.getElementById('checkOut').value;
    const guests = document.getElementById('guests').value;
    const rooms = document.getElementById('rooms').value;

    try {
        const url = `http://localhost:3000/api/hotels?location=${destination}`;
        const response = await fetch(url);
        const hotels = await response.json();

        currentBooking.checkIn = checkIn;
        currentBooking.checkOut = checkOut;
        currentBooking.nights = calculateNights();
        currentBooking.rooms = parseInt(rooms);

        const container = document.getElementById('hotelsContainer');
        container.innerHTML = '';

        if (hotels.length === 0) {
            container.innerHTML = '<div class="empty-state"><h3>No hotels found</h3></div>';
            return;
        }

        hotels.forEach(hotel => {
            container.appendChild(createHotelCard(hotel));
        });
    } catch (error) {
        console.error('Error:', error);
        alert('Failed to search hotels');
    }
}
```

### Step 2: Update Booking Submission
```javascript
async function submitBooking(event) {
    event.preventDefault();

    if (!validateBookingForm()) return;

    const firstName = document.getElementById('firstName').value;
    const lastName = document.getElementById('lastName').value;
    const email = document.getElementById('email').value;
    const phone = document.getElementById('phone').value;
    const roomType = document.getElementById('roomType').value;

    const pricePerNight = currentHotel.rooms[roomType];
    const bookingData = {
        firstName,
        lastName,
        email,
        phone,
        hotelId: currentHotel.id,
        checkInDate: currentBooking.checkIn,
        checkOutDate: currentBooking.checkOut,
        numberOfGuests: document.getElementById('guests').value,
        numberOfRooms: currentBooking.rooms,
        roomType,
        pricePerNight
    };

    try {
        const response = await fetch('http://localhost:3000/api/bookings', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify(bookingData)
        });

        const result = await response.json();
        
        if (response.ok) {
            showConfirmation(result);
            closeBooking();
        } else {
            alert('Booking failed: ' + result.error);
        }
    } catch (error) {
        console.error('Error:', error);
        alert('Failed to create booking');
    }
}
```

### Step 3: Enable CORS
The `server.js` already has CORS enabled, so requests from the frontend should work.

---

## 📝 Sample Requests (Using cURL or Postman)

### Search Hotels
```bash
curl -X GET "http://localhost:3000/api/hotels?location=Miami&minRating=4"
```

### Get Hotel Details
```bash
curl -X GET "http://localhost:3000/api/hotels/2"
```

### Get Available Rooms
```bash
curl -X GET "http://localhost:3000/api/hotels/1/rooms?checkIn=2024-12-20&checkOut=2024-12-25&roomType=Double%20Room"
```

### Create Booking
```bash
curl -X POST "http://localhost:3000/api/bookings" \
  -H "Content-Type: application/json" \
  -d '{
    "firstName": "John",
    "lastName": "Doe",
    "email": "john@example.com",
    "phone": "555-0123",
    "hotelId": 1,
    "checkInDate": "2024-12-20",
    "checkOutDate": "2024-12-25",
    "numberOfGuests": 2,
    "numberOfRooms": 1,
    "roomType": "Double Room",
    "pricePerNight": 350
  }'
```

### User Registration
```bash
curl -X POST "http://localhost:3000/api/users/register" \
  -H "Content-Type: application/json" \
  -d '{
    "firstName": "Jane",
    "lastName": "Smith",
    "email": "jane@example.com",
    "phone": "555-0456",
    "password": "SecurePassword123!"
  }'
```

---

## 🚀 Deployment

### Deploy to Heroku
1. Install Heroku CLI
2. Create `Procfile`:
   ```
   web: node server.js
   ```
3. Deploy:
   ```bash
   heroku login
   heroku create your-app-name
   heroku addons:create cleardb:ignite
   git push heroku main
   ```

### Deploy to AWS
Use Elastic Beanstalk or EC2 with RDS for MySQL

### Deploy to Azure
Use App Service with Azure Database for MySQL

---

## 🐛 Troubleshooting

### Database Connection Error
- Check MySQL is running
- Verify credentials in `.env`
- Ensure database exists:
  ```bash
  mysql -u root -p -e "SHOW DATABASES;"
  ```

### Port Already in Use
Change port in `.env`:
```
PORT=3001
```

### CORS Error
- Make sure server is running
- Check frontend URL matches CORS configuration
- Add frontend URL to CORS in `server.js` if needed:
  ```javascript
  app.use(cors({
    origin: 'http://your-frontend-url'
  }));
  ```

### Module Not Found
Reinstall dependencies:
```bash
rm -rf node_modules package-lock.json
npm install
```

---

## 📚 Additional Resources

- [Express.js Documentation](https://expressjs.com/)
- [MySQL Documentation](https://dev.mysql.com/doc/)
- [MDN JavaScript Guide](https://developer.mozilla.org/en-US/docs/Web/JavaScript)
- [REST API Best Practices](https://restfulapi.net/)

---

## ✅ Checklist

- [ ] Frontend works (open index.html in browser)
- [ ] MySQL installed and running
- [ ] Database created with `database.sql`
- [ ] Node.js and npm installed
- [ ] Dependencies installed (`npm install`)
- [ ] `.env` file created and configured
- [ ] Server runs without errors (`npm start`)
- [ ] API endpoints respond to requests
- [ ] Frontend connected to backend
- [ ] Booking flow works end-to-end

---

**Happy Coding! 🎉**
