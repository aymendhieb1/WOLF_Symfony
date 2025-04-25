$(document).ready(function () {
    // Initialize DataTable
    var table = $('#datatables').DataTable({
        pagingType: "full_numbers",
        lengthMenu: [[10, 25, 50, -1], [10, 25, 50, "All"]],
        responsive: true,
        language: { search: "", searchPlaceholder: "Chercher" }
    });

    // Notification function
    function showNotification(type, message) {
        $.notify({
            icon: type === 'success' ? "check" : "warning",
            message: message
        }, {
            type: type === 'success' ? 'success' : 'danger',
            timer: 3000,
            placement: { from: 'top', align: 'right' }
        });
    }

    // Function to refresh table data
    function refreshTable() {
        $.ajax({
            url: '/back/contrat/refresh',
            type: 'GET',
            success: function(response) {
                // Clear and redraw the table with new data
                table.clear();
                table.rows.add(response);
                table.draw();
            },
            error: function(xhr, status, error) {
                console.error('Error refreshing table:', error);
            }
        });
    }

    // Initialize date pickers
    $('.datepicker').datetimepicker({
        format: 'YYYY-MM-DD',
        icons: {
            time: "fa fa-clock-o",
            date: "fa fa-calendar",
            up: "fa fa-chevron-up",
            down: "fa fa-chevron-down",
            previous: 'fa fa-chevron-left',
            next: 'fa fa-chevron-right',
            today: 'fa fa-screenshot',
            clear: 'fa fa-trash',
            close: 'fa fa-remove'
        }
    });

    // Preview image function
    function previewImage(input, previewId) {
        if (input.files && input.files[0]) {
            var reader = new FileReader();
            reader.onload = function(e) {
                $('#' + previewId).attr('src', e.target.result).show();
            };
            reader.readAsDataURL(input.files[0]);
        }
    }

    // Handle file input change for add form
    $('#photo_permit').change(function() {
        var fileName = $(this).val().split('\\').pop();
        $(this).next('.custom-file-label').html(fileName);
        
        if (this.files && this.files[0]) {
            var reader = new FileReader();
            reader.onload = function(e) {
                $('#previewPhotoPermit img').attr('src', e.target.result);
                $('#previewPhotoPermit').show();
            };
            reader.readAsDataURL(this.files[0]);
        }
    });

    // Handle file input change for edit form
    $('#editPhotoPermit').change(function() {
        var fileName = $(this).val().split('\\').pop();
        $(this).next('.custom-file-label').html(fileName);
        
        if (this.files && this.files[0]) {
            var reader = new FileReader();
            reader.onload = function(e) {
                $('#previewEditPhotoPermit img').attr('src', e.target.result);
                $('#previewEditPhotoPermit').show();
            };
            reader.readAsDataURL(this.files[0]);
        }
    });

    // Format date string function
    function formatDateString(dateStr) {
        if (!dateStr) return '';
        // Remove any non-numeric characters
        return dateStr.replace(/[^0-9]/g, '');
    }

    // Validate date string function
    function isValidDateString(dateStr) {
        // Check if string is 8 digits (YYYYMMDD)
        return /^\d{8}$/.test(dateStr);
    }

    // Compare date strings (YYYYMMDD format)
    function compareDateStrings(date1, date2) {
        return parseInt(date1) - parseInt(date2);
    }

    // Remove duplicate form submission handlers
    $('#submitContrat').off('click');
    $('#addContratForm').off('submit');

    // Validate dates before form submission
    function validateDates(formData) {
        const dateD = formData.get('contrat[dateD]');
        const dateF = formData.get('contrat[dateF]');
        
        // Check if dates are in YYYY-MM-DD format
        const dateRegex = /^\d{4}-\d{2}-\d{2}$/;
        if (!dateRegex.test(dateD) || !dateRegex.test(dateF)) {
            Swal.fire({
                icon: 'error',
                title: 'Erreur',
                text: 'Format de date invalide. Utilisez le format YYYY-MM-DD'
            });
            return false;
        }

        // Compare dates
        if (dateF < dateD) {
            Swal.fire({
                icon: 'error',
                title: 'Erreur',
                text: 'La date de fin doit être postérieure à la date de début'
            });
            return false;
        }

        return true;
    }

    // Handle form submission
    $('#submitContrat').on('click', function(e) {
        e.preventDefault();
        var formData = new FormData($('#addContratForm')[0]);
        
        $.ajax({
            url: '/back/contrat/new',
            type: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            success: function(response) {
                $('#addContratModal').modal('hide');
                $('#addContratForm')[0].reset();
                refreshTable();
            },
            error: function(xhr, status, error) {
                $('#addContratModal').modal('hide');
                $('#addContratForm')[0].reset();
                refreshTable();
            }
        });
    });

    // Remove all existing handlers first
    $(document).off('click', '.edit');
    $(document).off('click', '.edit-contrat');
    $(document).off('click', '.delete-contrat');
    $(document).off('click', '.remove');
    $('#saveContratChanges').off('click');

    // Edit button click handler
    $(document).on('click', '.edit', function(e) {
        e.preventDefault();
        var button = $(this);
        
        $('#editId').val(button.data('id'));
        $('#editDateD').val(button.data('dated'));
        $('#editDateF').val(button.data('datef'));
        $('#editCinLocateur').val(button.data('cinlocateur'));
        $('#editVehicule').val(button.data('id_vehicule'));
        
        $('#editContratModal').modal('show');
    });

    // Save changes button click handler
    $('#saveContratChanges').on('click', function(e) {
        e.preventDefault();
        var id = $('#editId').val();
        var formData = new FormData($('#editContratForm')[0]);
        
        $.ajax({
            url: '/back/contrat/' + id + '/edit',
            type: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            success: function(response) {
                $('#editContratModal').modal('hide');
                $('#editContratForm')[0].reset();
                refreshTable();
            },
            error: function(xhr, status, error) {
                $('#editContratModal').modal('hide');
                $('#editContratForm')[0].reset();
                refreshTable();
            }
        });
    });

    // Delete button click handler
    $(document).on('click', '.delete-contrat', function(e) {
        e.preventDefault();
        var button = $(this);
        var id = button.data('id');
        
        if (confirm('Êtes-vous sûr de vouloir supprimer ce contrat?')) {
            $.ajax({
                url: '/back/contrat/delete/' + id,
                type: 'POST',
                success: function(response) {
                    refreshTable();
                },
                error: function(xhr, status, error) {
                    refreshTable();
                }
            });
        }
    });

    // Form validation
    function validateForm() {
        var isValid = true;
        var dateD = $('#dateD').val();
        var dateF = $('#dateF').val();
        var cinlocateur = $('#cinlocateur').val();
        var id_vehicule = $('#id_vehicule').val();

        if (!dateD || !dateF || !cinlocateur || !id_vehicule) {
            showNotification('warning', 'Veuillez remplir tous les champs obligatoires');
            isValid = false;
        }

        if (cinlocateur && !/^\d{8}$/.test(cinlocateur)) {
            showNotification('warning', 'Le CIN doit contenir exactement 8 chiffres');
            isValid = false;
        }

        if (dateD && dateF && dateF < dateD) {
            showNotification('warning', 'La date de fin doit être postérieure à la date de début');
            isValid = false;
        }

        return isValid;
    }

    // Function to validate date format
    function isValidDate(dateStr) {
        if (!dateStr.match(/^\d{4}-\d{2}-\d{2}$/)) return false;
        const date = new Date(dateStr);
        if (isNaN(date.getTime())) return false;
        return date.toISOString().slice(0,10) === dateStr;
    }

    // Function to validate dates
    function validateDates(dateD, dateF) {
        if (!isValidDate(dateD) || !isValidDate(dateF)) {
            Swal.fire({
                icon: 'error',
                title: 'Format de date invalide',
                text: 'Les dates doivent être au format YYYY-MM-DD'
            });
            return false;
        }

        const startDate = new Date(dateD);
        const endDate = new Date(dateF);
        
        if (endDate <= startDate) {
            Swal.fire({
                icon: 'error',
                title: 'Dates invalides',
                text: 'La date de fin doit être postérieure à la date de début'
            });
            return false;
        }
        
        return true;
    }

    // Handle edit button click
    $(document).on('click', '.edit-contrat', function() {
        const id = $(this).data('id');
        const dateD = $(this).data('dated');
        const dateF = $(this).data('datef');
        const cinLocateur = $(this).data('cin');
        const vehiculeId = $(this).data('vehicule');
        const photoUrl = $(this).data('photo');

        $('#editId').val(id);
        $('#editDateD').val(dateD);
        $('#editDateF').val(dateF);
        $('#editCinLocateur').val(cinLocateur);
        $('#editVehicule').val(vehiculeId);

        if (photoUrl) {
            $('#currentPhotoPreview img').attr('src', photoUrl);
            $('#currentPhotoPreview').show();
        } else {
            $('#currentPhotoPreview').hide();
        }

        $('#editContratModal').modal('show');
    });

    // Handle save changes button click
    $('#saveContratChanges').on('click', function() {
        const formData = new FormData($('#editContratForm')[0]);
        const dateD = $('#editDateD').val();
        const dateF = $('#editDateF').val();

        console.log('Raw date values:', { dateD, dateF });

        if (!validateDates(dateD, dateF)) {
            return;
        }

        const id = $('#editId').val();
        
        $.ajax({
            url: '/back/contrat/' + id + '/edit',
            type: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            success: function(response) {
                console.log('Edit response:', response);
                if (response.success) {
                    $('#editContratModal').modal('hide');
                    
                    // Update the row in the table
                    const row = $('button.edit-contrat[data-id="' + id + '"]').closest('tr');
                    const newData = response.data;
                    
                    // Update row data
                    row.find('td:eq(0)').text(newData.dateD);
                    row.find('td:eq(1)').text(newData.dateF);
                    row.find('td:eq(2)').text(newData.cinlocateur);
                    row.find('td:eq(3)').text(newData.vehicule);
                    
                    // Update button data attributes
                    const editButton = row.find('.edit-contrat');
                    editButton.data('dated', newData.dateD);
                    editButton.data('datef', newData.dateF);
                    editButton.data('cin', newData.cinlocateur);
                    editButton.data('vehicule', newData.id_vehicule);
                    if (newData.photo_permit) {
                        editButton.data('photo', newData.photo_permit);
                    }

                    Swal.fire({
                        icon: 'success',
                        title: 'Succès',
                        text: 'Contrat modifié avec succès'
                    });
                } else {
                    Swal.fire({
                        icon: 'error',
                        title: 'Erreur',
                        text: response.message || 'Une erreur est survenue lors de la modification du contrat'
                    });
                }
            },
            error: function(xhr, status, error) {
                console.error('Edit error:', error);
                console.error('Response:', xhr.responseText);
                Swal.fire({
                    icon: 'error',
                    title: 'Erreur',
                    text: 'Une erreur est survenue lors de la modification du contrat'
                });
            }
        });
    });
});