🛒 Grocery Store Management System
A complete Grocery Store Management System built using PHP, MySQL, Bootstrap, HTML, CSS, and JavaScript. This project includes secure login functionality and provides features to manage products, customers, orders, returns, and billing—all through a simple and responsive admin dashboard.

🔐 Features

✅ Secure Login System (Admin only)
🛍️ Product Management (Add, update, delete products)
📦 Inventory Tracking
🧾 Order & Billing System with dynamic price calculation
🔄 Return Item System
📈 Sales History & Reports
🧑‍💼 Customer Management
🎨 Clean UI using Bootstrap
📊 Sortable Order History Table

🧰 Technologies Used

Frontend: HTML, CSS, Bootstrap, JavaScript
Backend: PHP 
Database: MySQL

⚙️ Setup Instructions
Clone the repository:

bash
Copy
Edit
git clone https://github.com/yourusername/grocery-store.git
cd grocery-store
Import the Database:

Open phpMyAdmin or any MySQL client.

Create a database named grocery_store.

Import the provided .sql file located in the project root or /database/ folder.

Configure Database Connection:

Open db_connect.php and update your database credentials:

php
Copy
Edit
$conn = mysqli_connect("localhost", "your_username", "your_password", "grocery_store");
Run the Project:

Start Apache and MySQL in XAMPP or your preferred local server.

Navigate to http://localhost/grocery-store/ in your browser.

🔑 Default Admin Credentials
Email: admin
Password: admin123
(You can change credentials directly in the database.)

🚀 Future Improvements

✅ Role-based access (Admin/Staff)
📱 Mobile-responsive enhancements
📦 Barcode or QR integration
📊 Graphical sales analytics

🤝 Contribution
Pull requests are welcome! Feel free to fork this repository and contribute to its improvement.

📃 License
This project is open-source and available under the MIT License.
