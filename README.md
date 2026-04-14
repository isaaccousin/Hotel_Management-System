Hotel Management System

A full-featured, smart hotel management system designed to automate daily hotel operations including booking, check-in, check-out, billing, and administration.
Built with PHP, MySQL, JavaScript, and AJAX, this system delivers a modern, real-time experience similar to enterprise hotel platforms.

Overview

This system streamlines hotel operations by integrating:

Guest booking and reservation management
Real-time room availability tracking
Automated check-in and check-out
Billing, receipts, and refund handling
Role-based access (Admin, Manager, Staff)
Smart dashboard with analytics

It is designed to be:

Fast (AJAX-based, no page reloads)
Secure (hashed passwords, session control)
Multilingual (English / French support)
Data-driven (live stats and reporting)
How It Works (Smart Logic)
1. Room Lifecycle System

Each room moves through intelligent states:

Available → ready for booking
Booked → reserved but not checked-in
Occupied → guest checked-in
Maintenance → unavailable
Pending Payment → balance due

The system automatically updates room status based on actions:

Booking → sets room to booked
Check-in → sets room to occupied
Check-out → resets to available
2. Smart Booking Engine
Prevents double booking
Allows booking only if time slot is free
Keeps room available until booking start time
Supports short stays and extensions
3. Secure Authentication System
Passwords are encrypted using PHP password_hash()
Login verification via password_verify()
Session timeout (auto logout after inactivity)
Role-based permissions:
Admin → full access
Manager → limited financial access
Staff → operational tasks only
4. Billing and Revenue Engine
Tracks:
Total paid
Balance due
Refunds
Automatically updates:
Daily revenue
Weekly revenue
Monthly revenue
Yearly revenue

Refunds are deducted from revenue intelligently.

5. Smart Receipt System
Generates professional receipts for:
Booking
Check-in
Check-out
Refunds
Auto-print feature (no new tab)
Clean, professional layout
6. Real-Time Dashboard
Displays:
Room occupancy rate
Available vs booked rooms
Revenue analytics
Uses AJAX for live updates
No page refresh required
7. Multilingual System
Supports:
English
French
Language stored in session
Dynamic UI translation
8. UI and User Experience Features
SweetAlert2 notifications
Modal-based actions (no reloads)
Pagination and search filters
Responsive professional design
Tech Stack
Backend: PHP
Database: MySQL
Frontend: HTML5, CSS3, JavaScript
AJAX for real-time updates
Libraries: SweetAlert2, Bootstrap
System Workflow
Guest books a room
System checks availability
Room status becomes booked
Guest arrives and checks in
Room status becomes occupied
Payment is tracked (full or partial)
Guest checks out and receipt is generated
Room status returns to available
Security Features
Password hashing (bcrypt)
Session protection
Role-based access control
Admin verification system (special passcode)
Prepared statements (PDO) to prevent SQL injection
Advanced Features
Room extension with cost calculation
Refund management system
Audit trail (logs for all actions)
Export to Excel (reports)
Auto-refreshing data tables
Use Cases
Hotels
Guest Houses
Apartments
Short-term rentals
Future Improvements
Mobile app (React Native)
RFID key card integration
Email and SMS notifications
Online payments (Stripe, Klarna)
Cloud-based multi-property system
Author

Isaac Kalonji
Cloud Engineering Student | Software Technician | Full-Stack Developer
