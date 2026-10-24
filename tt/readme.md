# Ứng dụng Tích hợp Generative AI vào PHP Backend (cURL & PDO)

Đồ án môn học / Bài tập lớn thực hiện tích hợp trí tuệ nhân tạo (Google Gemini API) vào hệ thống PHP Backend[cite: 7], sử dụng **PHP cURL** để giao tiếp API[cite: 7] và **PHP PDO** để quản lý cơ sở dữ liệu MySQL[cite: 7].

---

## 🚀 Các tính năng chính
- **Giao diện Chat trực tuyến:** Thiết kế hiện đại, trực quan giống Google AI / Gemini (có khung chat bong bóng và sidebar lịch sử trò chuyện).
- **Tích hợp cURL Backend:** Gửi request phương thức `POST` đóng gói JSON payload an toàn tới Google Gemini API[cite: 7].
- **Lưu trữ CSDL qua PDO:** Sử dụng PDO Prepared Statements để lưu vết toàn bộ lịch sử hỏi đáp (Prompt, Response, Model, Thời gian) vào MySQL[cite: 7].

---

## 🛠 Yêu cầu hệ thống (Prerequisites)
- Máy chủ Web cục bộ hỗ trợ PHP (Khuyến nghị dùng **XAMPP** từ phiên bản PHP 8.0 trở lên).
- Trình quản lý cơ sở dữ liệu **MySQL / phpMyAdmin**.
- Một **Google Gemini API Key** hợp lệ (lấy miễn phí tại [Google AI Studio](https://aistudio.google.com/)).

---

## ⚙️ Hướng dẫn Cài đặt & Chạy ứng dụng (Installation & Setup)

1. **Clone hoặc tải mã nguồn:**
   Tải toàn bộ mã nguồn của dự án và đặt vào thư mục gốc của server (Ví dụ trên XAMPP: `C:/xampp/htdocs/generative_ai/`).

2. **Cấu hình Cơ sở dữ liệu (MySQL):**
   - Mở trình duyệt và truy cập vào **phpMyAdmin** (`http://localhost/phpmyadmin/`).
   - Tạo một Database mới với tên: `db_generative_ai`.
   - Chạy đoạn lệnh SQL sau để tạo bảng hệ thống:
     ```sql
     CREATE TABLE IF NOT EXISTS `api_logs` (
         `id` INT AUTO_INCREMENT PRIMARY KEY,
         `prompt` TEXT NOT NULL,
         `response` TEXT NOT NULL,
         `model_used` VARCHAR(50) NOT NULL,
         `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP
     ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
     ```

3. **Cấu hình API Key:**
   - Mở file `index.php` bằng trình soạn thảo mã nguồn (VS Code, Notepad++,...).
   - Tìm đến biến `$apiKey` và dán khóa API của bạn vào:
     ```php
     $apiKey = "DÁN_API_KEY_CỦA_BẠN_VÀO_ĐÂY";
     ```

4. **Chạy ứng dụng:**
   - Khởi động dịch vụ **Apache** và **MySQL** trong ứng dụng XAMPP Control Panel.
   - Mở trình duyệt web và truy cập đường dẫn: `http://localhost/generative_ai/index.php`

---

## 📂 Cấu trúc Mã nguồn (Project Structure)
```text
generative_ai/
│
├── db.php              # Kết nối cơ sở dữ liệu sử dụng PHP PDO
├── GeminiService.php   # Class xử lý kết nối Google Gemini API bằng PHP cURL
└── index.php           # Giao diện người dùng (Chat UI) & Điều hướng logic
