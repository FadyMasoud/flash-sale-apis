# Flash Sale Checkout API (Laravel 12)
A high-concurrency Flash Sale API built with **Laravel 12**, **MySQL**, and **Caching**.
The system supports **limited-stock products**, **short-lived holds**, **orders**, and **idempotent payment webhooks**.
Designed to
**prevent overselling**, handle **race conditions**, and remain **consistent** under heavy parallel traffic.
---
# 1. Assumptions & Invariants Enforced
###  Stock Invariants
- `available_stock = stock - reserved_stock - sold_stock`
- This value **never becomes negative**.
- All stock-related operations run inside **MySQL transactions** with row-level locks:
- Every write clears cache (`Cache::forget()`) to avoid stale stock.

###  Hold Invariants - Holds last **2 minutes**.
- Expired holds always release reserved stock (either during order creation or via background expiry job).
- A hold can be used **once only** (`order_id` ensures single-use). 
- Expiry processing is **idempotent** (cannot free stock twice).

### Order Invariants 
- Orders may only be created from:
- active - unexpired - unused holds - Order status transitions:

### Webhook Invariants
- Webhook is fully **idempotent** via unique `idempotency_key`.
- Duplicate webhooks never: 
- change stock twice
- change order status again 
- Webhook may arrive **before** order creation; 
after order becomes visible, it is processed correctly.

---
##  Features 
- Flash sale simulation with limited stock 
- Short-lived reservation system (Holds) 
- Safe order creation using reserved stock 
- Idempotent payment webhook (dedupe + out-of-order safe) 
- Automatic expiry + cleanup of holds
- Cached product reads for high-traffic performance
- Fully transactional workflow using MySQL `SELECT ... FOR UPDATE` 
- Built-in deadlock protection & retry logic
- Clean, compact, and readable Laravel 12 codebase
--- 
# 1.  Installation & Setup 
### Requirements
- PHP 8.2+ 
- Laravel 12
- MySQL 8+ 
- Any Laravel cache driver (file / database / redis)
### Installation 
Steps 
```bash
git clone <repo-url>
cd flash-sale-api
composer install 
cp .env.example .env
php artisan key:generate
