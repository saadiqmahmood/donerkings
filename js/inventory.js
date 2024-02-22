document.querySelectorAll('.editIcon').forEach(function(editIcon) {
    editIcon.addEventListener('click', function() {
        var row = this.closest('tr');
        row.querySelectorAll('td:not(:first-child):not(:last-child)').forEach(function(td) {
            var originalText = td.textContent.trim(); // Get the current text of the td

            // Check if the td is for a field that should be a select dropdown
            if (td.dataset.field === 'supplier_id') {
                // Create and configure the select element
                var select = document.createElement('select');
                select.className = 'form-control';
                select.name = 'supplier_id';
                select.setAttribute('data-original-value', originalText); // Store the original text

                // Populate the select with options, including setting the correct original option as selected
                var optionsHtml = `<option value="" disabled>Select a supplier</option>
                                   <option value="1" ${originalText === 'Halal Butchers Co.' ? 'selected' : ''}>Halal Butchers Co.</option>
                                   <option value="2" ${originalText === 'Golden Wheat Bakery' ? 'selected' : ''}>Golden Wheat Bakery</option>
                                   <option value="3" ${originalText === 'Veggie Delight Wholesalers' ? 'selected' : ''}>Veggie Delight Wholesalers</option>
                                   <option value="4" ${originalText === 'Flavor Fusion Sauces' ? 'selected' : ''}>Flavor Fusion Sauces</option>
                                   <option value="5" ${originalText === 'Crispy Edge Potatoes' ? 'selected' : ''}>Crispy Edge Potatoes</option>
                                   <option value="6" ${originalText === 'Refresh Beverage Distributors' ? 'selected' : ''}>Refresh Beverage Distributors</option>`;
                select.innerHTML = optionsHtml;
                td.innerHTML = ''; // Clear the td before appending the select
                td.appendChild(select);
            } else {
                // For other fields, use an input element
                var input = document.createElement('input');
                input.type = 'text';
                input.className = 'form-control';
                input.value = originalText;
                input.setAttribute('data-original-value', originalText); // Store the original text
                td.innerHTML = ''; // Clear the td before appending the input
                td.appendChild(input);
            }
        });

        // Adjust visibility of icons and buttons
        this.style.display = 'none'; // Hide the edit icon
        row.querySelector('.saveBtn').style.display = 'inline-block';
        row.querySelector('.cancelBtn').style.display = 'inline-block';
    });
});

document.querySelectorAll('.cancelBtn').forEach(function(cancelBtn) {
    cancelBtn.addEventListener('click', function() {
        var row = this.closest('tr');
        row.querySelectorAll('td').forEach(function(td) {
            // Check if the cell contains an input or select element
            var input = td.querySelector('input[type="text"]');
            var select = td.querySelector('select');

            if (input) {
                // If it's an input, revert to the original value
                var originalText = input.getAttribute('data-original-value');
                td.textContent = originalText;
            } else if (select) {
                // If it's a select, find the option with the original value and revert to displaying its text
                var originalValue = select.getAttribute('data-original-value');
                var originalTextOption = Array.from(select.options).find(option => option.textContent === originalValue);
                if (originalTextOption) {
                    td.textContent = originalTextOption.textContent;
                } else {
                    // If for some reason the original option is not found, clear the cell or handle as needed
                    td.textContent = ''; // or some default/fallback text
                }
            }
        });

        // Restore visibility of edit icons and hide save and cancel buttons
        row.querySelector('.editIcon').style.display = 'inline-block';
        row.querySelector('.saveBtn').style.display = 'none';
        row.querySelector('.cancelBtn').style.display = 'none';
    });
});

document.querySelectorAll('.saveBtn').forEach(function(saveBtn) {
    saveBtn.addEventListener('click', function() {
        var row = this.closest('tr');
        var productId = this.getAttribute('data-id');
        var updatedData = {};

        // Handle input fields
        row.querySelectorAll('input[type="text"]').forEach(function(input) {
            var field = input.closest('td').dataset.field; // Use dataset for data-* attributes
            var value = input.value;
            if (field) updatedData[field] = value;
        });

        // Handle select dropdowns, specifically for the supplier_id
        row.querySelectorAll('select').forEach(function(select) {
            var field = select.name; // Assuming the name attribute is used for identifying the field
            var value = select.value;
            if (field) updatedData[field] = value;
        });

        // Convert updatedData to URL-encoded form data
        var formData = new URLSearchParams();
        formData.append('id', productId);
        Object.keys(updatedData).forEach(key => {
            formData.append(`data[${key}]`, updatedData[key]);
        });

        fetch('inventory.php', { // Ensure this points to your product update handling script
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: formData.toString()
        })
        .then(response => response.text())
        .then(text => {
            sessionStorage.setItem('updateSuccess', 'true');
            location.reload();
        })
        .catch(error => console.error("An error occurred", error));
    });
});

document.addEventListener('DOMContentLoaded', function() {
    const urlParams = new URLSearchParams(window.location.search);
    if (urlParams.has('addSuccess')) {
        speakText('New product added successfully');
        showNotification("New product added successfully", 5000);
        let newUrl = window.location.pathname;
        history.pushState(null, '', newUrl);
    }
    if (sessionStorage.getItem('updateSuccess') === 'true') {
        showNotification("Update successful", 5000);
        sessionStorage.removeItem('updateSuccess');
    }
    if (sessionStorage.getItem('deleteSuccess')) {
        showNotification(sessionStorage.getItem('deleteSuccess'), 5000);
        sessionStorage.removeItem('deleteSuccess');
    }
});

document.getElementById('deleteSelectedBtn').addEventListener('click', function () {
    var selectedIds = Array.from(document.querySelectorAll('.productCheckbox:checked')).map(checkbox => checkbox.value);

    if (selectedIds.length === 0) {
        speakText('Please select at least one product to delete.'); // Speak alert message
        showNotification('Please select at least one product to delete.', 5000);
        return;
    }

    speakText('Are you sure you want to delete the selected products?'); // Speak confirm message
    if (confirm('Are you sure you want to delete the selected products?')) {
        var formData = new URLSearchParams();
        selectedIds.forEach(id => formData.append('product_id[]', id));

        fetch('inventory.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: formData.toString()
        })
            .then(response => response.text())
            .then(text => {
                speakText('Selected products deleted successfully.'); 
                sessionStorage.setItem('deleteSuccess', 'Selected products deleted successfully.');
                location.reload();
            })
            .catch(error => {
                speakText('Error occurred while deleting products.'); // Speak error message
                console.error('Error:', error);
                showNotification('Error occurred during deletion: ' + error, 5000);
            });
    }
});

document.querySelectorAll('.productCheckbox, .editIcon, .saveBtn, .cancelBtn').forEach(function(icon) {
    icon.addEventListener('keydown', function(e) {
        if (e.key === 'Enter' || e.keyCode === 13) {
            console.log('Enter key pressed on icon');
            icon.click();
        }
    });
});

function speakText(text) {
    if ('speechSynthesis' in window) {
        var utterance = new SpeechSynthesisUtterance(text);
        window.speechSynthesis.speak(utterance);
    } else {
        console.error('Speech synthesis not supported.');
    }
}