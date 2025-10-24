# 🛍️ ShopMaster PHP Native API

A complete **E-Commerce RESTful API** built using **PHP Native**, **PDO**, and **JWT Authentication**.  
This project handles everything from user authentication, product & category management, to cart checkout, coupons, and orders — all via secure JSON-based APIs.

---

## 🚀 Features

✅ User Authentication (Register / Login / JWT)  
✅ Role-based Access Control (Admin / User)  
✅ Profile Management (View / Update)  
✅ Categories & Products CRUD  
✅ Cart Management (Add / Update / Delete / View)  
✅ Checkout Process (Subtotal, Discount, Shipping, Total)  
✅ Coupon System (Create, Update, Delete, Apply)  
✅ Shipping Methods (CRUD + Apply)  
✅ Orders System (Show / Update / Delete)  
✅ Dashboard (Overview / Daily Sales / Top Products)  
✅ Unified API Responses (via helper)  
✅ Secure PDO Connection + Error Handling

---

## 🧩 Project Structure

ShopMaster_PHP_Native/
- auth/
  - register.php
  - login.php
  - validate_token.php
  - profile/
    - show.php
    - update.php
- category/
  - create.php
  - list.php
  - update.php
  - delete.php
- product/
  - create.php
  - list.php
  - show.php
  - update.php
  - delete.php
- cart/
  - add.php
  - view.php
  - update_quantity.php
  - delete.php
  - checkout.php
- coupon/
  - create.php
  - list.php
  - update.php
  - delete.php
  - apply.php
- shipping/
  - create.php
  - list.php
  - update.php
  - delete.php
  - setShipping.php
- orders/
  - show.php
  - update_status.php
  - delete.php
- admin/
  - dashboard/
    - overview.php
    - daily_sales.php
    - top_products.php
- conn/
  - db.php
- helpers/
  - response_helper.php
- vendor/
  - autoload.php

---

## ⚙️ Installation & Setup

1. Clone the project:
   ```bash
   git clone https://github.com/m77mdabdo/ShopMaster_PHP_Native.git
   ```

2. Create the database (e.g. `shopmaster_db`) in phpMyAdmin and import tables (users, products, categories, carts, orders, etc.)

3. Configure the database connection in `conn/db.php`:
   ```php
   $host = "localhost";
   $dbname = "shopmaster_db";
   $username = "root";
   $password = "";
   ```

4. Install JWT library using Composer:
   ```bash
   composer require firebase/php-jwt
   ```

5. Run XAMPP and access:
   ```
   http://localhost/ShopMaster_PHP_Native/
   ```

---

## 🔑 Authentication Endpoints

- POST `/auth/register.php` → Register a new user  
- POST `/auth/login.php` → Login and receive JWT token  
- GET `/auth/profile/show.php` → Get user profile  
- PUT `/auth/profile/update.php` → Update user profile  

> ⚠️ Use header: `Authorization: Bearer <token>`

---

## 🏷️ Category Endpoints

- GET `/category/list.php` → List all categories  
- POST `/category/create.php` → Create new category *(Admin only)*  
- PUT `/category/update.php?id={id}` → Update a category *(Admin only)*  
- DELETE `/category/delete.php?id={id}` → Delete category *(Admin only)*  

---

## 📦 Product Endpoints

- GET `/product/list.php` → List all products  
- GET `/product/show.php?id={id}` → Get product details  
- POST `/product/create.php` → Add new product *(Admin only)*  
- PUT `/product/update.php?id={id}` → Update product *(Admin only)*  
- DELETE `/product/delete.php?id={id}` → Delete product *(Admin only)*  

---

## 🛒 Cart Endpoints

- POST `/cart/add.php` → Add product to cart  
- GET `/cart/view.php` → View user cart  
- PUT `/cart/update_quantity.php` → Update item quantity  
- DELETE `/cart/delete.php?id={id}` → Remove item from cart  
- POST `/cart/checkout.php` → Checkout cart and create order  

---

## 🧧 Coupon Endpoints

- GET `/coupon/list.php` → List all coupons *(Admin)*  
- POST `/coupon/create.php` → Create new coupon *(Admin)*  
- PUT `/coupon/update.php?id={id}` → Update coupon *(Admin)*  
- DELETE `/coupon/delete.php?id={id}` → Delete coupon *(Admin)*  
- POST `/coupon/apply.php` → Apply coupon to cart *(User)*  

---

## 🚚 Shipping Endpoints

- GET `/shipping/list.php` → List all shipping methods  
- POST `/shipping/create.php` → Add shipping method *(Admin)*  
- PUT `/shipping/update.php?id={id}` → Update shipping method *(Admin)*  
- DELETE `/shipping/delete.php?id={id}` → Delete shipping method *(Admin)*  
- POST `/shipping/setShipping.php` → Assign shipping to cart  

---

## 🧾 Orders Endpoints

- GET `/orders/show.php` → List all orders for user  
- PUT `/orders/update_status.php` → Update order status *(Admin)*  
- DELETE `/orders/delete.php?id={id}` → Delete order *(Admin)*  

---

## 📊 Dashboard Endpoints (Admin Only)

- GET `/admin/dashboard/overview.php` → Get overall statistics  
- GET `/admin/dashboard/daily_sales.php` → Get daily/weekly/monthly sales report  
- GET `/admin/dashboard/top_products.php` → Get top selling products  

---

## 🧠 Technologies Used

- PHP (Native)
- MySQL (PDO)
- JWT Authentication
- Composer Autoload
- JSON REST APIs
- Postman Testing

---

## 👤 Author

**Ahmed Abdo**  
Back-End Developer | PHP & Laravel Specialist  
📧 [mohamedabdo2002815@gmail.com](mohamedabdo2002815@gmail.com)

---

## 🪪 License

This project is open-source and available under the MIT License.
