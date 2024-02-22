document.querySelectorAll('.editIcon').forEach(function (editIcon) {
    editIcon.addEventListener('click', function () {
        var row = this.closest('tr');
        row.querySelectorAll('td.edit').forEach(function (td) {
            var text = td.textContent.trim();
            // Use .trim() to remove any potential whitespace from the text
            // Check if the current td is the price field
            if (td.dataset.field === 'price') {
                // Extract the price value without the currency symbol
                text = text.replace('£', '').trim();
            }
            td.innerHTML = '<input autocomplete="off" type="text" class="form-control" value="' + text + '">';
        });

        // Hide the edit icon immediately
        this.style.display = 'none';

        // Ensure we accurately find the container for the icons
        var iconsContainer = row.querySelector('.editIconsContainer');
        if (iconsContainer) {
            var saveBtn = iconsContainer.querySelector('.saveBtn');
            var cancelBtn = iconsContainer.querySelector('.cancelBtn');

            if (saveBtn && cancelBtn) {
                saveBtn.style.display = 'inline-block';
                cancelBtn.style.display = 'inline-block';
            }
        } else {
            console.error('Could not find the editIconsContainer.');
        }
    });
});

document.querySelectorAll('.cancelBtn').forEach(function (cancelBtn) {
    cancelBtn.addEventListener('click', function () {
        var row = this.closest('tr');
        row.querySelectorAll('input[type="text"]').forEach(function (input) {
            var td = input.parentElement;
            var text = input.value;
            td.textContent = text; // Replace input fields back to text
        });

        row.querySelector('.editIcon').style.display = 'inline-block'; // Show the edit icon
        row.querySelector('.saveBtn').style.display = 'none';
        row.querySelector('.cancelBtn').style.display = 'none'; // Hide save and cancel buttons
    });
});

document.querySelectorAll('.saveBtn').forEach(function (saveBtn) {
    saveBtn.addEventListener('click', function () {
        var row = this.closest('tr');
        var menuId = this.getAttribute('data-id'); // Changed to menuId
        var updatedData = {};

        row.querySelectorAll('input[type="text"]').forEach(function (input) {
            var field = input.closest('td').dataset.field; // Use dataset for data-* attributes
            var value = input.value;
            if (field) updatedData[field] = value;
        });

        // Convert updatedData to URL-encoded form data
        var formData = new URLSearchParams();
        formData.append('id', menuId); // Changed to menuId
        Object.keys(updatedData).forEach(key => {
            formData.append(`data[${key}]`, updatedData[key]);
        });

        fetch('menu.php', { // Changed to menu.php
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: formData
        })
            .then(response => response.text())
            .then(text => {
                sessionStorage.setItem('updateSuccess', 'true');
                location.reload();
            })
            .catch(error => showNotification("An error occurred"));
    });
});

document.addEventListener('DOMContentLoaded', function () {
    const urlParams = new URLSearchParams(window.location.search);
    if (urlParams.has('addSuccess')) {
        speakText('New menu item added successfully.');
        showNotification("New menu item added successfully", 5000); // Changed to "Menu added successfully"
        let newUrl = window.location.pathname;
        history.pushState(null, '', newUrl);
    }
    if (sessionStorage.getItem('updateSuccess') === 'true') {
        showNotification("Update successful");
        sessionStorage.removeItem('updateSuccess'); // Clear the flag after showing the message
    }
    if (sessionStorage.getItem('deleteSuccess')) {
        showNotification(sessionStorage.getItem('deleteSuccess'));
        sessionStorage.removeItem('deleteSuccess');
    }
});

document.getElementById('deleteSelectedBtn').addEventListener('click', function () {
    var selectedIds = Array.from(document.querySelectorAll('.menuCheckbox:checked')).map(checkbox => checkbox.value); // Changed to menuCheckbox

    if (selectedIds.length === 0) {
        speakText('Please select at least one menu item to delete.'); // Speak alert message
        alert('Please select at least one menu item to delete.'); // Changed to menu item
        return;
    }

    speakText('Are you sure you want to delete the selected orders?'); // Speak confirm message
    if (confirm('Are you sure you want to delete the selected menu items?')) { // Changed to menu items
        var formData = new URLSearchParams();
        selectedIds.forEach(id => formData.append('menu_id[]', id)); // Changed to menu_id

        fetch('menu.php', { // Changed to menu.php
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: formData
        })
            .then(response => response.text()) // Use .json() if your PHP script sends a JSON response
            .then(text => {
                speakText('Selected menu items deleted successfully.'); 
                sessionStorage.setItem('deleteSuccess', 'Selected menu items deleted successfully.'); // Changed to menu items
                location.reload(); // Reload the page to reflect the changes
            })
            .catch(error => {
                speakText('Error occurred during deletion.');
                console.error('Error:', error);
                showNotification('Error occurred during deletion: ' + error);
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