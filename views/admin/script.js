// Function to load user data
function loadUsers() {
  fetch("/api/users")
    .then((response) => response.json())
    .then((data) => {
      let userTable = document.getElementById("userTable");
      userTable.innerHTML = "";
      data.forEach((user) => {
        let row = `<tr>
                                <td>${user.user_id}</td>
                                <td>${user.username}</td>
                                <td>${user.email}</td>
                                <td>
                                    <button class="btn btn-warning btn-sm">Edit</button>
                                    <button class="btn btn-danger btn-sm">Delete</button>
                                </td>
                           </tr>`;
        userTable.innerHTML += row;
      });
    })
    .catch((error) => console.error("Error:", error));
}

// Function to load activity logs
function loadActivityLogs() {
  fetch("/api/activitylogs")
    .then((response) => response.json())
    .then((data) => {
      let logTable = document.getElementById("activityLogTable");
      logTable.innerHTML = "";
      data.forEach((log) => {
        let row = `<tr>
                                <td>${log.log_id}</td>
                                <td>${log.user_id}</td>
                                <td>${log.action}</td>
                                <td>${log.action_date}</td>
                           </tr>`;
        logTable.innerHTML += row;
      });
    })
    .catch((error) => console.error("Error:", error));
}

// Function to load products for management
function loadProducts() {
  fetch("/api/products")
    .then((response) => response.json())
    .then((data) => {
      let productTable = document.getElementById("productTable");
      productTable.innerHTML = "";
      data.forEach((product) => {
        let row = `<tr>
                                <td>${product.product_id}</td>
                                <td>${product.name}</td>
                                <td>${product.status}</td>
                                <td>
                                    <button class="btn btn-success btn-sm">Approve</button>
                                    <button class="btn btn-danger btn-sm">Reject</button>
                                </td>
                           </tr>`;
        productTable.innerHTML += row;
      });
    })
    .catch((error) => console.error("Error:", error));
}

// Load orders, pickup info, and report generation similarly
function loadOrders() {
  /* Similar to loadUsers */
}
function loadPickups() {
  /* Similar to loadUsers */
}
function generateReport() {
  /* Code for generating reports */
}

// Call functions on page load
document.addEventListener("DOMContentLoaded", () => {
  loadUsers();
  loadActivityLogs();
  loadProducts();
  loadOrders();
  loadPickups();
});

// Event listeners for buttons like Add User and Generate Report
document.getElementById("addUser").addEventListener("click", function () {
  alert("Add User button clicked");
});
document
  .getElementById("generateReport")
  .addEventListener("click", generateReport);
