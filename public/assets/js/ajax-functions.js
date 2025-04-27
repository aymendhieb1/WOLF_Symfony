// AJAX function for handling deletions
function handleDelete(url, element) {
    if (confirm('Êtes-vous sûr de vouloir supprimer cet élément?')) {
        // Get the CSRF token from the form
        const token = $(element).closest('form').find('input[name="_token"]').val();
        
        $.ajax({
            url: url,
            type: 'POST',
            data: {
                '_token': token,
                '_method': 'DELETE'  // Add this to indicate it's a DELETE request
            },
            headers: {
                'X-CSRF-TOKEN': token
            },
            success: function(response) {
                // Remove the row from the table
                $(element).closest('tr').fadeOut(300, function() {
                    $(this).remove();
                    
                    // Check if table is empty
                    if ($('tbody tr').length === 0) {
                        $('tbody').html('<tr><td colspan="6" class="text-center">Aucun enregistrement trouvé</td></tr>');
                    }
                    
                    // Show success notification
                    showNotification('top', 'right', 'success', 'Suppression réussie');
                });
            },
            error: function(xhr) {
                // Show error notification
                showNotification('top', 'right', 'danger', 'Erreur lors de la suppression');
                console.error('Error:', xhr);
            }
        });
    }
    return false; // Prevent form submission
}

// Function to show notifications
function showNotification(from, align, type, message) {
    $.notify({
        icon: "notifications",
        message: message
    }, {
        type: type,
        timer: 3000,
        placement: {
            from: from,
            align: align
        }
    });
} 