document.addEventListener('DOMContentLoaded', function() {
    loadStreamHistory();
});

async function loadStreamHistory(page = 1) {
    const container = document.getElementById('stream-history-container');
    container.innerHTML = '<div class="text-center py-5"><i class="bi bi-hourglass-split spinner-border"></i> Loading...</div>';

    try {
        const data = await $.getJSON(`/api/live-streams/history?page=${page}`);

        if (data.success && data.data.data.length > 0) {
            renderStreamHistory(data.data.data, container);
            if (data.meta.last_page > 1) {
                renderPagination(data.meta, container);
            }
        } else {
            container.innerHTML = `
                <div class="text-center text-muted py-5">
                    <i class="bi bi-broadcast" style="font-size: 48px;"></i>
                    <p class="mt-3">No streams yet. Start your first live stream!</p>
                </div>
            `;
        }
    } catch (error) {
        console.error('Failed to load stream history:', error);
        container.innerHTML = `
            <div class="text-center text-danger py-5">
                <i class="bi bi-exclamation-triangle" style="font-size: 48px;"></i>
                <p class="mt-3">Failed to load stream history</p>
            </div>
        `;
    }
}

function renderStreamHistory(streams, container) {
    let html = '<div class="row">';

    streams.forEach(stream => {
        const duration = formatDuration(stream.duration);
        const endedAt = new Date(stream.ended_at).toLocaleDateString('id-ID', {
            day: 'numeric',
            month: 'short',
            year: 'numeric',
            hour: '2-digit',
            minute: '2-digit'
        });

        html += `
            <div class="col-md-6 col-lg-4">
                <div class="history-card">
                    <div class="d-flex gap-3">
                        <div class="history-thumbnail-wrapper">
                            <img src="${stream.thumbnail || 'https://placehold.co/100x100'}"
                                    alt="${stream.title}"
                                    class="history-thumbnail"
                                    onerror="this.src='https://placehold.co/100x100'">
                            <span class="history-duration">${duration}</span>
                        </div>
                        <div class="flex-grow-1">
                            <h6 class="mb-1" style="font-weight: 600; color: #1f2937;">${stream.title}</h6>
                            <p class="text-muted small mb-2">${endedAt}</p>
                            <div class="d-flex gap-3">
                                <div class="history-stat">
                                    <svg fill="currentColor" viewBox="0 0 20 20">
                                        <path d="M10 12a2 2 0 100-4 2 2 0 000 4z"/>
                                        <path fill-rule="evenodd" d="M.458 10C1.732 5.943 5.522 3 10 3s8.268 2.943 9.542 7c-1.274 4.057-5.064 7-9.542 7S1.732 14.057.458 10zM14 10a4 4 0 11-8 0 4 4 0 018 0z" clip-rule="evenodd"/>
                                    </svg>
                                    <span>${stream.peak_viewers || 0}</span>
                                </div>
                                <div class="history-stat">
                                    <svg fill="currentColor" viewBox="0 0 20 20">
                                        <path d="M18 10c0 3.866-3.582 7-8 7a8.841 8.841 0 01-4.083-.98L2 17l1.338-3.123C2.493 12.767 2 11.434 2 10c0-3.866 3.582-7 8-7s8 3.134 8 7zM7 9H5v2h2V9zm8 0h-2v2h2V9zM9 9h2v2H9V9z"/>
                                    </svg>
                                    <span>${stream.total_messages || 0}</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        `;
    });

    html += '</div>';
    container.innerHTML = html;
}

function renderPagination(meta, container) {
    if (meta.last_page <= 1) return;

    let html = '<nav class="pagination-wrapper"><ul class="pagination">';

    // Previous button
    html += `<li class="page-item ${meta.current_page === 1 ? 'disabled' : ''}">
        <a class="page-link" href="#" onclick="loadStreamHistory(${meta.current_page - 1}); return false;">Previous</a>
    </li>`;

    // Page numbers
    for (let i = 1; i <= meta.last_page; i++) {
        if (i === 1 || i === meta.last_page || (i >= meta.current_page - 2 && i <= meta.current_page + 2)) {
            html += `<li class="page-item ${i === meta.current_page ? 'active' : ''}">
                <a class="page-link" href="#" onclick="loadStreamHistory(${i}); return false;">${i}</a>
            </li>`;
        } else if (i === meta.current_page - 3 || i === meta.current_page + 3) {
            html += '<li class="page-item disabled"><span class="page-link">...</span></li>';
        }
    }

    // Next button
    html += `<li class="page-item ${meta.current_page === meta.last_page ? 'disabled' : ''}">
        <a class="page-link" href="#" onclick="loadStreamHistory(${meta.current_page + 1}); return false;">Next</a>
    </li>`;

    html += '</ul></nav>';

    container.innerHTML += html;
}

function formatDuration(seconds) {
    if (!seconds) return '00:00';
    const hours = Math.floor(seconds / 3600);
    const minutes = Math.floor((seconds % 3600) / 60);
    const secs = seconds % 60;

    if (hours > 0) {
        return `${hours}:${String(minutes).padStart(2, '0')}:${String(secs).padStart(2, '0')}`;
    }
    return `${minutes}:${String(secs).padStart(2, '0')}`;
}