$(document).ready(function () {
  // Event delegation for dynamically loaded elements
  $(document).on("click", ".view-pickup", function (e) {
    e.preventDefault();
    const pickupId = $(this).data("id");
    viewPickupDetails(pickupId);
  });

  $(document).on("click", ".btn-track", function (e) {
    e.preventDefault();
    const pickupId = $(this).data("id");
    trackPickup(pickupId);
  });

  $(document).on("click", ".btn-assign", function (e) {
    e.preventDefault();
    const pickupId = $(this).data("id");
    assignPickup(pickupId);
  });

  $(document).on("click", ".update-status", function (e) {
    e.preventDefault();
    const pickupId = $(this).data("id");
    $("#statusPickupId").val(pickupId);
    $("#updateStatusModal").modal("show");
  });

  // Form submissions
  $("#assignPickupForm").submit(function (e) {
    e.preventDefault();
    const formData = $(this).serialize();

    // Validate that a driver is selected
    if (!$('select[name="driver_id"]').val()) {
      alert("Please select a driver");
      return;
    }

    $.ajax({
      url: "../../ajax/pickup-actions.php",
      type: "POST",
      data: formData + "&action=assign_pickup",
      dataType: "json",
      success: function (response) {
        if (response.success) {
          $("#assignPickupModal").modal("hide");
          location.reload();
        } else {
          alert(response.message || "Failed to assign pickup");
        }
      },
      error: function () {
        alert("Server error occurred");
      },
    });
  });

  $("#updateStatusForm").submit(function (e) {
    e.preventDefault();
    const formData = $(this).serialize();

    $.ajax({
      url: "../../ajax/pickup-actions.php",
      type: "POST",
      data: formData + "&action=update_status",
      dataType: "json",
      success: function (response) {
        if (response.success) {
          $("#updateStatusModal").modal("hide");
          location.reload();
        } else {
          alert(response.message || "Failed to update status");
        }
      },
      error: function () {
        alert("Server error occurred");
      },
    });
  });

  // ===== View Pickup Details =====
  window.viewPickupDetails = function (pickupId) {
    $("#viewPickupModal").modal("show");
    $("#pickupDetailsContent").html(
      '<div class="text-center"><div class="spinner-border text-primary"></div></div>'
    );

    $.ajax({
      url: "../../ajax/pickup-actions.php",
      type: "GET",
      data: {
        action: "get_pickup_details",
        pickup_id: pickupId,
      },
      success: function (response) {
        try {
          if (typeof response === "string") {
            response = JSON.parse(response);
          }

          if (response.success && response.pickup) {
            $("#pickupDetailsContent").html(
              generatePickupDetailsHtml(response.pickup)
            );
          } else {
            $("#pickupDetailsContent").html(
              '<div class="alert alert-danger">Failed to load pickup details</div>'
            );
          }
        } catch (e) {
          console.error("Error:", e);
          $("#pickupDetailsContent").html(
            '<div class="alert alert-danger">Error processing pickup details</div>'
          );
        }
      },
      error: function () {
        $("#pickupDetailsContent").html(
          '<div class="alert alert-danger">Error connecting to server</div>'
        );
      },
    });
  };

  // ===== Track Pickup =====
  window.trackPickup = function (pickupId) {
    $("#trackPickupModal").modal("show");
    $("#trackingDetails").html(
      '<div class="text-center"><div class="spinner-border text-primary"></div></div>'
    );

    $.ajax({
      url: "../../ajax/pickup-actions.php",
      type: "GET",
      data: {
        action: "get_tracking_details",
        pickup_id: pickupId,
      },
      success: function (response) {
        try {
          if (typeof response === "string") {
            response = JSON.parse(response);
          }

          if (response.success && response.tracking) {
            displayTrackingDetails(response.tracking);
          } else {
            $("#trackingDetails").html(
              '<div class="alert alert-info">No tracking information available</div>'
            );
          }
        } catch (e) {
          console.error("Error:", e);
          $("#trackingDetails").html(
            '<div class="alert alert-danger">Error processing tracking details</div>'
          );
        }
      },
      error: function () {
        $("#trackingDetails").html(
          '<div class="alert alert-danger">Error connecting to server</div>'
        );
      },
    });
  };

  // ===== Assign Pickup =====
  window.assignPickup = function (pickupId) {
    $("#assignPickupId").val(pickupId);

    $.ajax({
      url: "../../ajax/pickup-actions.php",
      type: "GET",
      data: {
        action: "get_drivers",
      },
      success: function (response) {
        try {
          if (typeof response === "string") {
            response = JSON.parse(response);
          }

          if (response.success && response.drivers) {
            if (response.drivers.length === 0) {
              alert("No drivers available. Please register drivers first.");
              return;
            }

            let options = '<option value="">Select a driver</option>';
            response.drivers.forEach((driver) => {
              options += `<option value="${driver.user_id}">${driver.first_name} ${driver.last_name}</option>`;
            });
            $('select[name="driver_id"]').html(options);
            $("#assignPickupModal").modal("show");
          } else {
            alert("Failed to load drivers. Please try again.");
          }
        } catch (e) {
          console.error("Error processing drivers:", e);
          alert("Error loading drivers. Please try again.");
        }
      },
      error: function () {
        alert("Failed to load drivers. Server error occurred.");
      },
    });
  };

  // Update Status Button Click
  $(".update-status").click(function () {
    const pickupId = $(this).data("id");
    $("#statusPickupId").val(pickupId);
    $("#updateStatusModal").modal("show");
  });

  // Export Pickup Report
  window.exportPickupReport = function () {
    window.location.href =
      "../../controllers/pickup-controller.php?action=export";
  };

  // Initialize search and filter functionality
  $("#searchPickup").on("keyup", function () {
    const value = $(this).val().toLowerCase();
    $("table tbody tr").filter(function () {
      $(this).toggle($(this).text().toLowerCase().indexOf(value) > -1);
    });
  });

  $("#statusFilter").on("change", function () {
    const value = $(this).val().toLowerCase();
    if (value === "") {
      $("table tbody tr").show();
    } else {
      $("table tbody tr")
        .filter(function () {
          const statusText = $(this)
            .find("td:nth-child(8)")
            .text()
            .toLowerCase();
          return statusText.indexOf(value) > -1;
        })
        .show();
      $("table tbody tr")
        .filter(function () {
          const statusText = $(this)
            .find("td:nth-child(8)")
            .text()
            .toLowerCase();
          return statusText.indexOf(value) === -1;
        })
        .hide();
    }
  });
});

// Helper function to generate pickup details HTML
function generatePickupDetailsHtml(pickup) {
  if (!pickup) {
    return '<div class="alert alert-warning">No pickup details found</div>';
  }

  return `
        <div class="pickup-details">
            <div class="row mb-4">
                <div class="col-md-6">
                    <h6 class="font-weight-bold"><i class="bi bi-info-circle"></i> Order Information</h6>
                    <div class="info-group">
                        <p><strong>Order ID:</strong> #${pickup.order_id}</p>
                        <p><strong>Order Status:</strong> 
                            <span class="badge badge-${getStatusClass(
                              pickup.order_status
                            )}">
                                ${pickup.order_status}
                            </span>
                        </p>
                        <p><strong>Order Date:</strong> ${formatDate(
                          pickup.order_date
                        )}</p>
                    </div>
                </div>
                <div class="col-md-6">
                    <h6 class="font-weight-bold"><i class="bi bi-person"></i> Customer Information</h6>
                    <div class="info-group">
                        <p><strong>Name:</strong> ${
                          pickup.consumer_name || "N/A"
                        }</p>
                        <p><strong>Email:</strong> ${pickup.email || "N/A"}</p>
                        <p><strong>Contact:</strong> ${
                          pickup.contact_number || "N/A"
                        }</p>
                    </div>
                </div>
            </div>
            <div class="row mb-4">
                <div class="col-md-6">
                    <h6 class="font-weight-bold"><i class="bi bi-geo-alt"></i> Pickup Details</h6>
                    <div class="info-group">
                        <p><strong>Location:</strong> ${
                          pickup.pickup_location || "Not set"
                        }</p>
                        <p><strong>Scheduled Date:</strong> ${formatDate(
                          pickup.pickup_date
                        )}</p>
                        <p><strong>Status:</strong> 
                            <span class="badge badge-${getStatusClass(
                              pickup.pickup_status
                            )}">
                                ${pickup.pickup_status}
                            </span>
                        </p>
                    </div>
                </div>
                <div class="col-md-6">
                    <h6 class="font-weight-bold"><i class="bi bi-person-badge"></i> Assignment Details</h6>
                    <div class="info-group">
                        <p><strong>Assigned Driver:</strong> ${
                          pickup.driver_name || "Not assigned"
                        }</p>
                        <p><strong>Notes:</strong> ${
                          pickup.pickup_notes || "No notes available"
                        }</p>
                    </div>
                </div>
            </div>
            ${
              pickup.items
                ? `
                <div class="mt-4">
                    <h6 class="font-weight-bold"><i class="bi bi-box"></i> Order Items</h6>
                    <div class="table-responsive">
                        <table class="table table-sm">
                            <thead>
                                <tr>
                                    <th>Item</th>
                                    <th>Quantity</th>
                                    <th>Price</th>
                                    <th>Total</th>
                                </tr>
                            </thead>
                            <tbody>
                                ${pickup.items
                                  .map(
                                    (item) => `
                                    <tr>
                                        <td>${item.product_name}</td>
                                        <td>${item.quantity}</td>
                                        <td>₱${formatPrice(item.price)}</td>
                                        <td>₱${formatPrice(
                                          item.quantity * item.price
                                        )}</td>
                                    </tr>
                                `
                                  )
                                  .join("")}
                            </tbody>
                        </table>
                    </div>
                </div>
            `
                : ""
            }
        </div>
    `;
}

// Display tracking details
function displayTrackingDetails(tracking) {
  const html = `
        <div class="tracking-timeline">
            <div class="current-status mb-4">
                <h6 class="font-weight-bold">Current Status</h6>
                <span class="badge badge-${getStatusClass(
                  tracking.current_status
                )}">
                    ${tracking.current_status.toUpperCase()}
                </span>
            </div>
            ${
              tracking.history.length > 0
                ? `
                <div class="timeline">
                    ${tracking.history
                      .map(
                        (item) => `
                        <div class="timeline-item">
                            <div class="timeline-date">${formatDate(
                              item.date
                            )}</div>
                            <div class="timeline-content">
                                <span class="badge badge-${getStatusClass(
                                  item.status
                                )}">
                                    ${item.status.toUpperCase()}
                                </span>
                                <p class="mb-0">${
                                  item.notes || "No additional notes"
                                }</p>
                            </div>
                        </div>
                    `
                      )
                      .join("")}
                </div>
            `
                : '<p class="text-muted">No status history available</p>'
            }
        </div>
    `;

  $("#trackingDetails").html(html);
}

// Helper functions
function getStatusClass(status) {
  status = String(status).toLowerCase();
  switch (status) {
    case "pending":
      return "warning";
    case "assigned":
      return "info";
    case "in_transit":
      return "primary";
    case "completed":
      return "success";
    case "cancelled":
      return "danger";
    default:
      return "secondary";
  }
}

function formatDate(dateString) {
  if (!dateString) return "N/A";
  return new Date(dateString).toLocaleString();
}

function formatPrice(price) {
  return parseFloat(price)
    .toFixed(2)
    .replace(/\d(?=(\d{3})+\.)/g, "$&,");
}

$(document).ready(function () {
  // Update Status Modal setup
  $("#updateStatusModal").on("show.bs.modal", function (e) {
    var button = $(e.relatedTarget);
    var orderId = button.data("order-id");
    var currentStatus = button.data("current-status");

    $("#statusOrderId").val(orderId);
    $("#statusSelect").val(currentStatus);
  });

  // Assign Driver Modal setup
  $("#assignDriverModal").on("show.bs.modal", function (e) {
    var button = $(e.relatedTarget);
    var orderId = button.data("order-id");

    $("#assignOrderId").val(orderId);

    // Default the pickup date to today
    var now = new Date();
    var dateString =
      now.getFullYear() +
      "-" +
      ("0" + (now.getMonth() + 1)).slice(-2) +
      "-" +
      ("0" + now.getDate()).slice(-2) +
      "T" +
      ("0" + now.getHours()).slice(-2) +
      ":" +
      ("0" + now.getMinutes()).slice(-2);

    $('input[name="pickup_date"]').val(dateString);
  });

  // View Order Details
  $("#viewPickupModal").on("show.bs.modal", function (e) {
    var button = $(e.relatedTarget);
    var orderId = button.data("order-id");

    $("#pickupDetailsContent").html(
      '<div class="text-center"><div class="spinner-border text-primary" role="status"><span class="sr-only">Loading...</span></div></div>'
    );

    // AJAX call to get order details
    $.ajax({
      url: "../../ajax/get-order-details.php",
      type: "GET",
      data: { order_id: orderId },
      success: function (response) {
        $("#pickupDetailsContent").html(response);
      },
      error: function () {
        $("#pickupDetailsContent").html(
          '<div class="alert alert-danger">Error loading order details</div>'
        );
      },
    });
  });

  // Initialize filters
  $("#searchPickup").on("keyup", function () {
    var value = $(this).val().toLowerCase();
    $("table tbody tr").filter(function () {
      $(this).toggle($(this).text().toLowerCase().indexOf(value) > -1);
    });
  });

  $("#statusFilter").on("change", function () {
    var value = $(this).val().toLowerCase();
    if (value === "") {
      $("table tbody tr").show();
    } else {
      $("table tbody tr").hide();
      $("table tbody tr")
        .filter(function () {
          return (
            $(this)
              .find("td:nth-child(8)")
              .text()
              .toLowerCase()
              .indexOf(value) > -1
          );
        })
        .show();
    }
  });

  $("#dateFilter").on("change", function () {
    var value = $(this).val();
    if (value === "") {
      $("table tbody tr").show();
    } else {
      $("table tbody tr").hide();
      $("table tbody tr")
        .filter(function () {
          var date = new Date($(this).find("td:nth-child(5)").text());
          var filterDate = new Date(value);
          return date.toDateString() === filterDate.toDateString();
        })
        .show();
    }
  });

  $("#sortFilter").on("change", function () {
    var value = $(this).val();
    var tbody = $("table tbody");
    var rows = tbody.find("tr").toArray();

    rows.sort(function (a, b) {
      var aVal, bVal;

      if (value === "newest" || value === "oldest") {
        aVal = new Date($(a).find("td:nth-child(3)").text());
        bVal = new Date($(b).find("td:nth-child(3)").text());

        return value === "newest" ? bVal - aVal : aVal - bVal;
      }

      // Priority sorting (pending first, then assigned, etc.)
      if (value === "priority") {
        var priorityOrder = [
          "pending",
          "assigned",
          "in_transit",
          "completed",
          "cancelled",
        ];

        aVal = priorityOrder.indexOf(
          $(a).find("td:nth-child(8)").text().toLowerCase()
        );
        bVal = priorityOrder.indexOf(
          $(b).find("td:nth-child(8)").text().toLowerCase()
        );

        return aVal - bVal;
      }
    });

    $.each(rows, function (index, row) {
      tbody.append(row);
    });
  });

  // Export report
  window.exportPickupReport = function () {
    window.location.href = "../../controllers/export-pickups.php";
  };
});

function viewOrderDetails(orderId) {
  $("#viewOrderModal").modal("show");
  $("#orderDetails").html(
    '<div class="text-center"><div class="spinner-border"></div></div>'
  );

  $.get("../../ajax/get-order-details.php", { order_id: orderId })
    .done(function (response) {
      $("#orderDetails").html(response);
    })
    .fail(function () {
      $("#orderDetails").html(
        '<div class="alert alert-danger">Failed to load order details</div>'
      );
    });
}

function assignDriver(orderId) {
  $("#assignOrderId").val(orderId);
  $("#assignDriverModal").modal("show");
}

function updateStatus(orderId, currentStatus) {
  $("#statusOrderId").val(orderId);
  $('#updateStatusModal select[name="pickup_status"]').val(currentStatus);
  $("#updateStatusModal").modal("show");
}

// Form submissions
$("#assignDriverForm").on("submit", function (e) {
  e.preventDefault();
  $.post("../../ajax/update-pickup.php", $(this).serialize())
    .done(function (response) {
      $("#assignDriverModal").modal("hide");
      location.reload();
    })
    .fail(function () {
      alert("Failed to assign driver");
    });
});

$("#updateStatusForm").on("submit", function (e) {
  e.preventDefault();
  $.post("../../ajax/update-pickup.php", $(this).serialize())
    .done(function (response) {
      $("#updateStatusModal").modal("hide");
      location.reload();
    })
    .fail(function () {
      alert("Failed to update status");
    });
});
