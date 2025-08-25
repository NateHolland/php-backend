# php-backend

This is a simple PHP backend with an endpoint to upload basketball shot data. It uses an SQLite database to store the data.

## Requirements
- PHP 7.4 or higher.
- The PHP `pdo_sqlite` extension must be enabled.

## Setup

1.  **Initialize the Database:**
    Before running the application for the first time, you need to set up the SQLite database. Run the following command from the project's root directory:
    ```bash
    php database.php
    ```
    This command will create a `database.sqlite` file in the root directory, which contains the necessary `users` and `shots` tables.

## How to Run

1.  **Start the PHP built-in web server:**
    Open your terminal in the root directory of this project and run the following command:
    ```bash
    php -S localhost:8000
    ```
    The server will start, and you can access the API at `http://localhost:8000`.

## API Endpoint

### `POST /upload`

This endpoint accepts a JSON payload to record a basketball shot.

**Example Request:**
```bash
curl -X POST http://localhost:8000/upload \
-H "Content-Type: application/json" \
-d '{
  "arcQuality": 0.85,
  "shortQuality": 0.92,
  "longQuality": 0.78,
  "brick": false,
  "userId": "user123"
}'
```

**Behavior:**
- The backend will start a database transaction.
- It checks if the `userId` exists in the `users` table. If the user does not exist, a new record is created for them.
- A new record is added to the `shots` table with the provided data and a server-generated timestamp.
- If successful, the transaction is committed, and the API returns a `201 Created` response containing the ID of the new shot record.
- If any step fails, the transaction is rolled back, and a `500` error is returned.
