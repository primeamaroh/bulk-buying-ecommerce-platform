# Bulk Buying Ecommerce Platform

A collaborative bulk buying platform where users can collectively purchase products at wholesale prices. The platform includes features for both users and administrators to manage bulk buying opportunities.

## Features

### User Features
- Browse active bulk buying opportunities
- View product details and join bulk orders
- Post product requests for bulk buying
- Vote on product requests with deposits
- Track orders and votes in user dashboard
- Cancel pending orders (with 5% fee)
- Comment and discuss on product requests

### Admin Features
- Upload and manage products
- Set product variations, quantities, and durations
- Manage admin fee percentage and shipping rates
- Review and approve product requests
- Monitor order statuses and fulfillment

### Product Management
- Minimum order quantity: 10 units
- Maximum order per user: 50% of total quantity
- Order status tracking (pending, active, fulfilled)
- Automatic order activation when quantity target is met
- Product request voting system (100 votes required for admin review)

## Technical Details

### Technologies Used
- PHP for backend logic
- MySQL for database
- Tailwind CSS for styling
- Font Awesome for icons
- Google Fonts for typography

### Database Structure
- users: Store user information and roles
- products: Manage product listings and details
- orders: Track user orders and statuses
- user_posts: Handle product requests and voting
- votes: Store user votes and deposits
- comments: Manage discussion threads
- admin_settings: Configure platform fees and rates

## Setup Instructions

1. Database Setup:
   ```sql
   # Import the schema
   mysql -u your_username -p < config/schema.sql
   ```

2. Configuration:
   - Update database credentials in `config/database.php`
   - Configure site settings in `config/config.php`

3. File Permissions:
   ```bash
   # Create and set permissions for uploads directory
   mkdir uploads
   chmod 777 uploads
   ```

4. Web Server:
   - Point your web server to the project root
   - Ensure PHP has write permissions for the uploads directory

## Directory Structure

```
├── admin/
│   ├── dashboard.php
│   ├── products/
│   │   └── add.php
│   └── settings.php
├── auth/
│   ├── login.php
│   ├── logout.php
│   └── signup.php
├── components/
│   ├── header.php
│   └── footer.php
├── config/
│   ├── config.php
│   ├── database.php
│   └── schema.sql
├── products/
│   ├── browse.php
│   ├── post.php
│   └── view.php
├── user/
│   └── dashboard.php
├── uploads/
│   ├── products/
│   └── posts/
└── index.php
```

## Security Features

- CSRF protection for forms
- Password hashing for user accounts
- Input sanitization and validation
- Role-based access control
- Secure file upload handling

## Business Rules

1. Order Management:
   - Minimum order quantity: 10 units
   - Maximum order: 50% of total quantity
   - 5% cancellation fee for pending orders

2. Product Requests:
   - Minimum R5 deposit for voting
   - 100 votes required for admin review
   - Deposits refundable if request rejected

3. Fees:
   - Configurable admin fee (default 5%)
   - Weight-based shipping calculation
   - Automatic fee calculations

## Contributing

1. Fork the repository
2. Create a feature branch
3. Commit your changes
4. Push to the branch
5. Create a Pull Request

## License

This project is licensed under the MIT License - see the LICENSE file for details.
