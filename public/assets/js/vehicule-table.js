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

    // Validate form data
    function validateFormData(formData, isEdit = false) {
        const matriculeRegex = /^\d{1,4}TUN\d{1,4}$/;
        const statusRegex = /^[A-Za-z]+$/;
        
        if (!matriculeRegex.test(formData.matricule)) {
            showNotification('error', 'Le matricule doit être au format 1 à 4 chiffres, TUN, 4 chiffres (ex: 1234TUN5678).');
            return false;
        }
        if (!statusRegex.test(formData.status)) {
            showNotification('error', 'Le statut doit contenir uniquement des lettres.');
            return false;
        }
        if (isNaN(formData.nbPlace) || formData.nbPlace < 0) {
            showNotification('error', 'Le nombre de places doit être un nombre positif ou zéro.');
            return false;
        }
        if (isNaN(formData.cylinder) || formData.cylinder < 0) {
            showNotification('error', 'Le cylindre doit être un nombre positif ou zéro.');
            return false;
        }
        return true;
    }

    // Add Vehicle
    $('#submitVehicle').on('click', function (e) {
        e.preventDefault();
        console.log('Add vehicle clicked');

        var form = $('#addVehicleForm');
        if (!form[0].checkValidity()) {
            console.log('Add form invalid');
            form[0].reportValidity();
            showNotification('error', 'Veuillez remplir tous les champs correctement.');
            return;
        }

        var formData = {
            matricule: $('#matricule').val().trim(),
            status: $('#status').val().trim(),
            nbPlace: parseInt($('#nbPlace').val()),
            cylinder: parseInt($('#cylinder').val())
        };
        console.log('Add data:', formData);

        if (!validateFormData(formData)) {
            return;
        }

        $.ajax({
            url: form.data('url'),
            type: 'POST',
            contentType: 'application/json',
            data: JSON.stringify(formData),
            beforeSend: function () {
                console.log('Sending add request');
                $('#submitVehicle').prop('disabled', true);
            },
            success: function (response) {
                console.log('Add response:', response);
                if (response && response.status === 'success' && response.data) {
                    try {
                        var newRow = table.row.add([
                            response.data.matricule || '',
                            response.data.status || '',
                            response.data.nbPlace || 0,
                            response.data.cylinder || 0,
                            '<a href="#" class="btn btn-link btn-warning btn-just-icon edit-vehicle" title="Modifier" ' +
                            'data-id="' + (response.id || '') + '" ' +
                            'data-matricule="' + (response.data.matricule || '') + '" ' +
                            'data-status="' + (response.data.status || '') + '" ' +
                            'data-nbplace="' + (response.data.nbPlace || 0) + '" ' +
                            'data-cylinder="' + (response.data.cylinder || 0) + '">' +
                            '<i class="material-icons">edit</i></a>' +
                            '<a href="#" class="btn btn-link btn-danger btn-just-icon remove" title="Supprimer" ' +
                            'data-url="' + (response.delete_url || '') + '">' +
                            '<i class="material-icons">close</i></a>'
                        ]).draw(false).node(); // Partial draw to prevent freeze

                        if (newRow) {
                            newRow.setAttribute('data-id', response.id || '');
                        }

                        $('#addVehicleModal').modal('hide');
                        form[0].reset();
                        showNotification('success', 'Véhicule ajouté.');
                    } catch (err) {
                        console.error('Table update error:', err);
                        showNotification('error', 'Erreur lors de l\'ajout à la table.');
                    }
                } else {
                    console.log('Add failed:', response.message);
                    showNotification('error', response.message || 'Erreur lors de l\'ajout.');
                }
            },
            error: function (xhr) {
                console.error('Add error:', xhr.responseJSON);
                var message = xhr.responseJSON && xhr.responseJSON.message ? xhr.responseJSON.message : 'Erreur serveur.';
                showNotification('error', message);
            },
            complete: function () {
                console.log('Add complete');
                $('#submitVehicle').prop('disabled', false);
            }
        });
    });

    // Edit Vehicle
    $(document).on('click', '.edit-vehicle', function (e) {
        e.preventDefault();
        console.log('Edit vehicle clicked');

        var button = $(this);
        $('#editVehicleId').val(button.data('id'));
        $('#editMatricule').val(button.data('matricule'));
        $('#editStatus').val(button.data('status'));
        $('#editNbPlace').val(button.data('nbplace'));
        $('#editCylinder').val(button.data('cylinder'));
        $('#editVehicleModal').modal('show');
    });

    // Submit Edit
    $('#submitEditVehicle').on('click', function (e) {
        e.preventDefault();
        console.log('Edit submit clicked');

        var form = $('#editVehicleForm');
        if (!form[0].checkValidity()) {
            console.log('Edit form invalid');
            form[0].reportValidity();
            showNotification('error', 'Veuillez remplir tous les champs correctement.');
            return;
        }

        var id = $('#editVehicleId').val();
        var formData = {
            matricule: $('#editMatricule').val().trim(),
            status: $('#editStatus').val().trim(),
            nbPlace: parseInt($('#editNbPlace').val()),
            cylinder: parseInt($('#editCylinder').val())
        };
        console.log('Edit data:', formData);

        if (!validateFormData(formData, true)) {
            return;
        }

        $.ajax({
            url: '/back/vehicule/' + id + '/edit',
            type: 'POST',
            contentType: 'application/json',
            data: JSON.stringify(formData),
            beforeSend: function () {
                console.log('Sending edit request');
                $('#submitEditVehicle').prop('disabled', true);
            },
            success: function (response) {
                console.log('Edit response:', response);
                if (response.status === 'success') {
                    var $tr = $('tr[data-id="' + id + '"]');
                    table.row($tr).data([
                        response.data.matricule,
                        response.data.status,
                        response.data.nbPlace,
                        response.data.cylinder,
                        $tr.find('td:last').html()
                    ]).draw();

                    $tr.find('.edit-vehicle').data({
                        id: response.id,
                        matricule: response.data.matricule,
                        status: response.data.status,
                        nbplace: response.data.nbPlace,
                        cylinder: response.data.cylinder
                    });

                    $('#editVehicleModal').modal('hide');
                    showNotification('success', 'Véhicule modifié.');
                } else {
                    console.log('Edit failed:', response.message);
                    showNotification('error', response.message || 'Erreur lors de la modification.');
                }
            },
            error: function (xhr) {
                console.error('Edit error:', xhr.responseJSON);
                var message = xhr.responseJSON && xhr.responseJSON.message ? xhr.responseJSON.message : 'Erreur serveur.';
                showNotification('error', message);
            },
            complete: function () {
                console.log('Edit complete');
                $('#submitEditVehicle').prop('disabled', false);
            }
        });
    });

    // Delete Vehicle
    $(document).on('click', '.remove', function (e) {
        e.preventDefault();
        console.log('Delete vehicle clicked');

        var $tr = $(this).closest('tr');
        var url = $(this).data('url');

        $.ajax({
            url: url,
            type: 'POST',
            contentType: 'application/json',
            data: JSON.stringify({}),
            success: function (response) {
                console.log('Delete response:', response);
                if (response.status === 'success') {
                    table.row($tr).remove().draw();
                    showNotification('success', 'Véhicule supprimé.');
                } else {
                    showNotification('error', response.message || 'Erreur lors de la suppression.');
                }
            },
            error: function (xhr) {
                console.error('Delete error:', xhr.responseJSON);
                showNotification('error', 'Erreur serveur.');
            }
        });
    });
});