(function () {
  function init() {
    const radioButtons = document.querySelectorAll(
      'input[type="radio"][name="payment"]'
    );
    const payBtn = document.getElementById("payBtn");
    const statusEl = document.getElementById("status");
    const spinner = document.getElementById("spinner");

    if (!payBtn || radioButtons.length === 0) {
      console.error("[ERROR] Không tìm thấy nút hoặc radio button");
      return;
    }

    let selectedMethod = null;

    radioButtons.forEach((radio) => {
      radio.addEventListener("change", function () {
        if (this.checked) {
          selectedMethod = this.value;
          // Remove selected from others
          document
            .querySelectorAll(".method")
            .forEach((el) => el.classList.remove("selected"));
          // Add selected to current
          this.closest(".method").classList.add("selected");
          // Enable button
          payBtn.disabled = false;
          console.log("[DEBUG] Đã chọn:", selectedMethod);
        }
      });
    });

    payBtn.addEventListener("click", async function () {
      if (!selectedMethod) {
        console.warn("[WARN] Chưa chọn phương thức");
        return;
      }

      console.log("[INFO] Bắt đầu tạo đơn với:", selectedMethod);
      payBtn.disabled = true;
      statusEl.textContent = "Đang tạo đơn hàng...";
      spinner.style.display = "block";

      try {
        const response = await fetch("create_order.php", {
          method: "POST",
          headers: { "Content-Type": "application/json" },
          body: JSON.stringify({
            payment_method: selectedMethod,
            amount: 50000,
            description: "Demo thanh toán",
          }),
        });

        if (!response.ok) throw new Error("HTTP " + response.status);

        const data = await response.json();
        console.log("[API] Response:", data);

        if (data.return_code === 1 && data.order_url) {
          statusEl.textContent = "Chuyển hướng đến ZaloPay...";
          spinner.style.display = "none";
          setTimeout(() => {
            window.location.href = data.order_url;
          }, 500);
        } else {
          throw new Error(data.return_message || "Lỗi không xác định");
        }
      } catch (err) {
        console.error("[ERROR]", err);
        statusEl.textContent = "❌ " + (err.message || "Lỗi hệ thống");
        spinner.style.display = "none";
        payBtn.disabled = false;
      }
    });
  }

  // Try init after DOM ready
  if (document.readyState === "loading") {
    document.addEventListener("DOMContentLoaded", init);
  } else {
    init();
  }
})();
