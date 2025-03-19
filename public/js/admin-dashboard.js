// Theme Toggle Functionality
const toggleSwitch = document.querySelector("#checkbox");
const mainContent = document.querySelector("#main-content");

// Check for saved theme preference
const currentTheme = localStorage.getItem("theme")
  ? localStorage.getItem("theme")
  : null;

if (currentTheme) {
  document.documentElement.setAttribute("data-theme", currentTheme);

  if (currentTheme === "dark") {
    toggleSwitch.checked = true;
  }
}

function switchTheme(e) {
  if (e.target.checked) {
    document.documentElement.setAttribute("data-theme", "dark");
    localStorage.setItem("theme", "dark");
  } else {
    document.documentElement.setAttribute("data-theme", "light");
    localStorage.setItem("theme", "light");
  }
}

toggleSwitch.addEventListener("change", switchTheme, false);

// Initialize charts once document is fully loaded
document.addEventListener("DOMContentLoaded", function () {
  // Get chart data from the DOM
  const orderPendingCount = parseInt(
    document.getElementById("order-pending-count").value
  );
  const orderCompletedCount = parseInt(
    document.getElementById("order-completed-count").value
  );
  const orderCanceledCount = parseInt(
    document.getElementById("order-canceled-count").value
  );

  const pickupPendingCount = parseInt(
    document.getElementById("pickup-pending-count").value
  );
  const pickupShippedCount = parseInt(
    document.getElementById("pickup-shipped-count").value
  );
  const pickupDeliveredCount = parseInt(
    document.getElementById("pickup-delivered-count").value
  );

  // Order Status Chart Configuration
  var orderStatusCtx = document
    .getElementById("orderStatusChart")
    .getContext("2d");
  var orderStatusChart = new Chart(orderStatusCtx, {
    type: "doughnut",
    data: {
      labels: ["Pending", "Completed", "Canceled"],
      datasets: [
        {
          label: "Order Status",
          data: [orderPendingCount, orderCompletedCount, orderCanceledCount],
          backgroundColor: ["#007bff", "#28a745", "#dc3545"],
          borderWidth: 5,
          borderColor:
            document.documentElement.getAttribute("data-theme") === "dark"
              ? "#2d2d3f"
              : "#ffffff",
        },
      ],
    },
    options: {
      responsive: true,
      maintainAspectRatio: false,
      cutout: "65%",
      animation: {
        animateRotate: true,
        animateScale: true,
      },
      plugins: {
        legend: {
          position: "top",
          labels: {
            padding: 20,
            font: {
              size: 12,
            },
          },
        },
        tooltip: {
          backgroundColor: "rgba(0, 0, 0, 0.7)",
          titleFont: { size: 16 },
          bodyFont: { size: 14 },
          padding: 10,
          displayColors: false,
          callbacks: {
            label: function (context) {
              return (
                context.label +
                ": " +
                context.raw +
                " (" +
                Math.round(
                  (context.raw /
                    context.dataset.data.reduce((a, b) => a + b, 0)) *
                    100
                ) +
                "%)"
              );
            },
          },
        },
      },
    },
  });

  // Pickup Status Chart Configuration
  var pickupStatusCtx = document
    .getElementById("pickupStatusChart")
    .getContext("2d");
  var pickupStatusChart = new Chart(pickupStatusCtx, {
    type: "doughnut",
    data: {
      labels: ["Pending", "Shipped", "Delivered"],
      datasets: [
        {
          label: "Pickup Status",
          data: [pickupPendingCount, pickupShippedCount, pickupDeliveredCount],
          backgroundColor: ["#17a2b8", "#ffc107", "#28a745"],
          borderWidth: 5,
          borderColor:
            document.documentElement.getAttribute("data-theme") === "dark"
              ? "#2d2d3f"
              : "#ffffff",
        },
      ],
    },
    options: {
      responsive: true,
      maintainAspectRatio: false,
      cutout: "65%",
      animation: {
        animateRotate: true,
        animateScale: true,
      },
      plugins: {
        legend: {
          position: "top",
          labels: {
            padding: 20,
            font: {
              size: 12,
            },
          },
        },
        tooltip: {
          backgroundColor: "rgba(0, 0, 0, 0.7)",
          titleFont: { size: 16 },
          bodyFont: { size: 14 },
          padding: 10,
          displayColors: false,
          callbacks: {
            label: function (context) {
              return (
                context.label +
                ": " +
                context.raw +
                " (" +
                Math.round(
                  (context.raw /
                    context.dataset.data.reduce((a, b) => a + b, 0)) *
                    100
                ) +
                "%)"
              );
            },
          },
        },
      },
    },
  });

  // Update chart colors when theme changes
  toggleSwitch.addEventListener("change", function () {
    const borderColor = this.checked ? "#2d2d3f" : "#ffffff";

    // Update chart borders
    orderStatusChart.data.datasets[0].borderColor = borderColor;
    pickupStatusChart.data.datasets[0].borderColor = borderColor;

    // Update the charts
    orderStatusChart.update();
    pickupStatusChart.update();
  });
});
