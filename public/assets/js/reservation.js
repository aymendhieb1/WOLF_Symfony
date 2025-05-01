let currentPdfBlob = null;

function makeReservation(sessionId) {
    // Show loading state
    const button = event.target;
    const originalText = button.innerHTML;
    button.disabled = true;
    button.innerHTML = '<i class="material-icons">hourglass_empty</i> Processing...';

    fetch(`/reservation/activite/add/${sessionId}`, {
        method: 'POST',
        headers: {
            'Accept': 'application/json'
        }
    })
    .then(response => {
        if (!response.ok) {
            throw new Error(`HTTP error! status: ${response.status}`);
        }
        return response.json();
    })
    .then(data => {
        if (data.error) {
            throw new Error(data.error);
        }
        
        // Success - handle PDF
        if (data.pdf) {
            try {
                // Convert base64 to binary
                const binaryStr = window.atob(data.pdf);
                const bytes = new Uint8Array(binaryStr.length);
                for (let i = 0; i < binaryStr.length; i++) {
                    bytes[i] = binaryStr.charCodeAt(i);
                }
                
                // Create blob
                const pdfBlob = new Blob([bytes], { type: 'application/pdf' });
                const pdfUrl = URL.createObjectURL(pdfBlob);

                // Configure and show SweetAlert2 modal with PDF viewer
                Swal.fire({
                    title: 'Reservation Successful!',
                    html: `
                        <div style="height: 400px;">
                            <iframe src="${pdfUrl}" style="width: 100%; height: 100%; border: none;"></iframe>
                        </div>
                    `,
                    width: '800px',
                    showCloseButton: true,
                    showCancelButton: true,
                    confirmButtonText: 'Download PDF',
                    cancelButtonText: 'Close',
                    confirmButtonColor: '#28a745',
                    cancelButtonColor: '#6c757d'
                }).then((result) => {
                    if (result.isConfirmed) {
                        // Download PDF
                        const a = document.createElement('a');
                        a.href = pdfUrl;
                        a.download = `reservation_${data.reservationId}.pdf`;
                        document.body.appendChild(a);
                        a.click();
                        document.body.removeChild(a);
                    }
                    // Cleanup
                    URL.revokeObjectURL(pdfUrl);
                    window.location.reload();
                });

            } catch (e) {
                console.error('PDF processing error:', e);
                Swal.fire({
                    title: 'Reservation Created',
                    text: 'Reservation was successful but there was an issue with the PDF. Please try again later.',
                    icon: 'warning',
                    confirmButtonText: 'OK'
                }).then(() => {
                    window.location.reload();
                });
            }
        } else {
            // No PDF in response
            Swal.fire({
                title: 'Success',
                text: 'Reservation created successfully!',
                icon: 'success',
                confirmButtonText: 'OK'
            }).then(() => {
                window.location.reload();
            });
        }
    })
    .catch(error => {
        console.error('Error:', error);
        Swal.fire({
            title: 'Error!',
            text: error.message || 'An error occurred while making the reservation.',
            icon: 'error',
            confirmButtonText: 'OK'
        });
    })
    .finally(() => {
        // Reset button state
        button.disabled = false;
        button.innerHTML = originalText;
    });
}

function checkReservations() {
    // Show loading state
    Swal.fire({
        title: 'Loading Reservations...',
        allowOutsideClick: false,
        didOpen: () => {
            Swal.showLoading();
        }
    });

    // Fetch user's reservations
    fetch('/reservation/activite/user/reservations', {
        method: 'GET',
        headers: {
            'Accept': 'application/json'
        }
    })
    .then(response => {
        if (!response.ok) {
            throw new Error(`HTTP error! status: ${response.status}`);
        }
        return response.json();
    })
    .then(data => {
        if (data.error) {
            throw new Error(data.error);
        }

        if (data.length === 0) {
            Swal.fire({
                title: 'No Reservations',
                text: 'You have no reservations yet.',
                icon: 'info',
                confirmButtonText: 'OK'
            });
            return;
        }

        // Create HTML for reservations list
        const reservationsHtml = data.map(reservation => `
            <div class="reservation-item">
                <div class="reservation-details">
                    <strong>Activity:</strong> ${reservation.session.activite.nom}<br>
                    <strong>Date:</strong> ${reservation.session.date}<br>
                    <strong>Time:</strong> ${reservation.session.heure}<br>
                    <strong>Reservation Date:</strong> ${reservation.dateReservation}
                </div>
                <button onclick="downloadReservationPdf(${reservation.id})" class="btn btn-primary btn-sm download-btn">
                    <i class="material-icons">download</i> PDF
                </button>
            </div>
        `).join('');

        // Show reservations in modal
        Swal.fire({
            title: 'Your Reservations',
            html: `
                <div class="reservation-list">
                    ${reservationsHtml}
                </div>
            `,
            width: '800px',
            showCloseButton: true,
            showConfirmButton: false,
            focusConfirm: false
        });
    })
    .catch(error => {
        console.error('Error:', error);
        Swal.fire({
            title: 'Error!',
            text: error.message || 'An error occurred while fetching reservations.',
            icon: 'error',
            confirmButtonText: 'OK'
        });
    });
}

function downloadReservationPdf(reservationId) {
    // Show loading state
    Swal.fire({
        title: 'Generating PDF...',
        allowOutsideClick: false,
        didOpen: () => {
            Swal.showLoading();
        }
    });

    // Fetch PDF for specific reservation
    fetch(`/reservation/activite/pdf/${reservationId}`, {
        method: 'GET',
        headers: {
            'Accept': 'application/json'
        }
    })
    .then(response => {
        if (!response.ok) {
            throw new Error(`HTTP error! status: ${response.status}`);
        }
        return response.json();
    })
    .then(data => {
        if (data.error) {
            throw new Error(data.error);
        }

        if (data.pdf) {
            // Convert base64 to binary
            const binaryStr = window.atob(data.pdf);
            const bytes = new Uint8Array(binaryStr.length);
            for (let i = 0; i < binaryStr.length; i++) {
                bytes[i] = binaryStr.charCodeAt(i);
            }

            // Create and download PDF
            const blob = new Blob([bytes], { type: 'application/pdf' });
            const url = URL.createObjectURL(blob);
            const a = document.createElement('a');
            a.href = url;
            a.download = `reservation_${reservationId}.pdf`;
            document.body.appendChild(a);
            a.click();
            document.body.removeChild(a);
            URL.revokeObjectURL(url);

            // Show success message
            Swal.fire({
                title: 'Success!',
                text: 'PDF downloaded successfully.',
                icon: 'success',
                timer: 2000,
                showConfirmButton: false
            });
        }
    })
    .catch(error => {
        console.error('Error:', error);
        Swal.fire({
            title: 'Error!',
            text: error.message || 'An error occurred while downloading the PDF.',
            icon: 'error',
            confirmButtonText: 'OK'
        });
    });
} 