# FAST-NUCES Chat Application

Link: http://22p9160.rf.gd/

## Purpose

A web-based chat and collaboration application designed for university students, facilitating communication and resource sharing within the campus community.

## Features

*   **User Authentication:** Secure registration and login system.
*   **User Profiles:** View and edit user profiles, including bio and profile picture.
*   **Timeline/Posts:** Create text posts with optional image uploads, view a feed of posts.
*   **Post Interactions:** Like and comment on posts.
*   **Direct Messaging:** Real-time one-on-one chat between users.
*   **Group Chats:** Create and participate in group conversations with file sharing (images, PDF).
*   **Forums:** Create forums, post topics, and engage in discussions.
*   **Resource Sharing:** Upload and download educational resources (PDFs) categorized by subject, type, batch, and campus.

## Technology Stack

*   **Backend:** PHP (Assumed 7+ compatibility)
*   **Database:** MySQL / MariaDB
*   **Frontend:** HTML5, CSS3, Vanilla JavaScript (ES6+)
*   **Note:** This application is built without the use of external frameworks or libraries (like jQuery, Bootstrap, React, Laravel, etc.).

## Configuration

Database connection details are managed in a configuration file.

1.  Locate the example configuration file: `chatapp/config.example.php`.
2.  **Rename or copy** this file to `chatapp/config.php`.
3.  Edit `chatapp/config.php` and replace the placeholder values with your actual database credentials:
    *   `DB_HOST`: Your database host (e.g., 'localhost' or a remote host address).
    *   `DB_NAME`: The name of the database you will use (e.g., 'web_lab').
    *   `DB_USER`: Your database username.
    *   `DB_PASS`: Your database password.
4.  **Important:** Ensure `chatapp/config.php` is added to your `.gitignore` file to prevent committing sensitive credentials. A `.gitignore` file is included in the project root for this purpose.

## Setup Instructions

### Prerequisites

*   A web server (e.g., Apache, Nginx) with PHP (7+ recommended).
*   A MySQL or MariaDB database server.
*   Access to a tool to manage the database (e.g., phpMyAdmin, command-line client).

### Local Development Setup

1.  **Clone/Download:** Obtain the project files.
2.  **Web Server:** Configure your web server to point to the project's root directory (or the `chatapp` subdirectory if you prefer). Ensure PHP is enabled.
3.  **Database:**
    *   Create a new database on your MySQL/MariaDB server (e.g., `web_lab`).
    *   Import the database schema using the provided consolidated script: `chatapp/setup.sql`. You can do this via phpMyAdmin's "Import" tab or using the command line:
        ```bash
        mysql -u YOUR_DB_USER -p YOUR_DB_NAME < chatapp/setup.sql
        ```
        (Replace `YOUR_DB_USER` and `YOUR_DB_NAME` accordingly).
4.  **Configuration:** Follow the steps in the **Configuration** section above to create and populate `chatapp/config.php` with your local database credentials.
5.  **Permissions:** Ensure the web server has write permissions for the `chatapp/uploads/` directory and its subdirectories (`profile`, `posts`, `resources`). On Linux/macOS, you might need to run:
    ```bash
    chmod -R 775 chatapp/uploads
    # You might also need to set the group ownership to the web server's group (e.g., www-data)
    # chown -R your_user:www-data chatapp/uploads
    ```
    (Consult your specific server setup documentation if needed).
6.  **Run the Application:**
    *   **Option A (Using a full web server like Apache/Nginx):** Ensure your configured web server is running. Access the application by navigating to the URL you configured (e.g., `http://localhost/` or `http://localhost/chatapp/`).
    *   **Option B (Using PHP's built-in server - for development only):**
        *   Navigate to the project's root directory in your terminal.
        *   Run the command: `php -S localhost:8000 -t chatapp/`
        *   Access the application in your browser at `http://localhost:8000`. You should be redirected to the login page. (Note: The `chatapp/` directory is specified as the document root using `-t`).

    Regardless of the method, you should be redirected to the login page (`login.php`).k

### Deployment (General Steps for Online Hosting)

Deploying to a live host follows similar principles but requires using the host's specific tools and credentials.

1.  **Choose Host:** Select a hosting provider that supports PHP and MySQL/MariaDB.
2.  **Upload Files:** Upload all project files (except potentially `chatapp/config.php` if you create it directly on the server) to the appropriate directory on your host (e.g., `public_html`, `htdocs`) using FTP or the host's file manager.
3.  **Database Setup:**
    *   Use the hosting control panel (e.g., cPanel) to create a new MySQL/MariaDB database and a database user. Note down the database name, username, password, and database host address (it's often *not* 'localhost' on shared hosting).
    *   Use the host's phpMyAdmin tool to import the `chatapp/setup.sql` script into the newly created database.
4.  **Configuration:** Create or upload the `chatapp/config.php` file in the `chatapp` directory on the server. Edit it to contain the correct database credentials provided by your host.
5.  **Permissions:** Use the host's file manager or FTP client to ensure the `chatapp/uploads/` directory (and its subdirectories: `profile`, `posts`, `resources`) are writable by the web server. Permissions like `755` or `775` are often required, but check your host's documentation.
6.  **Access:** Access your application using the domain or URL provided by your hosting provider (e.g., `http://yourdomain.com/` or `http://yourdomain.com/chatapp/`). You should be redirected to the login page.

## Usage

1.  **Register:** Navigate to the registration page (`register.php`) and create a new user account.
2.  **Login:** Use your registered credentials on the login page (`login.php`).
3.  **Dashboard:** After login, you will be directed to the main dashboard (`index.php` or `user/dashboard.php`) where you can see posts and create new ones.
4.  **Navigation:** Use the header or sidebar menus (depending on the page) to access different features like Profile, Messages, Groups, Forums, and Resources.
5.  **Interact:** Like/comment on posts, send direct messages, join/create groups, participate in forums, and upload/download resources.