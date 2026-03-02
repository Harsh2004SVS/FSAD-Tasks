// Sample Hotel Data
const hotels = [
    {
        id: 1,
        name: 'Luxury Palace Hotel',
        location: 'New York',
        rating: 4.8,
        pricePerNight: 250,
        image: '🏨',
        amenities: ['WiFi', 'Pool', 'Gym', 'Restaurant', 'Spa'],
        rooms: {
            single: 250,
            double: 350,
            suite: 500
        }
    },
    {
        id: 2,
        name: 'Ocean View Resort',
        location: 'Miami',
        rating: 4.6,
        pricePerNight: 180,
        image: '🏖️',
        amenities: ['Beach Access', 'WiFi', 'Bar', '24/7 Service'],
        rooms: {
            single: 180,
            double: 280,
            suite: 420
        }
    },
    {
        id: 3,
        name: 'Mountain Retreat',
        location: 'Denver',
        rating: 4.5,
        pricePerNight: 150,
        image: '⛰️',
        amenities: ['Hiking Trails', 'Fireplace', 'WiFi', 'Restaurant'],
        rooms: {
            single: 150,
            double: 220,
            suite: 350
        }
    },
    {
        id: 4,
        name: 'City Center Comfort',
        location: 'Chicago',
        rating: 4.3,
        pricePerNight: 120,
        image: '🏙️',
        amenities: ['WiFi', 'Gym', 'Business Center', 'Café'],
        rooms: {
            single: 120,
            double: 180,
            suite: 280
        }
    },
    {
        id: 5,
        name: 'Desert Oasis Hotel',
        location: 'Phoenix',
        rating: 4.4,
        pricePerNight: 140,
        image: '🌴',
        amenities: ['Pool', 'Spa', 'WiFi', 'Restaurant'],
        rooms: {
            single: 140,
            double: 210,
            suite: 330
        }
    },
    {
        id: 6,
        name: 'Historic Heritage Inn',
        location: 'Boston',
        rating: 4.7,
        pricePerNight: 200,
        image: '🏛️',
        amenities: ['Historic Tours', 'WiFi', 'Restaurant', 'Library'],
        rooms: {
            single: 200,
            double: 310,
            suite: 480
        }
    }
];

// Global variables
let currentHotel = null;
let currentBooking = {
    checkIn: null,
    checkOut: null,
    nights: 0,
    rooms: 0
};

// Initialize
document.addEventListener('DOMContentLoaded', function() {
    setMinDate();
    displayAllHotels();
});

// Set minimum date to today
function setMinDate() {
    const today = new Date().toISOString().split('T')[0];
    document.getElementById('checkIn').setAttribute('min', today);
    document.getElementById('checkOut').setAttribute('min', today);
}

// Update checkout minimum date
document.getElementById('checkIn').addEventListener('change', function() {
    const checkInDate = new Date(this.value);
    checkInDate.setDate(checkInDate.getDate() + 1);
    const minCheckOut = checkInDate.toISOString().split('T')[0];
    document.getElementById('checkOut').setAttribute('min', minCheckOut);
});

// Validate dates
function validateDates() {
    const checkIn = new Date(document.getElementById('checkIn').value);
    const checkOut = new Date(document.getElementById('checkOut').value);

    if (checkIn >= checkOut) {
        alert('Check-out date must be after check-in date');
        return false;
    }

    return true;
}

// Calculate number of nights
function calculateNights() {
    const checkIn = new Date(document.getElementById('checkIn').value);
    const checkOut = new Date(document.getElementById('checkOut').value);
    const nights = Math.ceil((checkOut - checkIn) / (1000 * 60 * 60 * 24));
    return nights > 0 ? nights : 0;
}

// Display all hotels
function displayAllHotels() {
    const container = document.getElementById('hotelsContainer');
    container.innerHTML = '';

    if (hotels.length === 0) {
        container.innerHTML = '<div class="empty-state"><h3>No hotels found</h3><p>Try adjusting your search criteria.</p></div>';
        return;
    }

    hotels.forEach(hotel => {
        container.appendChild(createHotelCard(hotel));
    });
}

// Search hotels
function searchHotels() {
    if (!validateDates()) {
        return;
    }

    const destination = document.getElementById('destination').value.toLowerCase().trim();
    const container = document.getElementById('hotelsContainer');

    let filteredHotels = hotels;

    if (destination) {
        filteredHotels = hotels.filter(hotel =>
            hotel.location.toLowerCase().includes(destination)
        );
    }

    currentBooking.checkIn = document.getElementById('checkIn').value;
    currentBooking.checkOut = document.getElementById('checkOut').value;
    currentBooking.nights = calculateNights();
    currentBooking.rooms = parseInt(document.getElementById('rooms').value);

    container.innerHTML = '';

    if (filteredHotels.length === 0) {
        container.innerHTML = '<div class="empty-state"><h3>No hotels found</h3><p>No hotels available in ' + destination + '. Try another destination.</p></div>';
        return;
    }

    filteredHotels.forEach(hotel => {
        container.appendChild(createHotelCard(hotel));
    });
}

// Create hotel card
function createHotelCard(hotel) {
    const card = document.createElement('div');
    card.className = 'hotel-card';

    const totalPrice = hotel.pricePerNight * currentBooking.nights * currentBooking.rooms;
    const priceDisplay = currentBooking.nights > 0 
        ? `Total: $${totalPrice}` 
        : `$${hotel.pricePerNight}<span class="hotel-price-per">/night</span>`;

    card.innerHTML = `
        <div class="hotel-image">${hotel.image}</div>
        <div class="hotel-info">
            <div class="hotel-name">${hotel.name}</div>
            <div class="hotel-location">📍 ${hotel.location}</div>
            <div class="hotel-rating">⭐ ${hotel.rating}/5.0</div>
            <div class="hotel-price">${priceDisplay}</div>
            <ul class="amenities">
                ${hotel.amenities.map(amenity => `<li>${amenity}</li>`).join('')}
            </ul>
            <button class="book-btn" onclick="openBooking(${hotel.id})">Book Now</button>
        </div>
    `;

    return card;
}

// Open booking modal
function openBooking(hotelId) {
    // Validate search criteria
    if (!currentBooking.checkIn || !currentBooking.checkOut) {
        alert('Please search for hotels first by selecting check-in and check-out dates');
        return;
    }

    currentHotel = hotels.find(h => h.id === hotelId);

    if (!currentHotel) {
        alert('Hotel not found');
        return;
    }

    // Display hotel details in modal
    const hotelDetails = document.getElementById('hotelDetails');
    hotelDetails.innerHTML = `
        <h3>${currentHotel.name}</h3>
        <p><strong>Location:</strong> ${currentHotel.location}</p>
        <p><strong>Check-in:</strong> ${formatDate(currentBooking.checkIn)}</p>
        <p><strong>Check-out:</strong> ${formatDate(currentBooking.checkOut)}</p>
        <p><strong>Number of Nights:</strong> ${currentBooking.nights}</p>
        <p><strong>Number of Rooms:</strong> ${currentBooking.rooms}</p>
    `;

    // Reset form
    document.getElementById('bookingForm').reset();

    // Add event listener for room type change
    document.getElementById('roomType').addEventListener('change', updatePrice);

    // Show modal
    document.getElementById('bookingModal').classList.add('show');
}

// Update price based on room type
function updatePrice() {
    const roomType = document.getElementById('roomType').value;

    if (!roomType) {
        document.getElementById('priceDetails').innerHTML = '';
        return;
    }

    const pricePerNight = currentHotel.rooms[roomType];
    const subtotal = pricePerNight * currentBooking.nights * currentBooking.rooms;
    const tax = subtotal * 0.1; // 10% tax
    const total = subtotal + tax;

    const priceDetails = document.getElementById('priceDetails');
    priceDetails.innerHTML = `
        <p>
            <span>Price per night (${roomType} room):</span>
            <span>$${pricePerNight}</span>
        </p>
        <p>
            <span>Number of nights:</span>
            <span>${currentBooking.nights}</span>
        </p>
        <p>
            <span>Number of rooms:</span>
            <span>${currentBooking.rooms}</span>
        </p>
        <p>
            <span>Subtotal:</span>
            <span>$${subtotal.toFixed(2)}</span>
        </p>
        <p>
            <span>Tax (10%):</span>
            <span>$${tax.toFixed(2)}</span>
        </p>
        <p class="total">
            <span>Total Price:</span>
            <span>$${total.toFixed(2)}</span>
        </p>
    `;
}

// Close booking modal
function closeBooking() {
    document.getElementById('bookingModal').classList.remove('show');
    document.getElementById('priceDetails').innerHTML = '';
}

// Submit booking
function submitBooking(event) {
    event.preventDefault();

    // Validate form
    if (!validateBookingForm()) {
        return;
    }

    // Get form data
    const firstName = document.getElementById('firstName').value;
    const lastName = document.getElementById('lastName').value;
    const email = document.getElementById('email').value;
    const phone = document.getElementById('phone').value;
    const roomType = document.getElementById('roomType').value;

    // Calculate final price
    const pricePerNight = currentHotel.rooms[roomType];
    const subtotal = pricePerNight * currentBooking.nights * currentBooking.rooms;
    const tax = subtotal * 0.1;
    const total = subtotal + tax;

    // Prepare confirmation data
    const confirmationData = {
        hotelName: currentHotel.name,
        location: currentHotel.location,
        guestName: firstName + ' ' + lastName,
        email: email,
        phone: phone,
        checkIn: formatDate(currentBooking.checkIn),
        checkOut: formatDate(currentBooking.checkOut),
        nights: currentBooking.nights,
        roomType: roomType.charAt(0).toUpperCase() + roomType.slice(1),
        rooms: currentBooking.rooms,
        pricePerNight: pricePerNight,
        subtotal: subtotal.toFixed(2),
        tax: tax.toFixed(2),
        total: total.toFixed(2),
        bookingId: generateBookingId(),
        bookingDate: new Date().toISOString().split('T')[0]
    };

    // Show confirmation
    showConfirmation(confirmationData);

    // Close booking modal
    closeBooking();

    // In a real application, you would send this data to the server
    console.log('Booking submitted:', confirmationData);
}

// Validate booking form
function validateBookingForm() {
    const firstName = document.getElementById('firstName').value.trim();
    const lastName = document.getElementById('lastName').value.trim();
    const email = document.getElementById('email').value.trim();
    const phone = document.getElementById('phone').value.trim();
    const roomType = document.getElementById('roomType').value;

    if (!firstName || !lastName || !email || !phone || !roomType) {
        alert('Please fill in all fields');
        return false;
    }

    // Validate email
    const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
    if (!emailRegex.test(email)) {
        alert('Please enter a valid email address');
        return false;
    }

    // Validate phone
    const phoneRegex = /^[\d\s\-\+\(\)]+$/;
    if (!phoneRegex.test(phone) || phone.replace(/\D/g, '').length < 10) {
        alert('Please enter a valid phone number (at least 10 digits)');
        return false;
    }

    return true;
}

// Show confirmation modal
function showConfirmation(data) {
    const confirmationDetails = document.getElementById('confirmationDetails');
    confirmationDetails.innerHTML = `
        <div class="confirmation-details">
            <p><strong>Booking ID:</strong> ${data.bookingId}</p>
            <p><strong>Hotel:</strong> ${data.hotelName}</p>
            <p><strong>Location:</strong> ${data.location}</p>
            <p><strong>Guest Name:</strong> ${data.guestName}</p>
            <p><strong>Email:</strong> ${data.email}</p>
            <p><strong>Phone:</strong> ${data.phone}</p>
            <p><strong>Check-in:</strong> ${data.checkIn}</p>
            <p><strong>Check-out:</strong> ${data.checkOut}</p>
            <p><strong>Duration:</strong> ${data.nights} nights</p>
            <p><strong>Room Type:</strong> ${data.roomType}</p>
            <p><strong>Number of Rooms:</strong> ${data.rooms}</p>
            <p><strong>Price per Night:</strong> $${data.pricePerNight}</p>
            <hr style="margin: 15px 0; border: none; border-top: 1px solid #ddd;">
            <p><strong>Subtotal:</strong> $${data.subtotal}</p>
            <p><strong>Tax (10%):</strong> $${data.tax}</p>
            <p style="font-size: 1.1em; color: #667eea;"><strong>Total Amount:</strong> $${data.total}</p>
            <p style="margin-top: 15px; font-size: 0.9em; color: #999;">Booked on: ${data.bookingDate}</p>
        </div>
    `;

    document.getElementById('confirmationModal').classList.add('show');
}

// Close confirmation modal
function closeConfirmation() {
    document.getElementById('confirmationModal').classList.remove('show');
    // Reset booking data
    currentHotel = null;
    currentBooking = {
        checkIn: null,
        checkOut: null,
        nights: 0,
        rooms: 0
    };
    // Display all hotels again
    displayAllHotels();
}

// Generate unique booking ID
function generateBookingId() {
    return 'BK' + Date.now().toString().slice(-8);
}

// Format date for display
function formatDate(dateString) {
    const options = { year: 'numeric', month: 'long', day: 'numeric' };
    return new Date(dateString).toLocaleDateString('en-US', options);
}

// Close modal when clicking outside
window.onclick = function(event) {
    const bookingModal = document.getElementById('bookingModal');
    const confirmationModal = document.getElementById('confirmationModal');

    if (event.target === bookingModal) {
        closeBooking();
    }

    if (event.target === confirmationModal) {
        closeConfirmation();
    }
}
