# Local Server Setup Instructions

## For Windows Users:

### Option 1: XAMPP (Recommended)
1. Download XAMPP from https://www.apachefriends.org/
2. Install XAMPP
3. Move your project folder to `C:\xampp\htdocs\your-project-name`
4. Start Apache and MySQL from XAMPP Control Panel
5. Access your site at `http://localhost/your-project-name`

### Option 2: WAMP
1. Download WAMP from http://www.wampserver.com/
2. Install WAMP
3. Move your project to `C:\wamp64\www\your-project-name`
4. Start WAMP services
5. Access your site at `http://localhost/your-project-name`

## Database Setup:
1. Open phpMyAdmin (usually at http://localhost/phpmyadmin)
2. Run the SQL script from `admin/includes/database-setup.sql`
3. This will create the necessary database and tables

## Testing:
- Access your contact page at `http://localhost/your-project-name/prgrammers-lab-contact.html`
- Fill out the form and test submission