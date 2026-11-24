VEHICLE REGISTRATION SYSTEM - BOOTSTRAP 5 VERSION

REQUIREMENTS
- XAMPP with Apache and MySQL
- PHP 8.0+ recommended
- Internet connection to load Bootstrap 5.3.3 from jsDelivr CDN

INSTALLATION
1. Extract this folder into C:\xampp\htdocs\
   Example: C:\xampp\htdocs\vehicle_registration_bootstrap
2. Start Apache and MySQL in the XAMPP Control Panel.
3. Open http://localhost/phpmyadmin
4. Import schema.sql.
5. Check db_connect.php and update the MySQL port, username, or password if needed.
   The included settings use 127.0.0.1, port 3307, username root, and a blank password.
6. Open http://localhost/vehicle_registration_bootstrap/

BOOTSTRAP
- Uses Bootstrap 5.3.3 CSS and JavaScript bundle via CDN.
- Uses Bootstrap grid, responsive top navigation, cards, forms, alerts, buttons, badges, and responsive table.
- Dashboard and Register Vehicle navigation links are positioned at the top-right on desktop; on small screens they collapse into a menu button.
- assets/style.css contains the project's custom colors, sidebar, and layout styling.

FEATURES
- Dashboard counts
- Create, read, update, and delete vehicle registrations
- Search by plate number, owner, or make/model
- Prepared SQL statements and CSRF token checks
- Responsive interface

NOTE
This is a starter/local project. Before public deployment, add authentication,
role permissions, HTTPS, stronger operational logging, and production error handling.
The system does not connect to any government/LTO database or validate official
registration documents. Status is maintained by the operator.
