# TODO: Add Role Selection to Registration Form

## Tasks
- [x] Update register.html to add radio button selection for "User" and "Owner" roles
- [x] Ensure the form includes the 'role' field in the POST data
- [x] Test the registration form to verify role selection works and data is sent correctly
- [x] Verify backend handles the role appropriately (inserts into users or owners table)

## Notes
- Backend already supports 'user' and 'owner' roles in api/register.php
- Default role is 'user' if not provided
- No changes needed to register.js as it uses FormData from the form
- Database connection updated to use 'stayfind' database
- Registration form now allows users to choose between registering as a guest user or property owner

# TODO: Admin Management System

## Tasks
- [x] Create settings.html with admin management interface
- [x] Create assets/js/settings.js for frontend functionality
- [x] Create api/admin_register.php for creating new admin accounts
- [x] Create api/admin_list.php for listing all admins
- [x] Update includes/auth.php to support role-based access for admin roles
- [ ] Test admin creation functionality
- [ ] Test admin listing functionality
- [ ] Add edit and delete functionality for admin accounts

## Notes
- Only SuperAdmin and Manager roles can create/view admin accounts
- Admin passwords are stored as plain text (matching existing pattern)
- Supports all admin roles: Staff, Encoder, Manager, SuperAdmin

# TODO: Admin Dashboard

## Tasks
- [x] Create admin_dashboard.html with system overview and management features
- [x] Create assets/js/admin_dashboard.js to fetch and display data from api/get_dashboard_data.php
- [x] Add Approvals button in admin dashboard navigation
- [x] Implement role-based access (only admins can view admin dashboard)
- [x] Update login redirect to send admins to admin_dashboard.html
- [x] Test admin dashboard access and data loading
- [x] Test approvals functionality from admin dashboard

## Notes
- Admin dashboard provides system-wide stats: total rooms, bookings, pending approvals, active users
- Displays recent bookings table
- Approvals modal allows approving/rejecting pending bookings
- Login now redirects admins to admin_dashboard.html, others to dashboard.html
- Redirects non-admin users to regular dashboard if they try to access admin_dashboard.html
- Consistent styling with Tailwind CSS
