/**
 * Script pour gérer les présences des enseignants avec AJAX
 */

document.addEventListener("DOMContentLoaded", function () {
    // Générer un nouveau code
    const generateButton = document.querySelector(".generate-code");
    if (generateButton) {
        generateButton.addEventListener("click", function () {
            fetch("/esbtp/admin/attendance/generate-code", {
                method: "POST",
                headers: {
                    "X-CSRF-TOKEN": document.querySelector(
                        'meta[name="csrf-token"]'
                    ).content,
                    "Content-Type": "application/json",
                },
            })
                .then((response) => response.json())
                .then((data) => {
                    if (data.success) {
                        location.reload();
                    } else {
                        alert(
                            "Erreur lors de la génération du code: " +
                                data.message
                        );
                    }
                });
        });
    }

    // Annuler un code
    const cancelButtons = document.querySelectorAll(".cancel-code");
    cancelButtons.forEach((button) => {
        button.addEventListener("click", function () {
            const codeId = this.dataset.codeId;
            if (confirm("Voulez-vous vraiment annuler ce code ?")) {
                fetch(`/esbtp/admin/attendance/cancel-code/${codeId}`, {
                    method: "POST",
                    headers: {
                        "X-CSRF-TOKEN": document.querySelector(
                            'meta[name="csrf-token"]'
                        ).content,
                        "Content-Type": "application/json",
                    },
                })
                    .then((response) => response.json())
                    .then((data) => {
                        if (data.success) {
                            location.reload();
                        } else {
                            alert(
                                "Erreur lors de l'annulation du code: " +
                                    data.message
                            );
                        }
                    });
            }
        });
    });

    // Valider une présence
    const validateButtons = document.querySelectorAll(".validate-attendance");
    validateButtons.forEach((button) => {
        button.addEventListener("click", function () {
            const attendanceId = this.dataset.attendanceId;
            const modal = document.getElementById("validationModal");
            const modalInstance = new bootstrap.Modal(modal);

            // Stocker l'ID de la présence dans le modal
            modal.dataset.attendanceId = attendanceId;

            modalInstance.show();
        });
    });

    // Rejeter une présence
    const rejectButtons = document.querySelectorAll(".reject-attendance");
    rejectButtons.forEach((button) => {
        button.addEventListener("click", function () {
            const attendanceId = this.dataset.attendanceId;
            const modal = document.getElementById("validationModal");
            const modalInstance = new bootstrap.Modal(modal);

            // Stocker l'ID de la présence dans le modal
            modal.dataset.attendanceId = attendanceId;
            modal.dataset.action = "reject";

            modalInstance.show();
        });
    });

    // Confirmer la validation/rejet
    const confirmButton = document.getElementById("confirmValidation");
    if (confirmButton) {
        confirmButton.addEventListener("click", function () {
            const modal = document.getElementById("validationModal");
            const attendanceId = modal.dataset.attendanceId;
            const action = modal.dataset.action || "validate";
            const notes = document.querySelector(
                '#validationForm textarea[name="validation_notes"]'
            ).value;

            fetch(`/esbtp/admin/attendance/${attendanceId}`, {
                method: "PUT",
                headers: {
                    "X-CSRF-TOKEN": document.querySelector(
                        'meta[name="csrf-token"]'
                    ).content,
                    "Content-Type": "application/json",
                },
                body: JSON.stringify({
                    validation_status:
                        action === "reject" ? "rejected" : "validated",
                    validation_notes: notes,
                }),
            })
                .then((response) => response.json())
                .then((data) => {
                    if (data.success) {
                        location.reload();
                    } else {
                        alert("Erreur lors de la validation: " + data.message);
                    }
                });
        });
    }

    // Voir les détails d'une présence
    const viewButtons = document.querySelectorAll(".view-details");
    viewButtons.forEach((button) => {
        button.addEventListener("click", function () {
            const attendanceId = this.dataset.attendanceId;
            fetch(`/esbtp/admin/attendance/${attendanceId}`)
                .then((response) => response.json())
                .then((data) => {
                    if (data.success) {
                        // Afficher les détails dans un modal
                        const detailsModal = new bootstrap.Modal(
                            document.getElementById("detailsModal")
                        );
                        document.getElementById(
                            "attendanceDetails"
                        ).innerHTML = `
                            <p><strong>Enseignant:</strong> ${
                                data.attendance.enseignant.name
                            }</p>
                            <p><strong>Cours:</strong> ${
                                data.attendance.matiere.nom
                            }</p>
                            <p><strong>Date:</strong> ${
                                data.attendance.date
                            }</p>
                            <p><strong>Heure d'arrivée:</strong> ${
                                data.attendance.time_in
                            }</p>
                            <p><strong>Statut:</strong> ${
                                data.attendance.status
                            }</p>
                            <p><strong>Adresse IP:</strong> ${
                                data.attendance.ip_address
                            }</p>
                            <p><strong>Appareil:</strong> ${
                                data.attendance.device_info
                            }</p>
                            ${
                                data.attendance.validation_notes
                                    ? `<p><strong>Notes:</strong> ${data.attendance.validation_notes}</p>`
                                    : ""
                            }
                        `;
                        detailsModal.show();
                    } else {
                        alert("Erreur lors de la récupération des détails");
                    }
                });
        });
    });
});
