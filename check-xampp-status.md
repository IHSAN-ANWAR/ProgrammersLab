# XAMPP Troubleshooting Guide

## Check XAMPP Control Panel

1. **Open XAMPP Control Panel** (as Administrator)
2. **Check Services Status:**
   - Apache: Should show "Running" (green)
   - MySQL: Should show "Running" (green)

## If Services are NOT Running:

### Start Apache:
- Click "Start" button next to Apache
- If it fails, check port conflicts (usually port 80)

### Start MySQL:
- Click "Start" button next to MySQL
- If it fails, check port conflicts (usually port 3306)

## Common Port Conflicts:

### Apache (Port 80):
- Skype uses port 80
- IIS (Windows web server) uses port 80
- **Solution**: Stop these services or change Apache port

### MySQL (Port 3306):
- Other MySQL installations
- **Solution**: Stop other MySQL services

## Access phpMyAdmin:

Once both Apache and MySQL are running:
- Open browser
- Go to: `http://localhost/phpmyadmin`
- OR: `http://127.0.0.1/phpmyadmin`

## If phpMyAdmin Still Won't Open:

### Check Apache Error:
1. In XAMPP Control Panel, click "Logs" next to Apache
2. Look for error messages

### Alternative URLs to Try:
- `http://localhost:80/phpmyadmin`
- `http://localhost/xampp/phpmyadmin`
- `http://127.0.0.1/phpmyadmin`

### Manual Database Creation:
If phpMyAdmin won't work, you can create database via command line:
1. Open XAMPP Control Panel
2. Click "Shell" button
3. Type: `mysql -u root -p`
4. Press Enter (no password needed)
5. Type: `CREATE DATABASE contact_messages;`
6. Type: `exit`