#!/bin/bash

echo "Creating test users for ESBTP-yAKRO application..."
php artisan esbtp:create-test-users

echo ""
echo "Test users have been created. You can now log in with these accounts:"
echo "SuperAdmin: superadmin@esbtp.ci / password123"
echo "Secretary: secretaire@esbtp.ci / password123"
echo "Student: etudiant@esbtp.ci / password123"
echo "Teacher: teacher@esbtp.ci / password123" 