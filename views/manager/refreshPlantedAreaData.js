// Track AJAX requests to prevent duplicates
let ajaxInProgress = false;

/**
 * Check if an AJAX request is currently in progress
 * @returns {boolean} True if a request is in progress, false otherwise
 */
function isAjaxInProgress() {
  return ajaxInProgress;
}

/**
 * Set the AJAX in progress flag
 * @param {boolean} status - The new status
 */
function setAjaxInProgress(status) {
  ajaxInProgress = !!status;
}

/**
 * Refresh planted area data for a specific product
 * @param {number} productId - The ID of the product to fetch data for
 * @param {Function} [callback] - Optional callback function to run after data is loaded
 */
function refreshPlantedAreaData(productId, callback) {
  if (!productId) {
    console.error("Error: No product ID provided to refreshPlantedAreaData");
    $("#plantedAreaAlert")
      .removeClass("alert-success")
      .addClass("alert-danger")
      .html("<strong>Error:</strong> Invalid product ID.")
      .show();
    return;
  }

  // Prevent duplicate requests
  if (isAjaxInProgress()) {
    console.warn(
      "AJAX request already in progress. Skipping duplicate request."
    );
    return;
  }

  // Mark that a request is starting
  setAjaxInProgress(true);

  // Clear previous data
  $("#plantedAreaTableBody").empty();
  $("#plantedAreaAlert").hide();

  // Show loading state
  $("#plantedAreaTableBody").html(
    '<tr><td colspan="6" class="text-center"><div class="spinner-border text-primary" role="status"><span class="sr-only">Loading...</span></div></td></tr>'
  );

  // Fetch planted area data via AJAX
  $.ajax({
    url: "../../ajax/get-planted-area.php",
    type: "GET",
    data: { product_id: productId },
    dataType: "json",
    success: function (response) {
      $("#plantedAreaTableBody").empty();

      if (response.success) {
        if (response.data && response.data.length > 0) {
          // Populate the table with the data
          $.each(response.data, function (index, item) {
            // Parse numeric strings to actual numbers
            const estimatedProduction = parseFloat(item.estimated_production);
            const plantedArea = parseFloat(item.planted_area);

            // Ensure valid numeric values or default to 0.00
            const validEstimatedProduction = isNaN(estimatedProduction)
              ? "0.00"
              : estimatedProduction.toFixed(2);
            const validPlantedArea = isNaN(plantedArea)
              ? "0.00"
              : plantedArea.toFixed(2);

            let row = `
                            <tr>
                                <td>${item.barangay_name || "N/A"}</td>
                                <td>${item.season_name || "N/A"}</td>
                                <td>${validEstimatedProduction}</td>
                                <td>${item.production_unit || "kilogram"}</td>
                                <td>${validPlantedArea}</td>
                                <td>
                                    <button class="btn btn-sm btn-primary edit-planted-area" 
                                            data-id="${item.id}"
                                            data-product-id="${productId}"
                                            data-barangay="${
                                              item.barangay_name || ""
                                            }"
                                            data-season="${
                                              item.season_name || ""
                                            }"
                                            data-production="${validEstimatedProduction}"
                                            data-unit="${
                                              item.production_unit || "kilogram"
                                            }"
                                            data-area="${validPlantedArea}">
                                        <i class="bi bi-pencil"></i>
                                    </button>
                                </td>
                            </tr>
                        `;
            $("#plantedAreaTableBody").append(row);
          });
        } else {
          // No data found
          $("#plantedAreaTableBody").html(
            '<tr><td colspan="6" class="text-center">No planted area data found for this product.</td></tr>'
          );
          $("#plantedAreaAlert")
            .removeClass("alert-danger")
            .addClass("alert-info")
            .html(
              "<strong>Info:</strong> No planted area data exists for this product yet. The ability to add new entries will be implemented soon."
            )
            .show();
        }
      } else {
        // Error message
        $("#plantedAreaAlert")
          .removeClass("alert-success")
          .addClass("alert-danger")
          .html(
            "<strong>Error:</strong> " +
              (response.message || "Failed to load planted area data.")
          )
          .show();
        $("#plantedAreaTableBody").html(
          '<tr><td colspan="6" class="text-center">Failed to fetch planted area data.</td></tr>'
        );
      }
      // Execute callback if provided
      if (typeof callback === "function") {
        callback(response);
      }
    },
    error: function (xhr, status, error) {
      console.error("AJAX Error:", status, error);
      $("#plantedAreaAlert")
        .removeClass("alert-success")
        .addClass("alert-danger")
        .html(
          "<strong>Error:</strong> Failed to load planted area data. Please try again."
        )
        .show();
      $("#plantedAreaTableBody").html(
        '<tr><td colspan="6" class="text-center">Error loading data.</td></tr>'
      );

      // Execute callback with error if provided
      if (typeof callback === "function") {
        callback({ success: false, message: "Connection error" });
      }
    },
    complete: function () {
      // Reset the in-progress flag when the request completes
      setAjaxInProgress(false);
    },
  });
}
