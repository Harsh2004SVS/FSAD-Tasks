# Hotel Booking System - Advanced Configuration & Deployment

## 🔐 Security Best Practices

### 1. Password Security
Ensure strong password hashing in `server.js`:
```javascript
const hashedPassword = await bcryptjs.hash(password, 12);
```

### 2. Environment Variables
Never commit `.env` file. Add to `.gitignore`:
```
.env
node_modules/
.DS_Store
```

### 3. SQL Injection Prevention
Always use prepared statements (already done in `server.js`):
```javascript
// ✓ Safe - uses parameterized queries
const [users] = await connection.execute(
    'SELECT * FROM users WHERE email = ?',
    [email]
);

// ✗ Never do this
const query = `SELECT * FROM users WHERE email = '${email}'`;
```

### 4. CORS Configuration
Restrict CORS to your domain:
```javascript
app.use(cors({
    origin: 'https://yourdomain.com',
    credentials: true
}));
```

### 5. HTTPS/SSL
In production, always use HTTPS:
```javascript
const https = require('https');
const fs = require('fs');

const options = {
    key: fs.readFileSync('path/to/private.key'),
    cert: fs.readFileSync('path/to/certificate.crt')
};

https.createServer(options, app).listen(443);
```

### 6. JWT Token Security
Store JWT in secure httpOnly cookies:
```javascript
res.cookie('token', token, {
    httpOnly: true,
    secure: true,
    sameSite: 'Strict',
    maxAge: 24 * 60 * 60 * 1000
});
```

### 7. Rate Limiting
Prevent brute force attacks:
```javascript
const rateLimit = require('express-rate-limit');

const limiter = rateLimit({
    windowMs: 15 * 60 * 1000, // 15 minutes
    max: 100 // limit each IP to 100 requests per windowMs
});

app.use('/api/', limiter);
app.post('/api/users/login', limiter, loginHandler);
```

### 8. Input Validation
Use express-validator:
```javascript
const { body, validationResult } = require('express-validator');

app.post('/api/bookings',
    body('email').isEmail(),
    body('phone').isMobilePhone(),
    body('firstName').notEmpty().trim(),
    (req, res) => {
        const errors = validationResult(req);
        if (!errors.isEmpty()) {
            return res.status(400).json({ errors: errors.array() });
        }
        // Process booking
    }
);
```

---

## 🗄️ Database Optimization

### 1. Indexing Strategy
Indexes already added in `database.sql`:
```sql
-- Check indexes
SHOW INDEXES FROM hotels;
SHOW INDEXES FROM bookings;
```

Add more indexes for common queries:
```sql
-- Query: Find upcoming check-ins
ALTER TABLE bookings ADD INDEX idx_checkIn_status (check_in_date, booking_status);

-- Query: User bookings
ALTER TABLE bookings ADD INDEX idx_user_status (user_id, booking_status);
```

### 2. Query Optimization
Use `EXPLAIN` to analyze queries:
```sql
EXPLAIN SELECT h.* FROM hotels h
WHERE h.rating > 4.0
ORDER BY h.rating DESC LIMIT 10;
```

### 3. Connection Pooling
Already configured in `server.js`:
```javascript
const pool = mysql.createPool({
    connectionLimit: 10,
    queueLimit: 0
});
```

### 4. Caching Strategy
Add Redis for frequently accessed data:
```javascript
const redis = require('redis');
const client = redis.createClient();

// Cache hotel list
app.get('/api/hotels', async (req, res) => {
    const cacheKey = `hotels:${req.query.location}`;
    const cached = await client.get(cacheKey);
    
    if (cached) {
        return res.json(JSON.parse(cached));
    }
    
    // Fetch from database
    // ...
    
    // Cache for 1 hour
    await client.setex(cacheKey, 3600, JSON.stringify(hotels));
    res.json(hotels);
});
```

---

## 📊 Monitoring & Logging

### 1. Logging Configuration
```javascript
const winston = require('winston');

const logger = winston.createLogger({
    level: 'info',
    format: winston.format.json(),
    transports: [
        new winston.transports.File({ filename: 'error.log', level: 'error' }),
        new winston.transports.File({ filename: 'combined.log' })
    ]
});

app.use((req, res, next) => {
    logger.info(`${req.method} ${req.path}`);
    next();
});
```

### 2. Error Tracking
Using Sentry:
```javascript
const Sentry = require("@sentry/node");

Sentry.init({ dsn: "your-sentry-dsn" });
app.use(Sentry.Handlers.errorHandler());
```

### 3. Performance Monitoring
```javascript
app.use((req, res, next) => {
    const start = Date.now();
    res.on('finish', () => {
        const duration = Date.now() - start;
        console.log(`${req.method} ${req.path} - ${duration}ms`);
    });
    next();
});
```

---

## 🚀 Performance Optimization

### 1. Minify and Compress
```javascript
const compression = require('compression');
app.use(compression());
```

### 2. Asset Optimization
Minify CSS and JavaScript:
```bash
npm install --save-dev minify
minify styles.css > styles.min.css
minify script.js > script.min.js
```

### 3. Database Query Optimization
Check slow query log:
```sql
SET GLOBAL slow_query_log='ON';
SET GLOBAL long_query_time=2;
```

### 4. Pagination
For large result sets:
```javascript
app.get('/api/hotels', async (req, res) => {
    const page = req.query.page || 1;
    const limit = 10;
    const offset = (page - 1) * limit;
    
    const [hotels] = await connection.execute(
        'SELECT * FROM hotels LIMIT ? OFFSET ?',
        [limit, offset]
    );
    
    res.json({ hotels, page, hasMore: hotels.length === limit });
});
```

---

## 🧪 Testing

### 1. Unit Tests
```javascript
const request = require('supertest');
const app = require('./server');

describe('GET /api/hotels', () => {
    it('should return all hotels', async () => {
        const res = await request(app)
            .get('/api/hotels')
            .expect(200);
        
        expect(res.body).toBeInstanceOf(Array);
    });
});
```

### 2. Integration Tests
```javascript
describe('Booking Flow', () => {
    it('should create booking and process payment', async () => {
        // Register user
        const userRes = await request(app)
            .post('/api/users/register')
            .send({...userData});
        
        // Create booking
        const bookingRes = await request(app)
            .post('/api/bookings')
            .send({...bookingData});
        
        // Process payment
        const paymentRes = await request(app)
            .post('/api/payments')
            .send({...paymentData});
        
        expect(paymentRes.status).toBe(201);
    });
});
```

Run tests:
```bash
npm test
```

---

## 📦 Docker Deployment

### 1. Create Dockerfile
```dockerfile
FROM node:18-alpine

WORKDIR /app

COPY package*.json ./
RUN npm ci --only=production

COPY . .

EXPOSE 3000

CMD ["node", "server.js"]
```

### 2. Create docker-compose.yml
```yaml
version: '3.8'

services:
  app:
    build: .
    ports:
      - "3000:3000"
    environment:
      - DB_HOST=db
      - DB_USER=root
      - DB_PASSWORD=password
    depends_on:
      - db

  db:
    image: mysql:8.0
    environment:
      - MYSQL_ROOT_PASSWORD=password
      - MYSQL_DATABASE=hotel_booking_system
    volumes:
      - ./database.sql:/docker-entrypoint-initdb.d/init.sql
    ports:
      - "3306:3306"
```

### 3. Build and Run
```bash
docker-compose up --build
```

---

## ☁️ Cloud Deployment

### AWS Elastic Beanstalk
```bash
# Install AWS EB CLI
pip install awsebcli

# Initialize
eb init -p node.js-18 hotel-booking

# Create environment
eb create hotel-booking-prod

# Deploy
eb deploy
```

### Google Cloud Platform
```bash
# Deploy to Cloud Run
gcloud run deploy hotel-booking \
    --source . \
    --platform managed \
    --region us-central1 \
    --allow-unauthenticated
```

### Azure App Service
```bash
# Create resource group
az group create --name hotel-booking-rg --location eastus

# Create app service plan
az appservice plan create --name hotel-booking-plan \
    --resource-group hotel-booking-rg --sku B1 --is-linux

# Deploy
az webapp up --resource-group hotel-booking-rg \
    --name hotel-booking-app --runtime "node|18-lts"
```

---

## 📈 Scaling Strategies

### 1. Horizontal Scaling (Multiple Servers)
Use load balancer (Nginx):
```nginx
upstream backend {
    server api1.example.com;
    server api2.example.com;
    server api3.example.com;
}

server {
    listen 80;
    location / {
        proxy_pass http://backend;
    }
}
```

### 2. Database Replication
Master-slave setup:
```
Master Database (Writes)
    ↓
Slave Database (Reads)
```

### 3. Caching Layer
Add Redis between app and database:
```
Frontend → Express API → Redis Cache → MySQL Database
```

---

## 🔄 CI/CD Pipeline

### GitHub Actions Example
```yaml
name: Deploy Hotel Booking System

on:
  push:
    branches: [main]

jobs:
  test:
    runs-on: ubuntu-latest
    steps:
      - uses: actions/checkout@v2
      - uses: actions/setup-node@v2
        with:
          node-version: '18'
      - run: npm install
      - run: npm test

  deploy:
    needs: test
    runs-on: ubuntu-latest
    steps:
      - uses: actions/checkout@v2
      - name: Deploy to Heroku
        uses: akhileshns/heroku-deploy@v3.12.12
        with:
          heroku_api_key: ${{ secrets.HEROKU_API_KEY }}
          heroku_app_name: "hotel-booking-app"
          heroku_email: ${{ secrets.HEROKU_EMAIL }}
```

---

## 📊 Backup & Recovery

### MySQL Backup
```bash
# Full backup
mysqldump -u root -p hotel_booking_system > backup.sql

# Restore
mysql -u root -p hotel_booking_system < backup.sql

# Automated daily backup
0 2 * * * mysqldump -u root -p hotel_booking_system > /backups/db_$(date +\%Y\%m\%d).sql
```

### Database Replication
```sql
-- On master
SHOW MASTER STATUS;

-- On slave
CHANGE MASTER TO
  MASTER_HOST='master-ip',
  MASTER_USER='replication_user',
  MASTER_PASSWORD='password';

START SLAVE;
```

---

## 🎯 Performance Checklist

- [ ] Enable compression (gzip)
- [ ] Implement caching (Redis)
- [ ] Optimize database queries
- [ ] Add proper indexing
- [ ] Use connection pooling
- [ ] Implement pagination
- [ ] Enable HTTPS/SSL
- [ ] Set up CDN for static assets
- [ ] Configure logging
- [ ] Set up monitoring
- [ ] Implement rate limiting
- [ ] Add input validation
- [ ] Use environment variables
- [ ] Set up CI/CD pipeline
- [ ] Regular backup strategy

---

For more information, refer to the main [README.md](README.md) and [GETTING_STARTED.md](GETTING_STARTED.md) files.
