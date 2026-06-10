# 🛡️ FraudGuard Pro - Bangladeshi E-commerce Fraud Protection

**FraudGuard Pro** হলো বাংলাদেশের ই-কমার্স ইন্ডাস্ট্রির জন্য একটি শক্তিশালী **Fraud Detection** এবং **Courier Data Aggregator** সিস্টেম। এটি ক্যাশ অন ডেলিভারি (COD) অর্ডারের ক্ষেত্রে কাস্টমারের অতীত ডেলিভারি হিস্ট্রি বিশ্লেষণ করে ফেক অর্ডার এবং পার্সেল ফ্রড কমাতে সাহায্য করে।

---

## 🚀 মূল বৈশিষ্ট্যসমূহ (Key Features)

- **Multi-Courier Aggregation:** স্টিডফাস্ট (Steadfast), পাঠাও (Pathao), রেডএক্স (RedX) এবং পেপারফ্লাই (Paperfly)-এর রিয়েল ডাটা এক জায়গায়।
- **Master API Integration:** [FraudBD.com](https://fraudbd.com) এপিআই ব্যবহার করে সব কুরিয়ারের ডাটা সংগ্রহ।
- **Smart Risk Scoring:** কাস্টমারের সাকসেস রেটের ওপর ভিত্তি করে **Low**, **Medium**, এবং **High** রিস্ক স্কোরিং।
- **Premium Dashboard:** আধুনিক এবং রেসপনসিভ ইউজার ইন্টারফেস (Plus Jakarta Sans ফন্ট)।
- **Bulk CSV Analysis:** একসাথে শত শত ফোন নম্বর চেক করার সুবিধা এবং লাইভ প্রগ্রেস ট্র্যাকিং।
- **RESTful API Service:** অন্য যেকোনো অ্যাপে (Laravel, PHP, Mobile App) ইন্টিগ্রেট করার জন্য Bearer Token সিকিউরড এপিআই।
- **Visual Analytics:** এপেক্স চার্ট (ApexCharts) ব্যবহার করে ডেলিভারি বনাম ক্যান্সেল রেশিও গ্রাফ।
- **Printable Reports:** প্রতিটি ফ্রড চেক রিপোর্টের PDF/Print কপি নেওয়ার সুবিধা।
- **Caching System:** এপিআই ল্যাটেন্সি এবং কস্ট কমাতে লোকাল ফাইল ক্যাশিং।

---

## 🛠️ টেক স্ট্যাক (Tech Stack)

- **Backend:** PHP 7.4+ (Core PHP), cURL
- **Frontend:** HTML5, CSS3 (Vanilla), JavaScript (ES6+), FontAwesome 6
- **Database:** MySQL
- **Visualization:** ApexCharts API
- **API Integration:** REST API (JSON)

---

## 📦 ইনস্টলেশন (Installation)

১. আপনার লোকাল সার্ভারে (XAMPP/WAMP) বা লাইভ হোস্টিংয়ে ফাইলগুলো আপলোড করুন।
২. আপনার ডাটাবেসে `fraud_checks` টেবিলটি তৈরি করুন:

```sql
CREATE TABLE `fraud_checks` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `phone` VARCHAR(15) NOT NULL,
  `success_rate` DECIMAL(5,2),
  `total_cancel` INT,
  `risk_level` ENUM('Low', 'Medium', 'High'),
  `recommendation` TEXT,
  `checked_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);
```

৩. এপিআই কনফিগার করুন:
   - `api_bridge.php` ফাইলটি ওপেন করুন।
   - আপনার [FraudBD](https://fraudbd.com) API Key-টি বসান:
     ```php
     $fraudBDConfig = ['api_key' => 'YOUR_API_KEY'];
     ```

---

## 🖥️ ড্যাশবোর্ড ব্যবহার (Usage)

- ব্রাউজারে `http://localhost/fraud_checker_system/dashboard.php` ওপেন করুন।
- **Single Check:** ফোন নম্বর দিয়ে "ANALYZE" বাটনে ক্লিক করুন।
- **Bulk Check:** একটি `.csv` ফাইল আপলোড করুন যেখানে এক কলামে সব ফোন নম্বর থাকবে।

---

## 🔌 এপিআই ডকুমেন্টেশন (API Usage)

অন্য কোনো অ্যাপ্লিকেশন থেকে এপিআই কল করার নিয়ম:

**Endpoint:** `GET /api/v1/check.php?phone=017XXXXXXXX`  
**Authorization:** `Bearer FG-SECRET-789`

**Sample Response:**
```json
{
  "status": "success",
  "data": {
    "phone": "017XXXXXXXX",
    "aggregate": {
      "success_rate": 92.5,
      "risk_level": "Low",
      "recommendation": "Trusted Customer: Safe to ship via COD."
    }
  }
}
```

---

## 🛡️ সিকিউরিটি টিপস

- প্রোডাকশন এনভায়রনমেন্টে অবশ্যই `api/v1/check.php` এর `FG-SECRET-789` টোকেনটি পরিবর্তন করুন।
- এপিআই সবসময় `HTTPS` প্রোটোকলে ব্যবহার করুন।

---

## 👨‍💻 কন্ট্রিবিউটর

- **[Mohammad Sheikh Shahinur Rahman](https://www.linkedin.com/in/mohammad-sheikh-shahinur-rahman/)**
  - Software Engineer, Digital Forensics Expert, Author of 30+ Books, and Entrepreneur.
-

---

## 📄 লাইসেন্স

এই প্রজেক্টটি **MIT License**-এর অধীনে লাইসেন্সকৃত। 