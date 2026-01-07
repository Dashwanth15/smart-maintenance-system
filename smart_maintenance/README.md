Smart Maintenance System - final ready build

Setup:
1. Import database/database.sql (or run the ALTER TABLE commands if you already have a DB).
2. Update backend/db.php with your DB credentials.
3. Place the project in your web root and ensure PHP & MySQL are available.

Sample credentials:
- Admin: admin@gmail.com / admin123
- Technician: tech@gmail.com / tech123
- Student: student@gmail.com / student123

Notes:
- Assign button displays status only. Dropdowns disable after assigning.
- Polling runs every 5 seconds to update admin/technician/student pages.
