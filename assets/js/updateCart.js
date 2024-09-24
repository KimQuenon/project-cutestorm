console.log('updateCart.js loaded');

document.addEventListener('DOMContentLoaded', function() {
    function updateQuantity(itemId, change) {
        const quantityInput = document.getElementById('quantity-' + itemId);
        let currentQuantity = parseInt(quantityInput.value, 10);
        currentQuantity += change;
    
        if (currentQuantity < 1) {
            currentQuantity = 1;
        }
    
        quantityInput.value = currentQuantity;
    
        const priceText = quantityInput.closest('tr').querySelector('td:nth-child(6)').textContent.replace('$', '').replace('.', '').replace(',', '.').trim();
        const price = parseFloat(priceText);
        
        const totalCell = document.getElementById('total-' + itemId);
        const total = (price * currentQuantity).toFixed(2);
        totalCell.textContent = total.replace('.', ',') + ' $';
    
        updateCartTotal();
    }
    
    function updateCartTotal() {
        let cartTotal = 0;
        const totalCells = document.querySelectorAll('td[id^="total-"]');
        totalCells.forEach(cell => {
            const totalText = cell.textContent.replace(' $', '').replace('.', '').replace(',', '.').trim();
            const total = parseFloat(totalText);
            if (!isNaN(total)) {
                cartTotal += total;
            }
        });
        
        const totalRow = document.querySelector('.totalRow');
        totalRow.textContent = cartTotal.toFixed(2).replace('.', ',') + ' $';
    }
});
