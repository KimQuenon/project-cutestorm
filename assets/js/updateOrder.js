document.addEventListener('DOMContentLoaded', function() {
    // Initialize the total price
    // var totalPrice = parseFloat('{{ totalPrice }}');
    var totalPriceElement = document.getElementById('order-total-price');
    var totalPrice = parseFloat(totalPriceElement.getAttribute('data-total-price'));

    // Find all delivery options
    var deliveryOptions = document.querySelectorAll('input[name="order[delivery]"]');

    // Initialize delivery cost to the first delivery option if available
    var deliveryCost = 0;
    if (deliveryOptions.length > 0) {
        var firstDeliveryOption = deliveryOptions[0];
        firstDeliveryOption.checked = true; // Set the first option as checked
        deliveryCost = parseFloat(firstDeliveryOption.getAttribute('data-price'));
    }

    // Update the delivery cost display
    document.getElementById('delivery-cost').textContent = '$ ' + deliveryCost.toFixed(2);

    // Update the grand total display
    var grandTotal = totalPrice + deliveryCost;
    document.getElementById('grand-total').textContent = '$ ' + grandTotal.toFixed(2);

    deliveryOptions.forEach(function(option) {
        option.addEventListener('change', function() {
            // Update delivery cost based on selected option
            var selectedDeliveryOption = document.querySelector('input[name="order[delivery]"]:checked');
            if (selectedDeliveryOption) {
                deliveryCost = parseFloat(selectedDeliveryOption.getAttribute('data-price'));
            }
            // Update total and grand total
            grandTotal = totalPrice + deliveryCost;
            document.getElementById('delivery-cost').textContent = '$ ' + deliveryCost.toFixed(2);
            document.getElementById('grand-total').textContent = '$ ' + grandTotal.toFixed(2);
        });
    });
});