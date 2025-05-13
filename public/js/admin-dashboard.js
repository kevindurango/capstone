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

  // Get agricultural data from the DOM
  let farmersPerBarangay = [];
  let cropsPerBarangay = [];
  let seasonalCrops = [];
  let currentSeasons = [];
  let allBarangays = [];

  try {
    farmersPerBarangay = JSON.parse(document.getElementById("farmers-per-barangay").value || "[]");
    cropsPerBarangay = JSON.parse(document.getElementById("crops-per-barangay").value || "[]");
    seasonalCrops = JSON.parse(document.getElementById("seasonal-crops").value || "[]");
    currentSeasons = JSON.parse(document.getElementById("current-seasons").value || "[]");
    allBarangays = JSON.parse(document.getElementById("all-barangays").value || "[]");
  } catch (e) {
    console.error("Error parsing agricultural data:", e);
  }

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
    
    // Update agricultural charts borders if they exist
    if (farmersBarangayChart) {
      farmersBarangayChart.options.plugins.tooltip.backgroundColor = this.checked ? "rgba(45, 45, 63, 0.9)" : "rgba(0, 0, 0, 0.7)";
      farmersBarangayChart.update();
    }
    
    if (cropsBarangayChart) {
      cropsBarangayChart.options.plugins.tooltip.backgroundColor = this.checked ? "rgba(45, 45, 63, 0.9)" : "rgba(0, 0, 0, 0.7)";
      cropsBarangayChart.update();
    }
    
    if (seasonalCropsChart) {
      seasonalCropsChart.options.plugins.tooltip.backgroundColor = this.checked ? "rgba(45, 45, 63, 0.9)" : "rgba(0, 0, 0, 0.7)";
      seasonalCropsChart.update();
    }
    
    if (currentSeasonsChart) {
      currentSeasonsChart.options.plugins.tooltip.backgroundColor = this.checked ? "rgba(45, 45, 63, 0.9)" : "rgba(0, 0, 0, 0.7)";
      currentSeasonsChart.update();
    }
  });
  
  // Farmers per Barangay Chart
  let farmersBarangayChart;
  if (document.getElementById("farmersPerBarangayChart")) {
    const farmersBarangayCtx = document.getElementById("farmersPerBarangayChart").getContext("2d");
    
    // Prepare data for chart
    const farmersData = farmersPerBarangay.length ? farmersPerBarangay : createDummyFarmersData();
    
    const barangayLabels = farmersData.map(item => item.barangay_name);
    const farmerCounts = farmersData.map(item => parseInt(item.farmer_count));
    const farmAreaData = farmersData.map(item => parseFloat(item.total_farm_area || 0));
    
    farmersBarangayChart = new Chart(farmersBarangayCtx, {
      type: "bar",
      data: {
        labels: barangayLabels,
        datasets: [
          {
            label: "Number of Farmers",
            data: farmerCounts,
            backgroundColor: "#4CAF50",
            borderColor: "#2E7D32",
            borderWidth: 1
          },
          {
            label: "Total Farm Area (hectares)",
            data: farmAreaData,
            backgroundColor: "#2196F3",
            borderColor: "#1565C0",
            borderWidth: 1,
            // Draw this dataset on a second y-axis
            yAxisID: "y1"
          }
        ]
      },
      options: {
        responsive: true,
        maintainAspectRatio: false,
        scales: {
          x: {
            title: {
              display: true,
              text: "Barangays in Valencia"
            }
          },
          y: {
            beginAtZero: true,
            title: {
              display: true,
              text: "Number of Farmers"
            }
          },
          y1: {
            beginAtZero: true,
            position: "right",
            grid: {
              drawOnChartArea: false
            },
            title: {
              display: true,
              text: "Farm Area (hectares)"
            }
          }
        },
        plugins: {
          title: {
            display: true,
            text: "Farmers Distribution Across Barangays in Valencia",
            font: {
              size: 16
            }
          },
          tooltip: {
            backgroundColor: document.documentElement.getAttribute("data-theme") === "dark" ? "rgba(45, 45, 63, 0.9)" : "rgba(0, 0, 0, 0.7)",
            titleFont: { size: 14 },
            bodyFont: { size: 13 },
            callbacks: {
              label: function(context) {
                let label = context.dataset.label || '';
                if (label) {
                  label += ': ';
                }
                if (context.parsed.y !== null) {
                  if (context.dataset.label === "Number of Farmers") {
                    label += context.parsed.y + " farmers";
                  } else {
                    label += context.parsed.y + " hectares";
                  }
                }
                return label;
              }
            }
          }
        }
      }
    });
  }
  
  // Crops per Barangay Chart
  let cropsBarangayChart;
  if (document.getElementById("cropsPerBarangayChart")) {
    const cropsBarangayCtx = document.getElementById("cropsPerBarangayChart").getContext("2d");
    
    // Prepare data for chart
    const cropsData = cropsPerBarangay.length ? cropsPerBarangay : createDummyCropsData();
    
    // Group by barangay and count unique products
    const barangayCropCounts = {};
    
    cropsData.forEach(item => {
      if (!barangayCropCounts[item.barangay_name]) {
        barangayCropCounts[item.barangay_name] = {
          cropCount: 0,
          totalProduction: 0,
          crops: new Set()
        };
      }
      barangayCropCounts[item.barangay_name].crops.add(item.product_name);
      barangayCropCounts[item.barangay_name].cropCount = barangayCropCounts[item.barangay_name].crops.size;
      barangayCropCounts[item.barangay_name].totalProduction += parseFloat(item.total_production || 0);
    });
    
    const barangayLabels = Object.keys(barangayCropCounts);
    const cropCounts = barangayLabels.map(b => barangayCropCounts[b].cropCount);
    const productionValues = barangayLabels.map(b => barangayCropCounts[b].totalProduction);
    
    cropsBarangayChart = new Chart(cropsBarangayCtx, {
      type: "bar",
      data: {
        labels: barangayLabels,
        datasets: [
          {
            label: "Number of Unique Crops",
            data: cropCounts,
            backgroundColor: "#FF9800",
            borderColor: "#EF6C00",
            borderWidth: 1
          },
          {
            label: "Total Production Volume",
            data: productionValues,
            backgroundColor: "#9C27B0",
            borderColor: "#6A1B9A",
            borderWidth: 1,
            yAxisID: "y1"
          }
        ]
      },
      options: {
        responsive: true,
        maintainAspectRatio: false,
        scales: {
          x: {
            title: {
              display: true,
              text: "Barangays in Valencia"
            }
          },
          y: {
            beginAtZero: true,
            title: {
              display: true,
              text: "Number of Crop Types"
            }
          },
          y1: {
            beginAtZero: true,
            position: "right",
            grid: {
              drawOnChartArea: false
            },
            title: {
              display: true,
              text: "Production Volume"
            }
          }
        },
        plugins: {
          title: {
            display: true,
            text: "Crop Variety and Production by Barangay",
            font: {
              size: 16
            }
          },
          tooltip: {
            backgroundColor: document.documentElement.getAttribute("data-theme") === "dark" ? "rgba(45, 45, 63, 0.9)" : "rgba(0, 0, 0, 0.7)",
            titleFont: { size: 14 },
            bodyFont: { size: 13 }
          }
        }
      }
    });
  }
  
  // Seasonal Crop Production Chart
  let seasonalCropsChart;
  if (document.getElementById("seasonalCropsChart")) {
    const seasonalCropsCtx = document.getElementById("seasonalCropsChart").getContext("2d");
    
    // Prepare data for chart
    const seasonalData = seasonalCrops.length ? seasonalCrops : createDummySeasonalData();
    
    // Group by season
    const seasonalProductionData = {};
    seasonalData.forEach(item => {
      if (!seasonalProductionData[item.season_name]) {
        seasonalProductionData[item.season_name] = 0;
      }
      seasonalProductionData[item.season_name] += parseFloat(item.total_production || 0);
    });
    
    const seasonLabels = Object.keys(seasonalProductionData);
    const productionValues = seasonLabels.map(s => seasonalProductionData[s]);
    
    // Generate distinct colors for each season
    const seasonColors = generateColors(seasonLabels.length);
    
    seasonalCropsChart = new Chart(seasonalCropsCtx, {
      type: "pie",
      data: {
        labels: seasonLabels,
        datasets: [
          {
            data: productionValues,
            backgroundColor: seasonColors,
            hoverOffset: 10
          }
        ]
      },
      options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
          title: {
            display: true,
            text: "Crop Production by Growing Season",
            font: {
              size: 16
            }
          },
          legend: {
            position: "right",
            labels: {
              padding: 15,
              font: {
                size: 12
              }
            }
          },
          tooltip: {
            backgroundColor: document.documentElement.getAttribute("data-theme") === "dark" ? "rgba(45, 45, 63, 0.9)" : "rgba(0, 0, 0, 0.7)",
            titleFont: { size: 14 },
            bodyFont: { size: 13 },
            callbacks: {
              label: function(context) {
                let label = context.label || '';
                if (label) {
                  label += ': ';
                }
                if (context.raw !== null) {
                  const total = context.dataset.data.reduce((acc, val) => acc + val, 0);
                  const percentage = ((context.raw / total) * 100).toFixed(1);
                  label += context.raw + " units (" + percentage + "%)";
                }
                return label;
              }
            }
          }
        }
      }
    });
  }
  
  // Current Crop Seasons Chart
  let currentSeasonsChart;
  if (document.getElementById("currentSeasonsChart")) {
    const currentSeasonsCtx = document.getElementById("currentSeasonsChart").getContext("2d");
    
    // Current month for highlighting active seasons
    const currentMonth = new Date().getMonth() + 1; // 1-12 format
    
    // Prepare data for chart
    const seasonsData = currentSeasons.length ? currentSeasons : createDummySeasonsData();
    
    // Create a dataset that shows month coverage for each season
    const datasets = [];
    const months = ["Jan", "Feb", "Mar", "Apr", "May", "Jun", "Jul", "Aug", "Sep", "Oct", "Nov", "Dec"];
    
    seasonsData.forEach((season, index) => {
      const monthData = Array(12).fill(0);
      
      // Handle wrapping seasons (e.g., Nov-Feb spans across year boundary)
      if (season.start_month > season.end_month) {
        for (let i = season.start_month; i <= 12; i++) {
          monthData[i-1] = 1; // Months are 1-indexed in data, 0-indexed in array
        }
        for (let i = 1; i <= season.end_month; i++) {
          monthData[i-1] = 1;
        }
      } else {
        for (let i = season.start_month; i <= season.end_month; i++) {
          monthData[i-1] = 1;
        }
      }
      
      // Highlight current month if it's in this season
      if (isMonthInSeason(currentMonth, season.start_month, season.end_month)) {
        monthData[currentMonth-1] = 2; // Higher value for emphasis
      }
      
      datasets.push({
        label: season.season_name,
        data: monthData,
        backgroundColor: getSeasonColor(index, seasonsData.length),
        borderColor: getBorderColor(index, seasonsData.length),
        borderWidth: 1,
        barPercentage: 0.8
      });
    });
    
    currentSeasonsChart = new Chart(currentSeasonsCtx, {
      type: "bar",
      data: {
        labels: months,
        datasets: datasets
      },
      options: {
        responsive: true,
        maintainAspectRatio: false,
        scales: {
          x: {
            title: {
              display: true,
              text: "Month"
            },
            stacked: true
          },
          y: {
            beginAtZero: true,
            max: 2.5, // To accommodate the emphasis on current month
            grid: {
              display: false
            },
            ticks: {
              display: false
            },
            stacked: true
          }
        },
        plugins: {
          title: {
            display: true,
            text: "Current Growing Seasons in Valencia",
            font: {
              size: 16
            }
          },
          tooltip: {
            backgroundColor: document.documentElement.getAttribute("data-theme") === "dark" ? "rgba(45, 45, 63, 0.9)" : "rgba(0, 0, 0, 0.7)",
            titleFont: { size: 14 },
            bodyFont: { size: 13 },
            callbacks: {
              label: function(context) {
                const seasonName = context.dataset.label;
                const monthName = context.label;
                if (context.raw === 0) {
                  return `${seasonName}: Not active in ${monthName}`;
                } else if (context.raw === 2) {
                  return `${seasonName}: Current active season (${monthName})`;
                } else {
                  return `${seasonName}: Active in ${monthName}`;
                }
              }
            }
          }
        }
      }
    });
  }
  
  // Helper Functions
  
  // Check if month is in season range
  function isMonthInSeason(month, startMonth, endMonth) {
    if (startMonth > endMonth) { // Season wraps across year boundary
      return month >= startMonth || month <= endMonth;
    } else {
      return month >= startMonth && month <= endMonth;
    }
  }
  
  // Generate colors for charts
  function generateColors(count) {
    const baseColors = [
      "#4CAF50", "#2196F3", "#9C27B0", "#FF9800", "#F44336",
      "#3F51B5", "#009688", "#FF5722", "#607D8B", "#E91E63",
      "#8BC34A", "#00BCD4", "#FFEB3B", "#9E9E9E", "#FFC107"
    ];
    
    const colors = [];
    for (let i = 0; i < count; i++) {
      colors.push(baseColors[i % baseColors.length]);
    }
    return colors;
  }
  
  // Get a color for each season
  function getSeasonColor(index, total) {
    const baseColors = [
      "rgba(76, 175, 80, 0.7)",  // Green
      "rgba(33, 150, 243, 0.7)", // Blue
      "rgba(156, 39, 176, 0.7)", // Purple
      "rgba(255, 152, 0, 0.7)",  // Orange
      "rgba(244, 67, 54, 0.7)"   // Red
    ];
    return baseColors[index % baseColors.length];
  }
  
  // Get border color for season
  function getBorderColor(index, total) {
    const borderColors = [
      "rgba(46, 125, 50, 1)",    // Dark Green
      "rgba(21, 101, 192, 1)",   // Dark Blue
      "rgba(106, 27, 154, 1)",   // Dark Purple
      "rgba(239, 108, 0, 1)",    // Dark Orange
      "rgba(198, 40, 40, 1)"     // Dark Red
    ];
    return borderColors[index % borderColors.length];
  }
  
  // Create dummy farmers data if real data is unavailable
  function createDummyFarmersData() {
    return [
      { barangay_id: 1, barangay_name: "Balayagmanok", farmer_count: 15, total_farm_area: 45.5 },
      { barangay_id: 2, barangay_name: "Balili", farmer_count: 8, total_farm_area: 25.0 },
      { barangay_id: 3, barangay_name: "Bongbong", farmer_count: 12, total_farm_area: 35.2 },
      { barangay_id: 4, barangay_name: "Cambucad", farmer_count: 6, total_farm_area: 18.8 },
      { barangay_id: 5, barangay_name: "Caidiocan", farmer_count: 10, total_farm_area: 30.0 },
      { barangay_id: 6, barangay_name: "Dobdob", farmer_count: 9, total_farm_area: 27.5 },
      { barangay_id: 7, barangay_name: "Jawa", farmer_count: 7, total_farm_area: 21.0 }
    ];
  }
  
  // Create dummy crops data if real data is unavailable
  function createDummyCropsData() {
    return [
      { barangay_id: 1, barangay_name: "Balayagmanok", product_id: 1, product_name: "Rice", total_production: 250, production_unit: "kg" },
      { barangay_id: 1, barangay_name: "Balayagmanok", product_id: 2, product_name: "Corn", total_production: 180, production_unit: "kg" },
      { barangay_id: 2, barangay_name: "Balili", product_id: 1, product_name: "Rice", total_production: 150, production_unit: "kg" },
      { barangay_id: 3, barangay_name: "Bongbong", product_id: 3, product_name: "Vegetables", total_production: 120, production_unit: "kg" },
      { barangay_id: 4, barangay_name: "Cambucad", product_id: 4, product_name: "Banana", total_production: 300, production_unit: "kg" },
      { barangay_id: 5, barangay_name: "Caidiocan", product_id: 5, product_name: "Coffee", total_production: 75, production_unit: "kg" }
    ];
  }
  
  // Create dummy seasonal data if real data is unavailable
  function createDummySeasonalData() {
    return [
      { season_name: "Dry Season", product_name: "Rice", total_production: 450 },
      { season_name: "Wet Season", product_name: "Rice", total_production: 320 },
      { season_name: "First Cropping", product_name: "Corn", total_production: 280 },
      { season_name: "Second Cropping", product_name: "Vegetables", total_production: 190 },
      { season_name: "Third Cropping", product_name: "Root Crops", total_production: 150 }
    ];
  }
  
  // Create dummy seasons data if real data is unavailable
  function createDummySeasonsData() {
    return [
      { season_id: 1, season_name: "Dry Season", start_month: 11, end_month: 4, description: "November to April" },
      { season_id: 2, season_name: "Wet Season", start_month: 5, end_month: 10, description: "May to October" },
      { season_id: 3, season_name: "First Cropping", start_month: 11, end_month: 2, description: "November to February" },
      { season_id: 4, season_name: "Second Cropping", start_month: 3, end_month: 6, description: "March to June" },
      { season_id: 5, season_name: "Third Cropping", start_month: 7, end_month: 10, description: "July to October" }
    ];
  }
});
