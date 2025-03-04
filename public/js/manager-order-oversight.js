$(document).ready(function () {
  $(".manage-pickup-btn").click(function () {
    // Get data from button attributes
    var orderId = $(this).data("order-id");
    var pickupId = $(this).data("pickup-id");
    var pickupDate = $(this).data("pickup-date");
    var pickupLocation = $(this).data("pickup-location");
    var assignedTo = $(this).data("assigned-to");
    var pickupNotes = $(this).data("pickup-notes");

    // Populate the modal form
    $("#order_id").val(orderId);
    $("#pickup_id").val(pickupId); // Add pickup_id to the form
    $("#pickup_date").val(pickupDate);
    $("#pickup_location").val(pickupLocation);
    $("#assigned_to").val(assignedTo);
    $("#pickup_notes").val(pickupNotes);
  });
});
