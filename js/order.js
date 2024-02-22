document.querySelectorAll('.editIcon').forEach(function(editIcon) {
    editIcon.addEventListener('click', function() {
        var row = this.closest('tr');
        
        // Apply changes to all td elements except for the first, last, and those within editIconsContainer
        row.querySelectorAll('td:not(:first-child):not(:last-child)').forEach(function(td) {
            // Check if the td is the status column
            if (td.classList.contains('status')) {
                // Assuming you might want to make changes to the select element, like enabling it
                var select = td.querySelector('select');
                if (select) {
                    select.disabled = false; // Enable the select if it was disabled
                }
            } else if (!td.classList.contains('editIconsContainer')) {
                // For other columns, replace their content with an input field
                var text = td.textContent;
                td.innerHTML = '<input autocomplete="off" type="text" class="form-control" value="' + text + '">';
            }
        });

        this.style.display = 'none'; // Hide the edit icon
        
        var saveBtn = row.querySelector('.saveBtn');
        if (saveBtn) saveBtn.style.display = 'inline-block';
        
        var cancelBtn = row.querySelector('.cancelBtn');
        if (cancelBtn) cancelBtn.style.display = 'inline-block';
        
        var optionsIcon = row.querySelector('.optionsIcon');
        if (optionsIcon) optionsIcon.style.display = 'none';
    });
});
document.querySelectorAll('.cancelBtn').forEach(function(cancelBtn) {
    cancelBtn.addEventListener('click', function() {
        var row = this.closest('tr');
        row.querySelectorAll('td:not(:first-child):not(:last-child)').forEach(function(td) {
            if (!td.classList.contains('editIconsContainer')) {
                // Check if the td contains a select element (for the 'status' field)
                const select = td.querySelector('select');
                if (select) {
                    // If there's a select element, do nothing to keep it as is
                } else {
                    // If there's no select element, revert the td to its original state
                    const input = td.querySelector('input');
                    if (input) {
                        td.textContent = input.value; // Use the input's current value
                    }
                }
            }
        });

        // Reset the visibility of icons
        row.querySelector('.editIcon').style.display = 'inline-block';
        row.querySelector('.saveBtn').style.display = 'none';
        row.querySelector('.cancelBtn').style.display = 'none';
        row.querySelector('.optionsIcon').style.display = 'inline-block';
    });
});

document.querySelectorAll('.saveBtn').forEach(function (saveBtn) {
    saveBtn.addEventListener('click', function () {
        var row = this.closest('tr');
        var orderId = this.getAttribute('data-id');
        var updatedData = {};

        row.querySelectorAll('input[type="text"]').forEach(function (input) {
            var field = input.closest('td').dataset.field; // Use dataset for data-* attributes
            var value = input.value;
            updatedData[field] = value;
        });

        // Convert updatedData to URL-encoded form data
        var formData = new URLSearchParams();
        formData.append('id', orderId);
        Object.keys(updatedData).forEach(key => {
            formData.append(`data[${key}]`, updatedData[key]);
        });

        fetch('order.php', { // Adjust the URL to your order update handling script
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: formData
        })
            .then(response => response.text())
            .then(text => {

                sessionStorage.setItem('updateSuccess', 'true');
                location.reload();
            })
            .catch(error => console.error("An error occurred", error));
    });
});

document.querySelectorAll('.status-select').forEach(function (select) {
    select.addEventListener('change', function () {
        var orderId = this.getAttribute('data-id');
        var newStatus = this.value;

        // Example AJAX call to update the status in the database
        fetch('order.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: `order_id=${orderId}&status=${newStatus}`
        })
            .then(response => response.text()) // Process response as text
            .then(text => {
                console.log(text);
                if (text.trim() === "success") {
                    speakText('Status updated successfully.');
                    console.log('Status updated successfully');
                } else {
                }
            })
            .catch(error => console.error('Error:', error));
    });
});

document.querySelectorAll('.orderCheckbox, .editIcon, .saveBtn, .cancelBtn, .optionsIcon').forEach(function(icon) {
    icon.addEventListener('keydown', function(e) {
        if (e.key === 'Enter' || e.keyCode === 13) {
            console.log('Enter key pressed on icon');
            // You can also directly simulate a click here for testing
            icon.click();
        }
    });
});

document.addEventListener('DOMContentLoaded', function() {
    const urlParams = new URLSearchParams(window.location.search);
    
    // Check for 'addSuccess' and show notification only if it hasn't been shown yet
    if (urlParams.has('addSuccess') && !sessionStorage.getItem('addSuccess')) {
        showNotification("New order added successfully", 5000);
        speakText('New order added successfully.'); // Speak alert message
        sessionStorage.setItem('addSuccess', 'true'); // Mark as shown
    }

    // Handle 'updateSuccess'
    if (sessionStorage.getItem('updateSuccess') === 'true') {
        showNotification("Update successful", 5000);
        sessionStorage.removeItem('updateSuccess'); // Clear flag
    }

    // Handle 'deleteSuccess'
    if (sessionStorage.getItem('deleteSuccess')) {
        showNotification(sessionStorage.getItem('deleteSuccess'), 5000);
        sessionStorage.removeItem('deleteSuccess'); // Clear flag
    }

    // Check for 'stockError' and show notification only if it hasn't been shown yet
    if (urlParams.has('stockError') && !sessionStorage.getItem('stockError')) {
        speakText('Inventory needs to be restocked.'); // Speak alert message
        showNotification("Not all products are available", 5000);
        sessionStorage.setItem('stockError', 'true'); // Mark as shown
    }

    // Cleanup: Remove query parameters from URL without refreshing the page
    if (urlParams.has('addSuccess') || urlParams.has('stockError')) {
        let newUrl = window.location.pathname;
        history.replaceState(null, '', newUrl); // Remove query parameters
    }
});


document.getElementById('deleteSelectedBtn').addEventListener('click', function () {
    var selectedIds = Array.from(document.querySelectorAll('.orderCheckbox:checked')).map(checkbox => checkbox.value);

    if (selectedIds.length === 0) {
        speakText('Please select at least one order to delete.'); // Speak alert message
        alert('Please select at least one order to delete.');
        return;
    }

    speakText('Are you sure you want to delete the selected orders?'); // Speak confirm message
    if (confirm('Are you sure you want to delete the selected orders?')) {
        var formData = new URLSearchParams();
        selectedIds.forEach(id => formData.append('order_id[]', id));

        fetch('order.php', { 
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: formData
        })
        .then(response => response.text())
        .then(text => {
            speakText('Selected orders deleted successfully.'); 
            sessionStorage.setItem('deleteSuccess', 'Selected orders deleted successfully.');
            location.reload();
        })
        .catch(error => {
            speakText('Error occurred while deleting orders.'); // Speak error message
            console.error('Error:', error);
        });
    }
});

function speakText(text) {
    if ('speechSynthesis' in window) {
        var utterance = new SpeechSynthesisUtterance(text);
        window.speechSynthesis.speak(utterance);
    } else {
        console.error('Speech synthesis not supported.');
    }
}

// Order Details

document.addEventListener('click', function(e) {
    if (e.target.classList.contains('removeOrderDetail')) {
        e.target.closest('.flex-column').remove(); // Removes the row containing the clicked delete button
    }
});

document.addEventListener('DOMContentLoaded', function() {
    const orderDetailsSection = document.getElementById('orderDetailsSection');
    const addOrderDetailBtn = document.getElementById('addOrderDetail');

    // Function to create a new order detail row
    function createOrderDetailRow() {
        const rowDiv = document.createElement('div');
        rowDiv.classList.add('d-flex', 'justify-content-center', 'align-items-center', 'flex-row', 'mb-3');
        rowDiv.innerHTML = `
            <div class="col">${menuItemsDropdown}</div>
            <div class="col">
                <input type="number" class="order-detail-form-control" name="quantities[]" placeholder="Quantity" min="1">
            </div>
            <i type="button" style="color: #c1121f;" class="bi bi-x-lg removeOrderDetail"></i>
        `;
        // Add event listener for the remove button in this row
        rowDiv.querySelector('.removeOrderDetail').addEventListener('click', function() {
            rowDiv.remove();
        });
        return rowDiv;
    }

    // Add new row on "Add Item" button click
    addOrderDetailBtn.addEventListener('click', function() {
        orderDetailsSection.appendChild(createOrderDetailRow());
    });
});

document.querySelectorAll('.optionsIcon').forEach(function(icon) {
    icon.addEventListener('click', function() {
        const orderId = this.getAttribute('data-order-id');
        fetch(`order.php?view_order_details=true&order_id=${orderId}`)
            .then(response => response.text())
            .then(html => {
                document.getElementById('orderDetailsContent').innerHTML = html;
                var orderDetailsModal = new bootstrap.Modal(document.getElementById('orderDetailsModal'));
                orderDetailsModal.show();
            })
            .catch(error => console.error('Error loading order details:', error));
    });
});

document.querySelectorAll('.productCheckbox, .editIcon, .saveBtn, .cancelBtn').forEach(function(icon) {
    icon.addEventListener('keydown', function(e) {
        if (e.key === 'Enter' || e.keyCode === 13) {
            console.log('Enter key pressed on icon');
            icon.click();
        }
    });
});