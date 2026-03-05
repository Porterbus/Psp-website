document.addEventListener('DOMContentLoaded', () => {
    const addButtons = document.querySelectorAll('.add-to-cart-btn');
    const cartItemsList = document.getElementById('cart-items');
    const checkoutBtn = document.getElementById('checkout-btn');

    let shoppingCart = [];

    addButtons.forEach(button => {
        button.addEventListener('click', function() {
            const itemId = this.getAttribute('data-id');
            const itemName = this.getAttribute('data-name');

            const existingItem = shoppingCart.find(item => item.id === itemId);
            
            if (existingItem) {
                existingItem.quantity += 1;
            } else {
                shoppingCart.push({ id: itemId, name: itemName, quantity: 1 });
            }
            
            renderCart();
        });
    });

    function renderCart() {
        cartItemsList.innerHTML = '';

        if (shoppingCart.length === 0) {
            cartItemsList.innerHTML = '<li style="color: #777;">Your list is currently empty.</li>';
            checkoutBtn.style.display = 'none';
            return;
        }

        shoppingCart.forEach((item, index) => {
            const li = document.createElement('li');
            li.innerHTML = `
                <span><strong>${item.quantity}x</strong> ${item.name}</span>
                <button class="btn-remove" onclick="removeItem(${index})" aria-label="Remove ${item.name}">X</button>
            `;
            cartItemsList.appendChild(li);
        });

        checkoutBtn.style.display = 'block';
    }

    window.removeItem = function(index) {
        shoppingCart.splice(index, 1);
        renderCart();
    };

    const checkoutModal = document.getElementById('checkout-modal');
    const closeModalBtn = document.getElementById('close-modal');

    checkoutBtn.addEventListener('click', () => {
        checkoutModal.className = 'modal-visible';
        checkoutModal.setAttribute('aria-hidden', 'false');
    });

    closeModalBtn.addEventListener('click', () => {
        checkoutModal.className = 'modal-hidden';
        checkoutModal.setAttribute('aria-hidden', 'true');
    });

    const checkoutForm = document.getElementById('checkout-form');

    checkoutForm.addEventListener('submit', function(event) {
        event.preventDefault();

        const formData = new FormData();
        formData.append('lesson_name', document.getElementById('lesson-name').value.trim());
        formData.append('room', document.getElementById('room-name').value.trim());
        formData.append('required_date', document.getElementById('required-date').value);
        formData.append('other_items', document.getElementById('other-items').value.trim());
        
        formData.append('items', JSON.stringify(shoppingCart)); 

        const fileInput = document.getElementById('upload-file');
        if (fileInput.files.length > 0) {
            formData.append('upload_file', fileInput.files[0]);
        }

        fetch('submit_request.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                alert('Success! Your equipment request has been sent to TORS.');
                shoppingCart = [];
                renderCart();
                checkoutForm.reset();
                checkoutModal.className = 'modal-hidden';
                checkoutModal.setAttribute('aria-hidden', 'true');
            } else {
                alert('Error: ' + data.message);
            }
        })
        .catch(error => {
            console.error('Network Error:', error);
            alert('A network error occurred. Please try again.');
        });
    });
});