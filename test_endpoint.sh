#!/bin/bash

# This script is for testing the /shots endpoint.
# It will:
# 1. Re-initialize the database.
# 2. Seed the database with test data.
# 3. Start the PHP server.
# 4. Run a series of curl commands to test the endpoint.
# 5. Stop the PHP server.

echo "--- Deleting old database file ---"
rm -f database.sqlite

echo "--- Initializing database ---"
php database.php

echo -e "\n--- Seeding database with test data ---"
sqlite3 database.sqlite <<EOF
INSERT INTO users (id) VALUES ('testuser1');
INSERT INTO users (id) VALUES ('testuser2');
-- Made shot (brick=0)
INSERT INTO shots (arcQuality, shortQuality, longQuality, brick, timestamp, user_id) VALUES (0.8, 0.9, 0.7, 0, 1672531200, 'testuser1');
-- Missed shot (brick=1)
INSERT INTO shots (arcQuality, shortQuality, longQuality, brick, timestamp, user_id) VALUES (0.7, 0.8, 0.6, 1, 1672617600, 'testuser1');
-- Made shot (brick=null, qualities are good)
INSERT INTO shots (arcQuality, shortQuality, longQuality, brick, timestamp, user_id) VALUES (0.5, 0.0, 0.0, null, 1672617602, 'testuser1');
-- Missed shot (brick=null, shortQuality is bad)
INSERT INTO shots (arcQuality, shortQuality, longQuality, brick, timestamp, user_id) VALUES (0.5, -0.2, 0.5, null, 1672617601, 'testuser1');
-- Made shot for another user
INSERT INTO shots (arcQuality, shortQuality, longQuality, brick, timestamp, user_id) VALUES (0.9, 0.8, 0.9, 0, 1672531200, 'testuser2');
-- Made shot outside of time range
INSERT INTO shots (arcQuality, shortQuality, longQuality, brick, timestamp, user_id) VALUES (0.6, 0.7, 0.5, 0, 1675209600, 'testuser1');
EOF
echo "Database seeded."

echo -e "\n--- Starting PHP server in the background ---"
php -S localhost:8000 &
SERVER_PID=$!
# Wait for server to start
sleep 1

echo -e "\n--- Running tests ---"

echo -e "\nTest Case 1: Get all shots for testuser1 in a specific range (should return 4 shots)"
curl "http://localhost:8000/shots?userId=testuser1&startTime=1672531200&endTime=1672704000"
echo -e "\n"

echo "Test Case 2: Get only made shots for testuser1 (should return 2 shots)"
curl "http://localhost:8000/shots?userId=testuser1&startTime=1672531200&endTime=1672704000&made=true"
echo -e "\n"

echo "Test Case 3: Get shots for testuser2 (should return 1 shot)"
curl "http://localhost:8000/shots?userId=testuser2&startTime=1672531200&endTime=1672704000"
echo -e "\n"

echo "Test Case 4: Get shots for a user that doesn't exist (should return empty array)"
curl "http://localhost:8000/shots?userId=nonexistentuser&startTime=1672531200&endTime=1672704000"
echo -e "\n"

echo "Test Case 5: Get shots for a time range where there are no shots (should return empty array)"
curl "http://localhost:8000/shots?userId=testuser1&startTime=1609459200&endTime=1640995200"
echo -e "\n"

echo "Test Case 6: Test parameter validation (should return 400 Bad Request)"
curl -i "http://localhost:8000/shots?userId=testuser1&startTime=1672531200"
echo -e "\n"

echo "--- Stopping PHP server ---"
kill $SERVER_PID
echo "Server stopped."

echo -e "\n--- Testing complete ---"
