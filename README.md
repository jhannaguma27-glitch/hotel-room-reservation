# Hotel Reservation System

A full-stack PHP web application for hotel room reservations featuring separate user and admin interfaces with comprehensive booking management capabilities.

## 🏨 Overview

This hotel reservation system provides a complete solution for managing hotel bookings, room inventory, and customer relationships. Built with PHP and MySQL, it offers both customer-facing booking functionality and administrative management tools.

## ✨ Features

### 👤 User Features
- **Room Browsing**: View available room types with detailed descriptions and pricing
- **Smart Search**: Filter rooms by check-in/check-out dates and availability
- **User Management**: Secure registration and login system
- **Online Booking**: Complete reservation process with real-time availability
- **Reservation History**: View past and current bookings
- **Cancellation**: Cancel reservations with proper status updates
- **Profile Management**: Update personal information and preferences

### 🔧 Admin Features
- **Analytics Dashboard**: Real-time statistics and booking insights
- **Reservation Management**: View, update, and manage all bookings
- **Room Inventory**: Add new rooms and update room status/availability
- **Room Type Management**: Create and manage different room categories
- **User Administration**: Activate/deactivate user accounts
- **Activity Logging**: Track administrative actions and changes

## 🚀 Quick Start

### Prerequisites
- PHP 7.4 or higher
- MySQL 5.7 or higher
- Web server (Apache/Nginx) or PHP built-in server

### Installation

1. **Clone the Repository**
   ```bash
   git clone <repository-url>
   cd hotel
   ```

2. **Database Setup**
   ```bash
   mysql -u root -p < database.sql
   ```

3. **Configure Database Connection**
   
   Copy the example configuration:
   ```bash
   cp config/database.example.php config/database.php
   ```
   
   Edit `config/database.php` with your database credentials:
   ```php
   private $host = 'localhost';
   private $db_name = 'hotel_reservation_system';
   private $username = 'your_username';
   private $password = 'your_password';
   ```

4. **Start the Application**
   ```bash
   php -S localhost:8000
   ```

5. **Access the Application**
   - **Main Site**: http://localhost:8000
   - **Admin Panel**: http://localhost:8000/admin/login.php

## 🔐 Default Credentials

### Admin Access
- **Username**: `admin`
- **Password**: `admin123`

> ⚠️ **Security Note**: Change the default admin password immediately after first login.

## 📁 Project Structure

```
hotel/
├── 📁 admin/                    # Admin panel files
│   ├── dashboard.php           # Admin dashboard with statistics
│   ├── login.php              # Admin authentication
│   ├── logout.php             # Admin session termination
│   ├── reservations.php       # Reservation management
│   ├── rooms.php              # Room inventory management
│   ├── room_types.php         # Room type configuration
│   └── users.php              # User account management
├── 📁 config/                   # Configuration files
│   ├── database.php           # Database connection settings
│   └── database.example.php   # Example configuration
├── 📁 user/                     # User-specific functionality
│   ├── cancel_reservation.php # Reservation cancellation
│   ├── dashboard.php          # User dashboard redirect
│   └── profile.php            # User profile management
├── book.php                   # Room booking interface
├── database.sql               # Database schema and initial data
├── index.php                  # Main homepage and room search
├── login.php                  # User authentication
├── logout.php                 # User session termination
├── register.php               # User registration
└── README.md                  # Project documentation
```

## 🗄️ Database Schema

The application uses a normalized database structure with 9 core tables:

| Table | Purpose |
|-------|---------|
| `admins` | Administrative user accounts |
| `users` | Customer accounts and profiles |
| `room_types` | Room categories and pricing |
| `rooms` | Individual room inventory |
| `reservations` | Booking records and status |
| `payments` | Payment transaction history |
| `reviews` | Customer feedback and ratings |
| `notifications` | User notification system |
| `admin_logs` | Administrative activity tracking |

### Key Relationships
- Users can have multiple reservations
- Rooms belong to specific room types
- Reservations link users to rooms with date ranges
- Payments are associated with reservations

## 🔒 Security Features

- **Password Security**: Bcrypt hashing with `password_hash()`
- **SQL Injection Prevention**: Prepared statements for all database queries
- **Session Management**: Secure session-based authentication
- **Input Sanitization**: XSS prevention using `htmlspecialchars()`
- **Role-Based Access**: Separate admin and user permission levels
- **CSRF Protection**: Form token validation (recommended for production)

## 🛠️ Development

### Adding New Features

1. **New Room Types**: Use admin panel → Room Types → Add New
2. **Custom Fields**: Modify database schema and update forms
3. **Payment Integration**: Extend the payments table and booking flow
4. **Email Notifications**: Implement SMTP configuration

### Code Standards
- Follow PSR-12 coding standards
- Use prepared statements for all database operations
- Implement proper error handling and logging
- Validate and sanitize all user inputs

## 🚀 Production Deployment

### Security Checklist
- [ ] Change default admin credentials
- [ ] Use environment variables for database credentials
- [ ] Enable HTTPS/SSL certificates
- [ ] Configure proper file permissions
- [ ] Set up regular database backups
- [ ] Implement rate limiting
- [ ] Add CSRF protection tokens

### Performance Optimization
- Enable PHP OPcache
- Configure MySQL query caching
- Implement connection pooling
- Add CDN for static assets
- Use proper database indexing

## 🐛 Troubleshooting

### Common Issues

**Database Connection Failed**
- Verify MySQL service is running
- Check database credentials in `config/database.php`
- Ensure database exists and is accessible

**Admin Login Not Working**
- Verify admin user exists in database
- Check password hash in `admins` table
- Clear browser cache and cookies

**Booking Errors**
- Check room availability in database
- Verify date format and validation
- Ensure proper session management

## 📝 License

This project is open source and available under the [MIT License](LICENSE).

## 🤝 Contributing

1. Fork the repository
2. Create a feature branch (`git checkout -b feature/new-feature`)
3. Commit changes (`git commit -am 'Add new feature'`)
4. Push to branch (`git push origin feature/new-feature`)
5. Create a Pull Request

## 📞 Support

For issues and questions:
- Create an issue in the repository
- Check existing documentation
- Review troubleshooting section

---

**Last Updated**: November 2024
