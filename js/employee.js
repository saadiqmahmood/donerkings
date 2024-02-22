document.querySelectorAll('.editIcon').forEach(function(editIcon) {
    editIcon.addEventListener('click', function() {
        var row = this.closest('tr');
        row.querySelectorAll('td:not(:first-child):not(:last-child)').forEach(function(td, index) {
            var text = td.textContent;
            td.innerHTML = '<input autocomplete="off" type="text" class="form-control" value="' + text + '">';
        });

        this.style.display = 'none'; // Hide the edit icon
        row.querySelector('.saveBtn').style.display = 'inline-block';
        row.querySelector('.cancelBtn').style.display = 'inline-block'; // Show save and cancel buttons
    });
});

document.querySelectorAll('.cancelBtn').forEach(function(cancelBtn) {
    cancelBtn.addEventListener('click', function() {
        var row = this.closest('tr');
        row.querySelectorAll('input[type="text"]').forEach(function(input) {
            var td = input.parentElement;
            var text = input.value;
            td.textContent = text; // Replace input fields back to text
        });

        row.querySelector('.editIcon').style.display = 'inline-block'; // Show the edit icon
        row.querySelector('.saveBtn').style.display = 'none';
        row.querySelector('.cancelBtn').style.display = 'none'; // Hide save and cancel buttons
    });
});

document.querySelectorAll('.saveBtn').forEach(function(saveBtn) {
    saveBtn.addEventListener('click', function() {
        var row = this.closest('tr');
        var employeeId = this.getAttribute('data-id');
        var updatedData = {};

        row.querySelectorAll('input[type="text"]').forEach(function(input) {
            var field = input.closest('td').dataset.field; // Use dataset for data-* attributes
            var value = input.value;
            if (field) updatedData[field] = value;
        });

        // Convert updatedData to URL-encoded form data
        var formData = new URLSearchParams();
        formData.append('id', employeeId);
        Object.keys(updatedData).forEach(key => {
            formData.append(`data[${key}]`, updatedData[key]);
        });

        fetch('employee.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: formData
        })
        .then(response => response.text())
        .then(text => {
            speakText('Employee details updated successfully.');
            sessionStorage.setItem('updateSuccess', 'true');
            location.reload();
        })
        .catch(error => showNotification("An error occurred"));
    });
});

document.addEventListener('DOMContentLoaded', function() {
    const urlParams = new URLSearchParams(window.location.search);
    if (urlParams.has('addSuccess')) {
        speakText('New employee added successfully.');
        showNotification("New employee added successfully", 5000);
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
    var selectedIds = Array.from(document.querySelectorAll('.employeeCheckbox:checked')).map(checkbox => checkbox.value);

    if (selectedIds.length === 0) {
        speakText('PLease select at least one employee to delete.');
        alert('Please select at least one employee to delete.');
        return;
    }

    speakText('Are you sure you want to delete the selected employees?');
    if (confirm('Are you sure you want to delete the selected employees?')) {
        var formData = new URLSearchParams();
        selectedIds.forEach(id => formData.append('employee_id[]', id)); // Match the PHP $_POST key

        fetch('employee.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: formData
        })
            .then(response => response.text()) // Use .json() if your PHP script sends a JSON response
            .then(text => {
                speakText('Selected employees deleted successfully.');
                sessionStorage.setItem('deleteSuccess', 'Selected employees deleted successfully.');
                location.reload(); // Reload the page to reflect the changes
            })
            .catch(error => {
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
