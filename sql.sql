-- Vô hiệu hóa kiểm tra khóa ngoại tạm thời để TRUNCATE bảng có tham chiếu
SET FOREIGN_KEY_CHECKS = 0;

-- Bảng Customers
CREATE TABLE IF NOT EXISTS Customers (
  CustomerID INT PRIMARY KEY AUTO_INCREMENT,
  CustomerName VARCHAR(255) NOT NULL,
  Email VARCHAR(255) UNIQUE NOT NULL,
  Phone VARCHAR(20),
  CreatedAt DATETIME DEFAULT CURRENT_TIMESTAMP
);

-- Bảng Orders
CREATE TABLE IF NOT EXISTS Orders (
  OrderID INT PRIMARY KEY AUTO_INCREMENT,
  CustomerID INT NOT NULL,
  OrderDate DATETIME DEFAULT CURRENT_TIMESTAMP,
  TotalAmount DECIMAL(18,2) NOT NULL,
  Status VARCHAR(50) DEFAULT 'Pending',
  OrderDescription VARCHAR(500) NULL,
  FOREIGN KEY (CustomerID) REFERENCES Customers(CustomerID)
);

-- Bảng PaymentMethods
CREATE TABLE IF NOT EXISTS PaymentMethods (
  MethodID INT PRIMARY KEY AUTO_INCREMENT,
  MethodName VARCHAR(100) NOT NULL
);

-- Bảng Payments (Đã thêm cột cho ZaloPay)
CREATE TABLE IF NOT EXISTS Payments (
  PaymentID INT PRIMARY KEY AUTO_INCREMENT,
  OrderID INT NOT NULL,
  Amount DECIMAL(18,2) NOT NULL,
  PaymentDate DATETIME DEFAULT CURRENT_TIMESTAMP,
  MethodID INT NOT NULL,
  TransactionCode VARCHAR(255) UNIQUE,  -- app_trans_id for ZaloPay
  IsSuccessful TINYINT(1) DEFAULT 0,
  UpdatedDate DATETIME NULL,
  BankCode VARCHAR(50) NULL,
  CardType VARCHAR(50) NULL,
  VnpTxnRef VARCHAR(100) NULL,
  VnpTransactionNo VARCHAR(100) NULL,
  VnpResponseCode VARCHAR(10) NULL,
  VnpOrderInfo VARCHAR(500) NULL,
  IPAddress VARCHAR(45) NULL,
  Currency VARCHAR(10) DEFAULT 'VND',
  -- Thêm cho ZaloPay
  ZpTransId VARCHAR(100) NULL,    -- zp_trans_id từ callback ZaloPay
  ReturnCode VARCHAR(10) NULL,    -- return_code chung
  ResponseMessage TEXT NULL,      -- return_message từ callback
  FOREIGN KEY (OrderID) REFERENCES Orders(OrderID),
  FOREIGN KEY (MethodID) REFERENCES PaymentMethods(MethodID)
);

-- 3. THÊM DỮ LIỆU MẪU VÀ CÁC PHƯƠNG THỨC THANH TOÁN

-- Xóa dữ liệu cũ trong PaymentMethods để tránh trùng lặp
-- (Đã được bảo vệ bởi lệnh SET FOREIGN_KEY_CHECKS = 0 ở trên)
TRUNCATE TABLE PaymentMethods;

-- Bật lại kiểm tra khóa ngoại
SET FOREIGN_KEY_CHECKS = 1;

-- Insert dữ liệu mẫu cho PaymentMethods (Đã bao gồm ZaloPay)
INSERT INTO PaymentMethods (MethodName) VALUES
('VNPay Domestic'),
('VNPay International'),
('Cash'),
('Bank Transfer'),
('ZaloPay'); -- Thêm ZaloPay

-- Insert dữ liệu mẫu cho Customers
-- (Bạn có thể bỏ qua nếu đã có dữ liệu)
INSERT INTO Customers (CustomerName, Email, Phone) VALUES
('Nguyen Van A', 'nguyenvana@email.com', '0912345678'),
('Tran Thi B', 'tranthib@email.com', '0923456789')
ON DUPLICATE KEY UPDATE CustomerName=CustomerName; -- Tránh lỗi nếu chạy lại

-- Insert dữ liệu mẫu cho Orders
-- (Bạn có thể bỏ qua nếu đã có dữ liệu)
INSERT INTO Orders (CustomerID, TotalAmount, OrderDescription) VALUES
(1, 1000000, 'Mua laptop Dell XPS'),
(2, 500000, 'Mua điện thoại Samsung');
