function viewOrder(orderId) {
  // Show loading indicator in modal
  $("#orderDetailsContent").html(
    '<div class="text-center py-4"><div class="spinner-border text-primary" role="status"></div></div>'
  );
  $("#orderDetailsModal").modal("show");
  $.ajax({
    url: "../../ajax/get-order-details.php",
    type: "GET",
    data: {
      order_id: orderId,
    },
    success: function (response) {
      try {
        if (typeof response === "string") {
          response = JSON.parse(response);
        }

        if (response.success && response.order) {
          const order = response.order;
          const content = `
                        <div class="order-details p-3">
                            <div class="row mb-4">
                                <div class="col-md-6">
                                    <h6 class="font-weight-bold">Order Information</h6>
                                    <p><strong>Order ID:</strong> #${
                                      order.order_id
                                    }</p>
                                    <p><strong>Date:</strong> ${new Date(
                                      order.order_date
                                    ).toLocaleString()}</p>
                                    <p><strong>Status:</strong> <span class="badge badge-${getStatusBadgeClass(
                                      order.order_status
                                    )}">${order.order_status}</span></p>
                                </div>
                                <div class="col-md-6">
                                    <h6 class="font-weight-bold">Customer Information</h6>
                                    <p><strong>Name:</strong> ${
                                      order.customer_name || "N/A"
                                    }</p>
                                    <p><strong>Email:</strong> ${
                                      order.email || "N/A"
                                    }</p>
                                </div>
                            </div>
                            <div class="table-responsive">
                                <table class="table table-hover">
                                    <thead class="thead-light">
                                        <tr>
                                            <th>Product</th>
                                            <th>Quantity</th>
                                            <th>Unit Price</th>
                                            <th>Total</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        ${order.items
                                          .map(
                                            (item) => `
                                            <tr>
                                                <td>${item.product_name}</td>
                                                <td>${item.quantity}</td>
                                                <td>₱${parseFloat(
                                                  item.price
                                                ).toLocaleString(undefined, {
                                                  minimumFractionDigits: 2,
                                                  maximumFractionDigits: 2,
                                                })}</td>
                                                <td>₱${(
                                                  item.quantity * item.price
                                                ).toLocaleString(undefined, {
                                                  minimumFractionDigits: 2,
                                                  maximumFractionDigits: 2,
                                                })}</td>
                                            </tr>
                                        `
                                          )
                                          .join("")}
                                    </tbody>
                                    <tfoot>
                                        <tr>
                                            <th colspan="3" class="text-right">Total Amount:</th>
                                            <th>₱${parseFloat(
                                              order.total_amount
                                            ).toLocaleString(undefined, {
                                              minimumFractionDigits: 2,
                                              maximumFractionDigits: 2,
                                            })}</th>
                                        </tr>
                                    </tfoot>
                                </table>
                            </div>
                        </div>
                    `;
          $("#orderDetailsContent").html(content);
        } else {
          $("#orderDetailsContent").html(
            '<div class="alert alert-danger">Failed to load order details</div>'
          );
        }
      } catch (e) {
        console.error("Error parsing response:", e);
        $("#orderDetailsContent").html(
          '<div class="alert alert-danger">Error processing order details</div>'
        );
      }
    },
    error: function (xhr, status, error) {
      console.error("AJAX Error:", error);
      $("#orderDetailsContent").html(
        '<div class="alert alert-danger">Error loading order details</div>'
      );
    },
  });
}

function updateOrderStatus(orderId) {
  $("#status_order_id").val(orderId);
  $("#updateStatusModal").modal("show");
}

// Handle status update form submission
$(document).ready(function () {
  $("#updateStatusForm").on("submit", function (e) {
    e.preventDefault();

    const orderId = $("#status_order_id").val();
    const newStatus = $("#new_status").val();

    // Show loading state
    const submitBtn = $(this).find('button[type="submit"]');
    const originalBtnText = submitBtn.html();
    submitBtn
      .prop("disabled", true)
      .html('<i class="bi bi-arrow-repeat"></i> Updating...');

    $.ajax({
      url: "manager-order-oversight.php",
      type: "POST",
      data: {
        order_id: orderId,
        new_status: newStatus,
        update_status: true,
      },
      success: function (response) {
        try {
          const result =
            typeof response === "string" ? JSON.parse(response) : response;

          if (result.success) {
            // Update the status badge in the table
            const statusBadgeClass = getStatusBadgeClass(newStatus);
            const row = $('button[data-id="' + orderId + '"]').closest("tr");
            const statusCell = row.find("td:eq(5)");
            statusCell.html(
              `<span class="badge badge-${statusBadgeClass}">${
                newStatus.charAt(0).toUpperCase() + newStatus.slice(1)
              }</span>`
            ); // Show success message
            const alertHtml = `
                            <div class="alert alert-success alert-dismissible fade show" role="alert">
                                <i class="bi bi-check-circle-fill"></i> ${
                                  result.message ||
                                  "Order status updated successfully"
                                }
                                <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                                    <span aria-hidden="true">&times;</span>
                                </button>
                            </div>`;
            $("main[role='main']").prepend(alertHtml);

            // Close the modal
            $("#updateStatusModal").modal("hide");
          } else {
            throw new Error(result.message || "Failed to update order status");
          }
        } catch (err) {
          alert(err.message);
        }
      },
      error: function () {
        alert("Server error occurred. Please try again.");
      },
      complete: function () {
        // Restore button state
        submitBtn.prop("disabled", false).html(originalBtnText);
      },
    });
  });
});

function exportOrderReport() {
  const dateRange = {
    start: $("#dateFilter").val() || "",
    end: new Date().toISOString().split("T")[0],
  };

  window.location.href = `export-orders.php?start=${dateRange.start}&end=${dateRange.end}`;
}

// Helper function to get badge class based on status
function getStatusBadgeClass(status) {
  switch (status?.toLowerCase()) {
    case "pending":
      return "warning";
    case "processing":
      return "info";
    case "completed":
      return "success";
    case "cancelled":
      return "danger";
    default:
      return "secondary";
  }
}

// Event Listeners
$(document).ready(function () {
  // View order button click
  $(".view-order").click(function () {
    viewOrder($(this).data("id"));
  });

  // Update status button click
  $(".update-status").click(function () {
    updateOrderStatus($(this).data("id"));
  });

  // Filter functionality
  $("#searchOrder, #statusFilter, #dateFilter, #sortFilter").on(
    "change keyup",
    function () {
      filterOrders();
    }
  );
});

function filterOrders() {
  const searchTerm = $("#searchOrder").val().toLowerCase();
  const statusFilter = $("#statusFilter").val();
  const dateFilter = $("#dateFilter").val();
  const sortFilter = $("#sortFilter").val();

  $("tbody tr").each(function () {
    const $row = $(this);
    const orderId = $row.find("td:first").text().toLowerCase();
    const customer = $row.find("td:eq(1)").text().toLowerCase();
    const status = $row.find("td:eq(5)").text().toLowerCase();
    const date = $row.find("td:eq(4)").text();

    const matchesSearch =
      orderId.includes(searchTerm) || customer.includes(searchTerm);
    const matchesStatus =
      !statusFilter || status.includes(statusFilter.toLowerCase());
    const matchesDate =
      !dateFilter || new Date(date).toISOString().split("T")[0] === dateFilter;

    $row.toggle(matchesSearch && matchesStatus && matchesDate);
  });

  // Apply sorting if needed
  if (sortFilter) {
    const rows = $("tbody tr").get();
    rows.sort((a, b) => {
      let aVal, bVal;
      switch (sortFilter) {
        case "newest":
          aVal = new Date($(b).find("td:eq(4)").text());
          bVal = new Date($(a).find("td:eq(4)").text());
          break;
        case "oldest":
          aVal = new Date($(a).find("td:eq(4)").text());
          bVal = new Date($(b).find("td:eq(4)").text());
          break;
        case "highest":
        case "lowest":
          aVal = parseFloat(
            $(a).find("td:eq(3)").text().replace("₱", "").replace(",", "")
          );
          bVal = parseFloat(
            $(b).find("td:eq(3)").text().replace("₱", "").replace(",", "")
          );
          if (sortFilter === "highest") [aVal, bVal] = [bVal, aVal];
          break;
      }
      return aVal > bVal ? 1 : aVal < bVal ? -1 : 0;
    });
    $("tbody").html(rows);
  }
}
