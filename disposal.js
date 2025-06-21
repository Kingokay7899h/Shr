

$(document).ready(function() {
    let userRole = '';

    // Fetch user role
    $.ajax({
        url: 'get_user_role.php',
        type: 'GET',
        dataType: 'json',
        success: function(data) {
            userRole = data.role;
            loadDisposals('pending');
        },
        error: function() {
            showNotification('Failed to fetch user role', 'error');
        }
    });

    // Tab switching
    $('.tab-button').click(function() {
        $('.tab-button').removeClass('active');
        $(this).addClass('active');
        $('.tab-content').hide();
        $(`#${$(this).data('tab')}`).show();
        loadDisposals($(this).data('tab'));
    });

    // Load disposals
    function loadDisposals(type = 'pending') {
        $.ajax({
            url: 'fetch_disposals.php',
            type: 'GET',
            data: { type: type },
            dataType: 'json',
            success: function(data) {
                const tableBody = $(`#${type}_disposal_table tbody`);
                tableBody.empty();
                data.forEach(item => {
                    const row = `
                        <tr>
                            <td>${item.id}</td>
                            <td>${item.name_of_the_item} (${item.item_specification})</td>
                            <td>${item.status}</td>
                            <td>${item.condemnation_reason || 'N/A'}</td>
                            <td>${item.created_at}</td>
                            <td>
                                <button onclick="viewDisposal(${item.id}, '${type}')">View</button>
                                ${type === 'pending' && (userRole === 'store' || userRole === 'principal') ? 
                                  `<button onclick="reviewDisposal(${item.id})">Review</button>` : 
                                  type === 'pending' && userRole === 'store' && item.status === 'Approved by Committee' ? 
                                  `<button onclick="disposeItem(${item.id})">Dispose</button>` : ''}
                            </td>
                        </tr>`;
                    tableBody.append(row);
                });
            },
            error: function() {
                showNotification('Failed to load disposals', 'error');
            }
        });
    }

    // View disposal form
    window.viewDisposal = function(id, type) {
        window.location.href = `fetch_disposal_form.php?id=${id}&type=${type}`;
    };

    // Open review modal
    window.reviewDisposal = function(id) {
        $('#requestId').val(id);
        $('#rejection_reason').val('');
        $('#reviewModal').show();
    };

    // Dispose item
    window.disposeItem = function(id) {
        if (confirm('Are you sure you want to mark this item as disposed?')) {
            $.ajax({
                url: 'dispose_approved.php',
                type: 'POST',
                contentType: 'application/json',
                data: JSON.stringify({ requestId: id }),
                success: function(response) {
                    if (response.status === 'success') {
                        loadDisposals('pending');
                        loadDisposals('past');
                        showNotification('Item disposed successfully', 'success');
                    } else {
                        showNotification(response.message, 'error');
                    }
                },
                error: function() {
                    showNotification('Failed to dispose item', 'error');
                }
            });
        }
    };

    // Review form submission
    $('#reviewForm').submit(function(e) {
        e.preventDefault();
        const data = {
            requestId: $('#requestId').val(),
            action: $('input[name="action"]:checked').val(),
            rejection_reason: $('#rejection_reason').val()
        };
        const url = userRole === 'store' ? 'stores_review.php' : 'principal_review.php';
        $.ajax({
            url: url,
            type: 'POST',
            contentType: 'application/json',
            data: JSON.stringify(data),
            success: function(response) {
                if (response.status === 'success') {
                    $('#reviewModal').hide();
                    loadDisposals('pending');
                    loadDisposals('rejected');
                    showNotification('Review submitted successfully', 'success');
                } else {
                    showNotification(response.message, 'error');
                }
            },
            error: function() {
                showNotification('Failed to submit review', 'error');
            }
        });
    });

    // Close modals
    $('.close').click(function() {
        $(this).closest('.modal').hide();
    });

    // Notification function
    function showNotification(message, type) {
        const notification = $('#notification');
        notification.html(message).removeClass('success error').addClass(type).show();
        setTimeout(() => notification.hide(), 10000);
    }
});