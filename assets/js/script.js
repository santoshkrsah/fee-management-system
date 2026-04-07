/**
 * Fee Management System - Custom JavaScript
 */

$(document).ready(function() {
    // Dark mode - sync icon on page load
    var currentTheme = document.documentElement.getAttribute('data-theme') || 'light';
    if (currentTheme === 'dark') {
        $('#darkModeIcon').removeClass('fa-moon').addClass('fa-sun');
    }

    // Dark mode toggle
    $('#darkModeToggle').on('click', function() {
        var theme = document.documentElement.getAttribute('data-theme');
        var newTheme = (theme === 'dark') ? 'light' : 'dark';
        document.documentElement.setAttribute('data-theme', newTheme);
        localStorage.setItem('theme', newTheme);

        if (newTheme === 'dark') {
            $('#darkModeIcon').removeClass('fa-moon').addClass('fa-sun');
        } else {
            $('#darkModeIcon').removeClass('fa-sun').addClass('fa-moon');
        }
    });

    // Auto-hide alerts after 5 seconds
    setTimeout(function() {
        $('.alert').fadeOut('slow');
    }, 5000);

    // Confirm delete actions
    $('.delete-btn').on('click', function(e) {
        if (!confirm('Are you sure you want to delete this record? This action cannot be undone.')) {
            e.preventDefault();
        }
    });

    // Fee calculation in collection form
    $('#calculateTotal').on('click', function() {
        calculateTotalFee();
    });

    // Auto-calculate when input fields change
    $('input[name*="fee_paid"], input[name="fine"], input[name="discount"]').on('input', function() {
        calculateTotalFee();
    });

    // Print receipt
    $('#printReceipt').on('click', function() {
        window.print();
    });

    // Form validation
    $('form').on('submit', function() {
        let isValid = true;
        $(this).find('input[required]:visible, select[required]:visible, textarea[required]:visible').each(function() {
            if ($(this).val() === '') {
                $(this).addClass('is-invalid');
                isValid = false;
            } else {
                $(this).removeClass('is-invalid');
            }
        });

        if (!isValid) {
            alert('Please fill in all required fields.');
            return false;
        }
    });

    // Remove invalid class on input
    $('input, select, textarea').on('input change', function() {
        $(this).removeClass('is-invalid');
    });

    // Load sections based on class selection
    $('#class_id').on('change', function() {
        const classId = $(this).val();
        if (classId) {
            loadSections(classId);
        }
    });

    // Load fee structure based on class selection
    $('.load-fee-structure').on('change', function() {
        const classId = $(this).val();
        if (classId) {
            loadFeeStructure(classId);
        }
    });

    // DataTable initialization (if library is included)
    if (typeof $.fn.DataTable !== 'undefined') {
        $('.data-table').DataTable({
            responsive: true,
            pageLength: 25,
            order: [[0, 'asc']]
        });
    }

    // Number input validation
    $('input[type="number"]').on('keypress', function(e) {
        const char = String.fromCharCode(e.which);
        if (!/[0-9.]/.test(char)) {
            e.preventDefault();
        }
    });

    // Phone number validation
    $('input[name="contact_number"]').on('input', function() {
        this.value = this.value.replace(/[^0-9]/g, '');
        if (this.value.length > 15) {
            this.value = this.value.slice(0, 15);
        }
    });

    // Academic session switcher
    $(document).on('click', '.session-switch-item', function(e) {
        e.preventDefault();
        var sessionName = $(this).data('session');
        $.ajax({
            url: '/admin/ajax_switch_session.php',
            method: 'POST',
            data: { session_name: sessionName },
            dataType: 'json',
            success: function(response) {
                if (response.success) {
                    location.reload();
                } else {
                    alert(response.message || 'Failed to switch session.');
                }
            },
            error: function() {
                alert('Error switching session. Please try again.');
            }
        });
    });

    // Month dropdown switcher (updates URL param)
    $(document).on('click', '.month-switch-item', function(e) {
        e.preventDefault();
        var month = $(this).data('month');
        var url = new URL(window.location.href);
        if (month === 'consolidated') {
            url.searchParams.delete('month');
            url.searchParams.set('month', 'consolidated');
        } else {
            url.searchParams.set('month', month);
        }
        window.location.href = url.toString();
    });

    // Date input max date to today
    $('input[type="date"]').each(function() {
        if ($(this).hasClass('max-today')) {
            const today = new Date().toISOString().split('T')[0];
            $(this).attr('max', today);
        }
    });

    // Wrap date inputs with a professional styled calendar icon
    $('input[type="date"]').each(function() {
        var $input = $(this);
        if ($input.parent().hasClass('date-input-wrapper')) return; // already wrapped
        $input.wrap('<div class="date-input-wrapper"></div>');
        $input.after('<i class="fas fa-calendar-alt date-cal-icon" aria-hidden="true"></i>');
    });
});

/**
 * Calculate total fee in fee collection form
 */
function calculateTotalFee() {
    let total = 0;

    // Add all fee components
    total += parseFloat($('#tuition_fee_paid').val() || 0);
    total += parseFloat($('#exam_fee_paid').val() || 0);
    total += parseFloat($('#library_fee_paid').val() || 0);
    total += parseFloat($('#sports_fee_paid').val() || 0);
    total += parseFloat($('#lab_fee_paid').val() || 0);
    total += parseFloat($('#transport_fee_paid').val() || 0);
    total += parseFloat($('#other_charges_paid').val() || 0);
    total += parseFloat($('#fine').val() || 0);

    // Subtract discount
    total -= parseFloat($('#discount').val() || 0);

    // Display total
    $('#total_paid').val(total.toFixed(2));
    $('#total_display').text('₹ ' + total.toFixed(2));
}

/**
 * Load sections for selected class
 */
function loadSections(classId) {
    $.ajax({
        url: '/modules/student/ajax_get_sections.php',
        method: 'POST',
        data: { class_id: classId },
        dataType: 'json',
        success: function(response) {
            if (response.success) {
                const sectionSelect = $('#section_id');
                sectionSelect.empty();
                sectionSelect.append('<option value="">Select Section</option>');

                response.sections.forEach(function(section) {
                    sectionSelect.append(
                        $('<option></option>')
                            .attr('value', section.section_id)
                            .text(section.section_name)
                    );
                });
            }
        },
        error: function() {
            alert('Error loading sections. Please try again.');
        }
    });
}

/**
 * Load fee structure for selected class
 */
function loadFeeStructure(classId) {
    $.ajax({
        url: '/modules/fee_structure/ajax_get_fee_structure.php',
        method: 'POST',
        data: { class_id: classId },
        dataType: 'json',
        success: function(response) {
            if (response.success) {
                const fee = response.fee_structure;
                $('#tuition_fee').val(fee.tuition_fee);
                $('#exam_fee').val(fee.exam_fee);
                $('#library_fee').val(fee.library_fee);
                $('#sports_fee').val(fee.sports_fee);
                $('#lab_fee').val(fee.lab_fee);
                $('#transport_fee').val(fee.transport_fee);
                $('#other_charges').val(fee.other_charges);
                $('#total_fee').val(fee.total_fee);

                // Also populate paid fields
                $('#tuition_fee_paid').val(fee.tuition_fee);
                $('#exam_fee_paid').val(fee.exam_fee);
                $('#library_fee_paid').val(fee.library_fee);
                $('#sports_fee_paid').val(fee.sports_fee);
                $('#lab_fee_paid').val(fee.lab_fee);
                $('#transport_fee_paid').val(fee.transport_fee);
                $('#other_charges_paid').val(fee.other_charges);

                // Store fee_structure_id
                $('#fee_structure_id').val(fee.fee_structure_id);

                // Calculate total
                calculateTotalFee();
            }
        },
        error: function() {
            alert('Error loading fee structure. Please try again.');
        }
    });
}

/**
 * Show loading spinner
 */
function showLoader() {
    $('.spinner-overlay').addClass('show');
}

/**
 * Hide loading spinner
 */
function hideLoader() {
    $('.spinner-overlay').removeClass('show');
}

/**
 * Format number as currency
 */
function formatCurrency(amount) {
    return '₹ ' + parseFloat(amount).toFixed(2).replace(/\d(?=(\d{3})+\.)/g, '$&,');
}

/**
 * Export table to CSV
 */
function exportTableToCSV(filename) {
    try {
        var table = document.querySelector('.table-custom');
        if (!table) { alert('No data to export.'); return; }
        var csv = [];
        var rows = table.querySelectorAll('tr');
        for (var i = 0; i < rows.length; i++) {
            var cols = rows[i].querySelectorAll('th, td');
            var rowData = [];
            for (var j = 0; j < cols.length; j++) {
                var text = cols[j].innerText.replace(/"/g, '""').replace(/\s+/g, ' ').trim();
                rowData.push('"' + text + '"');
            }
            csv.push(rowData.join(','));
        }
        var csvContent = '\uFEFF' + csv.join('\r\n');
        var link = document.createElement('a');
        link.href = 'data:text/csv;charset=utf-8,' + encodeURIComponent(csvContent);
        link.setAttribute('download', filename);
        link.style.visibility = 'hidden';
        link.style.position = 'fixed';
        link.style.left = '0';
        link.style.top = '0';
        document.body.appendChild(link);
        setTimeout(function() {
            link.click();
            document.body.removeChild(link);
        }, 100);
    } catch(e) {
        alert('Export failed: ' + e.message);
    }
}

/**
 * Print current report
 */
function printReport() {
    window.print();
}
