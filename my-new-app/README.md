# Farmers Market App

This is a mobile application for connecting farmers with consumers for direct product sales.

## Server Setup

The app requires a backend server running on PHP with a MySQL database.

### Prerequisites

- XAMPP (or similar) with PHP 8.0+ and MySQL
- Node.js and npm or yarn

### Backend Setup

1. Ensure XAMPP is installed and Apache + MySQL services are running
2. Import the database schema from `farmersmarketdb.sql` into MySQL
3. The backend files should be in `c:\xampp\htdocs\capstone\my-new-app\api`

### Frontend Development

1. Install dependencies:

   ```
   npm install
   ```

   or

   ```
   yarn
   ```

2. Start the development server:

   ```
   npm start
   ```

   or

   ```
   yarn start
   ```

3. Configure the server connection:
   - By default, the app will try to auto-discover the server
   - Use the "Server Settings" tab to configure the server IP manually

## Server Connection

The app uses a smart server discovery mechanism:

- It will try recently used server addresses first
- Then common development IP addresses
- You can manually set the server IP in the server configuration screen

### Common development server addresses:

- `localhost` - For local development on iOS
- `10.0.2.2` - For Android emulators pointing to the host machine
- `192.168.x.x` - For devices on the same network as the server

## Files Structure

- `/api` - Backend PHP files
- `/app` - React Native frontend screens
- `/components` - Reusable React components
- `/services` - JavaScript services for API communication and business logic
